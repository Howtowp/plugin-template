<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Views;

use Forouzandea\Bluwp\UI\AppConfig;
use Forouzandea\Bluwp\UI\Contracts\StartupNotice;

final class AppViewConfiguration
{
    public function __construct(private AppConfig $config, private ?StartupNotice $startupNotice = null)
    {
    }

    public function forPage(string $page): array
    {
        $config = $this->config->forPage($page);
        $notice = $this->startupNotice?->notice();
        if ($notice !== null) {
            $config['startupNotice'] = $notice;
        }
        return $config;
    }
}
