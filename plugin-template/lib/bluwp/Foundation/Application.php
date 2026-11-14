<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Foundation;

use Forouzandea\Bluwp\Container\Container;
use Forouzandea\Bluwp\Foundation\Bootstrap\DefaultProviders;
use Throwable;

class Application extends Container
{
    private array $providers = [];
    private array $bootedProviders = [];
    private bool $translationsLoaded = false;
    private bool $booted = false;
    private bool $booting = false;
    /** @var array<string, class-string<ServiceProvider>> */
    private array $deferred = [];
    private array $registeringServices = [];
    private array $registeringProviders = [];

    public function __construct(private readonly array $config)
    {
    }

    public static function fromPlugin(string $pluginFile): self
    {
        $path = rtrim(dirname($pluginFile), '/\\') . '/';
        $config = require $path . 'bootstrap/app.php';
        if (!is_array($config) || !is_string($config['name'] ?? null) || $config['name'] === '') {
            throw new \LogicException('Plugin configuration must return an array with a non-empty name.');
        }
        foreach ($config['autoload'] ?? [] as $namespace => $directory) {
            Bootstrap\Autoloader::register($namespace, $path . trim($directory, '/\\') . '/');
        }
        return new self(array_replace($config, [
            'path' => $path,
            'url' => plugin_dir_url($pluginFile),
            'pluginFile' => $pluginFile,
        ]));
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * @throws Throwable
     */
    public function initialize(): void
    {
        $this->loadTranslations();
        $providers = array_merge((new DefaultProviders())->forApplication($this), $this->config('providers', []));
        foreach ($providers as $provider) {
            if (is_subclass_of($provider, DeferrableProvider::class)) {
                $this->deferProvider($provider);
            } else {
                $this->registerProvider($provider);
            }
        }
        $this->boot();
    }

    private function loadTranslations(): void
    {
        $pluginFile = $this->config('pluginFile');
        if ($this->translationsLoaded || !is_string($pluginFile)) {
            return;
        }
        load_plugin_textdomain(
            (string) $this->config('name'),
            false,
            dirname(plugin_basename($pluginFile)) . '/lang'
        );
        $this->translationsLoaded = true;
    }

    public function has(string $key): bool
    {
        return parent::has($key) || isset($this->deferred[$key]);
    }

    public function set(string $key, mixed $service): void
    {
        parent::set($key, $service);
        unset($this->deferred[$key]);
    }

    public function singleton(string $key, callable $factory): void
    {
        parent::singleton($key, $factory);
        unset($this->deferred[$key]);
    }

    /**
     * @throws Throwable
     */
    public function get(string $key): mixed
    {
        if (isset($this->registeringServices[$key])) {
            throw new \LogicException('Service requested while its provider is registering: ' . $key);
        }
        if ($this->bound($key) && !isset($this->deferred[$key])) {
            return parent::get($key);
        }
        $loaded = [];
        while (isset($this->deferred[$key])) {
            $provider = $this->deferred[$key];
            if (isset($loaded[$provider])) {
                throw new \LogicException('Circular deferred provider delegation: ' . $key);
            }
            $loaded[$provider] = true;
            $this->registerProvider($provider);
        }
        if ($loaded !== [] && !$this->bound($key)) {
            throw new \LogicException('Deferred provider did not bind its advertised service: ' . $key);
        }
        return parent::get($key);
    }

    public function deferProvider(string $providerClass): void
    {
        if (!is_subclass_of($providerClass, ServiceProvider::class)) {
            throw new \InvalidArgumentException('Invalid deferred provider: ' . $providerClass);
        }
        $providerClass = (new \ReflectionClass($providerClass))->getName();
        if (isset($this->providers[$providerClass])) {
            return;
        }
        if (!is_subclass_of($providerClass, DeferrableProvider::class)) {
            throw new \InvalidArgumentException('Invalid deferred provider: ' . $providerClass);
        }
        $services = $providerClass::provides();
        foreach ($services as $service) {
            if (!is_string($service) || $service === '') {
                throw new \InvalidArgumentException('Invalid deferred service in: ' . $providerClass);
            }
            if (isset($this->deferred[$service]) && $this->deferred[$service] !== $providerClass) {
                throw new \LogicException('Multiple deferred providers for: ' . $service);
            }
        }
        foreach ($services as $service) {
            if (!$this->bound($service)) {
                $this->deferred[$service] = $providerClass;
            }
        }
    }

    public function registerProvider(string $providerClass): void
    {
        if (!is_subclass_of($providerClass, ServiceProvider::class)) {
            throw new \InvalidArgumentException('Invalid service provider: ' . $providerClass);
        }
        $providerClass = (new \ReflectionClass($providerClass))->getName();
        if (isset($this->providers[$providerClass])) {
            return;
        }
        $state = [$this->providers, $this->deferred, $this->registeringServices, $this->registeringProviders];
        try {
            $this->withBindingTransaction(function () use ($providerClass): void {
                $pending = array_filter($this->deferred, static fn (string $owner): bool => $owner === $providerClass);
                foreach ($pending as $key => $owner) {
                    unset($this->deferred[$key]);
                    $this->registeringServices[$key] = $owner;
                }
                $this->registeringProviders[$providerClass] = true;
                try {
                    $provider = new $providerClass($this);
                    $this->providers[$providerClass] = $provider;
                    $provider->register();
                    foreach ($pending as $key => $owner) {
                        if (!$this->bound($key) && !isset($this->deferred[$key])) {
                            throw new \LogicException('Deferred provider did not bind its advertised service: ' . $key);
                        }
                    }
                } finally {
                    unset($this->registeringProviders[$providerClass]);
                    foreach ($pending as $key => $owner) {
                        unset($this->registeringServices[$key]);
                    }
                }
            });
        } catch (Throwable $error) {
            [$this->providers, $this->deferred, $this->registeringServices, $this->registeringProviders] = $state;
            throw $error;
        }
        if ($this->booted && !$this->booting && $this->registeringProviders === []) {
            $this->boot();
        }
    }

    public function boot(): void
    {
        if ($this->booting || $this->registeringProviders !== []) {
            return;
        }
        $this->booting = true;
        try {
            // Providers may register further providers while booting.
            while ($pending = array_diff_key($this->providers, $this->bootedProviders)) {
                foreach ($pending as $class => $provider) {
                    $provider->boot();
                    $this->bootedProviders[$class] = true;
                }
            }
            $this->booted = true;
        } finally {
            $this->booting = false;
        }
    }
}
