<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Multiprocessing;

/**
 * Worker Pool class.
 *
 * @author Romain Cottard
 */
final class Pool implements \Iterator, \Countable
{
    /** @var Worker[] $workers List of worker in instance Pool */
    protected $workers = [];

    /** @var string $name Pool name */
    protected $name = '';

    /** @var float $ratio Ratio for all number of process. Must be a float number between 0.01 & 1.0 */
    protected $ratio = 0.0;

    /** @var bool $isShared If the pool is shared for different type of process. */
    protected $isShared = false;

    /** @var int $index Current index process in pool */
    protected $index = 0;

    /** @var int $count Number of process in pool. */
    protected $count = 0;

    /** @var Callback\Context $context Context data for process callback method. */
    protected $callbackContext = [];

    /** @var Worker[] $workersIdle List of process idle. */
    protected $workersIdle = [];

    /**
     * Pool constructor.
     *
     * @param  string $name
     * @param  float $ratio
     * @param  bool $isShared
     * @throws \OutOfRangeException
     */
    public function __construct(string $name, float $ratio, bool $isShared)
    {

        $this
            ->setName($name)
            ->setRatio($ratio)
            ->setIsShared($isShared)
        ;
    }

    /**
     * Attach process to the pool
     *
     * @param  Worker $worker
     * @return $this
     */
    public function attachWorker(Worker $worker): self
    {
        $this->workers[] = $worker;
        $this->count++;

        return $this;
    }

    /**
     * Get callback context
     *
     * @return Callback\Context
     */
    public function getCallbackContext(): Callback\Context
    {
        return $this->callbackContext;
    }

    /**
     * Set callback context
     *
     * @param  Callback\Context $callbackContext
     * @return $this
     */
    public function setCallbackContext(Callback\Context $callbackContext): self
    {
        $this->callbackContext = $callbackContext;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Resource is shared
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->isShared;
    }

    /**
     * Check if pool have worker idle
     *
     * @return bool
     */
    public function hasIdleWorker(): bool
    {
        $this->workersIdle = [];
        foreach ($this->workers as $worker) {
            if (!($worker instanceof Worker)) {
                continue;
            }

            if (!$worker->isIdle()) {
                continue;
            }

            $this->workersIdle[] = $worker;
        }

        return (bool) count($this->workersIdle);
    }

    /**
     * @return Worker
     */
    public function current(): Worker
    {
        return $this->workers[$this->index];
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
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        $this->index++;
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
        return count($this->workers);
    }

    /**
     * Set pool name
     *
     * @param  string $name
     * @return $this
     */
    private function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set pool ratio
     *
     * @param  float $ratio
     * @return $this
     * @throws \OutOfRangeException
     */
    private function setRatio(float $ratio): self
    {
        $this->ratio = $ratio;

        if ($this->ratio < 0.01 || $this->ratio > 1.0) {
            throw new \OutOfRangeException('Ratio must be a number between 0.1 and 0.9 (included)!');
        }

        return $this;
    }

    /**
     * Set if the pool is shared for different type of worker
     *
     * @param  bool $isShared
     * @return $this
     */
    private function setIsShared(bool $isShared): self
    {
        $this->isShared = $isShared;

        return $this;
    }
}
