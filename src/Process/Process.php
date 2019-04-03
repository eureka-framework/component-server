<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process;

use Eureka\Component\Server\Command;

/**
 * Process class. It is container for a Command instance.
 *
 * @author Romain Cottard
 */
class Process
{
    /** @var Command\CommandInterface $command ConsoleCommand instance. */
    protected $command = null;

    /** @var int|null $pid */
    protected $pid = null;

    /**
     * Process constructor.
     *
     * @param Command\CommandInterface $command
     * @param int|null $pid
     */
    public function __construct(Command\CommandInterface $command, ?int $pid = null)
    {
        $this->command = clone $command;
        $this->pid     = $pid;
    }

    /**
     * Get command linked to the process.
     *
     * @return Command\CommandInterface
     */
    public function getCommand(): Command\CommandInterface
    {
        return $this->command;
    }

    /**
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return ($this->pid !== null);
    }

    /*
     * Check if the process is idle or not.
     *
     * @return bool
     *
    public function isIdle()
    {
        if (!($this->command instanceof Command\ConsoleCommandInterface)) {
            return true;
        }

        return !$this->server->hasProcess($this, false, true);
    }

    /**
     * Check if the process is already running or not.
     *
     * @return bool
     *
    public function isAlreadyRunning()
    {
        if (!($this->command instanceof ConsoleCommandInterface)) {
            return true;
        }

        return $this->server->hasProcess($this, true, false);
    }

    /**
     * Execute command in this process.
     *
     * @param  boolean $isAsync If process must be asynchronous
     * @param  bool $safeMultiprocessing If must check if already running
     * @throws Exception\ProcessAlreadyRunningException
     * @return $this
     *
    public function run($isAsync = false, $safeMultiprocessing = true)
    {
        //~ Check the current command is not already running before execution.
        if ($safeMultiprocessing && $this->isAlreadyRunning()) {
            throw new Exception\ProcessAlreadyRunningException('Process already running with the same arguments!');
        }

        $this->command->exec($isAsync);

        return $this;
    }*/
}
