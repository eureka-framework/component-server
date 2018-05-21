<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process;

use Eureka\Component\Server\Command;
use Psr\Log\LoggerAwareTrait;

/**
 * Multi Process class.
 *
 * @author Romain Cottard
 */
class Multiprocessing
{
    use LoggerAwareTrait;

    /** @var Pool[] $pools List of process' pools */
    protected $pools = array();

    /** @var callable $callback Callback method / function. */
    protected $callback = null;

    /** @var Retry $retry Retry class instance. */
    protected $retry = null;

    /**
     * @var bool $safeMultiprocessing Check if process not already running before starting it
     * CAUTION: if false parallelism must be correctly handled by the worker!
     */
    protected $safeMultiprocessing = true;

    /**
     * Set retry instance.
     *
     * @param Retry $retry
     * @return void
     */
    public function setRetry(Retry $retry)
    {
        $this->retry = $retry;
    }

    /**
     * Add pool to the multi processing task
     *
     * @param  Pool $pool
     * @return $this
     */
    public function addPool(Pool $pool)
    {
        $this->pools[] = $pool;

        return $this;
    }

    /**
     * Set callback method to getfor the process
     *
     * @param callable $callback Callback method.
     * @return $this
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSafeMultiprocessing()
    {
        return $this->safeMultiprocessing;
    }

    /**
     * @param bool $safeMultiprocessing
     * @return $this
     */
    public function setSafeMultiprocessing($safeMultiprocessing)
    {
        $this->safeMultiprocessing = $safeMultiprocessing;

        return $this;
    }

    /**
     * Run multi processes
     *
     * @param  integer $sleepDelay
     * @return void
     * @throws \UnderflowException
     * @throws \Exception
     */
    public function run($sleepDelay = 1)
    {
        $sleepDelay = (int) $sleepDelay;

        //~ To prevent overload of the cpu, delay must be at least 1s
        if ($sleepDelay <= 0) {
            throw new \UnderflowException('Delay must be greater than 0 !');
        }

        while (true) {
            try {
                $shared     = null;
                $listShared = array();

                //~ iterate on all of pools
                foreach ($this->pools as $pool) {

                    //~ Keep shared pool for the next step
                    if ($pool->isShared()) {
                        $shared = $pool;
                        continue;
                    }

                    //~ Get list can be processed
                    $list = call_user_func_array($this->callback, array($pool->getCallbackContext()));

                    if (empty($list)) {
                        continue;
                    }

                    //~ Check process in pool
                    $this->checkPool($pool, $list);

                    //~ Merge element in list with shared list
                    $listShared = array_merge($listShared, $list);
                }

                //~ Check for shared pool & used element from non shared pool
                if ($shared instanceof Pool && !empty($listShared)) {
                    $this->checkPool($shared, $listShared);
                }

                sleep($sleepDelay);
            } catch (\Exception $exception) {
                if (!($this->retry instanceof Retry)) {
                    throw $exception;
                }

                $this->retry->retry($exception);
            }
        }
    }

    /**
     * Check if process are idle in pool.
     *
     * In case of yes, reset process & run it again.
     *
     * @param  Pool $pool
     * @param  Command\Argument[][] $list
     * @return void
     */
    protected function checkPool(Pool $pool, &$list)
    {
        if (empty($list)) {
            return;
        }

        foreach (new ProcessIdleFilterIterator($pool) as $process) {

            do {
                $arguments = array_shift($list);

                //~ If process is idle, reset it & add new arguments
                $command = $process->reset();

                foreach ($arguments as $argument) {
                    $command->addArgument($argument);
                }

                //~ If process is already running,
                try {
                    $process->run(true, $this->isSafeMultiprocessing());
                    break;
                } catch (Exception\ProcessAlreadyRunningException $exception) {
                    //~ Continue with next arguments
                }
            } while (!empty($list));

            if (empty($list)) {
                break;
            }
        }
    }
}
