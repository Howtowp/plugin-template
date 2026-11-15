<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\Container;

final class AutowireResolver
{
    public function resolve(string $class, Container $container): object
    {
        if (!class_exists($class)) {
            throw new \LogicException('Unknown service: ' . $class);
        }

        $reflection = new \ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new \LogicException('Service is not autowirable: ' . $class);
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }
        if (!$constructor->isPublic()) {
            throw new \LogicException('Service constructor must be public: ' . $class);
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                continue;
            }
            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependency = $type->getName();
                $declaringClass = $parameter->getDeclaringClass();
                if ($dependency === 'self' || $dependency === 'static') {
                    $dependency = $declaringClass?->getName() ?? $dependency;
                } elseif ($dependency === 'parent') {
                    $dependency = $declaringClass?->getParentClass()?->getName() ?? $dependency;
                }
                if (is_a($container, $dependency)) {
                    $arguments[] = $container;
                    continue;
                }
                if ($container->has($dependency) || class_exists($dependency)) {
                    $arguments[] = $container->get($dependency);
                    continue;
                }
            }
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }
            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }
            throw new \LogicException(sprintf(
                'Cannot autowire %s: parameter $%s has no resolvable class type or default value.',
                $class,
                $parameter->getName()
            ));
        }

        return $reflection->newInstanceArgs($arguments);
    }

}
