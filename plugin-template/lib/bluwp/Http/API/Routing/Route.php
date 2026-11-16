<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Routing;

use Howtowp\Bluwp\Http\API\Contracts\Permission;
use Howtowp\Bluwp\Http\HttpMethod;

final class Route
{
    public readonly \Closure $callback;

    public function __construct(
        public readonly string $namespace,
        public readonly string $path,
        public readonly HttpMethod $method,
        callable $callback,
        public readonly Permission $permission,
        public readonly array $arguments = [],
        public readonly ?string $name = null
    ) {
        if (($name !== null && trim($name) === '') || !preg_match('/^[a-zA-Z0-9_-]+(?:\/[a-zA-Z0-9_.-]+)*$/D', $namespace)
            || !str_starts_with($path, '/') || preg_match('/[\x00-\x20]/', $path)
            || @preg_match('@^' . str_replace('@', '\\@', $path) . '$@i', '') === false) {
            throw new \InvalidArgumentException(__('Invalid API route definition.', BLUWP));
        }
        $this->callback = \Closure::fromCallable($callback);
    }

    public function key(): string
    {
        return $this->method->value . ' ' . rtrim('/' . $this->namespace . '/' . trim($this->path, '/'), '/');
    }

    /** Fold literal case for comparison while preserving regex escapes and character classes. */
    public static function comparisonPath(string $path): string
    {
        // Explicit case-sensitive regex regions cannot be compared by folding literals.
        if (preg_match('/\(\?[a-zA-Z]*-[a-zA-Z]*i/', $path)) {
            return rtrim($path, '/');
        }
        $parts = preg_split('/(\\\\.|\[(?:\\\\.|[^\]\\\\])*\])/s', rtrim($path, '/'), -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                $parts[$index] = strtolower($part);
            }
        }
        return implode('', $parts);
    }
}
