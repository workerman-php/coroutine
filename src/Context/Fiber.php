<?php

namespace Workerman\Coroutine\Context;

use WeakMap;
use Fiber as BaseFiber;

/**
 * Class Fiber
 */
class Fiber implements ContextInterface
{
    /**
     * @var WeakMap
     */
    private static WeakMap $contexts;

    /**
     * @var array
     */
    private static array $nonFiberContext = [];

    /**
     * @inheritDoc
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        if (($fiber = BaseFiber::getCurrent()) === null) {
            return static::$nonFiberContext[$name] ?? $default;
        }
        return static::$contexts[$fiber][$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public static function set(string $name, $value): void
    {
        $fiber = BaseFiber::getCurrent();
        if ($fiber === null) {
            static::$nonFiberContext[$name] = $value;
            return;
        }
        static::$contexts[$fiber] ??= [];
        static::$contexts[$fiber][$name] = $value;
    }

    /**
     * @inheritDoc
     */
    public static function has(string $name): bool
    {
        $fiber = BaseFiber::getCurrent();
        if ($fiber === null) {
            return key_exists($name, static::$nonFiberContext);
        }
        $context = static::$contexts[$fiber] ?? [];
        return key_exists($name, $context);
    }

    /**
     * @inheritDoc
     */
    public static function init(array $data = []): void
    {
        $fiber = BaseFiber::getCurrent();
        if ($fiber === null) {
            static::$nonFiberContext = $data;
            return;
        }
        static::$contexts[$fiber] = $data;
    }

    /**
     * @inheritDoc
     */
    public static function destroy(): void
    {
        $fiber = BaseFiber::getCurrent();
        if ($fiber === null) {
            static::$nonFiberContext = [];
            return;
        }
        unset(static::$contexts[$fiber]);
    }

    /**
     * Initialize the weakMap.
     */
    public static function initWeakMap(): void
    {
        static::$contexts = new WeakMap();
    }

}

Fiber::initWeakMap();