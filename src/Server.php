<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server;

use Eureka\Component\Server\Command\Argument;
use Eureka\Component\Server\Command\CommandInterface;
use Eureka\Component\Server\Process\Process;

/**
 * Server class.
 *
 * @author Romain Cottard
 */
class Server
{
    /** @var  CommandInterface $pgrep Command for process grep */
    protected $pgrep = null;

    /**
     * Server constructor.
     *
     * @param CommandInterface $pgrep Command for process grep
     */
    public function __construct(CommandInterface $pgrep)
    {
        $this->pgrep = $pgrep;
    }

    /**
     * Check if there more than specified number of process are running.
     *
     * @param  Process $process
     * @param  bool $withArguments
     * @param  bool $withType
     * @return int
     */
    public function countProcess(Process $process, $withArguments = true, $withType = false)
    {
        return count($this->getProcess($process, $withArguments, $withType));
    }

    /**
     * Get list of processes / pid that matches the process pattern.
     *
     * @param  Process $process
     * @param  bool $withArguments
     * @param  bool $withType
     * @return bool
     */
    public function hasProcess(Process $process, $withArguments = true, $withType = false)
    {
        return (bool) $this->countProcess($process, $withArguments, $withType);
    }

    /**
     * Get list of processes / pid that matches the process pattern.
     *
     * @param  Process $process
     * @param  bool $withArguments
     * @param  bool $withType
     * @return \stdClass[] List of process
     */
    public function getProcess(Process $process, $withArguments = true, $withType = false)
    {
        $pgrep = clone $this->pgrep;
        $pgrep->addArgument(new Argument('a', null, false));
        $pgrep->addArgument(new Argument('f', $process->getPattern($withArguments, $withType), false));

        $pids = $pgrep->exec();
        $list = [];

        foreach ($pids as $pid) {
            $tmp = explode(' ', $pid);
            if (!isset($tmp[0]) || !is_numeric($tmp[0])) {
                continue;
            }

            $data       = new \stdClass();
            $data->id   = array_shift($tmp);
            $data->name = implode(' ', $tmp);
            $list[]     = $data;
        }

        return $list;
    }
}
