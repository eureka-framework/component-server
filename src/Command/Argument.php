<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * Argument class. Used to add command argument.
 *
 * @author Romain Cottard
 */
final class Argument
{
    /** @var string $name Argument name */
    protected $name = '';

    /** @var mixed $value Argument value if have value */
    protected $value = null;

    /** @var bool $isFullName If is long name name (--provider-id instead of -i) */
    protected $isFullName = true;

    /**
     * Argument constructor.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $isFullName
     */
    public function __construct(string $name, $value = null, bool $isFullName = true)
    {
        $this->name       = $name;
        $this->value      = $value;
        $this->isFullName = $isFullName;
    }

    /**
     * Get argument as a string for console
     *
     * @return string
     */
    public function getAsString(): string
    {
        $argument = '';

        if (!empty($this->name)) {
            $argument = $this->isFullName ? '--' : '-';
        }

        $argument .= $this->name;

        if ($this->value !== null) {
            $argument .= ($this->isFullName ? '=' : ' ') . escapeshellarg((string) $this->value);
        }

        return $argument;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get argument as a string for console
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getAsString();
    }
}
