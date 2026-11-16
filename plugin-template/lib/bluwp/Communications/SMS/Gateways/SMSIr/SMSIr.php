<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Gateways\SMSIr;

use Howtowp\Bluwp\Communications\SMS\Gateways\AbstractGateway;
use Howtowp\Bluwp\Communications\SMS\Data\SMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SMSResult;
use Howtowp\Bluwp\Communications\SMS\Exceptions\SMSConfigurationException;

final class SMSIr extends AbstractGateway
{
    public static function isValidSender(string $sender): bool
    {
        $sender = trim($sender);
        return ctype_digit($sender)
            && filter_var($sender, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }

    protected function creditRequest(): \Howtowp\Bluwp\Http\Client\HttpRequest
    {
        return new \Howtowp\Bluwp\Http\Client\HttpRequest(\Howtowp\Bluwp\Http\HttpMethod::Get, 'https://api.sms.ir/v1/credit', headers: ['X-API-KEY' => $this->required('api_key')]);
    }

    protected function creditValue(array $body): mixed
    {
        return ($body['status'] ?? null) === 1 ? ($body['data'] ?? null) : null;
    }

    protected function name(): string
    {
        return 'smsir';
    }

    protected function requestSimple(SimpleSMSMessage $message): array
    {
        $sender = $this->required('sender');
        if (!self::isValidSender($sender)) {
            throw new SMSConfigurationException(__('SMS.ir requires a numeric sender.', BLUWP));
        }
        return ['https://api.sms.ir/v1/send/bulk', [
            'lineNumber' => (int) $sender, 'messageText' => $message->text,
            'mobiles' => [$this->localNumber($message)],
        ], $this->headers(), true];
    }

    protected function requestPattern(PatternSMSMessage $message): array
    {
        if (!ctype_digit($message->template) || (int) $message->template < 1 || (int) $message->template > 2147483647) {
            throw new \InvalidArgumentException(__('SMS.ir requires a positive numeric template ID.', BLUWP));
        }
        $parameters = [];
        foreach ($message->variables as $name => $value) {
            $parameters[] = ['name' => $name, 'value' => $value];
        }
        return ['https://api.sms.ir/v1/send/verify', [
            'mobile' => $this->localNumber($message),
            'templateId' => (int) $message->template,
            'parameters' => $parameters,
        ], $this->headers(), true];
    }

    private function headers(): array
    {
        return ['X-API-KEY' => $this->required('api_key')];
    }

    protected function providerError(array $body): mixed
    {
        return $body['message'] ?? null;
    }

    protected function parse(array $body, SMSMessage $message, int $httpStatus): SMSResult
    {
        $status = $body['status'] ?? null;
        if (!is_int($status)) {
            return $this->failure($message, 'invalid_response', $httpStatus, true);
        }
        if ($status !== 1) {
            return $this->failure($message, 'provider_' . $status, $httpStatus);
        }
        if ($message instanceof SimpleSMSMessage) {
            $ids = $body['data']['messageIds'] ?? null;
            if (!is_array($ids) || !array_is_list($ids) || $ids === []) {
                return $this->failure($message, 'invalid_response', $httpStatus, true);
            }
            foreach ($ids as $id) {
                if ((!is_int($id) && !is_string($id)) || preg_match('/^[1-9][0-9]*$/D', (string) $id) !== 1) {
                    return $this->failure($message, 'missing_message_id', $httpStatus, true);
                }
            }
            // Sandbox can return a sample array unrelated to the recipient count.
            return $this->accepted($message, $ids[0], $httpStatus);
        }
        return $this->accepted($message, $body['data']['messageId'] ?? null, $httpStatus);
    }
}
