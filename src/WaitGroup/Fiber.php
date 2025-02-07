<?php
/**
 * @author workbunny/Chaz6chez
 * @email chaz6chez1993@outlook.com
 */
declare(strict_types=1);

namespace Workerman\Coroutine\WaitGroup;

use Workerman\Coroutine\Channel\Fiber as Channel;

class Fiber implements WaitGroupInterface
{
    /** @var int */
    protected int $_count;

    /**
     * @var Channel
     */
    protected Channel $_channel;

    public function __construct()
    {
        $this->_count = 0;
        $this->_channel = new Channel(1);
    }

    /** @inheritdoc  */
    public function add(int $delta = 1): bool
    {
        $this->_count += max($delta, 1);

        return true;
    }

    /** @inheritdoc  */
    public function done(): bool
    {
        $this->_count--;
        if ($this->_count <= 0) {
            $this->_channel->push(true);
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
       if ($this->count() > 0) {
           return $this->_channel->pop($timeout);
       }
       return true;
    }
}