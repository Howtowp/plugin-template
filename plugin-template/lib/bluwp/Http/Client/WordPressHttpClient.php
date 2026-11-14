<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\Client;

use Forouzandea\Bluwp\Http\Client\Contracts\HttpClient;
use Forouzandea\Bluwp\Http\Client\Exceptions\HttpException;

final class WordPressHttpClient implements HttpClient
{
    public function send(HttpRequest $request): HttpResponse
    {
        $response = wp_safe_remote_request($request->url, [
            'method' => $request->method->value,
            'timeout' => $request->timeout,
            'redirection' => $request->maximumRedirects,
            'sslverify' => true,
            'reject_unsafe_urls' => true,
            'limit_response_size' => $request->maximumResponseBytes,
            'headers' => $request->headers,
            'body' => $request->body,
        ]);
        if (is_wp_error($response)) {
            // The remote URL may contain credentials, so transport diagnostics stay private.
            throw new HttpException(__('HTTP request failed.', BLUWP));
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status < 100 || $status > 599) {
            throw new HttpException(__('HTTP request failed.', BLUWP));
        }

        $headers = [];
        foreach (wp_remote_retrieve_headers($response) as $name => $value) {
            $headers[$name] = $value;
        }
        return new HttpResponse($status, wp_remote_retrieve_body($response), $headers);
    }
}
