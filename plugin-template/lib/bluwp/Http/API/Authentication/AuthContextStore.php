<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Http\API\Authentication;

final class AuthContextStore
{
    /** @var \WeakMap<\WP_REST_Request, AuthContext> */
    private \WeakMap $contexts;

    public function __construct()
    {
        $this->contexts = new \WeakMap();
    }

    public function get(\WP_REST_Request $request): ?AuthContext
    {
        return $this->contexts[$request] ?? null;
    }

    public function put(\WP_REST_Request $request, AuthContext $context): void
    {
        $this->contexts[$request] = $context;
    }

    public function forget(\WP_REST_Request $request): void
    {
        unset($this->contexts[$request]);
    }
}
