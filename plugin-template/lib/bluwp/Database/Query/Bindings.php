<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Query;

/** Translate positional SQL parameters without interpreting quoted text or comments. */
final class Bindings
{
    public static function positional(string $sql, array $values): array
    {
        $result = '';
        $bindings = [];
        $index = 0;
        $length = strlen($sql);
        $quote = null;
        $comment = null;
        for ($i = 0; $i < $length; ++$i) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';
            if ($comment !== null) {
                if ($comment === 'line' && ($char === "\n" || $char === "\r")) {
                    $comment = null;
                } elseif ($comment === 'block' && $char === '*' && $next === '/') {
                    $result .= '*/';
                    ++$i;
                    $comment = null;
                    continue;
                }
            } elseif ($quote !== null) {
                if ($char === '\\') {
                    $result .= $char . ($next === '%' ? '%%' : $next);
                    ++$i;
                    continue;
                }
                if ($char === $quote) {
                    if ($next === $quote) {
                        $result .= $char . $next;
                        ++$i;
                        continue;
                    }
                    $quote = null;
                }
            } elseif (in_array($char, ["'", '"', chr(96)], true)) {
                $quote = $char;
            } elseif ($char === '#' || ($char === '-' && $next === '-' && ctype_space($sql[$i + 2] ?? ' '))) {
                $comment = 'line';
            } elseif ($char === '/' && $next === '*') {
                $comment = 'block';
            } elseif ($char === '?') {
                if (!array_key_exists($index, $values)) {
                    throw new \InvalidArgumentException('Not enough SQL bindings.');
                }
                $result .= SqlCompiler::bind($values[$index++], $bindings);
                continue;
            }
            $result .= $char === '%' ? '%%' : $char;
        }
        if ($index !== count($values)) {
            throw new \InvalidArgumentException('SQL binding count mismatch.');
        }
        return [$bindings ? $result : str_replace('%%', '%', $result), $bindings];
    }
}
