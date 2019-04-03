<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * Generic Command
 *
 * @author Romain Cottard
 */
final class GenericCommand extends AbstractCommand
{
    /** @var bool $safeMode When safe mode is active, disable execution of the command for security reasons. */
    private $safeMode = true;

    /**
     * GenericCommand constructor.
     *
     * @param string $name
     * @param bool $safeMode
     */
    public function __construct(string $name, bool $safeMode = true)
    {
        $this->name     = $name;
        $this->safeMode = $safeMode;
    }

    /**
     * Override exec() method to disable it for security.
     *
     * @param bool $isAsync
     * @param bool $useSystem
     * @return array
     */
    public function exec(bool $isAsync = false, bool $useSystem = false): array
    {
        if ($this->safeMode) {
            throw new \LogicException('Cannot exec generic command (for security reasons).');
        }

        return parent::exec($isAsync, $useSystem);
    }
}
