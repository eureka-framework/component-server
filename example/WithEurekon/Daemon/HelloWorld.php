<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Script\Daemon;

use Eureka\Component\Server\Command;
use Eureka\Component\Server\Multiprocessing;
use Eureka\Component\Server\Process\Finder\PGrepProcessFinder;
use Eureka\Eurekon;

/**
 * Daemon script example
 *
 * @author Romain Cottard
 */
class HelloWorld extends Eurekon\AbstractScript
{
    /** @var Command\Argument[] $arguments List of arguments. */
    protected $arguments = [];

    /** @var array $callbackContextData Context data for Callback Context instance */
    protected $callbackContextData = [];

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->setDescription('Daemon script example');
        $this->setExecutable();
    }

    /**
     * @return void
     */
    public function help(): void
    {
        $help = new Eurekon\Help(basename(self::class));
        $help->display();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function run(): void
    {
        $nbProcessInParallel = 10;

        //~ Initialize pools config
        $poolConfigs = [
            'fast' => new Multiprocessing\PoolConfig(0.6), // = 60%
            'slow' => new Multiprocessing\PoolConfig(0.4), // = 40%
        ];

        //~ Prepend worker name argument
        $this->addArgument(new Command\Argument('name', 'Application/Script/Worker/HelloWorld', true));

        //~ Instantiate multiprocessing class
        $multiprocessing = new Multiprocessing\Multiprocessing();
        $multiprocessing->setSafeMultiprocessing(true);
        $multiprocessing->setDetectPathChanges(true);
        $multiprocessing->setCallback([$this, 'buildArgumentsForWorker']);

        //~ Link create & add pool to multiprocessing instance
        foreach ($poolConfigs as $poolIndex => $poolConfig) {
            $multiprocessing->addPool($this->createPool($nbProcessInParallel, $poolIndex, $poolConfig));
        }

        $multiprocessing->run(5);
    }

    /**
     * Configure Multi processing pools
     *
     * @param  int $maxProcess
     * @param  string|int $poolIndex
     * @param  Multiprocessing\PoolConfig $poolConfig
     * @return Multiprocessing\Pool
     */
    protected function createPool($maxProcess, $poolIndex, Multiprocessing\PoolConfig $poolConfig)
    {
        //~ Global command
        $command = new Command\ConsoleCommand(__DIR__ . '/../../');
        $command->setArguments($this->arguments);

        //~ Create pool & set callback context for this pool.
        $pool = new Multiprocessing\Pool($poolIndex, $poolConfig->getRatio(), $poolConfig->isShared());

        //~ Attach context for the pool
        $pool->setCallbackContext(new Multiprocessing\Callback\Context(['pool-index' => $poolIndex]));

        //~ Create process & attach them to the pool
        for ($index = 0, $max = ceil($maxProcess * $poolConfig->getRatio()); $index < $max; $index++) {
            $commandProcess = clone $command;
            $commandProcess->setType(new Command\Argument('pool-index', $poolIndex . '.' . $index));

            $pool->attachWorker(
                new Multiprocessing\Worker($commandProcess, new PGrepProcessFinder(new Command\PGrepCommand()))
            );
        }

        return $pool;
    }

    /**
     * Callback method use by multiprocessing handle to build get arguments for workers
     *
     * @param Multiprocessing\Callback\Context $context
     * @return array
     */
    public function buildArgumentsForWorker(Multiprocessing\Callback\Context $context)
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

    /**
     * Add argument to list for workers
     *
     * @param  Command\Argument $argument
     * @param  bool $prepend
     * @return $this
     */
    protected function addArgument(Command\Argument $argument, bool $prepend = false): self
    {
        if ($prepend) {
            array_unshift($this->arguments, $argument);
        } else {
            $this->arguments[] = $argument;
        }

        return $this;
    }
}
