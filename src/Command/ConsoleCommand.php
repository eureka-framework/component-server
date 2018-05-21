<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * Main Console command class.
 * Use to execute script with Eureka bin/console executable script.
 *
 * @author Romain Cottard
 */
class ConsoleCommand extends AbstractCommand implements ConsoleCommandInterface
{
    /**
     * ConsoleCommand constructor.
     *
     * @param string $rootApp
     */
    public function __construct($rootApp)
    {
        $this->name  = realpath($rootApp . '/bin') . '/console';
    }

    /**
     * Get command pattern.
     *
     * @param  bool $withArguments
     * @param  bool $withType
     * @return string
     */
    public function getPattern($withArguments = true, $withType = true)
    {
        static $replaces = array(
            '-'  => '[-]',
            '\'' => '',
        );

        $pattern = '';

        if ($withType) {
            $pattern .= $this->buildArgumentType();
        }

        $pattern .= $this->buildArguments($withArguments);

        return (string) str_replace(array_keys($replaces), array_values($replaces), $pattern);
    }

    /**
     * Set argument to use as command type id.
     *
     * @param  Argument $argument
     * @return $this
     */
    public function setType(Argument $argument)
    {
        $this->argumentType = $argument;

        return $this;
    }

    /**
     * Build command.
     *
     * @param  boolean $isAsync
     * @return string
     */
    protected function build($isAsync)
    {
        $command = '';

        //~ Force log to the void when we used asynchronous mode.

        if ($isAsync && $this->logStandard === null) {
            $this->setLog('/dev/null');
        }

        $command .= $this->getName();
        $command .= $this->buildArgumentType();
        $command .= $this->buildArguments(true);
        $command .= $this->buildOutput();

        if ($isAsync) {
            $command .= ' &';
        }

        return $command;
    }

    /**
     * Build list of arguments for the command.
     *
     * @param  bool $withArguments
     * @return string
     */
    protected function buildArguments($withArguments = true)
    {
        $arguments = [];

        foreach ($this->arguments as $argument) {
            if (!$withArguments && $argument->getName() !== 'name') {
                continue;
            }
            $arguments[] = (string) $argument;
        }

        return ' ' . implode(' ', $arguments);
    }

    /**
     * Build argument command type id.
     * Used by multi processing. If null, return an empty string
     *
     * @return string
     */
    protected function buildArgumentType()
    {
        return ' ' . (string) $this->argumentType;
    }
}
