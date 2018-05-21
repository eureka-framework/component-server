<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Application\Script\Worker;

use Eureka\Eurekon;

/**
 * Worker Script Example
 *
 * @author Romain Cottard
 */
class HelloWorld extends Eurekon\AbstractScript
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
        sleep(10);
        Eurekon\IO\Out::std('Hello World (with ID: ' . Eurekon\Argument\Argument::getInstance()->get('test-id') . ')');
    }
}
