<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Http\API\Authentication;

final class AuthContext
{
    /** @param list<string> $scopes */
    public function __construct(public readonly string $subject, public readonly string $authority, public readonly array $scopes = [])
    {
        if (trim($subject) === '' || trim($authority) === '' || array_filter($scopes, static fn ($scope): bool => !is_string($scope) || trim($scope) === '') !== []) {
            throw new \InvalidArgumentException(__('Invalid API authentication configuration.', BLUWP));
        }
    }

    public function allows(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
