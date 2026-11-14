<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Gateways;

use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;
use Forouzandea\Bluwp\Http\Client\HttpRequest;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Contracts\SimpleSMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Contracts\PatternSMSGateway;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SimpleSMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\PatternSMSMessage;
use Forouzandea\Bluwp\Communications\SMS\Data\SMSResult;
use Forouzandea\Bluwp\Communications\SMS\Security\SMSDataFilter;
use Forouzandea\Bluwp\Http\Client\HttpResponse;
use Forouzandea\Bluwp\Communications\SMS\Sending\SMSExecutor;
use Forouzandea\Bluwp\Communications\SMS\Exceptions\SMSConfigurationException;

abstract class AbstractGateway implements SMSGateway, SimpleSMSGateway, PatternSMSGateway
{
    private SMSExecutor $executor;

    public function __construct(protected HttpClient $http, protected array $config, ?SMSExecutor $executor = null)
    {
        $this->executor = $executor ?? new SMSExecutor($http);
    }

    abstract protected function name(): string;
    /** @return array{0: string, 1: array, 2: array, 3: bool} */
    abstract protected function requestSimple(SimpleSMSMessage $message): array;
    /** @return array{0: string, 1: array, 2: array, 3: bool} */
    abstract protected function requestPattern(PatternSMSMessage $message): array;
    abstract protected function parse(array $body, SMSMessage $message, int $httpStatus): SMSResult;

    abstract protected function creditRequest(): HttpRequest;
    abstract protected function creditValue(array $body): mixed;

    final public function credit(): float
    {
        return $this->executor->credit($this->creditRequest(), fn (array $body): mixed => $this->creditValue($body));
    }

    final public function send(SMSMessage $message): SMSResult
    {
        return match (true) {
            $message instanceof SimpleSMSMessage => $this->sendSimple($message),
            $message instanceof PatternSMSMessage => $this->sendPattern($message),
            default => throw new \LogicException('Unsupported SMS message type.'),
        };
    }

    final public function sendSimple(SMSMessage $message): SMSResult
    {
        if (!$message instanceof SimpleSMSMessage) {
            throw new \LogicException('sendSimple requires a simple SMSMessage.');
        }
        return $this->executeSend($message);
    }

    final public function sendPattern(SMSMessage $message): SMSResult
    {
        if (!$message instanceof PatternSMSMessage) {
            throw new \LogicException('sendPattern requires a pattern SMSMessage.');
        }
        return $this->executeSend($message);
    }

    private function executeSend(SimpleSMSMessage|PatternSMSMessage $message): SMSResult
    {
        try {
            [$url, $parameters, $headers, $json] = $message instanceof PatternSMSMessage
                ? $this->requestPattern($message) : $this->requestSimple($message);
        } catch (SMSConfigurationException $error) {
            return SMSResult::failed($this->name(), $message->recipient, 'invalid_configuration', null, false, $error->getMessage());
        } catch (\InvalidArgumentException $error) {
            // Request validation failed before the executor could send anything.
            return SMSResult::failed(
                $this->name(),
                $message->recipient,
                'invalid_message',
                null,
                false,
                (new SMSDataFilter($this->config))->text(strip_tags($error->getMessage()))
            );
        }
        return $this->executor->send(
            $this->name(),
            $message,
            [$url, $parameters, $headers, $json],
            new SMSDataFilter($this->config),
            fn (array $body, int $status): SMSResult => $this->parse($body, $message, $status),
            fn (array $body): mixed => $this->providerError($body),
            fn (HttpResponse $response): ?SMSResult => $this->responseFailure($response, $message)
        );
    }

    /** Override only when the provider has an exceptional HTTP-level outcome. */
    protected function responseFailure(HttpResponse $response, SMSMessage $message): ?SMSResult
    {
        return null;
    }

    /** Extract the public explanation; the executor sanitizes and masks it. */
    protected function providerError(array $body): mixed
    {
        return null;
    }

    protected function required(string $key): string
    {
        $value = $this->config[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new SMSConfigurationException(sprintf(
                __('The %s SMS gateway setting is missing or invalid.', BLUWP),
                match ($key) {
                    'sender' => __('Sender line number', BLUWP),
                    'api_key' => __('SMS API key', BLUWP),
                    'username' => __('SMS service username', BLUWP),
                    'password' => __('SMS service password', BLUWP),
                    default => __('SMS configuration value', BLUWP),
                }
            ));
        }
        return trim($value);
    }

    protected function failure(SMSMessage $message, string $code, ?int $httpStatus, bool $uncertain = false): SMSResult
    {
        return SMSResult::failed($this->name(), $message->recipient, $code, $httpStatus, $uncertain);
    }

    protected function accepted(SMSMessage $message, mixed $id, int $httpStatus): SMSResult
    {
        if ((!is_string($id) && !is_int($id)) || !preg_match('/^[1-9][0-9]*$/D', (string) $id)) {
            return $this->failure($message, 'missing_message_id', $httpStatus, true);
        }
        return SMSResult::accepted($this->name(), $message->recipient, (string) $id, $httpStatus);
    }

    protected function localNumber(SMSMessage $message): string
    {
        return '0' . substr($message->recipient, 2);
    }
}
