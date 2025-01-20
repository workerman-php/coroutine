<?php

namespace Workerman\Coroutine\Context;

use Swow\Coroutine;
use WeakMap;

class Swow implements ContextInterface
{
    public static WeakMap $contexts;

    public static function get(string $name, mixed $default = null): mixed
    {
        return static::$contexts[Coroutine::getCurrent()][$name] ?? $default;
    }

    public static function set(string $name, $value): void
    {
        $coroutine = Coroutine::getCurrent();
        static::$contexts[$coroutine] ??= [];
        static::$contexts[$coroutine][$name] = $value;
    }

    public static function has(string $name): bool
    {
        return isset(static::$contexts[Coroutine::getCurrent()][$name]);
    }

    public static function init(array $data): void
    {
        $coroutine = Coroutine::getCurrent();
        static::$contexts[$coroutine] = $data;
    }

    public static function destroy(): void
    {
        unset(static::$contexts[Coroutine::getCurrent()]);
    }

    public static function initWeakMap()
    {
        self::$contexts = new WeakMap();
    }

}

Swow::initWeakMap();