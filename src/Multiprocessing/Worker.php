<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Multiprocessing;

use Eureka\Component\Server\Command;
use Eureka\Component\Server\Process\Finder\ProcessFinderInterface;

/**
 * Process class. It is container for a Command instance.
 *
 * @author Romain Cottard
 */
class Worker
{
    /** @var Command\ConsoleCommandInterface $command ConsoleCommand instance. */
    protected $command;

    /** @var Command\ConsoleCommandInterface $commandBase Command instance. */
    protected $commandBase = null;

    /** @var ProcessFinderInterface $processFinder */
    protected $processFinder;

    /**
     * Worker constructor.
     *
     * @param Command\ConsoleCommandInterface $command
     * @param ProcessFinderInterface $processFinder
     */
    public function __construct(Command\ConsoleCommandInterface $command, ProcessFinderInterface $processFinder)
    {
        $this->command       = clone $command;
        $this->commandBase   = $command;
        $this->processFinder = $processFinder;
    }

    /**
     * Get command linked to the process.
     *
     * @return Command\ConsoleCommandInterface
     */
    public function getCommand(): Command\ConsoleCommandInterface
    {
        return $this->command;
    }

    /**
     * Check if the process is idle or not.
     *
     * @param bool $checkArguments
     * @param bool $withType
     * @return bool
     */
    public function isIdle(bool $checkArguments = false, bool $withType = true)
    {
        $collection = $this->processFinder->find($this->command->getPattern($checkArguments, $withType));

        return (count($collection) === 0);
    }

    /**
     * Check if the process is already running or not.
     *
     * @param bool $checkArguments
     * @param bool $withType
     * @return bool
     */
    public function isAlreadyRunning(bool $checkArguments = false, bool $withType = true)
    {
        $collection = $this->processFinder->find($this->command->getPattern($checkArguments, $withType));

        return (count($collection) > 0);
    }

    /**
     * Reset command attach to this process.
     *
     * @return Command\ConsoleCommandInterface
     */
    public function reset()
    {
        $this->command = clone $this->commandBase;

        return $this->command;
    }

    /**
     * Execute command in this process.
     *
     * @param  boolean $isAsync If process must be asynchronous
     * @param  bool $safeMultiprocessing If must check if already running
     * @throws Exception\WorkerAlreadyRunningException
     * @return $this
     */
    public function run($isAsync = false, $safeMultiprocessing = true)
    {
        $checkArguments = $safeMultiprocessing;
        $withType       = !$safeMultiprocessing;

        //~ Check the current command is not already running before execution.
        if ($safeMultiprocessing && $this->isAlreadyRunning($checkArguments, $withType)) {
            throw new Exception\WorkerAlreadyRunningException('Process already running with the same arguments!');
        }

        $this->command->exec($isAsync);

        return $this;
    }
}
