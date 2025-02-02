<?php

use Workerman\Events\Event;
use Workerman\Events\Select;
use Workerman\Events\Swow;
use Workerman\Events\Swoole;
use Workerman\Events\Fiber;
use Workerman\Timer;
use Workerman\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

if (DIRECTORY_SEPARATOR === '/' || (!extension_loaded('swow') && !class_exists(Revolt\EventLoop::class))) {
    $worker = new Worker();
    $worker->eventLoop = Select::class;
    $worker->onWorkerStart = function () {
        echo "TEST EVENT-LOOP: Select\n";
        (new PHPUnit\TextUI\Application)->run([
            __DIR__ . '/../vendor/bin/phpunit',
            __DIR__ . '/ChannelTest.php',
            __DIR__ . '/PoolTest.php',
            __DIR__ . '/BarrierTest.php',
            __DIR__ . '/ContextTest.php',
        ]);
    };
}

if (extension_loaded('event')) {
    $worker = new Worker();
    $worker->eventLoop = Event::class;
    $worker->onWorkerStart = function () {
        echo "TEST EVENT-LOOP: Event\n";
        (new PHPUnit\TextUI\Application)->run([
            __DIR__ . '/../vendor/bin/phpunit',
            __DIR__ . '/ChannelTest.php',
            __DIR__ . '/PoolTest.php',
            __DIR__ . '/BarrierTest.php',
            __DIR__ . '/ContextTest.php',
        ]);
    };
}

if (class_exists(Revolt\EventLoop::class) && (DIRECTORY_SEPARATOR === '/' || !extension_loaded('swow'))) {
    $worker = new Worker();
    $worker->eventLoop = Fiber::class;
    $worker->onWorkerStart = function () {
        echo "TEST EVENT-LOOP: Fiber\n";
        (new PHPUnit\TextUI\Application)->run([
            __DIR__ . '/../vendor/bin/phpunit',
            ...glob(__DIR__ . '/*Test.php')
        ]);
    };
}

if (extension_loaded('Swoole')) {
    $worker = new Worker();
    $worker->eventLoop = Swoole::class;
    $worker->onWorkerStart = function () {
        echo "TEST EVENT-LOOP: Swoole\n";
        (new PHPUnit\TextUI\Application)->run([
            __DIR__ . '/../vendor/bin/phpunit',
            ...glob(__DIR__ . '/*Test.php')
        ]);
        Timer::delay(1, function () {
            function_exists('posix_kill') && posix_kill(posix_getppid(), SIGINT);
        });
    };
}

if (extension_loaded('Swow')) {
    $worker = new Worker();
    $worker->eventLoop = Swow::class;
    $worker->onWorkerStart = function () {
        echo "TEST EVENT-LOOP: Swow\n";
        (new PHPUnit\TextUI\Application)->run([
            __DIR__ . '/../vendor/bin/phpunit',
            ...glob(__DIR__ . '/*Test.php')
        ]);
        Timer::delay(1, function () {
            function_exists('posix_kill') && posix_kill(posix_getppid(), SIGINT);
        });
    };
}

Worker::runAll();
