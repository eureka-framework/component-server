<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../../vendor/autoload.php';

use Eureka\Component\Server\Process;
use Eureka\Component\Server\Command;
use Eureka\Component\Server\Server;

$poolConfigs = [
    'fast' => new Process\PoolConfig(0.6),
    'slow' => new Process\PoolConfig(0.4),
];

//~ Prepend worker name argument
$arguments = [];

//~ Create server
$server = new Server(new Command\PGrepCommand());

$multiprocessing = new Process\Multiprocessing();
$multiprocessing->setSafeMultiprocessing(true);
$multiprocessing->setCallback('buildArgumentsForWorker');

foreach ($poolConfigs as $poolIndex => $poolConfig) {
    $multiprocessing->addPool(createPool($server, $arguments, 10, $poolIndex, $poolConfig));
}

$multiprocessing->run(5);


/**
 * Configure Multi processing pools
 *
 * @param  Server $server
 * @param  \Eureka\Component\Server\Command\Argument[]
 * @param  int $maxProcess
 * @param  string|int $poolIndex
 * @param  \Eureka\Component\Server\Process\PoolConfig $poolConfig
 * @return \Eureka\Component\Server\Process\Pool
 */
function createPool(Server $server, array &$arguments, $maxProcess, $poolIndex, Process\PoolConfig $poolConfig)
{
    //~ Global command
    $command = new Command\PHPCommand(__DIR__ . '/worker.php');
    $command->addArguments($arguments);

    //~ Create pool & set callback context for this pool.
    $pool = new Process\Pool($poolIndex, $poolConfig->getRatio(), $poolConfig->isShared());

    //~ Attach context for the pool
    $pool->setCallbackContext(new Process\Callback\Context(['pool-index' => $poolIndex]));

    //~ Create process & attach them to the pool
    for ($index = 0, $max = ceil($maxProcess * $poolConfig->getRatio()); $index < $max; $index++) {
        $commandProcess = clone $command;

        $pool->attachProcess(new Process\Process($server, $commandProcess));
    }

    return $pool;
}

/**
 * Callback method use by multiprocessing handle to build get arguments for workers
 *
 * @param \Eureka\Component\Server\Process\Callback\Context $context
 * @return array
 */
function buildArgumentsForWorker(Process\Callback\Context $context)
{
    $parameters = [];

    for ($index = 0; $index < 10; $index++) {
        $parameters[] = [
            new Command\Argument('test-id', rand(1, 50)),
            new Command\Argument('test-worker'),
        ];
    }

    return $parameters;
}
