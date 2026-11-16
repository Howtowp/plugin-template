<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Requests;

enum RequestSource
{
    case Query;
    case Route;
    case Form;
    case Json;

    public function read(\WP_REST_Request $request): array|\WP_Error
    {
        if ($this !== self::Json) {
            return match ($this) {
                self::Query => $request->get_query_params(),
                self::Route => $request->get_url_params(),
                self::Form => $request->get_body_params(),
            };
        }
        $body = trim($request->get_body());
        if ($body === '') {
            return [];
        }
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            return $this->invalidBody();
        }
        return str_starts_with($body, '{') && is_array($data) ? $data : $this->invalidBody();
    }

    private function invalidBody(): \WP_Error
    {
        return new \WP_Error('invalid_json_body', __('The request body must be a valid JSON object.', BLUWP), ['status' => 400]);
    }
}
