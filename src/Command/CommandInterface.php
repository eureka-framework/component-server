<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * Command interface
 *
 * @author Romain Cottard
 */
interface CommandInterface
{
    /**
     * Add argument to the command.
     *
     * @param  Argument $argument
     * @return CommandInterface
     */
    public function addArgument(Argument $argument): CommandInterface;

    /**
     * Add arguments to the command.
     *
     * @param  Argument[] $arguments
     * @return $this
     */
    public function addArguments(array $arguments): CommandInterface;

    /**
     * Set argument to use as command type id.
     *
     * @param  Argument[] $arguments
     * @return CommandInterface
     */
    public function setArguments(array $arguments): CommandInterface;

    /**
     * Set output log for the command
     *
     * @param  string $logStandard
     * @param  string $logError
     * @param  bool $isLogAppend
     * @return CommandInterface
     */
    public function setLog(string $logStandard, string $logError = null, bool $isLogAppend = false): CommandInterface;

    /**
     * Get command pattern
     *
     * @param bool $withArguments
     * @param bool $withType
     * @return string
     */
    public function getPattern(bool $withArguments = true, bool $withType = false): string;

    /**
     * Execute command on server.
     *
     * @param  boolean $isAsync
     * @return array Command output result.
     */
    public function exec(bool $isAsync = false): array;
}
