<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Responses;

use WP_Error;

final class ResponseFactory
{
    /** @param list<string> $messages */
    public static function make(mixed $data = null, int $status = 200, array $headers = [], array $messages = [], ?string $code = null): \WP_REST_Response
    {
        if ($status < 200 || $status > 599) {
            throw new \InvalidArgumentException(__('Invalid API response status.', BLUWP));
        }
        foreach ($messages as $message) {
            if (!is_string($message)) {
                throw new \InvalidArgumentException(__('Invalid API response messages.', BLUWP));
            }
        }
        // Preserve existing error callers while moving machine codes into the envelope.
        // Successful domain payloads may have their own code field; leave those intact.
        if ($status >= 400 && is_array($data) && isset($data['code']) && is_string($data['code'])) {
            $code ??= $data['code'];
            unset($data['code']);
            $data = $data === [] ? null : $data;
        }
        if ($code !== null && trim($code) === '') {
            throw new \InvalidArgumentException(__('Invalid API response code.', BLUWP));
        }
        if (in_array($status, [204, 205, 304], true)) {
            return new EnvelopedResponse(null, $status, $headers);
        }
        $body = ['success' => $status < 300];
        if ($code !== null) {
            $body['code'] = $code;
        }
        $body['messages'] = array_values($messages);
        if ($data !== null) {
            $body['data'] = $data;
        }
        return new EnvelopedResponse($body, $status, $headers);
    }

    /** @param array<string, string|list<string>> $errors */
    public static function validation(array $errors): \WP_REST_Response
    {
        if ($errors === []) {
            throw new \InvalidArgumentException(__('A validation error response requires errors.', BLUWP));
        }
        $fieldErrors = [];
        $messages = [];
        foreach ($errors as $field => $fieldMessages) {
            $fieldMessages = (array) $fieldMessages;
            $fieldErrors[$field] = $fieldMessages[0] ?? '';
            foreach ($fieldMessages as $message) {
                if (is_string($message) && $message !== '') {
                    $messages[] = $message;
                }
            }
        }
        return self::make(['errors' => $fieldErrors], 422, messages: $messages, code: 'validation_failed');
    }

    public static function error(string $code, string $message, int $status = 400, array $data = []): WP_Error
    {
        if ($code === '' || $status < 400 || $status > 599) {
            throw new \InvalidArgumentException(__('Invalid API response status.', BLUWP));
        }
        return new WP_Error($code, $message, ['status' => $status] + $data);
    }
}
