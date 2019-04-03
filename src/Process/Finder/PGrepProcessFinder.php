<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process\Finder;

use Eureka\Component\Server\Command;
use Eureka\Component\Server\Process\Process;
use Eureka\Component\Server\Process\ProcessCollection;

/**
 * Class PGrepProcessFinder
 *
 * @author Romain Cottard
 */
class PGrepProcessFinder implements ProcessFinderInterface
{
    /** @var Command\CommandInterface $command */
    private $command;

    /**
     * PGrepProcessFinder constructor.
     *
     * @param Command\CommandInterface $command
     */
    public function __construct(Command\CommandInterface $command)
    {
        $this->command = $command;
    }

    /**
     * @param string $pattern
     * @return ProcessCollection
     */
    public function find(string $pattern): ProcessCollection
    {
        $command = clone $this->command;
        $command->addArguments([
            new Command\Argument('a', null, false),
            new Command\Argument('f', $pattern, false),
        ]);

        $output = $command->exec();

        $commandFactory = new Command\CommandFactory();
        $collection     = new ProcessCollection();

        foreach ($output as $line) {
            $tmp = explode(' ', $line);

            //~ Skip non process
            if (!isset($tmp[0]) || !is_numeric($tmp[0])) {
                continue;
            }

            $commandPid    = (int) array_shift($tmp);
            $commandString = implode(' ', $tmp);

            $collection->add(new Process($commandFactory->createConsoleCommandFromString($commandString), $commandPid));
        }

        return $collection;
    }
}
