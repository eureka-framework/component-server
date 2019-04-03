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
    /** @var Multiprocessing\Multiprocessing $multiprocessing Multiprocessing instance */
    protected $multiprocessing = null;

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
        $this->initMultiprocessing();

        $this->multiprocessing->run(5);
    }

    /**
     * Initialize multiprocessing.
     *
     * @return void
     */
    protected function initMultiprocessing(): void
    {
        $poolConfigs = [
            'fast' => new Multiprocessing\PoolConfig(0.6),
            'slow' => new Multiprocessing\PoolConfig(0.4),
        ];

        //~ Prepend worker name argument
        $this->addArgument(new Command\Argument('name', 'Application/Script/Worker/HelloWorld', true));

        $this->multiprocessing = new Multiprocessing\Multiprocessing();
        $this->multiprocessing->setSafeMultiprocessing(true);
        $this->multiprocessing->setCallback([$this, 'buildArgumentsForWorker']);

        foreach ($poolConfigs as $poolIndex => $poolConfig) {
            $this->multiprocessing->addPool($this->createPool(10, $poolIndex, $poolConfig));
        }
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

        $command->exec();
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
    protected function addArgument(Command\Argument $argument, $prepend = false)
    {
        if ($prepend) {
            array_unshift($this->arguments, $argument);
        } else {
            $this->arguments[] = $argument;
        }

        return $this;
    }
}
