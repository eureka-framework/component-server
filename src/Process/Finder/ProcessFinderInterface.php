<?php

/*
 * Copyright (c) Romain
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process\Finder;

use Eureka\Component\Server\Process\ProcessCollection;

/**
 * Interface ProcessFinderInterface
 *
 * @author Romain Cottard
 */
interface ProcessFinderInterface
{
    /**
     * @param string $pattern
     * @return ProcessCollection
     */
    public function find(string $pattern): ProcessCollection;
}
