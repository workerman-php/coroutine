<?php

namespace Workerman\Coroutine\Context;

use Swoole\Coroutine;
class Swoole implements ContextInterface
{

    /**
     * @inheritDoc
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return Coroutine::getContext()[$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public static function set(string $name, $value): void
    {
        Coroutine::getContext()[$name] = $value;
    }

    /**
     * @inheritDoc
     */
    public static function has(string $name): bool
    {
        return isset(Coroutine::getContext()[$name]);
    }

    /**
     * @inheritDoc
     */
    public static function init(array $data = []): void
    {
        $context = Coroutine::getContext();
        foreach ($data as $key => $value) {
            $context[$key] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public static function destroy(): void
    {
        $context = Coroutine::getContext();
        foreach ($context as $key => $value) {
            unset($context[$key]);
        }
    }

}