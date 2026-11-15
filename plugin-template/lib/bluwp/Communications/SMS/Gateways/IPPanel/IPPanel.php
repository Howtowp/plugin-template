<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Gateways\IPPanel;

use Forouzandea\Bluwp\Communications\SMS\Gateways\AbstractGateway;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSResult;
use Forouzandea\Bluwp\Communications\SMS\Exceptions\SMSConfigurationException;
use Forouzandea\Bluwp\Http\Client\HttpRequest;
use Forouzandea\Bluwp\Http\Client\HttpResponse;
use Forouzandea\Bluwp\Http\HttpMethod;

final class IPPanel extends AbstractGateway
{
    public static function isValidSender(string $sender): bool
    {
        return preg_match('/^\+?[0-9]+$/D', trim($sender)) === 1;
    }

    protected function creditRequest(): HttpRequest
    {
        return new HttpRequest(HttpMethod::Get, 'https://edge.ippanel.com/v1/api/payment/credit/mine', headers: ['Authorization' => $this->required('api_key')]);
    }

    protected function creditValue(array $body): mixed
    {
        return ($body['meta']['status'] ?? null) === true ? ($body['data']['credit'] ?? null) : null;
    }

    protected function name(): string
    {
        return 'ippanel';
    }

    protected function requestSimple(SimpleSMSMessage $message): array
    {
        return $this->sendingRequest([
            'from_number' => $this->sender(), 'sending_type' => 'webservice',
            'message' => $message->text, 'params' => ['recipients' => ['+' . $message->recipient]],
        ]);
    }

    protected function requestPattern(PatternSMSMessage $message): array
    {
        return $this->sendingRequest([
            'from_number' => $this->sender(), 'sending_type' => 'pattern',
            'code' => $message->template, 'recipients' => ['+' . $message->recipient],
            'params' => (object) $message->variables,
        ]);
    }

    private function sender(): string
    {
        $sender = $this->required('sender');
        if (!self::isValidSender($sender)) {
            throw new SMSConfigurationException(__('Invalid IPPanel sender.', BLUWP));
        }
        return str_starts_with($sender, '+') ? $sender
            : (str_starts_with($sender, '98') ? '+' . $sender : '+98' . $sender);
    }

    /** @return array{0: string, 1: array, 2: array, 3: bool} */
    private function sendingRequest(array $parameters): array
    {
        return ['https://edge.ippanel.com/v1/api/send', $parameters,
            ['Authorization' => $this->required('api_key')], true];
    }

    protected function providerError(array $body): mixed
    {
        return $body['meta']['message'] ?? null;
    }

    protected function responseFailure(HttpResponse $response, SMSMessage $message): ?SMSResult
    {
        // A duplicate request may refer to an earlier accepted send.
        return $response->status === 429 ? $this->failure($message, 'duplicate_request', 429, true) : null;
    }

    protected function parse(array $body, SMSMessage $message, int $httpStatus): SMSResult
    {
        $status = $body['meta']['status'] ?? null;
        if (!is_bool($status)) {
            return $this->failure($message, 'invalid_response', $httpStatus, true);
        }
        if (!$status) {
            $code = $body['meta']['message_code'] ?? '';
            $code = is_string($code) && preg_match('/^[0-9-]+$/D', $code) ? $code : 'rejected';
            return $this->failure($message, 'provider_' . $code, $httpStatus);
        }
        $ids = $body['data']['message_outbox_ids'] ?? null;
        if (!is_array($ids) || count($ids) !== 1) {
            return $this->failure($message, 'invalid_response', $httpStatus, true);
        }
        return $this->accepted($message, $ids[0] ?? null, $httpStatus);
    }
}
