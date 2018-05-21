<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process\Callback;

/**
 * Process Pool class.
 *
 * @author Romain Cottard
 */
class Context
{
    /** @var mixed $data Context data for process callback method. */
    protected $data = null;

    /**
     * CallbackContext constructor.
     *
     * @param mixed $data Context data
     */
    public function __construct($data)
    {
        $this->setData($data);
    }

    /**
     * Get offset value.
     *
     * @return int
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set context data.
     *
     * @param  mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
