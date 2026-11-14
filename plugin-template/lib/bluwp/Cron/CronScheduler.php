<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Cron;

final class CronScheduler
{
    public const SIX_TIMES_PER_MINUTE = 10;
    public const THREE_TIMES_PER_MINUTE = 20;
    public const ONCE_PER_MINUTE = 60;
    public const TWELVE_TIMES_PER_HOUR = 300;
    public const SIX_TIMES_PER_HOUR = 600;
    public const FOUR_TIMES_PER_HOUR = 900;
    public const TWICE_PER_HOUR = 1800;
    public const ONCE_PER_HOUR = 3600;
    public const SIX_TIMES_PER_DAY = 14400;
    public const THREE_TIMES_PER_DAY = 28800;
    public const TWICE_PER_DAY = 43200;
    public const ONCE_PER_DAY = 86400;
    public const TWICE_PER_WEEK = 302400;
    public const ONCE_PER_WEEK = 604800;
    public const THREE_TIMES_PER_MONTH = 864000; // Fixed 10 days.
    public const TWICE_PER_MONTH = 1296000; // Fixed 15 days.
    public const ONCE_PER_MONTH = 2592000; // Fixed 30 days, not a calendar month.

    private array $hooks = [];
    private array $intervals = [];

    public function __construct(private string $scope)
    {
        if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $scope)) {
            throw new \InvalidArgumentException('Invalid cron scope.');
        }
    }

    public function on(string $hook, callable $callback, int $acceptedArgs = 1): void
    {
        $this->assertHook($hook);
        if ($acceptedArgs < 0 || $acceptedArgs > 20) {
            throw new \InvalidArgumentException('Invalid cron callback argument count.');
        }
        if (isset($this->hooks[$hook])) {
            if ($this->hooks[$hook] !== [$callback, $acceptedArgs]) {
                throw new \LogicException('Cron hook already has a different handler.');
            }
            return;
        }
        add_action($hook, $callback, 10, $acceptedArgs);
        $this->hooks[$hook] = [$callback, $acceptedArgs];
    }

    public function once(string $hook, int $timestamp, array $args = []): bool
    {
        $this->assertRegistered($hook);
        $this->assertArgs($args);
        if ($timestamp <= time()) {
            throw new \InvalidArgumentException('A one-time cron event must be scheduled in the future.');
        }
        if (wp_next_scheduled($hook, $args) !== false) {
            return true;
        }
        return wp_schedule_single_event($timestamp, $hook, $args, true) === true;
    }

    /** Register a reusable WordPress schedule without scheduling a job. */
    public function interval(int $seconds, string $label): string
    {
        if ($seconds < 10 || $seconds > 2592000 || trim($label) === '') {
            throw new \InvalidArgumentException('Invalid recurring cron interval or label.');
        }
        if ($this->intervals === []) {
            add_filter('cron_schedules', function (array $schedules): array {
                return array_replace($schedules, $this->intervals);
            });
        }
        $schedule = $this->scope . '_every_' . $seconds . '_seconds';
        $this->intervals[$schedule] = ['interval' => $seconds, 'display' => $label];
        return $schedule;
    }

    public function every(string $hook, int $interval, string $label, callable $callback, array $args = [], int $acceptedArgs = 1): bool
    {
        $this->assertArgs($args);
        $schedule = $this->interval($interval, $label);
        $this->on($hook, $callback, $acceptedArgs);
        $current = wp_get_schedule($hook, $args);
        $next = wp_next_scheduled($hook, $args);
        if ($current === $schedule && $next !== false) {
            return true;
        }
        if ($next !== false) {
            $cleared = wp_clear_scheduled_hook($hook, $args, true);
            if ($cleared === false || $cleared instanceof \WP_Error) {
                return false;
            }
        }
        return wp_schedule_event(time() + $interval, $schedule, $hook, $args, true) === true;
    }

    public function clearRegistered(): bool
    {
        $success = true;
        foreach (array_keys($this->hooks) as $hook) {
            if (wp_unschedule_hook($hook, true) instanceof \WP_Error) {
                $success = false;
            }
        }
        return $success;
    }

    private function assertHook(string $hook): void
    {
        if (!preg_match('/^[a-z][a-z0-9_\/-]{0,190}$/D', $hook)
            || (!str_starts_with($hook, $this->scope . '_') && !str_starts_with($hook, $this->scope . '/'))) {
            throw new \InvalidArgumentException('Cron hook must be namespaced to its plugin.');
        }
    }

    private function assertRegistered(string $hook): void
    {
        $this->assertHook($hook);
        if (!isset($this->hooks[$hook])) {
            throw new \LogicException('Register the cron handler before scheduling its hook.');
        }
    }

    private function assertArgs(array $args): void
    {
        $encoded = json_encode($args);
        if (!array_is_list($args) || $encoded === false || strlen($encoded) > 65536
            || !$this->isScalarTree($args) || strlen(serialize($args)) > 65536) {
            throw new \InvalidArgumentException('Cron arguments must be a small list of scalar values and arrays.');
        }
    }

    private function isScalarTree(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value)) {
                if (!$this->isScalarTree($value)) {
                    return false;
                }
            } elseif ($value !== null && !is_scalar($value)) {
                return false;
            }
        }
        return true;
    }
}
