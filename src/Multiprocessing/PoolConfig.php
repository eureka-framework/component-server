<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Multiprocessing;

/**
 * Pool Config class.
 *
 * @author Romain Cottard
 */
class PoolConfig
{
    /** @var string|int $index Pool index (name or value) */
    protected $index = null;

    /** @var float $ratio Ratio for all number of process. Must be a float number between 0.01 & 1.0 */
    protected $ratio = 0.0;

    /** @var bool $isShared If the pool is shared for different type of process. */
    protected $isShared = false;

    /**
     * Pool constructor.
     *
     * @param  float $ratio
     * @param  bool $isShared
     * @param  string|null $index
     * @throws \OutOfRangeException
     */
    public function __construct(float $ratio, bool $isShared = false, ?string $index = null)
    {
        $this
            ->setRatio($ratio)
            ->setIsShared($isShared)
            ->setIndex($index)
        ;
    }

    /**
     * Get index
     *
     * @return string|null
     */
    public function getIndex(): ?string
    {
        return $this->index;
    }

    /**
     * Get ratio
     *
     * @return float
     */
    public function getRatio(): float
    {
        return $this->ratio;
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
            throw new \OutOfRangeException('Ratio must be a number between 0.01s and 1.0 (included)!');
        }

        return $this;
    }

    /**
     * Set if the pool is shared for different type of process
     *
     * @param  bool $isShared
     * @return $this
     */
    private function setIsShared(bool $isShared): self
    {
        $this->isShared = $isShared;

        return $this;
    }

    /**
     * Set the pool "index" (value or name)
     *
     * @param  string|null $index
     * @return $this
     */
    private function setIndex(?string $index): self
    {
        $this->index = $index;

        return $this;
    }
}
