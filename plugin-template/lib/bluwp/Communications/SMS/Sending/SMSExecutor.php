<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Communications\SMS\Sending;

use Howtowp\Bluwp\Communications\Enums\MessageStatus;
use Howtowp\Bluwp\Communications\SMS\Data\SMSMessage;
use Howtowp\Bluwp\Communications\SMS\Data\SMSResult;
use Howtowp\Bluwp\Communications\SMS\Security\SMSDataFilter;
use Howtowp\Bluwp\Http\Client\Contracts\HttpClient;
use Howtowp\Bluwp\Http\Client\Exceptions\HttpException;
use Howtowp\Bluwp\Http\Client\HttpRequest;
use Howtowp\Bluwp\Http\Client\HttpResponse;

/** Executes one exchange; gateway-specific decisions are supplied by the adapter. */
final class SMSExecutor
{
    public function __construct(private HttpClient $http)
    {
    }

    /**
     * @param array{0: string, 1: array, 2: array, 3: bool} $request
     * @param \Closure(array, int): SMSResult $parse
     * @param \Closure(array): mixed $errorMessage
     * @param null|\Closure(HttpResponse): ?SMSResult $responseFailure
     */
    public function send(
        string $gateway,
        SMSMessage $message,
        array $request,
        SMSDataFilter $filter,
        \Closure $parse,
        \Closure $errorMessage,
        ?\Closure $responseFailure = null
    ): SMSResult {
        [$url, $parameters, $headers, $json] = $request;
        $body = $json
            ? json_encode($parameters, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
            : http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $headers += ['Accept' => 'application/json', 'Content-Type' => $json
            ? 'application/json' : 'application/x-www-form-urlencoded'];
        $requestData = ['method' => 'POST', 'url' => $filter->text($url),
            'headers' => $filter->headers($headers), 'body' => $filter->body($body, $headers['Content-Type'])];
        try {
            $result = $this->sendRequest($gateway, $message, HttpRequest::postRaw($url, $body, $headers), $filter, $parse, $errorMessage, $responseFailure);
        } catch (\Throwable) {
            // Preserve the request for diagnostics without triggering another billable send.
            $result = SMSResult::failed($gateway, $message->recipient, 'gateway_exception', null, true);
        }
        return $result->withData($requestData, $result->responseData);
    }

    /** @param \Closure(array): mixed $valueFromBody */
    public function credit(HttpRequest $request, \Closure $valueFromBody): float
    {
        $response = $this->http->send($request);
        if ($response->status < 200 || $response->status >= 300) {
            throw new \RuntimeException('credit_http_error');
        }
        $body = json_decode($response->body, true, 64, JSON_THROW_ON_ERROR);
        $value = is_array($body) ? $valueFromBody($body) : null;
        if ((!is_int($value) && !is_float($value) && !is_string($value))
            || !is_numeric($value) || !is_finite((float) $value) || (float) $value < 0) {
            throw new \RuntimeException('credit_invalid_response');
        }
        return (float) $value;
    }

    private function sendRequest(
        string $gateway,
        SMSMessage $message,
        HttpRequest $request,
        SMSDataFilter $filter,
        \Closure $parse,
        \Closure $errorMessage,
        ?\Closure $responseFailure
    ): SMSResult {
        try {
            $response = $this->http->send($request);
        } catch (HttpException) {
            return SMSResult::failed($gateway, $message->recipient, 'transport_error', null, true);
        }
        $contentType = $response->header('Content-Type');
        $responseData = ['status' => $response->status, 'headers' => $filter->headers($response->headers),
            'body' => $filter->body($response->body, is_string($contentType) ? $contentType : null)];
        try {
            $result = $this->parseResponse($gateway, $message, $response, $filter, $parse, $errorMessage, $responseFailure);
        } catch (\Throwable) {
            $result = SMSResult::failed($gateway, $message->recipient, 'gateway_exception', $response->status, true);
        }
        return $result->withData(null, $responseData);
    }

    private function parseResponse(
        string $gateway,
        SMSMessage $message,
        HttpResponse $response,
        SMSDataFilter $filter,
        \Closure $parse,
        \Closure $errorMessage,
        ?\Closure $responseFailure
    ): SMSResult {
        if ($responseFailure !== null && ($failure = $responseFailure($response)) !== null) {
            return $failure;
        }
        $httpFailed = $response->status < 200 || $response->status >= 300;
        try {
            $decoded = json_decode($response->body, true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException) {
            return SMSResult::failed($gateway, $message->recipient, $httpFailed ? 'http_error' : 'invalid_response', $response->status, !$httpFailed || $response->status >= 500 || $response->status === 408);
        }
        if (!is_array($decoded)) {
            return SMSResult::failed($gateway, $message->recipient, $httpFailed ? 'http_error' : 'invalid_response', $response->status, !$httpFailed || $response->status >= 500 || $response->status === 408);
        }
        $result = $parse($decoded, $response->status);
        if ($httpFailed || $result->statusEnum() !== MessageStatus::Accepted) {
            return SMSResult::failed(
                $gateway,
                $message->recipient,
                $httpFailed && $result->statusEnum() === MessageStatus::Accepted ? 'http_error' : ($result->errorCode ?? 'http_error'),
                $response->status,
                $httpFailed ? ($response->status >= 500 || $response->status === 408 || $result->statusEnum() !== MessageStatus::Rejected) : $result->statusEnum() === MessageStatus::Unknown,
                $this->filteredError($errorMessage($decoded), $filter)
            );
        }
        return $result;
    }

    private function filteredError(mixed $value, SMSDataFilter $filter): ?string
    {
        if (!is_string($value) || !preg_match('//u', $value)) {
            return null;
        }
        $value = strip_tags($value);
        $value = $filter->text($value);
        $value = trim(preg_replace('/[\x00-\x1F\x7F]/', ' ', $value) ?? '');
        if ($value === '') {
            return null;
        }
        // Bound UI text without cutting a UTF-8 character.
        return preg_match('/^.{0,500}/us', $value, $match) ? $match[0] : null;
    }
}
