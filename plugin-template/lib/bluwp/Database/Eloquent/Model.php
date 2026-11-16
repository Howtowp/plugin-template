<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Database\Eloquent;

use Howtowp\Bluwp\Database\DatabaseRegistry;
use Howtowp\Bluwp\Database\Connection\ConnectionInterface;
use Howtowp\Bluwp\Database\Eloquent\Events\ModelEventDispatcher;
use Howtowp\Bluwp\Database\Eloquent\Relations\BelongsTo;
use Howtowp\Bluwp\Database\Eloquent\Relations\HasMany;

/** @phpstan-consistent-constructor */
abstract class Model implements \JsonSerializable, \ArrayAccess, \Howtowp\Bluwp\Contracts\Arrayable, \Howtowp\Bluwp\Contracts\Jsonable
{
    protected $table;
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    protected $connection;
    protected $perPage = 15;
    public $incrementing = true;
    private static array $connections = [];
    private static array $booted = [];
    private static array $globalScopes = [];
    protected ?ConnectionInterface $instanceConnection = null;
    protected ?string $instanceTable = null;
    protected ?string $instancePrefix = null;
    private static bool $preventLazyLoading = false;
    private static array $morphMap = [];
    public static function morphMap(array $map): void
    {
        foreach ($map as $alias => $class) {
            if (!is_string($alias) || $alias === '' || !is_subclass_of($class, self::class)) {
                throw new \InvalidArgumentException('Morph map requires explicit aliases and model classes.');
            }
            if (isset(self::$morphMap[$alias]) && self::$morphMap[$alias] !== $class) {
                throw new \LogicException('Morph alias already belongs to another model. Use plugin-specific aliases.');
            }
        }
        self::$morphMap = array_merge(self::$morphMap, $map);
    }
    public static function resolveMorphType(string $alias): string
    {
        return self::$morphMap[$alias] ?? throw new \LogicException('Unregistered morph type.');
    }
    public function getMorphClass(): string
    {
        $alias = array_search(static::class, self::$morphMap, true);
        return $alias === false ? throw new \LogicException('Register the model in morphMap before using polymorphic relations.') : $alias;
    }
    public function morphMany(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null): Relations\MorphMany
    {
        return new Relations\MorphMany($this, $related, $type ?? $name . '_type', $id ?? $name . '_id', $localKey ?? $this->getKeyName());
    }
    public function belongsToMany(string $related, string $table, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null, ?string $parentKey = null, ?string $relatedKey = null): Relations\BelongsToMany
    {
        $model = $this->newRelatedInstance($related);
        return new Relations\BelongsToMany(
            $this,
            $related,
            $table,
            $foreignPivotKey ?? $this->getForeignKey(),
            $relatedPivotKey ?? $model->getForeignKey(),
            $parentKey ?? $this->getKeyName(),
            $relatedKey ?? $model->getKeyName()
        );
    }
    public function morphOne(string $related, string $name, ?string $type = null, ?string $id = null, ?string $localKey = null): Relations\MorphOne
    {
        return new Relations\MorphOne($this, $related, $type ?? $name . '_type', $id ?? $name . '_id', $localKey ?? $this->getKeyName());
    }
    public function morphTo(?string $name = null, ?string $type = null, ?string $id = null): Relations\MorphTo
    {
        $name ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        return new Relations\MorphTo($this, $type ?? $name . '_type', $id ?? $name . '_id', $name);
    }
    protected array $attributes = [];
    protected array $original = [];
    protected $fillable = [];
    protected $guarded = ['*'];
    protected $casts = [];
    protected $hidden = [];
    protected $visible = [];
    protected $appends = [];
    protected array $relations = [];
    public $exists = false;
    public $timestamps = true;
    public $wasRecentlyCreated = false;
    protected array $changes = [];
    private static bool $strictMassAssignment = false;
    private static bool $unguarded = false;

    public function __construct(array $attributes = [])
    {
        self::bootIfNeeded();
        $this->fill($attributes);
    }

    protected static function boot(): void
    {
    }
    protected static function booted(): void
    {
    }
    private static function bootIfNeeded(): void
    {
        if (isset(self::$booted[static::class])) {
            return;
        }
        self::$booted[static::class] = true;
        try {
            $traits = [];
            $collect = static function (string $class) use (&$collect, &$traits): void {
                foreach (class_uses($class) as $trait) {
                    if (isset($traits[$trait])) {
                        continue;
                    }
                    $traits[$trait] = true;
                    $collect($trait);
                }
            };
            foreach (array_merge([static::class], array_values(class_parents(static::class))) as $class) {
                $collect($class);
            }
            foreach (array_keys($traits) as $trait) {
                $method = 'boot' . substr($trait, strrpos($trait, '\\') + 1);
                if (method_exists(static::class, $method)) {
                    static::$method();
                }
            }
            static::boot();
            static::booted();
        } catch (\Throwable $error) {
            unset(self::$booted[static::class], self::$globalScopes[static::class]);
            throw $error;
        }
    }

    public static function setDefaultConnection(ConnectionInterface $connection): void
    {
        self::$connections[static::class] = $connection;
    }
    public static function resolveConnection(?string $name = null): ConnectionInterface
    {
        if ($name !== null) {
            return DatabaseRegistry::forModel(static::class)->adapter($name);
        }
        foreach (array_merge([static::class], array_values(class_parents(static::class))) as $class) {
            if (isset(self::$connections[$class])) {
                return self::$connections[$class];
            }
        }
        return DatabaseRegistry::forModel(static::class)->adapter();
    }
    public function useConnection(ConnectionInterface $connection): static
    {
        $this->assertConnectionChange($connection);
        $this->instanceConnection = $connection;
        return $this;
    }
    private function assertConnectionChange(ConnectionInterface $connection): void
    {
        if ($this->exists && $connection !== $this->getConnection()) {
            throw new \LogicException('Cannot move a persisted model to another connection.');
        }
    }
    public function getConnection(): ConnectionInterface
    {
        return $this->instanceConnection ?? static::resolveConnection($this->connection);
    }
    public function getConnectionName(): ?string
    {
        return $this->connection;
    }
    public function setConnection(?string $name): static
    {
        $connection = null;
        if ($this->exists) {
            $connection = static::resolveConnection($name);
            $this->assertConnectionChange($connection);
        }
        $this->connection = $name;
        $this->instanceConnection = $connection;
        return $this;
    }
    public function getTable(): string
    {
        if ($this->table !== null) {
            return $this->table;
        }
        $name = (new \ReflectionClass($this))->getShortName();
        $name = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        return preg_match('/[^aeiou]y$/', $name) ? substr($name, 0, -1) . 'ies'
            : $name . (preg_match('/(s|x|z|ch|sh)$/', $name) ? 'es' : 's');
    }
    public function getTablePrefix(): string
    {
        return $this->instancePrefix ?? $this->getConnection()->getPrefix();
    }
    public function useTablePrefix(string $prefix): static
    {
        $this->instancePrefix = $prefix;
        $this->instanceTable = null;
        return $this;
    }
    public function getTableName(): string
    {
        return $this->instanceTable ?? $this->getTablePrefix() . $this->getTable();
    }
    public function newRelatedInstance(string $class): Model
    {
        if ($class === static::class) {
            return $this->newInstance();
        }
        $model = new $class();
        return $model->getConnectionName() !== null ? $model
            : $model->useConnection($this->getConnection())->useTablePrefix($this->getTablePrefix());
    }
    public static function preventLazyLoading(bool $value = true): void
    {
        self::$preventLazyLoading = $value;
    }
    public function setTable(string $table): static
    {
        $this->table = $table;
        $this->instanceTable = null;
        return $this;
    }
    public function getKeyName(): string
    {
        return $this->primaryKey;
    }
    public function setKeyName(string $key): static
    {
        $this->primaryKey = $key;
        return $this;
    }
    public function getKey(): mixed
    {
        return $this->getAttribute($this->getKeyName());
    }
    public function getCasts(): array
    {
        $casts = $this->timestamps ? [$this->getCreatedAtColumn() => 'datetime', $this->getUpdatedAtColumn() => 'datetime'] : [];
        if ($this->getIncrementing()) {
            $casts[$this->getKeyName()] = $this->getKeyType();
        }
        return array_merge($casts, $this->casts);
    }
    public function getIncrementing(): bool
    {
        return (bool) $this->incrementing;
    }
    public function getKeyType(): string
    {
        return $this->keyType;
    }
    public function getPerPage(): int
    {
        return $this->perPage;
    }
    public function setPerPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }
    public function usesSoftDeletes(): bool
    {
        return false;
    }
    public function getDeletedAtColumn(): string
    {
        return 'deleted_at';
    }
    public function getCreatedAtColumn(): string
    {
        return 'created_at';
    }
    public function getUpdatedAtColumn(): string
    {
        return 'updated_at';
    }
    public function freshTimestamp(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (!$this->isFillable((string) $key)) {
                if (self::$strictMassAssignment || ($this->fillable === [] && $this->guarded === ['*'])) {
                    throw new MassAssignmentException('Attribute is not mass assignable: ' . $key);
                }
                continue;
            }
            $this->setAttribute($key, $value);
        }
        return $this;
    }
    public function isFillable(string $key): bool
    {
        return self::$unguarded || in_array($key, $this->fillable, true)
            || (!$this->fillable && !in_array('*', $this->guarded, true) && !in_array($key, $this->guarded, true)
                && !str_contains($key, '.') && !str_starts_with($key, '_'));
    }
    public static function preventSilentlyDiscardingAttributes(bool $value = true): void
    {
        self::$strictMassAssignment = $value;
    }
    public static function unguard(bool $value = true): void
    {
        self::$unguarded = $value;
    }
    public static function reguard(): void
    {
        self::$unguarded = false;
    }
    public static function unguarded(callable $callback): mixed
    {
        $previous = self::$unguarded;
        self::$unguarded = true;
        try {
            return $callback();
        } finally {
            self::$unguarded = $previous;
        }
    }
    public function forceFill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }
    /** Create a fresh record with this prototype's configuration, then apply mass assignment. */
    public function newInstance(array $attributes = []): static
    {
        $model = new static();
        $model->table = $this->getTable();
        $model->connection = $this->connection;
        $model->useConnection($this->getConnection());
        $model->instanceTable = $this->getTableName();
        $model->instancePrefix = $this->getTablePrefix();
        $model->primaryKey = $this->primaryKey;
        $model->keyType = $this->keyType;
        $model->incrementing = $this->incrementing;
        $model->timestamps = $this->timestamps;
        $model->perPage = $this->perPage;
        $model->fillable = $this->fillable;
        $model->guarded = $this->guarded;
        $model->casts = $this->casts;
        $model->hidden = $this->hidden;
        $model->visible = $this->visible;
        $model->appends = $this->appends;
        return $model->fill($attributes);
    }

    public function newFromBuilder(array $attributes): static
    {
        $model = $this->newInstance();
        $model->attributes = $attributes;
        $model->exists = true;
        $model->syncOriginal();
        $model->fireEvent('retrieved', false);
        return $model;
    }
    private function attributeMethod(string $prefix, string $key): string
    {
        return $prefix . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))) . 'Attribute';
    }
    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        if (!array_key_exists($key, $this->attributes) && method_exists($this, $key)
            && !method_exists(self::class, $key)) {
            if (self::$preventLazyLoading && $this->exists) {
                throw new \LogicException('Lazy loading is disabled: ' . static::class . '::' . $key);
            }
            $relation = $this->$key();
            if (!$relation instanceof \Howtowp\Bluwp\Database\Eloquent\Relations\Relation) {
                throw new \LogicException('Relationship methods must return a Relation.');
            }
            return $this->relations[$key] = $relation->getResults();
        }
        $value = $this->attributes[$key] ?? null;
        $method = $this->attributeMethod('get', $key);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        if ($value === null) {
            return null;
        }
        $cast = $this->getCasts()[$key] ?? null;
        if (is_string($cast) && str_starts_with($cast, 'decimal:')) {
            return Casts\Decimal::normalize($value, (int) substr($cast, 8));
        }
        if (is_string($cast) && is_subclass_of($cast, \BackedEnum::class)) {
            return $this->castEnum($cast, $value);
        }
        if (is_string($cast) && is_subclass_of($cast, \Howtowp\Bluwp\Database\Contracts\Eloquent\CastsAttributes::class)) {
            return (new $cast())->get($this, $key, $value, $this->attributes);
        }
        return match ($this->getCasts()[$key] ?? null) {
            'int', 'integer' => (int) $value,
            'bool', 'boolean' => (bool) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'array', 'json' => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),
            'date' => (new \DateTimeImmutable((string) $value, new \DateTimeZone('UTC')))->setTime(0, 0),
            'datetime' => new \DateTimeImmutable((string) $value, new \DateTimeZone('UTC')),
            default => $value,
        };
    }
    public function setAttribute(string $key, mixed $value): static
    {
        $method = $this->attributeMethod('set', $key);
        if (method_exists($this, $method)) {
            $this->$method($value);
            return $this;
        }
        $cast = $this->getCasts()[$key] ?? null;
        if (is_string($cast) && is_subclass_of($cast, \Howtowp\Bluwp\Database\Contracts\Eloquent\CastsAttributes::class)) {
            $attributes = (new $cast())->set($this, $key, $value, $this->attributes);
            foreach (is_array($attributes) ? $attributes : [$key => $attributes] as $name => $stored) {
                $this->attributes[$name] = $stored;
            }
            return $this;
        }
        if ($value !== null && is_string($cast) && str_starts_with($cast, 'decimal:')) {
            $value = Casts\Decimal::normalize($value, (int) substr($cast, 8));
        }
        if ($value !== null && is_string($cast) && is_subclass_of($cast, \BackedEnum::class)) {
            $value = $this->castEnum($cast, $value)->value;
        }
        if ($value !== null) {
            $value = match ($this->getCasts()[$key] ?? null) {
                'int', 'integer' => (int) $value,
                'bool', 'boolean' => (int) (bool) $value,
                'float', 'double' => (float) $value,
                'string' => (string) $value,
                'array', 'json' => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'date', 'datetime' => ($value instanceof \DateTimeInterface
                    ? \DateTimeImmutable::createFromInterface($value)
                    : new \DateTimeImmutable((string) $value, new \DateTimeZone('UTC')))
                    ->setTimezone(new \DateTimeZone('UTC'))->format($cast === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s'),
                default => $value,
            };
        }
        $this->attributes[$key] = $value;
        return $this;
    }
    public function __get(string $key): mixed
    {
        return $this->relations[$key] ?? $this->getAttribute($key);
    }
    private function castEnum(string $class, mixed $value): \BackedEnum
    {
        if ($value instanceof $class) {
            return $value;
        }
        $backing = (new \ReflectionEnum($class))->getBackingType();
        if (!$backing instanceof \ReflectionNamedType) {
            throw new \InvalidArgumentException('Only backed enums can be cast.');
        }
        if ($backing->getName() === 'int' && is_string($value)) {
            $value = filter_var($value, FILTER_VALIDATE_INT);
            if ($value === false) {
                throw new \ValueError('Invalid integer-backed enum value.');
            }
        }
        return $class::from($value);
    }
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }
    public function __isset(string $key): bool
    {
        return isset($this->relations[$key]) || $this->getAttribute($key) !== null;
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        return $this;
    }
    public function getRawOriginal(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->original : ($this->original[$key] ?? $default);
    }
    public function getOriginal(?string $key = null, mixed $default = null): mixed
    {
        $model = clone $this;
        $model->attributes = $this->original;
        $model->relations = [];
        if ($key !== null) {
            return array_key_exists($key, $this->original) ? $model->getAttribute($key) : $default;
        }
        $result = [];
        foreach ($this->original as $name => $_) {
            $result[$name] = $model->getAttribute($name);
        }
        return $result;
    }
    public function getChanges(): array
    {
        return $this->changes;
    }
    public function wasChanged(?string $key = null): bool
    {
        return $key === null ? $this->changes !== [] : array_key_exists($key, $this->changes);
    }
    public function isClean(?string $key = null): bool
    {
        return !$this->isDirty($key);
    }
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || !$this->equivalent($key, $value, $this->original[$key])) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }
    public function isDirty(?string $key = null): bool
    {
        $dirty = $this->getDirty();
        return $key === null ? $dirty !== [] : array_key_exists($key, $dirty);
    }
    private function equivalent(string $key, mixed $value, mixed $original): bool
    {
        if ($value === $original) {
            return true;
        }
        if ($value === null || $original === null) {
            return false;
        }
        return match ($this->getCasts()[$key] ?? null) {
            'int', 'integer' => (int) $value === (int) $original,
            'bool', 'boolean' => (bool) $value === (bool) $original,
            'float', 'double' => (float) $value === (float) $original,
            'string' => (string) $value === (string) $original,
            'array', 'json' => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR)
                === json_decode((string) $original, true, 512, JSON_THROW_ON_ERROR),
            default => false,
        };
    }
    public function setRelation(string $key, mixed $value): static
    {
        $this->relations[$key] = $value;
        return $this;
    }
    public function getRelation(string $key): mixed
    {
        return $this->relations[$key] ?? null;
    }
    public function getRelations(): array
    {
        return $this->relations;
    }
    public function unsetRelation(string $key): static
    {
        unset($this->relations[$key]);
        return $this;
    }
    public function makeHidden(array|string $attributes): static
    {
        $this->hidden = array_unique(array_merge($this->hidden, (array) $attributes));
        return $this;
    }
    public function makeVisible(array|string $attributes): static
    {
        $this->hidden = array_diff($this->hidden, (array) $attributes);
        if ($this->visible) {
            $this->visible = array_unique(array_merge($this->visible, (array) $attributes));
        }
        return $this;
    }
    public function append(array|string $attributes): static
    {
        $this->appends = array_unique(array_merge($this->appends, (array) $attributes));
        return $this;
    }
    public function relationLoaded(string $key): bool
    {
        return array_key_exists($key, $this->relations);
    }
    public function toArray(): array
    {
        $data = [];
        foreach (array_unique(array_merge(array_keys($this->attributes), $this->appends, array_keys($this->relations))) as $key) {
            if (in_array($key, $this->hidden, true) || ($this->visible && !in_array($key, $this->visible, true))) {
                continue;
            }
            $value = array_key_exists($key, $this->relations) ? $this->relations[$key] : $this->getAttribute($key);
            $data[$key] = match (true) {
                $value instanceof \BackedEnum => $value->value,
                $value instanceof \DateTimeInterface => $this->serializeDate($value),
                $value instanceof \JsonSerializable => $value->jsonSerialize(),
                default => $value,
            };
        }
        return $data;
    }
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
    }
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }
    public function offsetExists(mixed $offset): bool
    {
        return $this->getAttribute($offset) !== null;
    }
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute($offset);
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute($offset, $value);
    }
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }
    public function __toString(): string
    {
        return $this->toJson();
    }

    public static function addGlobalScope(string|Scope $name, ?callable $scope = null): void
    {
        if ($name instanceof Scope) {
            self::$globalScopes[static::class][$name::class] = $name;
        } else {
            self::$globalScopes[static::class][$name] = $scope ?? throw new \InvalidArgumentException('Scope callback required.');
        }
    }
    public function getGlobalScopes(): array
    {
        return self::$globalScopes[static::class] ?? [];
    }
    public static function on(string $event, callable $callback): void
    {
        ModelEventDispatcher::listen(static::class, $event, $callback);
    }
    public function fireEvent(string $event, bool $halt = true): bool
    {
        return ModelEventDispatcher::dispatch($event, $this, $halt);
    }

    public static function query(): Builder
    {
        return (new static())->newQuery();
    }
    public function newQuery(): Builder
    {
        return new Builder($this);
    }
    public function newModelQuery(): Builder
    {
        return (new Builder($this))->withoutGlobalScopes();
    }
    public static function __callStatic(string $method, array $arguments): mixed
    {
        $query = static::query();
        if (!is_callable([$query, $method])) {
            throw new \BadMethodCallException('Unknown model query method: ' . $method);
        }
        return $query->$method(...$arguments);
    }
    public function __call(string $method, array $arguments): mixed
    {
        return $this->newQuery()->$method(...$arguments);
    }
    public static function all(array|string $columns = ['*']): Collection
    {
        return static::query()->get($columns);
    }
    public function hasNamedScope(string $name): bool
    {
        return method_exists($this, 'scope' . ucfirst($name));
    }
    /** @phpstan-impure */
    public function callNamedScope(string $name, array $arguments): mixed
    {
        return $this->{'scope' . ucfirst($name)}(...$arguments);
    }
    public static function creating(callable $callback): void
    {
        static::on('creating', $callback);
    }
    public static function created(callable $callback): void
    {
        static::on('created', $callback);
    }
    public static function saving(callable $callback): void
    {
        static::on('saving', $callback);
    }
    public static function saved(callable $callback): void
    {
        static::on('saved', $callback);
    }
    public static function updating(callable $callback): void
    {
        static::on('updating', $callback);
    }
    public static function updated(callable $callback): void
    {
        static::on('updated', $callback);
    }
    public static function deleting(callable $callback): void
    {
        static::on('deleting', $callback);
    }
    public static function deleted(callable $callback): void
    {
        static::on('deleted', $callback);
    }
    public static function retrieved(callable $callback): void
    {
        static::on('retrieved', $callback);
    }
    public function load(string|array $relations): static
    {
        (new Collection([$this]))->load($relations);
        return $this;
    }
    public function loadMissing(string|array $relations): static
    {
        (new Collection([$this]))->loadMissing($relations);
        return $this;
    }
    public function save(): bool
    {
        $this->wasRecentlyCreated = false;
        if (!$this->fireEvent('saving')) {
            return false;
        }
        $creating = !$this->exists;
        if ($creating && !$this->fireEvent('creating')) {
            return false;
        }
        if (!$creating && $this->isDirty() && !$this->fireEvent('updating')) {
            return false;
        }
        if ($creating && $this->getKey() === null && method_exists($this, 'newUniqueId')) {
            $this->attributes[$this->getKeyName()] = $this->newUniqueId();
        }
        if ($creating && !$this->getIncrementing() && $this->getKey() === null) {
            throw new \LogicException('A non-incrementing model requires a primary key.');
        }
        if ($this->timestamps && ($creating || $this->isDirty())) {
            $now = $this->freshTimestamp();
            if (!$this->isDirty($this->getUpdatedAtColumn())) {
                $this->attributes[$this->getUpdatedAtColumn()] = $now;
            }
            if ($creating && !$this->isDirty($this->getCreatedAtColumn())) {
                $this->attributes[$this->getCreatedAtColumn()] = $now;
            }
        }
        if ($creating) {
            $table = $this->getTableName();
            $connection = $this->getConnection();
            $id = $connection->insert($table, $this->attributes);
            $this->instanceConnection = $connection;
            $this->instanceTable = $table;
            $this->instancePrefix = $this->getTablePrefix();
            if ($this->getIncrementing() && $this->getKey() === null) {
                $this->attributes[$this->getKeyName()] = $id;
            }
            $this->exists = $this->wasRecentlyCreated = true;
        } else {
            $key = $this->original[$this->getKeyName()] ?? $this->getKey();
            if ($key === null) {
                throw new \LogicException('Cannot save a model without its selected primary key.');
            }
            $dirty = $this->getDirty();
            if ($dirty) {
                $this->getConnection()->update($this->getTableName(), $dirty, [$this->getKeyName() => $key]);
            }
        }
        $this->changes = $creating ? [] : $this->getDirty();
        if ($creating || $this->changes) {
            $this->fireEvent($creating ? 'created' : 'updated', false);
        }
        $this->fireEvent('saved', false);
        $this->syncOriginal();
        return true;
    }
    public function update(array $attributes): bool
    {
        return $this->exists && $this->fill($attributes)->save();
    }
    public function saveOrFail(): bool
    {
        return $this->getConnection()->transaction(fn () => $this->save());
    }
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        if (!$this->fireEvent('deleting')) {
            return false;
        }
        $key = $this->original[$this->getKeyName()] ?? $this->getKey();
        if ($key === null) {
            throw new \LogicException('Cannot delete without the primary key.');
        }
        if ($this->usesSoftDeletes()) {
            $data = [$this->getDeletedAtColumn() => $this->freshTimestamp()];
            if ($this->timestamps) {
                $data[$this->getUpdatedAtColumn()] = $this->freshTimestamp();
            }
            $this->getConnection()->update($this->getTableName(), $data, [$this->getKeyName() => $key]);
            foreach ($data as $column => $value) {
                $this->attributes[$column] = $this->original[$column] = $value;
            }
        } else {
            $this->getConnection()->delete($this->getTableName(), [$this->getKeyName() => $key]);
            $this->exists = false;
        }
        $this->fireEvent('deleted', false);
        return true;
    }
    public function refresh(): static
    {
        if (!$this->exists) {
            return $this;
        }
        $relations = array_keys($this->relations);
        $fresh = $this->newModelQuery()->withoutGlobalScopes()->findOrFail($this->getKey());
        $this->attributes = $fresh->getAttributes();
        $this->relations = [];
        if ($relations) {
            $this->load($relations);
        }
        $this->syncOriginal();
        return $this;
    }
    public function fresh(string|array $with = []): ?static
    {
        if (!$this->exists) {
            return null;
        }
        $fresh = $this->newModelQuery()->with($with)->whereKey($this->getKey())->first();
        if ($fresh !== null && !$fresh instanceof static) {
            throw new \LogicException('A model query returned a different model type.');
        }
        return $fresh;
    }
    public static function destroy(mixed $ids): int
    {
        $ids = $ids instanceof \Traversable ? iterator_to_array($ids) : (is_array($ids) ? $ids : func_get_args());
        $deleted = 0;
        foreach (static::query()->whereKey($ids)->get() as $model) {
            $deleted += (int) $model->delete();
        }
        return $deleted;
    }
    public static function transaction(callable $callback): mixed
    {
        return static::resolveConnection()->transaction($callback);
    }
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $foreignKey ??= $this->getForeignKey();
        return new HasMany($this, $related, $foreignKey, $localKey ?? $this->getKeyName());
    }
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $ownerKey ??= $this->newRelatedInstance($related)->getKeyName();
        $relation = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $foreignKey ??= strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $relation)) . '_' . $ownerKey;
        return new BelongsTo($this, $related, $foreignKey, $ownerKey, $relation);
    }
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): \Howtowp\Bluwp\Database\Eloquent\Relations\HasOne
    {
        $foreignKey ??= $this->getForeignKey();
        return new \Howtowp\Bluwp\Database\Eloquent\Relations\HasOne($this, $related, $foreignKey, $localKey ?? $this->getKeyName());
    }
    public function getForeignKey(): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', (new \ReflectionClass($this))->getShortName())) . '_' . $this->getKeyName();
    }
}
