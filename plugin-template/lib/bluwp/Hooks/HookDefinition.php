<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Hooks;

use Forouzandea\Bluwp\Foundation\Application;

final class HookDefinition
{
    private ?string $class = null;
    private string $method = '';
    private int $priority = 10;
    private int $acceptedArgs = 1;

    public function __construct(private string $name, private bool $isFilter = false)
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Hook name must not be empty.');
        }
    }

    public function handler(string $class, string $method): self
    {
        if ($class === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $method)) {
            throw new \InvalidArgumentException('Invalid hook handler.');
        }
        $this->class = $class;
        $this->method = $method;
        return $this;
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function acceptedArgs(int $count): self
    {
        if ($count < 0) {
            throw new \InvalidArgumentException('Accepted argument count must not be negative.');
        }
        $this->acceptedArgs = $count;
        return $this;
    }

    public function validate(): void
    {
        if ($this->class === null) {
            throw new \LogicException('Hook handler is missing: ' . $this->name);
        }
    }

    public function register(Application $app): void
    {
        $this->validate();
        $class = $this->class;
        $method = $this->method;
        $callback = static function (...$arguments) use ($app, $class, $method): mixed {
            $handler = $app->get($class);
            if (!is_callable([$handler, $method])) {
                throw new \LogicException('Hook handler is not callable: ' . $class . '::' . $method);
            }
            return $handler->{$method}(...$arguments);
        };
        if ($this->isFilter) {
            add_filter($this->name, $callback, $this->priority, $this->acceptedArgs);
        } else {
            add_action($this->name, static function (...$arguments) use ($callback): void {
                $callback(...$arguments);
            }, $this->priority, $this->acceptedArgs);
        }
    }
}
