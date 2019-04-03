<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server;

use Eureka\Component\Server\Process\Finder\ProcessFinderInterface;
use Eureka\Component\Server\Process\Process;

/**
 * Server class.
 *
 * @author Romain Cottard
 */
class Server
{
    /** @var  ProcessFinderInterface $processFinder */
    protected $processFinder;

    /**
     * Server constructor.
     *
     * @param ProcessFinderInterface $processFinder Command for process grep
     */
    public function __construct(ProcessFinderInterface $processFinder)
    {
        $this->processFinder = $processFinder;
    }


    /**
     * @param Process $process
     * @param bool $withArguments
     * @param bool $withType
     * @return bool
     */
    public function hasProcess(Process $process, $withArguments = true, $withType = false): bool
    {
        return ($this->countProcesses($process, $withArguments, $withType) > 0);
    }

    /**
     * @param Process $process
     * @param bool $withArguments
     * @param bool $withType
     * @return int
     */
    public function countProcesses(Process $process, $withArguments = true, $withType = false): int
    {
        return count($this->processFinder->find($process->getCommand()->getPattern($withArguments, $withType)));
    }
}
