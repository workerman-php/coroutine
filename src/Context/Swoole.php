<?php

namespace Workerman\Coroutine\Context;

use Swoole\Coroutine;
class Swoole implements ContextInterface
{

    public static function get(string $name, mixed $default = null): mixed
    {
        return Coroutine::getContext()[$name] ?? $default;
    }

    public static function set(string $name, $value): void
    {
        Coroutine::getContext()[$name] = $value;
    }

    public static function has(string $name): bool
    {
        return isset(Coroutine::getContext()[$name]);
    }

    public static function init(array $data): void
    {
        $context = Coroutine::getContext();
        foreach ($data as $key => $value) {
            $context[$key] = $value;
        }
    }

    public static function destroy(): void
    {
        $context = Coroutine::getContext();
        foreach ($context as $key => $value) {
            unset($context[$key]);
        }
    }

}