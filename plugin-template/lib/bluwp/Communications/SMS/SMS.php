<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS;

use Forouzandea\Bluwp\Communications\SMS\Contracts\SMSService;
use Forouzandea\Bluwp\Communications\SMS\Contracts\PrioritySMSService;
use Forouzandea\Bluwp\Communications\SMS\Sending\SMSPriorityBuilder;
use Forouzandea\Bluwp\Communications\SMS\Sending\SMSBuilder;
use Forouzandea\Bluwp\Foundation\Application;

/** Stateless entry point; application integrations select their own scope. */
class SMS
{
    public static function gateway(string $name, ?int $jobId = null, int $attemptNumber = 1): SMSBuilder
    {
        return static::forApplication(static::application())->gateway($name, $jobId, $attemptNumber);
    }

    public static function priorities(?int $jobId = null, int $attemptNumber = 1): SMSPriorityBuilder
    {
        $service = static::forApplication(static::application());
        if (!$service instanceof PrioritySMSService) {
            throw new \LogicException('This SMS application service does not support priority sending.');
        }
        return $service->priorities($jobId, $attemptNumber);
    }

    public static function to(string|array $recipients): SMSPriorityBuilder
    {
        return static::priorities()->to($recipients);
    }

    public static function forApplication(Application $app): SMSService
    {
        if (!$app->has(SMSService::class)) {
            throw new \LogicException('SMS is unavailable. Initialize the application with its messaging provider before sending.');
        }
        return $app->get(SMSService::class);
    }

    protected static function application(): Application
    {
        throw new \LogicException('Use SMS::forApplication($app) or an application-scoped SMS facade.');
    }
}
