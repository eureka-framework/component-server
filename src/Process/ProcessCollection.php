<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process;

/**
 * Class ProcessCollection
 *
 * @author Romain Cottard
 */
class ProcessCollection implements \Countable, \Iterator
{
    /** @var Process[] $collection */
    private $collection = [];

    /** @var int $index */
    private $index = 0;

    /** @var int $count */
    private $count = 0;

    /**
     * @param Process $process
     * @return ProcessCollection
     */
    public function add(Process $process): self
    {
        $this->collection[$this->count] = $process;
        $this->count++;

        return $this;
    }

    /**
     * @return Process
     */
    public function current(): Process
    {
        return $this->collection[$this->index];
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->index++;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return ($this->index < $this->count);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }
}
