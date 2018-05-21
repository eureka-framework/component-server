<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process\Callback;

/**
 * Callback context range. Used to manage offset / limit subset result set for scripts.
 *
 * @author Romain Cottard
 */
class ContextRange implements \Iterator, \Countable
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
    public function __construct($offset = 0, $limit = 100, $step = 300, $nbRepeat = 5)
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
    public function getNbRepeat()
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
    public function setNbRepeat($nbRepeat)
    {
        $nbRepeat = (int) $nbRepeat;

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
    public function getOffset()
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
    public function setOffset($offset)
    {
        $offset = (int) $offset;

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
    public function getLimit()
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
    public function setLimit($limit)
    {
        $limit = (int) $limit;

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
    public function getStep()
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
    public function setStep($step)
    {
        $step = (int) $step;

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
    public function getOffsetMax()
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
    public function setOffsetMax($offsetMax)
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
    public function getOffsetCurrent()
    {
        return $this->getOffset() + ($this->key() * $this->getStep());
    }

    /**
     * Get the max number of repeat for iterator.
     *
     * @return int
     */
    protected function getNbRepeatMax()
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
    protected function setNbRepeatMax($nbRepeatMax)
    {
        if ($nbRepeatMax < 0) {
            throw new \OutOfRangeException('Offset max must be greater than 0!');
        }

        $this->nbRepeatMax = (int) $nbRepeatMax;

        return $this;
    }

    /**
     * Get offset max based on nb repeat, step, limit & base offset.
     *
     * @return int
     */
    protected function calculateOffsetMax()
    {
        return $this->getOffset() + ($this->getLimit() + $this->getStep() * ($this->getNbRepeat() - 1));
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->nbRepeat;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this;
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
    public function next()
    {
        $this->index++;
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
    public function valid()
    {
        return $this->index < $this->getNbRepeatMax();
    }
}
