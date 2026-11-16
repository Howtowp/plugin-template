<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Responses;

final class ResponseNormalizer
{
    private array $namespaces = [];

    public function includeNamespace(string $namespace): void
    {
        if (!preg_match('@^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_.-]+)*$@D', $namespace)) {
            throw new \InvalidArgumentException(__('Invalid API route definition.', BLUWP));
        }
        $this->namespaces['/' . strtolower($namespace)] = true;
    }

    public function normalize(\WP_REST_Response $response, \WP_REST_Server $server, \WP_REST_Request $request): \WP_REST_Response
    {
        return $this->includes($request) ? $this->envelope($response) : $response;
    }

    /** WordPress handles HEAD/204 itself; also suppress 205/304 bodies at emission. */
    public function suppressBody(bool $served, \WP_REST_Response $response, \WP_REST_Request $request): bool
    {
        return $served || ($this->includes($request) && in_array($response->get_status(), [204, 205, 304], true));
    }

    private function includes(\WP_REST_Request $request): bool
    {
        $path = strtolower($request->get_route());
        foreach ($this->namespaces as $prefix => $_) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }
        return false;
    }

    private function envelope(\WP_REST_Response $response): \WP_REST_Response
    {
        if ($response instanceof EnvelopedResponse) {
            return $response;
        }
        $status = $response->get_status();
        if (in_array($status, [204, 205, 304], true)) {
            $empty = clone $response;
            $empty->set_data(null);
            return $empty;
        }
        // Redirects and explicit non-JSON media retain their native response contract.
        if ($status < 200 || ($status >= 300 && $status < 400)) {
            return $response;
        }
        foreach ($response->get_headers() as $name => $value) {
            if (strtolower((string) $name) === 'content-type') {
                $mediaType = strtolower(trim(explode(';', (string) $value, 2)[0]));
                if ($mediaType !== 'application/json' && !str_ends_with($mediaType, '+json')) {
                    return $response;
                }
            }
        }
        $data = $response->get_data();
        $messages = [];
        // Only WordPress error-shaped responses move their public message out of data.
        if ($status >= 400 && is_array($data) && isset($data['code'], $data['message'], $data['data'])
            && is_string($data['message']) && is_array($data['data']) && isset($data['data']['status'])) {
            $messages = $data['message'] === '' ? [] : [$data['message']];
            $details = $data['data'];
            unset($details['status'], $data['message'], $data['data']);
            $data += $details;
        }
        $wrapped = ResponseFactory::make($data, $status, $response->get_headers(), $messages);
        foreach ($response->get_links() as $relation => $links) {
            foreach ($links as $link) {
                $wrapped->add_link($relation, $link['href'], $link['attributes']);
            }
        }
        $wrapped->set_matched_route($response->get_matched_route());
        $wrapped->set_matched_handler($response->get_matched_handler());
        return $wrapped;
    }
}
