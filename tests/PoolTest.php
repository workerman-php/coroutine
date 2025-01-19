<?php

namespace test;

use PHPUnit\Framework\TestCase;
use Workerman\Coroutine\Pool;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use stdClass;
use RuntimeException;
use Exception;

class PoolTest extends TestCase
{
    public function testConstructorWithConfig()
    {
        $config = [
            'min_connections' => 2,
            'idle_timeout' => 30,
            'heartbeat_interval' => 10,
            'wait_timeout' => 5,
        ];
        $pool = new Pool(10, $config);

        $this->assertEquals(10, $this->getPrivateProperty($pool, 'maxConnections'));
        $this->assertEquals(2, $this->getPrivateProperty($pool, 'minConnections'));
        $this->assertEquals(30, $this->getPrivateProperty($pool, 'idleTimeout'));
        $this->assertEquals(10, $this->getPrivateProperty($pool, 'heartbeatInterval'));
        $this->assertEquals(5, $this->getPrivateProperty($pool, 'waitTimeout'));
    }

    public function testSetConnectionCreator()
    {
        $pool = new Pool(5);
        $connectionCreator = function () {
            return new stdClass();
        };
        $pool->setConnectionCreator($connectionCreator);
        $this->assertSame($connectionCreator, $this->getPrivateProperty($pool, 'connectionCreateHandler'));
    }

    public function testSetConnectionCloser()
    {
        $pool = new Pool(5);
        $connectionCloser = function ($conn) {
            // Close connection.
        };
        $pool->setConnectionCloser($connectionCloser);
        $this->assertSame($connectionCloser, $this->getPrivateProperty($pool, 'connectionDestroyHandler'));
    }

    public function testGetConnection()
    {
        $pool = new Pool(5);

        $connectionMock = $this->createMock(stdClass::class);

        // 设置连接创建器
        $pool->setConnectionCreator(function () use ($connectionMock) {
            return $connectionMock;
        });

        $connection = $pool->get();

        $this->assertSame($connectionMock, $connection);
        $this->assertEquals(1, $this->getPrivateProperty($pool, 'currentConnections'));

        // 检查 WeakMap 是否更新
        $lastUsedTimes = $this->getPrivateProperty($pool, 'lastUsedTimes');
        $lastHeartbeatTimes = $this->getPrivateProperty($pool, 'lastHeartbeatTimes');

        $this->assertTrue($lastUsedTimes->offsetExists($connection));
        $this->assertTrue($lastHeartbeatTimes->offsetExists($connection));
    }

    public function testPutConnection()
    {
        $pool = new Pool(5);
        $connection = new stdClass();

        // 模拟连接属于连接池
        $lastUsedTimes = $this->getPrivateProperty($pool, 'lastUsedTimes');
        $lastUsedTimes[$connection] = time();

        $pool->put($connection);

        $channel = $this->getPrivateProperty($pool, 'channel');
        $this->assertEquals(1, $channel->length());
    }

    public function testPutConnectionDoesNotBelong()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The connection does not belong to the connection pool.');

        $pool = new Pool(5);
        $connection = new stdClass();

        $pool->put($connection);
    }

    public function testCreateConnection()
    {
        $pool = new Pool(5);
        $connectionMock = $this->createMock(stdClass::class);

        $pool->setConnectionCreator(function () use ($connectionMock) {
            return $connectionMock;
        });

        $connection = $pool->createConnection();

        $this->assertSame($connectionMock, $connection);

        // 确保 currentConnections 增加
        $this->assertEquals(1, $this->getPrivateProperty($pool, 'currentConnections'));

        // 确保连接已推入通道
        $channel = $this->getPrivateProperty($pool, 'channel');
        $this->assertEquals(1, $channel->length());

        // 检查 WeakMap 是否更新
        $lastUsedTimes = $this->getPrivateProperty($pool, 'lastUsedTimes');
        $lastHeartbeatTimes = $this->getPrivateProperty($pool, 'lastHeartbeatTimes');

        $this->assertTrue($lastUsedTimes->offsetExists($connection));
        $this->assertTrue($lastHeartbeatTimes->offsetExists($connection));
    }

    public function testCreateConnectionThrowsException()
    {
        $pool = new Pool(5);

        $pool->setConnectionCreator(function () {
            throw new Exception('Failed to create connection');
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to create connection');

        try {
            $pool->createConnection();
        } finally {
            // 确保 currentConnections 减少
            $this->assertEquals(0, $this->getPrivateProperty($pool, 'currentConnections'));
        }
    }

    public function testCloseConnection()
    {
        $pool = new Pool(5);
        $this->setPrivateProperty($pool, 'currentConnections', 1);

        $connection = $this->createMock(ConnectionMock::class);

        // 模拟连接属于连接池
        $lastUsedTimes = $this->getPrivateProperty($pool, 'lastUsedTimes');
        $lastUsedTimes[$connection] = time();

        $connection->expects($this->once())->method('close');
        $pool->setConnectionCloser(function ($conn) {
            $conn->close();
        });

        $pool->closeConnection($connection);

        // 确保 currentConnections 减少
        $this->assertEquals(0, $this->getPrivateProperty($pool, 'currentConnections'));

        // 确保连接从 WeakMap 中移除
        $this->assertFalse($lastUsedTimes->offsetExists($connection));
    }

    public function testCloseConnectionWithExceptionInDestroyHandler()
    {
        $pool = new Pool(5);
        $this->setPrivateProperty($pool, 'currentConnections', 1);

        $connection = $this->createMock(stdClass::class);

        // 模拟连接属于连接池
        $lastUsedTimes = $this->getPrivateProperty($pool, 'lastUsedTimes');
        $lastUsedTimes[$connection] = time();

        $exception = new Exception('Error closing connection');

        $pool->setConnectionCloser(function ($conn) use ($exception) {
            throw $exception;
        });

        // 设置日志记录器
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Error closing connection'));

        $this->setPrivateProperty($pool, 'logger', $loggerMock);

        $pool->closeConnection($connection);

        // 确保 currentConnections 减少
        $this->assertEquals(0, $this->getPrivateProperty($pool, 'currentConnections'));

        // 确保连接从 WeakMap 中移除
        $this->assertFalse($lastUsedTimes->offsetExists($connection));
    }

    public function testHeartbeatChecker()
    {
        $pool = $this->getMockBuilder(Pool::class)
            ->setConstructorArgs([5])
            ->onlyMethods(['closeConnection'])
            ->getMock();

        $connection = $this->createMock(stdClass::class);

        // 设置连接心跳检测器
        $pool->setHeartbeatChecker(function ($conn) {
            // 模拟心跳检测
        });

        // 模拟连接在通道中
        $channel = $this->getPrivateProperty($pool, 'channel');
        $channel->push($connection);

        // 设置连接的上次使用时间和心跳时间
        $lastUsedTimes = $this->getPrivateProperty($pool, 'lastUsedTimes');
        $lastUsedTimes[$connection] = time();

        $lastHeartbeatTimes = $this->getPrivateProperty($pool, 'lastHeartbeatTimes');
        $lastHeartbeatTimes[$connection] = time() - 100; // 超过心跳间隔

        // 调用受保护的 checkConnections 方法
        $reflectedMethod = new \ReflectionMethod($pool, 'checkConnections');
        $reflectedMethod->setAccessible(true);
        $reflectedMethod->invoke($pool);

        // 检查心跳时间是否更新
        $lastHeartbeatTimes = $this->getPrivateProperty($pool, 'lastHeartbeatTimes');
        $this->assertGreaterThan(time() - 2, $lastHeartbeatTimes[$connection]);
    }

    public function testConnectionDestroyedWithoutReturn()
    {
        $pool = new Pool(5);

        // 设置连接创建器
        $pool->setConnectionCreator(function () {
            return new stdClass;
        });

        // 获取初始的 currentConnections
        $initialConnections = $this->getPrivateProperty($pool, 'currentConnections');

        // 从连接池获取一个连接
        $connection = $pool->get();

        // 检查 currentConnections 是否增加
        $this->assertEquals($initialConnections + 1, $this->getPrivateProperty($pool, 'currentConnections'));

        // 不归还连接，并销毁连接对象
        unset($connection);

        // 强制触发垃圾回收
        gc_collect_cycles();

        // 模拟时间经过，让连接池检测到连接已销毁
        // 调用受保护的 checkConnections 方法
        $reflectedMethod = new \ReflectionMethod($pool, 'checkConnections');
        $reflectedMethod->setAccessible(true);
        $reflectedMethod->invoke($pool);

        // 检查 currentConnections 是否减少
        $this->assertEquals($initialConnections, $this->getPrivateProperty($pool, 'currentConnections'));
    }

    private function getPrivateProperty($object, string $property)
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }

    private function setPrivateProperty($object, string $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }
}

// 定义 ConnectionMock 类用于测试
class ConnectionMock
{
    public function close()
    {
        // 模拟关闭连接
    }
}
