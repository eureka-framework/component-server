<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * PGrep command
 * Possible option to grep a process:
 * -a: get all data, including the full process name / arguments
 * -f "PATTERN": find process matches the specified pattern.
 *
 * @author Romain Cottard
 */
final class PGrepCommand extends AbstractCommand
{
    /** @var string COMMAND_NAME */
    public const COMMAND_NAME = 'pgrep';

    /**
     * PGrepCommand constructor.
     */
    public function __construct()
    {
        $this->name = self::COMMAND_NAME;
    }
}
