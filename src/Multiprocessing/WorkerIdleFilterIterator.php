<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Multiprocessing;

/**
 * Process class. It is container for a Command instance.
 *
 * @author Romain Cottard
 */
class WorkerIdleFilterIterator extends \FilterIterator
{
    /**
     * Return true if the current process is idle.
     *
     * @return bool
     */
    public function accept(): bool
    {
        return $this->current()->isIdle();
    }
}
