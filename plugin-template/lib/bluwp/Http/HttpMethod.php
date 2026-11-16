<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http;

enum HttpMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Patch = 'PATCH';
    case Delete = 'DELETE';
    case Head = 'HEAD';

    public function isIdempotent(): bool
    {
        return in_array($this, [self::Get, self::Put, self::Delete, self::Head], true);
    }
}
