<?php
/**
 * @author workbunny/Chaz6chez
 * @email chaz6chez1993@outlook.com
 */
declare(strict_types=1);

namespace Workerman\Coroutine\WaitGroup;

/**
 * Class Swoole
 */
class Swoole implements WaitGroupInterface
{

    /** @var WaitGroup */
    protected WaitGroup $_waitGroup;

    /** @inheritdoc  */
    public function __construct()
    {
        $this->_waitGroup = new WaitGroup();
    }

    /** @inheritdoc  */
    public function add(int $delta = 1): bool
    {
        $this->_waitGroup->add(max($delta, 1));

        return true;
    }

    /** @inheritdoc  */
    public function done(): bool
    {
        if ($this->count() > 0) {
            $this->_waitGroup->done();
        }

        return true;
    }

    /** @inheritdoc  */
    public function count(): int
    {
        return $this->_waitGroup->count();
    }

    /** @inheritdoc  */
    public function wait(int|float $timeout = -1): bool
    {
        try {
            $this->_waitGroup->wait(max($timeout, $timeout > 0 ? 0.001 : -1));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

}
