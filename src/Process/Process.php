<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process;

use Eureka\Component\Server\Command\ConsoleCommandInterface;
use Eureka\Component\Server\Server;

/**
 * Process class. It is container for a Command instance.
 *
 * @author Romain Cottard
 */
class Process
{
    /** @var Server $server Server instance */
    protected $server = null;

    /** @var ConsoleCommandInterface $commandBase Command instance. */
    protected $commandBase = null;

    /** @var ConsoleCommandInterface $command ConsoleCommand instance. */
    protected $command = null;

    /**
     * Process constructor.
     *
     * @param Server $server Server
     * @param ConsoleCommandInterface $command Base command to execute
     */
    public function __construct(Server $server, ConsoleCommandInterface $command)
    {
        $this->server      = $server;
        $this->commandBase = $command;
        $this->command     = clone $command;
    }

    /**
     * Get command linked to the process.
     *
     * @return ConsoleCommandInterface
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Get pattern from command
     *
     * @param  bool $withArguments
     * @param  bool $withType
     * @return string
     */
    public function getPattern($withArguments, $withType = true)
    {
        return $this->command->getPattern($withArguments, $withType);
    }

    /**
     * Check if the process is idle or not.
     *
     * @return bool
     */
    public function isIdle()
    {
        if (!($this->command instanceof ConsoleCommandInterface)) {
            return true;
        }

        return !$this->server->hasProcess($this, false, true);
    }

    /**
     * Check if the process is already running or not.
     *
     * @return bool
     */
    public function isAlreadyRunning()
    {
        if (!($this->command instanceof ConsoleCommandInterface)) {
            return true;
        }

        return $this->server->hasProcess($this, true, false);
    }

    /**
     * Reset command attach to this process.
     *
     * @return ConsoleCommandInterface
     */
    public function reset()
    {
        $this->command = clone $this->commandBase;

        return $this->getCommand();
    }

    /**
     * Execute command in this process.
     *
     * @param  boolean $isAsync If process must be asynchronous
     * @param  bool $safeMultiprocessing If must check if already running
     * @throws Exception\ProcessAlreadyRunningException
     * @return $this
     */
    public function run($isAsync = false, $safeMultiprocessing = true)
    {
        //~ Check the current command is not already running before execution.
        if ($safeMultiprocessing && $this->isAlreadyRunning()) {
            throw new Exception\ProcessAlreadyRunningException('Process already running with the same arguments!');
        }

        $this->command->exec($isAsync);

        return $this;
    }
}
