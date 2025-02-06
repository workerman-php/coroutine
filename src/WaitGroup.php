<?php
/**
 * @author workbunny/Chaz6chez
 * @email chaz6chez1993@outlook.com
 */
declare(strict_types=1);

namespace Workerman;

use Workerman\Coroutine\Coroutine\CoroutineInterface;
use Workerman\Coroutine\WaitGroup\Fiber as FiberWaitGroup;
use Workerman\Coroutine\WaitGroup\Swoole as SwooleWaitGroup;
use Workerman\Coroutine\WaitGroup\Swow as SwowWaitGroup;
use Workerman\Coroutine\WaitGroup\WaitGroupInterface;

/**
 * @method bool add(int $delta = 1)
 * @method bool done()
 * @method int count()
 * @method void wait(int $timeout = -1)
 */
class WaitGroup
{
    /**
     * @var class-string<CoroutineInterface>
     */
    protected static string $driverClass;

    /**
     * @var WaitGroupInterface
     */
    protected WaitGroupInterface $driver;

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->driver = new (self::driverClass());
    }

    /**
     * Get driver class.
     *
     * @return class-string<CoroutineInterface>
     */
    protected static function driverClass(): string
    {
        return static::$driverClass ??= $driverClass ?? match (Worker::$eventLoopClass ?? null) {
            SwooleEvent::class => SwooleWaitGroup::class,
            SwowEvent::class => SwowWaitGroup::class,
            default => FiberWaitGroup::class,
        };
    }


    /**
     * 代理调用WaitGroupInterface方法
     *
     * @codeCoverageIgnore 系统魔术方法，忽略覆盖
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!method_exists($this->driver, $name)) {
            throw new \BadMethodCallException("Method $name not exists. ");
        }

        return $this->driver->$name(...$arguments);
    }
}
