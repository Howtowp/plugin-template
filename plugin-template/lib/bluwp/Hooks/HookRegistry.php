<?php

declare(strict_types=1);

namespace Howtowp\Bluwp\Hooks;

use Howtowp\Bluwp\Foundation\Application;

final class HookRegistry
{
    /** @var list<HookDefinition> */
    private array $definitions = [];
    private int $registeredCount = 0;

    public function __construct(private Application $app)
    {
    }

    public function action(string $name): HookDefinition
    {
        $definition = new HookDefinition($name);
        $this->definitions[] = $definition;
        return $definition;
    }

    public function filter(string $name): HookDefinition
    {
        $definition = new HookDefinition($name, true);
        $this->definitions[] = $definition;
        return $definition;
    }

    public function register(): void
    {
        $pending = array_slice($this->definitions, $this->registeredCount);
        foreach ($pending as $definition) {
            $definition->validate();
        }
        foreach ($pending as $definition) {
            $definition->register($this->app);
            ++$this->registeredCount;
        }
    }
}
