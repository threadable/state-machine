<?php

declare(strict_types=1);

namespace Threadable\StateMachine\Support;

use Illuminate\Support\Str;

class ClassResolver
{
    public static function model(string $class): string
    {
        return self::resolveConfiguredClass('threadstate.namespaces.models', $class);
    }

    public static function action(string $class): string
    {
        return self::resolveConfiguredClass('threadstate.namespaces.actions', $class);
    }

    public static function condition(string $class): string
    {
        return self::resolveConfiguredClass('threadstate.namespaces.conditions', $class);
    }

    public static function repository(string $class): string
    {
        $suffix = (string) config('threadstate.repositories.suffix', 'Repository');

        return self::resolveConfiguredClass('threadstate.namespaces.repositories', $class, $suffix);
    }

    protected static function resolveConfiguredClass(string $configKey, string $class, string $suffix = ''): string
    {
        if (class_exists($class) || interface_exists($class)) {
            return $class;
        }

        $namespace = self::normalizeNamespace((string) config($configKey, ''));

        return $namespace.Str::studly($class).$suffix;
    }

    protected static function normalizeNamespace(string $namespace): string
    {
        if ($namespace === '') {
            return '';
        }

        return trim($namespace, '\\').'\\';
    }
}
