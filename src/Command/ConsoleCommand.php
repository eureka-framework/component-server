<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

use Eureka\Component\Server\Command\Exception\InvalidCommandNameException;

/**
 * Main Console command class.
 * Use to execute script with Eureka bin/console executable script.
 *
 * @author Romain Cottard
 */
final class ConsoleCommand extends AbstractCommand implements ConsoleCommandInterface
{
    /** @var string COMMAND_NAME_CONSOLE */
    public const COMMAND_NAME = 'console';

    /** @var string COMMAND_NAME_ALIAS */
    public const COMMAND_NAME_ALIAS = 'script';

    /**
     * ConsoleCommand constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        if (!in_array(basename($name), [self::COMMAND_NAME, self::COMMAND_NAME_ALIAS])) {
            throw new InvalidCommandNameException('Command name is invalid (must be "[/.../]script" or "[/.../]console"');
        }

        $this->name  = $name;
    }

    /**
     * Get command pattern.
     *
     * @param  bool $withArguments
     * @param  bool $withType
     * @return string
     */
    public function getPattern(bool $withArguments = true, bool $withType = true): string
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
    public function setType(Argument $argument): ConsoleCommandInterface
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
    protected function build(bool $isAsync): string
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
    protected function buildArguments(bool $withArguments = true): string
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
    protected function buildArgumentType(): string
    {
        return ' ' . (string) $this->argumentType;
    }
}
