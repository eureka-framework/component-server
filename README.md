# component-server
Component server to handle Server command &amp; parallelism with multi process.

For this documentation, we can imagine we need to resize avatars from users.
They can upload an avatar through a website, and resizing it will be processed asynchronously by a backend daemon.

In the following example, we use `Eurekon`, but can it used in any project, even in procedural && function style.

## Daemon Script

### Main script method
```php
<?php

public function run(): void
{
    $nbProcessInParallel = 10; // Should be defined in argument script

    //~ Initialize pools config
    $poolConfigs = [
        'premium' => new Multiprocessing\PoolConfig(0.6), // = 60%
        'basic'   => new Multiprocessing\PoolConfig(0.4), // = 40%
    ];

    //~ Prepend worker name argument
    $this->addArgument(new Command\Argument('name', 'Worker/Resize/UserAvatar', true), true);

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
```

### Arguments lists
```php
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
```

### Create Pool of "process"
```php
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
    $command = new Command\ConsoleCommand($_SERVER['argv'][0]); // Use app console used to execute daemon
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
```

### Callback method to build list of worker arguments

The goal of this method is to return a list of arguments for each worker that could be
launched by the daemons.
For example, if you have to process 100 user uploaded avatars, and you want process it
(one avatar by worker, 10 in parallels), you can do something like that:

```php
/**
 * Callback method used by multiprocessing to build list of arguments for workers
 *
 * @param Multiprocessing\Callback\Context $context
 * @return Command\Argument[][]
 */
function buildArgumentsForWorker(Multiprocessing\Callback\Context $context): array
{
    $data = $context->getData();
    $userAvatarRepository = $data['userAvatarRepository'];

    //~ Retrieve N (= number of process max) avatar to process for "type" of user (="premium" or "basic")
    $usersAvatarList = $userAvatarRepository->findAllToProcess($data['type'], $data['maxProcess']);

    //~ Build workers arguments for up to N users avatar
    $parameters = [];

    foreach ($usersAvatarList as $userAvatar) {
        $parameters[] = [
            new Command\Argument('user-avatar-id', $userAvatar->getId()),
        ];
    }

    return $parameters;
}
```

## Worker script

### Main method

```php
/**
 * @return void
 */
public function run(): void
{
    $userAvatarId = (int) Eurekon\Argument\Argument::getInstance()->get('user-avatar-id');

    //~ Retrieve entity
    $userAvatar = $userAvatarRepository->findById($userAvatarId);

    //~ Do stuff
    [...]
}
```


## Tuning multiprocessing

### Pool configuration

#### Only one pool
If you don't need to reserve percent of process for dedicated tasks, you can define only one pool:

```php
//~ Initialize pools config
$poolConfigs = [
    'just.one.pool' => new Multiprocessing\PoolConfig(1.0), // = 100%
];
```


#### Shared pool
When you have several pools, it is possible that one pool is full, and other(s) are not fully occupied.
You can define a shared pool to have a better efficiency of your process.

Consider the following config example:
```php
//~ Daemon is configured to have 10 process in parallel
//~ Initialize pools config
$poolConfigs = [
    'pool.for.short.tasks' => new Multiprocessing\PoolConfig(0.7), // = 70% = 7 process reserved
    'pool.for.long.tasks'  => new Multiprocessing\PoolConfig(0.3), // = 30% = 3 process reserved
];
```
Long task: Task can take several minutes to be processed
Short task: Task should not be take more than few seconds to be processed

In queue, we have 100 long tasks and 10 shorts tasks.
Quickly, all short tasks will be processed, and pool of process for short task will not be used anymore (until the system have new short tasks to process).

So we can add a shared pool for a better efficiency.

```php
//~ Initialize pools config
$poolConfigs = [
    'pool.for.short.tasks' => new Multiprocessing\PoolConfig(0.3),       // = 30% = 3 process reserved
    'pool.for.long.tasks'  => new Multiprocessing\PoolConfig(0.3),       // = 30% = 3 process reserved
    'pool.shared'          => new Multiprocessing\PoolConfig(0.4, true), // = 40% = 4 process shared
];
```

By default, shared pool is used to process tasks from others pools which are in excess.

In callback method `buildArgumentsForWorker`, we fetch number of element equals to the number of process, even if
have a lower ratio for given pool. In this way, the elements in excess can be processed by shared pool.

If callback give 10 elements for short tasks and 10 elements for long tasks, repartition will be:
 * 3 short tasks processed by pool `pool.for.short.tasks` 
 * 3 long task processed by pool `pool.for.long.tasks`
 * 4 short tasks process by pool `pool.shared` (from 7 short tasks in excess)

If callback give 2 elements for short tasks and 10 elements for long tasks, repartition will be:
 * 2 short tasks processed by pool `pool.for.short.tasks` 
 * 3 long task processed by pool `pool.for.long.tasks`
 * 4 long tasks process by pool `pool.shared` (from 7 long tasks in excess)

If callback give 0 elements for short tasks and 10 elements for long tasks, repartition will be:
 * 0 short tasks processed by pool `pool.for.short.tasks` 
 * 3 long task processed by pool `pool.for.long.tasks`
 * 4 long tasks process by pool `pool.shared` (from 7 long tasks in excess)

If callback give 6 elements for short tasks and 10 elements for long tasks, repartition will be:
 * 3 short tasks processed by pool `pool.for.short.tasks` 
 * 3 long task processed by pool `pool.for.long.tasks`
 * 2 short tasks and 2 long tasks process by pool `pool.shared` (from 2 shorts tasks in excess and 7 long tasks in excess)

In summary:
 * Non shared pool have process "reserved" by the Multiprocessing instance.
 * Shared pool process in priority the elements in excess from non shared pool ordering by pool definition.

#### "Disable" parallelism
To create daemon with only one worker (no parallelism), you just need to set the following config:
```php
//~ Initialize pools config
$poolConfigs = [
    'main' => new Multiprocessing\PoolConfig(1.0),
];
```

And you must just attach only one command process to this pool.

## "Safe Multiprocessing" versus "Not Safe Multiprocessing"
### Safe mode enabled
By default, the Multiprocessing instance has flag "safe" enabled.

When this flag is enabled, the Multiprocessing instance ensure that 2 worker cannot be ran in parallel with the sames arguments (except pool index).

By this way, we cannot process the same element in parallel and that can prevent same exact query executed at the same moment, moving same file, etc...
The check is based on the whole list of arguments passed to the worker, except type argument (`pool-index` in current example).

To enable safe mode on daemon side, set:
```php
$multiprocessing->setSafeMultiprocessing(true);
```

### Safe mode disabled
If the flag is disable, the system does not check arguments, and only free process will check to run new workers.

> /!\ The responsibility of non concurrency is delegated to workers.

This mode is useful for queue consumer for example, because non concurrency is handled by queue system.

To disable safe mode on daemon side, set:
```php
$multiprocessing->setSafeMultiprocessing(false);
```

## Auto checking source code path update

In some environment, updated code in production can be done by deploy a new version tag and symbolic link switch.
In this case, the daemon should be stopped and restarted automatically without any manual intervention.

The auto checking can be done through the following method:
```php
$multiprocessing->setDetectPathChanges(true);
```
To work as intended, the daemon must be executed with (or in) the symlink path, not the real path.

```shell
user@:~$ /path/to/linked/app/bin/console --name=Daemon/ResizeUserAvatar
```
or
```shell
user@:/path/to/linked/app$ bin/console --name=Daemon/ResizeUserAvatar
```

with symbolic link like that:
```shell
/path/to/linked/app -> /path/to/deployed/app-1.2.3
```

## Run php daemon as an linux daemon

This component provide some bash helper to wrap php daemon as standalone linux daemon in your application.
To do that, you need to create an executable bash script that use provided helpers.

Example (file name: `resize_users_avatar`)
```bash
#!/bin/bash

# file: resize_users_avatar

#~ Project directory
PROJECT_DIR="$( cd "$(dirname "$0")/../.." && pwd )"

#~ Daemon config data
DAEMON_NAME="resize_users_avatar"
DAEMON_SCRIPT="${PROJECT_DIR}/bin/console"
DAEMON_SCRIPT_ARGS="--name=Daemon/ResizeUserAvatar --nb-process=10"

#~ Uncomment and update path if you would another directory to store daemon's pid files
#DAEMON_PID_PATH="/data/tmp/daemon/pid"

#~ Include shared daemon system
. "${PROJECT_DIR}/vendor/eureka/component-server/bash/daemon-functions.sh"
. "${PROJECT_DIR}/vendor/eureka/component-server/bash/daemon-simple.sh"
```

Commands are:
 * `start`: Start daemon
 * `stop`: Stop daemon
 * `restart`: Stop daemon and start it again
 * `status`: Status about daemon

To start the previous example daemon, we can do:
```shell
user@:/path/to/linked/app$ bin/daemon/resize_users_avatar start
```

## Auto checking & restart daemon

Sometime, for any reason, the daemon script can be stopped (memory leak, code error, stopped by someone...).

But a daemon should be running every time.

So component provide an helper to check your daemons.

Example of daemon_check.sh cron script

```bash
#!/bin/bash

###############################################################################

#~ Main config
PROJECT_DIR="$( cd "$(dirname "$0")/../.." && pwd )"
DEBUG_VALUE=3

#~ Binary(ies)
#SENDMAIL_BIN="/usr/sbin/sendmail"

#~ Mail config define a mail command to receive the reporting
#MAIL_ADDRESS_FROM="noreply@example.com"
#MAIL_ADDRESS_DISPLAY="Daemon Restart"
#MAIL_SUBJECT="[Monitoring] Daemons - RESTARTING Daemon - `date '+%Y/%m/%d %H:%M:%S'`"
MAIL_ADDRESS_TO="me@example.com"

. "${PROJECT_DIR}/vendor/eureka/component-server/bash/daemon-check.sh"

###############################################################################

DAEMONS_TO_CHECK=($(find ${PROJECT_DIR}/bin/daemon -type f))

CHECK_DAEMON_STATUS=$(check_daemons "${DAEMONS_TO_CHECK[@]}" "${DEBUG_VALUE}")

# Send mail only if we detected some problem detected
if [[ ! -z "${CHECK_DAEMON_STATUS}" ]]
then
    send_mail "${CHECK_DAEMON_STATUS}"
fi

exit 0
```

This cron will check daemon status, and will start any daemon will be down.
An email will send in this case with list of daemon started by this script.
