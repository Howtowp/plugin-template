<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Admin\Support;

use InvalidArgumentException;
use Howtowp\Bluwp\UI\Support\DefinitionValidator as ValueValidator;

final class DefinitionValidator
{
    public static function callback(mixed $value): void
    {
        if ($value !== null && !is_callable($value)) {
            throw new InvalidArgumentException('Admin menu callback must be callable or omitted.');
        }
    }

    /** @param array<string, mixed> $definition */
    public static function menu(array $definition, bool $submenu = false): void
    {
        ValueValidator::text($definition['page_title'] ?? null, 'page_title');
        ValueValidator::text($definition['menu_title'] ?? null, 'menu_title');
        ValueValidator::identity($definition['menu_slug'] ?? null, 'menu_slug');
        ValueValidator::capability($definition['capability'] ?? null);
        self::callback($definition['callback'] ?? null);
        if ($submenu) {
            // WordPress parent slugs may include paths and query parameters.
            ValueValidator::text($definition['parent_slug'] ?? null, 'parent_slug');
        } elseif (!is_string($definition['icon_url'] ?? '')) {
            throw new InvalidArgumentException('Admin menu icon must be a string.');
        }
        $position = $definition['position'] ?? null;
        if ($position !== null && (!is_int($position) && !is_float($position) || !is_finite((float) $position))) {
            throw new InvalidArgumentException('Admin menu position must be a finite number or omitted.');
        }
    }
}
