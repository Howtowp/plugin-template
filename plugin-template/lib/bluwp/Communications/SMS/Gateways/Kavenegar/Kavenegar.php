<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Gateways\Kavenegar;

use Howtowp\Bluwp\Communications\SMS\Gateways\AbstractGateway;
use Howtowp\Bluwp\Communications\SMS\Data\SMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SMSResult;

final class Kavenegar extends AbstractGateway
{
    protected function creditRequest(): \Howtowp\Bluwp\Http\Client\HttpRequest
    {
        return new \Howtowp\Bluwp\Http\Client\HttpRequest(\Howtowp\Bluwp\Http\HttpMethod::Get, 'https://api.kavenegar.com/v1/' . rawurlencode($this->required('api_key')) . '/account/info.json');
    }

    protected function creditValue(array $body): mixed
    {
        return ($body['return']['status'] ?? null) === 200 ? ($body['entries']['remaincredit'] ?? null) : null;
    }

    protected function name(): string
    {
        return 'kavenegar';
    }

    protected function requestSimple(SimpleSMSMessage $message): array
    {
        $params = ['receptor' => $this->localNumber($message), 'message' => $message->text];
        if (!empty($this->config['sender'])) {
            $params['sender'] = $this->required('sender');
        }
        return [$this->baseUrl() . '/sms/send.json', $params, [], false];
    }

    protected function requestPattern(PatternSMSMessage $message): array
    {
        $variables = $message->variables;
        $allowed = ['token', 'token2', 'token3', 'token10', 'token20'];
        if (!isset($variables['token']) || array_diff(array_keys($variables), $allowed)) {
            throw new \InvalidArgumentException(__('Kavenegar patterns require token and support token2, token3, token10 and token20.', BLUWP));
        }
        return [$this->baseUrl() . '/verify/lookup.json',
            ['receptor' => $this->localNumber($message), 'template' => $message->template] + $variables, [], false];
    }

    private function baseUrl(): string
    {
        return 'https://api.kavenegar.com/v1/' . rawurlencode($this->required('api_key'));
    }

    protected function providerError(array $body): mixed
    {
        return $body['return']['message'] ?? null;
    }

    protected function parse(array $body, SMSMessage $message, int $httpStatus): SMSResult
    {
        $status = $body['return']['status'] ?? null;
        if (!is_int($status)) {
            return $this->failure($message, 'invalid_response', $httpStatus, true);
        }
        if ($status !== 200) {
            return $this->failure($message, 'provider_' . $status, $httpStatus);
        }
        $entry = $body['entries'][0] ?? null;
        if (!is_array($entry) || count($body['entries']) !== 1) {
            return $this->failure($message, 'invalid_response', $httpStatus, true);
        }
        $deliveryStatus = $entry['status'] ?? null;
        if (in_array($deliveryStatus, [6, 11, 13, 14, 100], true)) {
            return $this->failure($message, 'message_' . $deliveryStatus, $httpStatus);
        }
        if (!in_array($deliveryStatus, [1, 2, 4, 5, 10], true)) {
            return $this->failure($message, 'unknown_message_status', $httpStatus, true);
        }
        return $this->accepted($message, $entry['messageid'] ?? null, $httpStatus);
    }
}
