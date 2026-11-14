<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Database;

use Forouzandea\Bluwp\Database\Connection\ConnectionInterface;

final class DatabaseManager
{
    private array $adapters = [];
    private string $defaultConnection = 'wordpress';

    public function __construct(ConnectionInterface $wordpress)
    {
        $this->adapters['wordpress'] = $wordpress;
    }

    public function addConnection(string $name, ConnectionInterface $adapter): void
    {
        if ($name === '' || isset($this->adapters[$name])) {
            throw new \InvalidArgumentException('Connection name must be nonempty and unique within this plugin.');
        }
        $this->adapters[$name] = $adapter;
    }

    public function setDefaultConnection(string $name): void
    {
        $this->adapter($name);
        $this->defaultConnection = $name;
    }

    public function adapter(?string $name = null): ConnectionInterface
    {
        return $this->adapters[$name ?? $this->defaultConnection]
            ?? throw new \InvalidArgumentException('Unknown plugin database connection: ' . $name);
    }

    public function connection(?string $name = null): Connection
    {
        return new Connection($this->adapter($name));
    }

    public function __call(string $method, array $arguments): mixed
    {
        $connection = $this->connection();
        if (!is_callable([$connection, $method])) {
            throw new \BadMethodCallException('Unknown database operation: ' . $method);
        }
        return $connection->$method(...$arguments);
    }
}
