<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API;

use Forouzandea\Bluwp\Http\API\Requests\PaginationParameters;
use Forouzandea\Bluwp\Http\API\Responses\ResponseFactory;
use Forouzandea\Bluwp\UI\Contracts\Schema;
use LogicException;
use ReflectionClass;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

abstract class Controller
{
    /** @return array<string, Schema> */
    protected function schemas(): array
    {
        return [];
    }

    public function getSchema(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $action = $request->get_param('action') ?? 'create';
        $schemas = $this->schemas();
        if (!is_string($action) || !isset($schemas[$action])) {
            return new WP_Error('schema_not_found', __('Schema not found.', BLUWP), ['status' => 404]);
        }
        if (!$schemas[$action] instanceof Schema) {
            throw new LogicException(__('Invalid UI schema contract.', BLUWP));
        }
        return $this->respond([
            'page' => $this->id(),
            'schema' => ['id' => $this->id(), 'action' => $action] + $schemas[$action]->toArray(),
        ]);
    }

    public function id(): string
    {
        $name = (new ReflectionClass($this))->getShortName();
        $name = preg_replace('/Controller$/', '', $name);
        $name = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $name);
        $name = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name);

        return strtolower($name);
    }

    protected function paginationArguments(\WP_REST_Request $request, int $default = 20, int $maximum = 100): array|\WP_Error
    {
        return PaginationParameters::parse($request->get_query_params(), $default, $maximum);
    }

    protected function respond(mixed $data = null, int $status = 200, array $headers = [], array $messages = []): \WP_REST_Response
    {
        return ResponseFactory::make($data, $status, $headers, $messages);
    }
}
