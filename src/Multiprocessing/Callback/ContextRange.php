<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Multiprocessing\Callback;

/**
 * Callback context range. Used to manage offset / limit subset result set for scripts.
 *
 * @author Romain Cottard
 */
final class ContextRange implements \Iterator, \Countable
{
    /** @var int $index Index of current step range. */
    protected $index = 0;

    /** @var int $offsetMax Offset max for the context. */
    protected $offsetMax = 0;

    /** @var int $nbRepeatMax Max number of repeat, based on the max offset value. */
    protected $nbRepeatMax = 0;

    /** @var int $nbRepeat Number of repeat range context */
    protected $nbRepeat = 0;

    /** @var int $offset Offset start context. */
    protected $offset = 0;

    /** @var int $limit Limit context */
    protected $limit = 100;

    /** @var int $step Increase step context. */
    protected $step = 500;

    /**
     * ContextRange constructor.
     *
     * @param int $offset
     * @param int $limit
     * @param int $step
     * @param int $nbRepeat
     */
    public function __construct(int $offset = 0, int $limit = 100, int $step = 300, int $nbRepeat = 5)
    {
        $this->setOffset($offset);
        $this->setLimit($limit);
        $this->setStep($step);
        $this->setNbRepeat($nbRepeat);
    }

    /**
     * Get number of repeat range context
     *
     * @return int
     */
    public function getNbRepeat(): int
    {
        return $this->nbRepeat;
    }

    /**
     * Set number of repeat range context
     *
     * @param  int $nbRepeat
     * @return $this
     * @throws \UnderflowException
     */
    public function setNbRepeat(int $nbRepeat): self
    {
        if ($nbRepeat < 0) {
            throw new \UnderflowException('Nb Repeat must be greater than 0!');
        }

        $this->nbRepeat = $nbRepeat;

        return $this;
    }

    /**
     * Get offset value.
     *
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Set offset value.
     *
     * @param  int $offset
     * @return $this
     * @throws \UnderflowException
     */
    public function setOffset(int $offset): self
    {
        if ($offset < 0) {
            throw new \UnderflowException('Offset must be equals or greater than 0!');
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * Get limit value.
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Set limit value.
     *
     * @param  int $limit
     * @return $this
     * @throws \UnderflowException
     */
    public function setLimit(int $limit): self
    {
        if ($limit < 0) {
            throw new \UnderflowException('Limit must be equals or greater than 0!');
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * Get step value.
     *
     * @return int
     */
    public function getStep(): int
    {
        return $this->step;
    }

    /**
     * Set step value.
     *
     * @param  int $step
     * @return $this
     * @throws \UnderflowException
     */
    public function setStep(int $step): self
    {
        if ($step <= 0) {
            throw new \UnderflowException('Step must be greater than 0!');
        }

        $this->step = $step;

        return $this;
    }

    /**
     * Get offset max.
     *
     * @return int
     */
    public function getOffsetMax(): int
    {
        return $this->offsetMax;
    }

    /**
     * Set offset max based on the max result for ranged query.
     *
     * @param  int $offsetMax
     * @return $this
     * @throws \UnderflowException
     */
    public function setOffsetMax(int $offsetMax): self
    {
        if ($offsetMax < 0) {
            throw new \UnderflowException('Offset max must be greater than 0!');
        }

        $this->offsetMax = min($this->calculateOffsetMax(), $offsetMax);

        if ($this->offsetMax > 0) {
            $this->setNbRepeatMax(((int) ($this->offsetMax - $this->getOffset()) / $this->getStep()) + 1);
        } else {
            $this->setNbRepeatMax(0);
        }

        return $this;
    }

    /**
     * Get current offset during iteration on current instance.
     *
     * @return int
     */
    public function getOffsetCurrent(): int
    {
        return $this->getOffset() + ($this->key() * $this->getStep());
    }

    /**
     * Get the max number of repeat for iterator.
     *
     * @return int
     */
    protected function getNbRepeatMax(): int
    {
        return $this->nbRepeatMax;
    }

    /**
     * Set max number of repeat for iterator.
     *
     * @param  int $nbRepeatMax
     * @return $this
     * @throws \OutOfRangeException
     */
    protected function setNbRepeatMax(int $nbRepeatMax): self
    {
        if ($nbRepeatMax < 0) {
            throw new \OutOfRangeException('Offset max must be greater than 0!');
        }

        $this->nbRepeatMax = $nbRepeatMax;

        return $this;
    }

    /**
     * Get offset max based on nb repeat, step, limit & base offset.
     *
     * @return int
     */
    protected function calculateOffsetMax(): int
    {
        return $this->getOffset() + ($this->getLimit() + $this->getStep() * ($this->getNbRepeat() - 1));
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->nbRepeat;
    }

    /**
     * @return ContextRange
     */
    public function current(): self
    {
        return $this;
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
     * {@inheritdoc}
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
        return $this->index < $this->getNbRepeatMax();
    }
}
