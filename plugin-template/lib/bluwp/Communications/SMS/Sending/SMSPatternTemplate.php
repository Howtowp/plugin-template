<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Communications\SMS\Sending;

/** Resolves the colon template once; values cannot introduce additional placeholders. */
final class SMSPatternTemplate
{
    /** @return array{string, array<string, string>} */
    public static function resolve(string $template, array $variables, ?string $patternCode = null): array
    {
        if (!str_starts_with($template, ':')) {
            if ($patternCode !== null) {
                throw new \InvalidArgumentException(__('Invalid SMS template identifier.', BLUWP));
            }
            // Original pattern(code, variables) API.
            return [$template, $variables];
        }
        if (!preg_match('/^:\{[a-zA-Z0-9_-]+\}(?:,\{[a-zA-Z0-9_-]+\})*$/D', $template)) {
            throw new \InvalidArgumentException(__('Invalid SMS template identifier.', BLUWP));
        }
        preg_match_all('/\{([a-zA-Z0-9_-]+)\}/', $template, $matches);
        $names = $matches[1];
        if ($patternCode === null) {
            $patternCode = array_shift($names);
        }
        $resolved = [];
        foreach ($names as $name) {
            $value = $variables[$name] ?? null;
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $name) || array_key_exists($name, $resolved)
                || (!is_string($value) && !is_int($value) && !is_float($value))
                || (is_float($value) && !is_finite($value)) || trim((string) $value) === '') {
                throw new \InvalidArgumentException(__('Pattern variables require named, non-empty string values.', BLUWP));
            }
            $resolved[$name] = (string) $value;
        }
        if ($patternCode === null || !preg_match('/^[a-zA-Z0-9_-]+$/D', $patternCode)) {
            throw new \InvalidArgumentException(__('Invalid SMS template identifier.', BLUWP));
        }
        return [$patternCode, $resolved];
    }
}
