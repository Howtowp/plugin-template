<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI;

use Forouzandea\Bluwp\UI\ClientMessages;

final class AppConfig
{
    private array $messages;

    public function __construct(
        private string $appId,
        private string $name,
        private string $icon,
        private string $namespace,
        private string $pagesEndpoint = 'pages',
        array $messages = [],
        private bool $navigation = false
    ) {
        if ($appId === '' || $name === '' || $namespace === '' || $pagesEndpoint === '') {
            throw new \InvalidArgumentException(__('App configuration is incomplete.', BLUWP));
        }
        $this->messages = $messages ?: ClientMessages::messages();
    }

    public function forPage(string $initialPage): array
    {
        return [
            'appId' => $this->appId,
            'platform' => 'wordpress',
            'direction' => is_rtl() ? 'rtl' : 'ltr',
            'locale' => determine_locale(),
            'restUrl' => esc_url_raw(rest_url(trim($this->namespace, '/') . '/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'initialPage' => $initialPage,
            'pagesEndpoint' => $this->pagesEndpoint,
            'name' => $this->name,
            'icon' => $this->icon,
            'messages' => $this->messages,
        ];
    }

    public function metadata(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'navigation' => ['enabled' => $this->navigation],
            'messages' => $this->messages,
        ];
    }
}
