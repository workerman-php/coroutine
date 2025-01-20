<?php

namespace Workerman\Coroutine\Context;

class Memory implements ContextInterface
{
    /**
     * @var array
     */
    private static array $context = [];

    /**
     * @inheritDoc
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return static::$context[$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public static function set(string $name, $value): void
    {
        static::$context[$name] = $value;
    }

    /**
     * @inheritDoc
     */
    public static function has(string $name): bool
    {
        return key_exists($name, static::$context);
    }

    /**
     * @inheritDoc
     */
    public static function init(array $data = []): void
    {
        static::$context = $data;
    }

    /**
     * @inheritDoc
     */
    public static function destroy(): void
    {
        static::$context = [];
    }

}
