<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * Interface script class.
 *
 * @author Romain Cottard
 */
interface ConsoleCommandInterface extends CommandInterface
{
    /**
     * Add argument to specify the type of command.
     * Only used with multi-processing scripts.
     *
     * @param  Argument $argument
     * @return $this
     */
    public function setType(Argument $argument);

    /**
     * Get script pattern
     *
     * @param  bool $withArguments
     * @param  bool $withType
     * @return string
     */
    public function getPattern($withArguments = true, $withType = false);
}
