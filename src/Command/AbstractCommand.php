<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * Command abstract class command
 *
 * @author Romain Cottard
 */
abstract class AbstractCommand implements CommandInterface
{
    /** @var Argument[] $arguments List of arguments for the command. */
    protected $arguments = [];

    /** @var Argument $argumentType Argument used as a command type id. */
    protected $argumentType = null;

    /** @var string $name Command name. */
    protected $name = '';

    /** @var string $logStandard Log name */
    protected $logStandard = null;

    /** @var string $logError Errors log name. If empty, redirect all output into standard log. */
    protected $logError = null;

    /** @var boolean $isLogAppend Set to true to append output to existing log. Be careful with size of log */
    protected $isLogAppend = false;

    /**
     * Add argument to the command.
     *
     * @param  Argument $argument
     * @return $this
     */
    public function addArgument(Argument $argument): CommandInterface
    {
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * Add arguments to the command.
     *
     * @param  Argument[] $arguments
     * @return $this
     */
    public function addArguments(array $arguments): CommandInterface
    {
        foreach ($arguments as $argument) {
            $this->addArgument($argument);
        }

        return $this;
    }

    /**
     * Set argument to use as command type id.
     *
     * @param  Argument[] $arguments
     * @return $this
     */
    public function setArguments(array $arguments): CommandInterface
    {
        $this->arguments = [];

        return $this->addArguments($arguments);
    }

    /**
     * @param bool $withArguments
     * @param bool $withType
     * @return string
     */
    public function getPattern(bool $withArguments = true, bool $withType = false): string
    {
        static $replaces = array(
            '-'  => '[-]',
            '\'' => '',
        );

        $pattern = $this->name . ' ' . $this->buildArguments();

        return (string) str_replace(array_keys($replaces), array_values($replaces), $pattern);
    }

    /**
     * Set output log for the command
     *
     * @param  string $logStandard
     * @param  string $logError
     * @param  bool $isLogAppend
     * @return $this
     */
    public function setLog(string $logStandard, string $logError = null, bool $isLogAppend = false): CommandInterface
    {
        $this->logStandard = $logStandard;
        $this->logError    = $logError;
        $this->isLogAppend = $isLogAppend;

        return $this;
    }

    /**
     * Execute command on server.
     *
     * @param  bool $isAsync
     * @param  bool $useSystem
     * @return array|int Command output result.
     */
    public function exec(bool $isAsync = false, bool $useSystem = false): array
    {
        $command = $this->build($isAsync);

        if ($useSystem) {
            $return = null;
            system($command, $return);
        } else {
            $return = [];
            exec($command, $return);
        }

        return $return;
    }

    /**
     * Get command name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Build command.
     *
     * @param  bool $isAsync
     * @return string
     */
    protected function build(bool $isAsync): string
    {
        $command = '';

        //~ Force log to the void when we used asynchronous mode.

        if ($isAsync && $this->logStandard === null) {
            $this->setLog('/dev/null');
        }

        $command .= $this->getName();
        $command .= $this->buildArguments();
        $command .= $this->buildOutput();

        if ($isAsync) {
            $command .= ' &';
        }

        return $command;
    }

    /**
     * Build list of arguments for the command.
     *
     * @return string
     */
    protected function buildArguments(): string
    {
        $arguments = [];

        foreach ($this->arguments as $argument) {
            $arguments[] = (string) $argument;
        }

        return ' ' . implode(' ', $this->arguments);
    }

    /**
     * Build logs output
     *
     * @return string
     */
    protected function buildOutput(): string
    {
        $output     = '';
        $outputSign = ($this->isLogAppend ? '>>' : '>');

        //~ Case no log
        if ($this->logStandard === null && $this->logError === null) {
            return $output;
        }

        //~ Case one log defined.
        if ($this->logStandard !== null && $this->logError === null) {
            return ' ' . $outputSign . ' ' . escapeshellarg($this->logStandard);
        }

        //~ Redirect
        $output = ($this->logStandard === null ? ' 1' . $outputSign . ' ' . escapeshellarg($this->logStandard) : '');

        return $output . ($this->logError === null ? ' 2' . $outputSign . ' ' . escapeshellarg($this->logError) : '');
    }
}
