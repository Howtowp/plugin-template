<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Dispatching;

use Howtowp\Bluwp\Http\API\Contracts\ErrorReporter;
use Howtowp\Bluwp\Http\API\Responses\ResponseFactory;

final class EndpointExecutor
{
    public function __construct(private ErrorReporter $reporter)
    {
    }

    public function execute(callable $callback, \WP_REST_Request $request): mixed
    {
        try {
            return $callback($request);
        } catch (\Throwable $error) {
            $this->reporter->report($error);
            return ResponseFactory::make(status: 500, messages: [__('The request could not be completed.', BLUWP)]);
        }
    }
}
