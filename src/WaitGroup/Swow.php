<?php
/**
 * @author workbunny/Chaz6chez
 * @email chaz6chez1993@outlook.com
 */
declare(strict_types=1);

namespace Workerman\Coroutine\WaitGroup;

use Swow\Sync\WaitGroup;

class Swow implements WaitGroupInterface
{
    /** @var WaitGroup */
    protected WaitGroup $_waitGroup;

    /** @var int count */
    protected int $_count;

    public function __construct()
    {
        $this->_waitGroup = new WaitGroup();
        $this->_count = 0;
    }

    /** @inheritdoc  */
    public function add(int $delta = 1): bool
    {
        $this->_waitGroup->add($delta = max($delta, 1));
        $this->_count += $delta;

        return true;
    }

    /** @inheritdoc  */
    public function done(): bool
    {
        if ($this->count() > 0) {
            $this->_waitGroup->done();
            $this->_count--;
        }

        return true;
    }

    /** @inheritdoc  */
    public function count(): int
    {
        return $this->_count;
    }

    /** @inheritdoc  */
    public function wait(int|float $timeout = -1): bool
    {
        try {
            $this->_waitGroup->wait($timeout > 0 ? (int) ($timeout * 1000) : $timeout);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
