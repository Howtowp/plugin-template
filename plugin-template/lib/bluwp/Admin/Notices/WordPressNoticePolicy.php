<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Admin\Notices;

use Howtowp\Bluwp\Admin\Contracts\NoticePolicy;

final class WordPressNoticePolicy implements NoticePolicy
{
    private const CHANNELS = ['admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices'];
    private array $registered = [];
    private bool $active = false;

    public function register(array $hooks): void
    {
        foreach ($hooks as $hook) {
            if ($hook !== '' && !isset($this->registered[$hook])) {
                add_action('load-' . $hook, [$this, 'suppress']);
                $this->registered[$hook] = true;
            }
        }
    }

    public function suppress(): void
    {
        foreach (self::CHANNELS as $hook) {
            remove_all_actions($hook);
        }
        if (!$this->active) {
            // 'all' runs before WordPress snapshots the specific hook's callbacks.
            add_action('all', static function (string $hook): void {
                if (in_array($hook, self::CHANNELS, true)) {
                    remove_all_actions($hook);
                }
            }, PHP_INT_MAX);
            $this->active = true;
        }
    }
}
