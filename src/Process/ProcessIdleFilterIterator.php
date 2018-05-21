<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process;

/**
 * Process class. It is container for a Command instance.
 *
 * @author Romain Cottard
 */
class ProcessIdleFilterIterator extends \FilterIterator
{
    /**
     * Return true if the current process is idle.
     *
     * @return bool
     */
    public function accept()
    {
        return $this->current()->isIdle();
    }

    /**
     * Current method.
     *
     * @return Process
     */
    public function current()
    {
        return parent::current();
    }
}
