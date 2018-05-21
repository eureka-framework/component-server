<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process;

/**
 * Process Pool class.
 *
 * @author Romain Cottard
 */
class Pool implements \Iterator, \Countable
{
    /** @var Process[] $pool List of process in instance Pool */
    protected $pool = [];

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

    /** @var Process[] $processIdle List of process idle. */
    protected $processIdle = [];

    /**
     * Pool constructor.
     *
     * @param  string $name
     * @param  float $ratio
     * @param  bool $isShared
     * @throws \OutOfRangeException
     */
    public function __construct($name, $ratio, $isShared)
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
     * @param  Process $process
     * @return $this
     */
    public function attachProcess(Process $process)
    {
        $this->pool[] = $process;
        $this->count++;

        return $this;
    }

    /**
     * Get callback context
     *
     * @return Callback\Context
     */
    public function getCallbackContext()
    {
        return $this->callbackContext;
    }

    /**
     * Set callback context
     *
     * @param  Callback\Context $callbackContext
     * @return $this
     */
    public function setCallbackContext(Callback\Context $callbackContext)
    {
        $this->callbackContext = $callbackContext;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Resource is shared
     *
     * @return bool
     */
    public function isShared()
    {
        return $this->isShared;
    }

    /**
     * Check if pool have process idle
     *
     * @return bool
     */
    public function hasIdleProcess()
    {
        $this->processIdle = [];
        foreach ($this->pool as $process) {
            if (!($process instanceof Process)) {
                continue;
            }

            if (!$process->isIdle()) {
                continue;
            }

            $this->processIdle[] = $process;
        }

        return (bool) count($this->processIdle);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->pool[$this->index];
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->index++;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return ($this->index < $this->count);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->pool);
    }

    /**
     * Set pool name
     *
     * @param  string $name
     * @return $this
     */
    private function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Set pool ratio
     *
     * @param  float $ratio
     * @return $this
     * @throws \OutOfRangeException
     */
    private function setRatio($ratio)
    {
        $this->ratio = (float) $ratio;

        if ($this->ratio < 0.01 || $this->ratio > 1.0) {
            throw new \OutOfRangeException('Ratio must be a number between 0.1 and 0.9 (included)!');
        }

        return $this;
    }

    /**
     * Set if the pool is shared for different type of process
     *
     * @param  bool $isShared
     * @return $this
     */
    private function setIsShared($isShared)
    {
        $this->isShared = (bool) $isShared;

        return $this;
    }
}
