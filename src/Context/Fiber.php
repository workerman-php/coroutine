<?php

namespace Workerman\Coroutine\Context;


use WeakMap;
use Fiber as BaseFiber;

class Fiber implements ContextInterface
{
    /**
     * @var WeakMap
     */
    private static WeakMap $contexts;

    /**
     * @inheritDoc
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return static::$contexts[BaseFiber::getCurrent()][$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public static function set(string $name, $value): void
    {
        static::$contexts[BaseFiber::getCurrent()] ??= [];
        static::$contexts[BaseFiber::getCurrent()][$name] = $value;
    }

    /**
     * @inheritDoc
     */
    public static function has(string $name): bool
    {
        $context = static::$contexts[BaseFiber::getCurrent()] ?? [];
        return key_exists($name, $context);
    }

    /**
     * @inheritDoc
     */
    public static function init(array $data): void
    {
        static::$contexts[BaseFiber::getCurrent()] = $data;
    }

    /**
     * @inheritDoc
     */
    public static function destroy(): void
    {
        unset(static::$contexts[BaseFiber::getCurrent()]);
    }

    /**
     * @inheritDoc
     */
    public static function initWeakMap(): void
    {
        static::$contexts = new WeakMap();
    }

}

Fiber::initWeakMap();