<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Script\Daemon;

use Eureka\Eurekon;
use Eureka\Component\Server\Server;
use Eureka\Component\Server\Command;
use Eureka\Component\Server\Process;

/**
 * Daemon script example
 *
 * @author Romain Cottard
 */
class HelloWorld extends Eurekon\AbstractScript
{
    /** @var Process\Multiprocessing $multiprocessing Multiprocessing instance */
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
     * {@inheritdoc}
     */
    public function help()
    {
        $help = new Eurekon\Help(basename(self::class));
        $help->display();

        return;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function run()
    {
        $this->initMultiprocessing();

        $this->multiprocessing->run(5);
    }

    /**
     * Initialize multiprocessing.
     *
     * @return void
     */
    protected function initMultiprocessing()
    {
        $poolConfigs = [
            'fast' => new Process\PoolConfig(0.6),
            'slow' => new Process\PoolConfig(0.4),
        ];

        //~ Prepend worker name argument
        $this->addArgument(new Command\Argument('name', 'Application/Script/Worker/HelloWorld', true));

        //~ Create server
        $server = new Server(new Command\PGrepCommand());

        $this->multiprocessing = new Process\Multiprocessing();
        $this->multiprocessing->setSafeMultiprocessing(true);
        $this->multiprocessing->setCallback([$this, 'buildArgumentsForWorker']);

        foreach ($poolConfigs as $poolIndex => $poolConfig) {
            $this->multiprocessing->addPool($this->createPool($server, 10, $poolIndex, $poolConfig));
        }
    }

    /**
     * Configure Multi processing pools
     *
     * @param  Server $server
     * @param  int $maxProcess
     * @param  string|int $poolIndex
     * @param  \Eureka\Component\Server\Process\PoolConfig $poolConfig
     * @return \Eureka\Component\Server\Process\Pool
     */
    protected function createPool(Server $server, $maxProcess, $poolIndex, Process\PoolConfig $poolConfig)
    {
        //~ Global command
        $command = new Command\ConsoleCommand(__DIR__ . '/../../');
        $command->setArguments($this->arguments);

        $command->exec();
        //~ Create pool & set callback context for this pool.
        $pool = new Process\Pool($poolIndex, $poolConfig->getRatio(), $poolConfig->isShared());

        //~ Attach context for the pool
        $pool->setCallbackContext(new Process\Callback\Context(['pool-index' => $poolIndex]));

        //~ Create process & attach them to the pool
        for ($index = 0, $max = ceil($maxProcess * $poolConfig->getRatio()); $index < $max; $index++) {
            $commandProcess = clone $command;
            $commandProcess->setType(new Command\Argument('pool-index', $poolIndex . '.' . $index));

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
    public function buildArgumentsForWorker(Process\Callback\Context $context)
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
