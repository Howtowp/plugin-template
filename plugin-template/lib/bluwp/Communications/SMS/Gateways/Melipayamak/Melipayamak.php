<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Gateways\Melipayamak;

use Howtowp\Bluwp\Communications\SMS\Gateways\AbstractGateway;
use Howtowp\Bluwp\Communications\SMS\Data\SMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SMSResult;
use Howtowp\Bluwp\Http\Client\HttpRequest;

final class Melipayamak extends AbstractGateway
{
    protected function creditRequest(): HttpRequest
    {
        return HttpRequest::postForm('https://rest.payamak-panel.com/api/SendSMS/GetCredit', ['username' => $this->required('username'), 'password' => $this->required('password')]);
    }

    protected function creditValue(array $body): mixed
    {
        return in_array($body['RetStatus'] ?? null, [1, '1'], true) ? ($body['Value'] ?? null) : null;
    }

    protected function name(): string
    {
        return 'melipayamak';
    }

    protected function requestSimple(SimpleSMSMessage $message): array
    {
        return $this->sendingRequest('SendSMS', $this->credentials($message) + [
            'from' => $this->required('sender'), 'text' => $message->text, 'isflash' => 'false',
        ]);
    }

    protected function requestPattern(PatternSMSMessage $message): array
    {
        if (!ctype_digit($message->template) || (int) $message->template < 1) {
            throw new \InvalidArgumentException(__('Melipayamak requires a positive numeric template ID.', BLUWP));
        }
        foreach ($message->variables as $value) {
            if (str_contains($value, ';')) {
                throw new \InvalidArgumentException(__('Melipayamak pattern values cannot contain semicolons.', BLUWP));
            }
        }
        return $this->sendingRequest('BaseServiceNumber', $this->credentials($message) + [
            'bodyId' => $message->template, 'text' => implode(';', $message->variables),
        ]);
    }

    private function credentials(SMSMessage $message): array
    {
        return ['username' => $this->required('username'), 'password' => $this->required('password'),
            'to' => $this->localNumber($message)];
    }

    /** @return array{0: string, 1: array, 2: array, 3: bool} */
    private function sendingRequest(string $method, array $parameters): array
    {
        return ['https://rest.payamak-panel.com/api/SendSMS/' . $method, $parameters, [], false];
    }

    protected function providerError(array $body): mixed
    {
        return $body['StrRetStatus'] ?? null;
    }

    protected function parse(array $body, SMSMessage $message, int $httpStatus): SMSResult
    {
        $value = $body['Value'] ?? null;
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^-?[0-9]+$/D', (string) $value)) {
            return $this->failure($message, 'invalid_response', $httpStatus, true);
        }
        $value = (string) $value;
        $status = $body['RetStatus'] ?? null;
        if (in_array($value, ['-111', '-110', '-109', '-108', '-10', '-7', '-6', '-5', '-4', '-3', '-2', '-1',
            '0', '2', '3', '4', '5', '6', '7', '9', '10', '11', '12', '14', '15', '16', '17', '18', '19', '35', '108', '109', '110'], true)) {
            return $this->failure($message, 'provider_' . $value, $httpStatus);
        }
        if ($status !== null && !in_array($status, [1, '1'], true)) {
            return $this->failure($message, 'provider_rejected', $httpStatus);
        }
        if (($status !== null && preg_match('/^[1-9][0-9]+$/D', $value))
            || preg_match('/^[1-9][0-9]{14,}$/D', $value)) {
            return $this->accepted($message, $value, $httpStatus);
        }
        return $this->failure($message, 'unrecognized_provider_result', $httpStatus, true);
    }
}
