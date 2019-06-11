<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Multiprocessing;

use Eureka\Component\Server\Command;
use Eureka\Eurekon\IO\Out;
use Eureka\Eurekon\Style\Color;
use Eureka\Eurekon\Style\Style;
use Psr\Log\LoggerAwareTrait;

/**
 * Multi processing class.
 *
 * @author Romain Cottard
 */
final class Multiprocessing
{
    use LoggerAwareTrait;

    /** @var Pool[] $pools List of workers' pools */
    protected $pools = [];

    /** @var callable $callback Callback method / function. */
    protected $callback = null;

    /** @var Retry $retry Retry class instance. */
    protected $retry = null;

    /** @var string Original path (when */
    protected $originalPath;

    /** @var string */
    protected $realOriginalPath;

    /**
     * @var bool $safeMultiprocessing Check if worker not already running before starting it
     * CAUTION: if false parallelism must be correctly handled by the worker!
     */
    protected $safeMultiprocessing = true;

    /** @var bool $detectPathChanges If must detect path changed & stop daemon main loop when change is detected */
    protected $detectPathChanges = false;

    /**
     * Multiprocessing constructor.
     */
    public function __construct()
    {
        $directoryName = rtrim(dirname($_SERVER['argv'][0]));

        //~ If directoryName is relative, concat to pwd
        $this->originalPath     = (strpos($directoryName, '/') === 0 ? $directoryName : getenv('PWD') . '/' . $directoryName);
        $this->realOriginalPath = realpath($this->originalPath);
    }

    /**
     * Set retry instance.
     *
     * @param Retry $retry
     * @return $this
     */
    public function setRetry(Retry $retry): self
    {
        $this->retry = $retry;

        return $this;
    }

    /**
     * Add pool to the multi processing task
     *
     * @param  Pool $pool
     * @return $this
     */
    public function addPool(Pool $pool): self
    {
        $this->pools[] = $pool;

        return $this;
    }

    /**
     * Set callback method to get for the worker
     *
     * @param callable $callback Callback method.
     * @return $this
     */
    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSafeMultiprocessing(): bool
    {
        return $this->safeMultiprocessing;
    }

    /**
     * @param bool $safeMultiprocessing
     * @return $this
     */
    public function setSafeMultiprocessing(bool $safeMultiprocessing): self
    {
        $this->safeMultiprocessing = $safeMultiprocessing;

        return $this;
    }

    /**
     * @param bool $detectPathChanges
     * @return $this
     */
    public function setDetectPathChanges(bool $detectPathChanges): self
    {
        $this->detectPathChanges = $detectPathChanges;

        return $this;
    }

    /**
     * Run multi workers
     *
     * @param  integer $sleepDelay
     * @return void
     * @throws \UnderflowException
     * @throws \Exception
     */
    public function run(int $sleepDelay = 1): void
    {
        //~ To prevent overload of the cpu, delay must be at least 1s
        if ($sleepDelay <= 0) {
            throw new \UnderflowException('Delay must be greater than 0 !');
        }

        //~ start main infinite loop
        while (true) {

            //~ Detect path changes to stop main loop (useful when production code - based on symlink - is updated)
            if ($this->detectPathChanges && $this->hasPathChanged()) {
                Out::std((new Style('Path source code has be changed, daemon is ending now!'))->colorForeground(Color::RED));
                break;
            }

            try {
                $this->checkPools();
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
     * Check all pool and run worker currently idle if have task to do.
     *
     * @return void
     */
    protected function checkPools(): void
    {
        $shared     = null;
        $listShared = [];

        //~ iterate on all of pools
        foreach ($this->pools as $pool) {

            //~ Keep shared pool for the next step
            if ($pool->isShared()) {
                $shared = $pool;
                continue;
            }

            //~ Get list can be processed
            $list = call_user_func_array($this->callback, [$pool->getCallbackContext()]);

            if (empty($list)) {
                continue;
            }

            //~ Check worker in pool
            $this->checkWorkersInPool($pool, $list);

            //~ Merge element in list with shared list
            $listShared = array_merge($listShared, $list);
        }

        //~ Check for shared pool & used element from non shared pool
        if ($shared instanceof Pool && !empty($listShared)) {
            $this->checkWorkersInPool($shared, $listShared);
        }
    }

    /**
     * Check if have some workers are idle in pool.
     * If yes, reset worker & run it again.
     *
     * @param  Pool $pool
     * @param  Command\Argument[][] $list
     * @return void
     */
    protected function checkWorkersInPool(Pool $pool, array &$list): void
    {
        if (empty($list)) {
            return;
        }

        foreach (new WorkerIdleFilterIterator($pool) as $worker) {
            /** @var Worker $worker */
            do {
                $arguments = array_shift($list);

                //~ If worker is idle, reset it & add new arguments
                $worker
                    ->reset()
                    ->addArguments($arguments)
                ;

                //~ If worker is already running,
                try {
                    $worker->run(true, $this->isSafeMultiprocessing());
                    break;
                } catch (Exception\WorkerAlreadyRunningException $exception) {
                    //~ Continue with next arguments
                }
            } while (!empty($list));

            if (empty($list)) {
                break;
            }
        }
    }

    /**
     * @return bool
     */
    protected function hasPathChanged(): bool
    {
        $realCurrentPath = realpath($this->originalPath);

        return ($realCurrentPath !== $this->realOriginalPath);
    }
}
