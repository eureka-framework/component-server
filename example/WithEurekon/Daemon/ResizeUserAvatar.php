<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Example\Script\Daemon;

use Eureka\Component\Server\Command;
use Eureka\Component\Server\Multiprocessing;
use Eureka\Component\Server\Process\Finder\PGrepProcessFinder;
use Eureka\Eurekon;

/**
 * Daemon script example
 *
 * @author Romain Cottard
 */
class ResizeUserAvatar extends Eurekon\AbstractScript
{
    /** @var Command\Argument[] $arguments List of arguments. */
    protected $arguments = [];

    /** @var array $callbackContextData Context data for Callback Context instance */
    protected $callbackContextData = [];

    /** @var ResizeUserAvatar $userAvatarRepository */
    private $userAvatarRepository;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->setDescription('Daemon script example');
        $this->setExecutable();

        $this->userAvatarRepository = $this;
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
        $nbProcessInParallel = 10; // Should be defined in argument script

        //~ Initialize pools config
        $poolConfigs = [
            'premium' => new Multiprocessing\PoolConfig(0.6), // = 60%
            'basic'   => new Multiprocessing\PoolConfig(0.4), // = 40%
        ];

        //~ Prepend worker name argument
        $this->addArgument(new Command\Argument('name', 'Worker/ResizeUserAvatar', true), true);

        //~ Instantiate multiprocessing class
        $multiprocessing = new Multiprocessing\Multiprocessing();
        $multiprocessing->setSafeMultiprocessing(true); // enable safe multiprocessing
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
    protected function createPool(int $maxProcess, $poolIndex, Multiprocessing\PoolConfig $poolConfig): Multiprocessing\Pool
    {
        //~ Global command
        $command = new Command\ConsoleCommand($_SERVER['argv'][0]); // Set console command script
        $command->setArguments($this->arguments);

        //~ Create pool
        $pool = new Multiprocessing\Pool($poolIndex, $poolConfig->getRatio(), $poolConfig->isShared());

        //~ Attach callback context to the pool
        $pool->setCallbackContext(new Multiprocessing\Callback\Context([
            'type'                 => $poolIndex, // (here: "premium" or "basic")
            'maxProcess'           => $maxProcess,
            'userAvatarRepository' => $this->userAvatarRepository,
        ]));

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
     * Add argument to list for worker
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

    /**
     * Callback method used by multiprocessing to build list of arguments for workers
     *
     * @param Multiprocessing\Callback\Context $context
     * @return Command\Argument[][]
     */
    public function buildArgumentsForWorker(Multiprocessing\Callback\Context $context): array
    {
        $data = $context->getData();
        $userAvatarRepository = $data['userAvatarRepository'];

        //~ Retrieve N (= number of process max) avatar to process for "type" of user (="premium" or "basic")
        $usersAvatarList = $userAvatarRepository->findAllToProcess($data['type'], $data['maxProcess']);

        //~ Build workers arguments for up to N users avatar
        $parameters = [];

        foreach ($usersAvatarList as $userAvatar) {
            $parameters[] = [
                new Command\Argument('user-avatar-id', $userAvatar->id),
            ];
        }

        return $parameters;
    }

    /**
     * @param string $type
     * @param int $limit
     * @return array
     */
    public function findAllToProcess(string $type, int $limit): array
    {
        $entities = [];

        for ($i = 0; $i < $limit; $i++) {
            $entities[] = (object) [
                'id' => $type . '-' . rand(1, 10),
            ];
        }

        return $entities;
    }
}
