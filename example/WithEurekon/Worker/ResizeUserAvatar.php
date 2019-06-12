<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Example\Script\Worker;

use Eureka\Eurekon;

/**
 * Worker Script Example
 *
 * @author Romain Cottard
 */
class ResizeUserAvatar extends Eurekon\AbstractScript
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->setDescription('Hello World script');
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
     */
    public function run(): void
    {
        $userAvatarId = Eurekon\Argument\Argument::getInstance()->get('user-avatar-id');

        Eurekon\IO\Out::std('Process user avatar: ' . $userAvatarId);
        sleep(10);
        Eurekon\IO\Out::std('Done');
    }
}
