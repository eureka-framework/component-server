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
     * @return $this
     */
    public function addArgument(Argument $argument);

    /**
     * Set argument to use as command type id.
     *
     * @param  Argument[] $arguments
     * @return $this
     */
    public function setArguments(array $arguments);

    /**
     * Execute command on server.
     *
     * @param  boolean $isAsync
     * @return array Command output result.
     */
    public function exec($isAsync = false);
}
