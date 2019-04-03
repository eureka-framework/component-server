<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../../vendor/autoload.php';

use Eureka\Component\Server\Multiprocessing;
use Eureka\Component\Server\Command;
use Eureka\Component\Server\Process\Finder\PGrepProcessFinder;

$poolConfigs = [
    'fast' => new Multiprocessing\PoolConfig(0.6),
    'slow' => new Multiprocessing\PoolConfig(0.4),
];

//~ Prepend worker name argument
$arguments = [];

$multiprocessing = new Multiprocessing\Multiprocessing();
$multiprocessing->setSafeMultiprocessing(true);
$multiprocessing->setCallback('buildArgumentsForWorker');

foreach ($poolConfigs as $poolIndex => $poolConfig) {
    $multiprocessing->addPool(createPool($arguments, 10, $poolIndex, $poolConfig));
}

$multiprocessing->run(5);


/**
 * Configure Multi processing pools
 *
 * @param  Command\Argument[]
 * @param  int $maxProcess
 * @param  string|int $poolIndex
 * @param  Multiprocessing\PoolConfig $poolConfig
 * @return Multiprocessing\Pool
 */
function createPool(array &$arguments, $maxProcess, $poolIndex, Multiprocessing\PoolConfig $poolConfig): Multiprocessing\Pool
{
    //~ Global command
    $command = new Command\PHPCommand(__DIR__ . '/worker.php');
    $command->addArguments($arguments);

    //~ Create pool & set callback context for this pool.
    $pool = new Multiprocessing\Pool($poolIndex, $poolConfig->getRatio(), $poolConfig->isShared());

    //~ Attach context for the pool
    $pool->setCallbackContext(new Multiprocessing\Callback\Context(['pool-index' => $poolIndex]));

    //~ Create process & attach them to the pool
    for ($index = 0, $max = ceil($maxProcess * $poolConfig->getRatio()); $index < $max; $index++) {
        $commandProcess = clone $command;

        $pool->attachWorker(new Multiprocessing\Worker($commandProcess, new PGrepProcessFinder(new Command\PGrepCommand())));
    }

    return $pool;
}

/**
 * Callback method use by multiprocessing handle to build get arguments for workers
 *
 * @param Multiprocessing\Callback\Context $context
 * @return array
 */
function buildArgumentsForWorker(Multiprocessing\Callback\Context $context)
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
