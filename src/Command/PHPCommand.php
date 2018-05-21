<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * PHP command
 *
 * @author Romain Cottard
 */
class PHPCommand extends AbstractCommand implements ConsoleCommandInterface
{
    /** @var string $name Command name */
    protected $name = 'php';

    /**
     * PHPCommand constructor.
     *
     * @param string $scriptFile
     */
    public function __construct($scriptFile)
    {
        if (!file_exists($scriptFile)) {
            throw new \LogicException('Invalid file script');
        }

        $this->addArgument(new Argument('', $scriptFile, false));
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
     * Build list of arguments for the command.
     *
     * @param  bool $withArguments
     * @return string
     */
    protected function buildArguments($withArguments = true)
    {
        $arguments = [];

        foreach ($this->arguments as $argument) {
            if (!$withArguments && $argument->getName() !== '') {
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
