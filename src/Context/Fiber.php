<?php

namespace Workerman\Coroutine\Context;

use ArrayObject;
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
     * @var ArrayObject
     */
    private static ArrayObject $nonFiberContext;

    /**
     * @inheritDoc
     */
    public static function get(?string $name = null, mixed $default = null): mixed
    {
        $fiber = BaseFiber::getCurrent();
        if ($fiber === null) {
            return $name !== null ? (static::$nonFiberContext[$name] ?? $default) : static::$nonFiberContext;
        }
        if ($name === null) {
            return static::$contexts[$fiber] ??= new ArrayObject();
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
        static::$contexts[$fiber] ??= new ArrayObject();
        static::$contexts[$fiber][$name] = $value;
    }

    /**
     * @inheritDoc
     */
    public static function has(string $name): bool
    {
        $fiber = BaseFiber::getCurrent();
        if ($fiber === null) {
            return static::$nonFiberContext->offsetExists($name);
        }
        return isset(static::$contexts[$fiber]) && static::$contexts[$fiber]->offsetExists($name);
    }

    /**
     * @inheritDoc
     */
    public static function reset(?ArrayObject $data = null): void
    {
        $fiber = BaseFiber::getCurrent();
        if ($fiber === null) {
            static::$nonFiberContext = $data ?: new ArrayObject();
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
            static::$nonFiberContext = new ArrayObject();
            return;
        }
        unset(static::$contexts[$fiber]);
    }

    /**
     * Initialize the weakMap.
     */
    public static function initContext(): void
    {
        static::$contexts = new WeakMap();
        static::$nonFiberContext = new ArrayObject();
    }

}

Fiber::initContext();