<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database\Query;

final class SqlCompiler
{
    public static function identifier(string $name, bool $wildcard = false): string
    {
        $parts = explode('.', $name);
        foreach ($parts as $i => $part) {
            if ($part === '*' && $wildcard && $i === count($parts) - 1) {
                continue;
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_$]*$/D', $part)) {
                throw new \InvalidArgumentException('Invalid SQL identifier: ' . $name);
            }
            $parts[$i] = '`' . $part . '`';
        }
        return implode('.', $parts);
    }

    public static function column(string $column): string
    {
        $parts = preg_split('/\s+as\s+/i', $column);
        if (count($parts) > 2) {
            throw new \InvalidArgumentException('Invalid column alias.');
        }
        return self::identifier($parts[0], true)
            . (isset($parts[1]) ? ' AS ' . self::identifier($parts[1]) : '');
    }

    public static function placeholder(mixed $value): string
    {
        if (!is_scalar($value) && $value !== null) {
            throw new \InvalidArgumentException('Bindings must be scalar or null; cast attributes before querying.');
        }
        if (is_float($value) && !is_finite($value)) {
            throw new \InvalidArgumentException('Non-finite numbers cannot be stored.');
        }
        return match (true) {
            $value === null => 'NULL',
            is_int($value), is_bool($value) => '%d',
            is_float($value) => '%f',
            default => '%s',
        };
    }

    public static function bind(mixed $value, array &$bindings): string
    {
        if ($value instanceof Expression) {
            array_push($bindings, ...$value->bindings);
            return $value->sql;
        }
        $placeholder = self::placeholder($value);
        if ($value !== null) {
            $bindings[] = is_bool($value) ? (int) $value : $value;
        }
        return $placeholder;
    }

    public function wheres(array $wheres): array
    {
        $parts = [];
        $bindings = [];
        foreach ($wheres as $where) {
            $clause = '';
            $column = isset($where['column']) ? self::identifier($where['column']) : '';
            switch ($where['type']) {
                case 'subquery':
                    array_push($bindings, ...$where['bindings']);
                    $clause = '(' . $where['sql'] . ') ' . $where['operator'] . ' ' . self::bind($where['value'], $bindings);
                    break;
                case 'exists':
                    $clause = ($where['not'] ? 'NOT ' : '') . 'EXISTS (' . $where['sql'] . ')';
                    array_push($bindings, ...$where['bindings']);
                    break;
                case 'jsonContains':
                    $clause = ($where['not'] ? 'NOT ' : '') . 'JSON_CONTAINS(' . $column . ', '
                        . self::bind($where['value'], $bindings) . ', ' . self::bind($where['path'], $bindings) . ')';
                    break;
                case 'jsonLength':
                    $clause = 'JSON_LENGTH(' . $column . ', ' . self::bind($where['path'], $bindings) . ') '
                        . $where['operator'] . ' ' . self::bind($where['value'], $bindings);
                    break;
                case 'nested':
                    [$nested, $values] = $this->wheres($where['wheres']);
                    if ($nested === '') {
                        continue 2;
                    }
                    $clause = '(' . $nested . ')';
                    array_push($bindings, ...$values);
                    break;
                case 'null':
                    $clause = $column . ($where['not'] ? ' IS NOT NULL' : ' IS NULL');
                    break;
                case 'in':
                    $placeholders = [];
                    foreach ($where['values'] as $value) {
                        $placeholders[] = self::bind($value, $bindings);
                    }
                    $clause = $placeholders
                        ? $column . ($where['not'] ? ' NOT IN (' : ' IN (') . implode(', ', $placeholders) . ')'
                        : ($where['not'] ? '1 = 1' : '0 = 1');
                    break;
                case 'between':
                    $clause = $column . ($where['not'] ? ' NOT BETWEEN ' : ' BETWEEN ')
                        . self::bind($where['values'][0], $bindings) . ' AND '
                        . self::bind($where['values'][1], $bindings);
                    break;
                case 'column':
                    $clause = $column . ' ' . $where['operator'] . ' ' . self::identifier($where['value']);
                    break;
                case 'basic':
                    $clause = $column . ' ' . $where['operator'] . ' ' . self::bind($where['value'], $bindings);
                    break;
                default:
                    throw new \LogicException('Unknown where type.');
            }
            $parts[] = ($parts ? $where['boolean'] . ' ' : '') . $clause;
        }
        return [implode(' ', $parts), $bindings];
    }

    public function select(string $table, QueryState $state): array
    {
        $columnBindings = [];
        $columns = [];
        foreach ($state->columns as $column) {
            $columns[] = $column instanceof Expression ? self::bind($column, $columnBindings) : self::column($column);
        }
        $sql = 'SELECT ' . ($state->distinct ? 'DISTINCT ' : '')
            . implode(', ', $columns)
            . ' FROM ' . self::identifier($table);
        foreach ($state->joins as $join) {
            $sql .= ' ' . $join['type'] . ' JOIN ' . self::identifier($join['table'])
                . ' ON ' . self::identifier($join['first']) . ' ' . $join['operator'] . ' '
                . self::identifier($join['second']);
        }
        [$where, $whereBindings] = $this->wheres($state->wheres);
        $bindings = array_merge($columnBindings, $whereBindings);
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        if ($state->groups) {
            $sql .= ' GROUP BY ' . implode(', ', array_map([self::class, 'identifier'], $state->groups));
        }
        [$having, $havingBindings] = $this->wheres($state->havings);
        if ($having !== '') {
            $sql .= ' HAVING ' . $having;
        }
        array_push($bindings, ...$havingBindings);
        if ($state->orders) {
            $sql .= ' ORDER BY ' . implode(', ', array_map(
                static fn (array $order): string => self::identifier($order[0]) . ' ' . $order[1],
                $state->orders
            ));
        }
        if ($state->limit !== null) {
            $sql .= ' LIMIT ' . $state->limit;
        } elseif ($state->offset !== null) {
            $sql .= ' LIMIT 18446744073709551615';
        }
        if ($state->offset !== null) {
            $sql .= ' OFFSET ' . $state->offset;
        }
        if ($state->lock) {
            $sql .= ' ' . $state->lock;
        }
        return [$sql, $bindings];
    }

    public function insert(string $table, array $data): array
    {
        if (!$data) {
            return ['INSERT INTO ' . self::identifier($table) . ' () VALUES ()', []];
        }
        $columns = array_map([self::class, 'identifier'], array_keys($data));
        $bindings = [];
        $values = [];
        foreach ($data as $value) {
            $values[] = self::bind($value, $bindings);
        }
        return ['INSERT INTO ' . self::identifier($table) . ' (' . implode(', ', $columns)
            . ') VALUES (' . implode(', ', $values) . ')', $bindings];
    }

    public function write(string $table, QueryState $state, ?array $data): array
    {
        if ($state->groups || $state->joins || $state->havings || $state->offset !== null || $state->distinct || $state->lock !== null) {
            throw new \LogicException('Grouped, distinct, offset or locked writes are not supported.');
        }
        $bindings = [];
        $sql = 'DELETE FROM ' . self::identifier($table);
        if ($data !== null) {
            if (!$data) {
                throw new \InvalidArgumentException('Update requires attributes.');
            }
            $assignments = [];
            foreach ($data as $column => $value) {
                $assignments[] = self::identifier($column) . ' = ' . self::bind($value, $bindings);
            }
            $sql = 'UPDATE ' . self::identifier($table) . ' SET ' . implode(', ', $assignments);
        }
        [$where, $whereBindings] = $this->wheres($state->wheres);
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        array_push($bindings, ...$whereBindings);
        if ($state->orders) {
            $sql .= ' ORDER BY ' . implode(', ', array_map(
                static fn (array $order): string => self::identifier($order[0]) . ' ' . $order[1],
                $state->orders
            ));
        }
        if ($state->limit !== null) {
            $sql .= ' LIMIT ' . $state->limit;
        }
        return [$sql, $bindings];
    }
}
