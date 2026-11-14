<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Sending;

use Forouzandea\Bluwp\Communications\SMS\Data\SMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSResult;
use Forouzandea\Bluwp\Communications\SMS\Gateways\GatewayRegistry;
use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;

final class SMSManager
{
    public function __construct(private GatewayRegistry $registry, private HttpClient $http)
    {
    }

    /** @param null|\Closure(SMSMessage, SMSResult): void $onResult */
    public function gateway(string $name, array $configuration, ?\Closure $onResult = null): SMSBuilder
    {
        return new SMSBuilder($this->registry->resolve($name, $this->http, $configuration), $onResult);
    }

    /**
     * @param array<string, array> $configurations
     * @param null|\Closure(SMSMessage, SMSResult): void $onResult
     */
    public function priorities(array $configurations, ?\Closure $onResult = null): SMSPriorityBuilder
    {
        return new SMSPriorityBuilder(fn (string $name): SMSBuilder => $this->gateway($name, $configurations[$name] ?? []), $onResult);
    }

}
