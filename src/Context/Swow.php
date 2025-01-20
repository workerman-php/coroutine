<?php

namespace Workerman\Coroutine\Context;

use Swow\Coroutine;
use WeakMap;

class Swow implements ContextInterface
{
    /**
     * @var WeakMap
     */
    public static WeakMap $contexts;

    /**
     * @inheritDoc
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return static::$contexts[Coroutine::getCurrent()][$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public static function set(string $name, $value): void
    {
        $coroutine = Coroutine::getCurrent();
        static::$contexts[$coroutine] ??= [];
        static::$contexts[$coroutine][$name] = $value;
    }

    /**
     * @inheritDoc
     */
    public static function has(string $name): bool
    {
        return isset(static::$contexts[Coroutine::getCurrent()][$name]);
    }

    /**
     * @inheritDoc
     */
    public static function init(array $data = []): void
    {
        $coroutine = Coroutine::getCurrent();
        static::$contexts[$coroutine] = $data;
    }

    /**
     * @inheritDoc
     */
    public static function destroy(): void
    {
        unset(static::$contexts[Coroutine::getCurrent()]);
    }

    /**
     * Initialize the weakMap.
     *
     * @return void
     */
    public static function initWeakMap(): void
    {
        self::$contexts = new WeakMap();
    }

}

Swow::initWeakMap();