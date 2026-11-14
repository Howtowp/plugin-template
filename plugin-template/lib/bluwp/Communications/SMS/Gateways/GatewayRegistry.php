<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Gateways;

use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SimpleSMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Contracts\PatternSMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SMSGatewayCredit;

final class GatewayRegistry
{
    private array $factories = [];

    public static function defaults(): self
    {
        $registry = new self();
        foreach (['kavenegar' => Kavenegar\Kavenegar::class, 'smsir' => SMSIr\SMSIr::class,
                     'ippanel' => IPPanel\IPPanel::class, 'melipayamak' => Melipayamak\Melipayamak::class] as $name => $class) {
            $registry->register($name, static fn (HttpClient $http, array $config): SMSGateway => new $class($http, $config));
        }
        return $registry;
    }

    public function register(string $name, callable $factory): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/D', $name) || isset($this->factories[$name])) {
            throw new \InvalidArgumentException(__('Invalid or duplicate SMS gateway name.', BLUWP));
        }
        $this->factories[$name] = $factory;
    }

    public function resolve(string $name, HttpClient $http, array $config): SMSGateway|SimpleSMSGateway|PatternSMSGateway|SMSGatewayCredit
    {
        if (!isset($this->factories[$name])) {
            throw new \InvalidArgumentException(__('Unknown SMS gateway.', BLUWP));
        }
        $gateway = ($this->factories[$name])($http, $config);
        if (!$gateway instanceof SMSGateway && !$gateway instanceof SimpleSMSGateway
            && !$gateway instanceof PatternSMSGateway && !$gateway instanceof SMSGatewayCredit) {
            throw new \LogicException(__('SMS factory must return an SMSGateway.', BLUWP));
        }
        return $gateway;
    }
}
