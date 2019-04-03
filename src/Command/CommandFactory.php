<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Command;

/**
 * Class CommandFactory
 *
 * @author Romain Cottard
 */
final class CommandFactory
{
    /**
     * @param string $commandString
     * @return CommandInterface
     */
    public function createFromString(string $commandString): CommandInterface
    {
        $data = explode(' ', $commandString);

        $name      = array_shift($data);
        $arguments = $this->parseStringArguments($data);

        switch (basename($name)) {
            case PHPCommand::COMMAND_NAME:
                $command = new PHPCommand($arguments['__default__'] ?? '');
                $command->addArguments($arguments);
                break;
            case ConsoleCommand::COMMAND_NAME:
            case ConsoleCommand::COMMAND_NAME_ALIAS:
                $command = new ConsoleCommand(dirname($name));
                break;
            default:
                $command = new GenericCommand($name);
        }

        $command->addArguments($arguments);

        return $command;
    }
    /**
     * @param string $commandString
     * @return ConsoleCommandInterface
     */
    public function createConsoleCommandFromString(string $commandString): ConsoleCommandInterface
    {
        $data = explode(' ', $commandString);

        $name      = array_shift($data);
        $arguments = $this->parseStringArguments($data);

        $command = new ConsoleCommand($name);
        $command->addArguments($arguments);

        return $command;
    }

    /**
     * @param array $commandArguments
     * @return array
     */
    private function parseStringArguments(array $commandArguments): array
    {
        $arguments = [];

        while (current($commandArguments) !== false) {

            $current = current($commandArguments);
            $key     = key($commandArguments);

            //~ First, get next element value
            next($commandArguments);
            $next = (string) current($commandArguments);
            prev($commandArguments);

            $arg1 = substr($current, 0, 1);
            $arg2 = substr($current, 0, 2);

            if ('--' == $arg2) {
                //~ Case '--argument[=value]'
                $arguments[] = $this->getFullNamedArgument($current, $next);
            } elseif ('-' == $arg1) {
                //~ Case -t[ value]
                $arguments = array_merge($arguments, $this->getShortNamedArguments($current, $next));
            } elseif ($key !== 0 && $arg1 !== '-' && $arg2 !== '--' && !isset($arguments['__default__'])) {
                //~ Case "value" (without name)
                $arguments['__default__'] = $current;
            }
        }

        return $arguments;
    }

    /**
     * @param string $current
     * @param string $next
     * @return Argument
     */
    private function getFullNamedArgument(string $current, string $next): Argument
    {
        $arg   = [];
        $match = preg_match('`--([0-9a-z_-]+)="?(.+)"?`', $current, $arg);

        //~ Try to find "--argument=value"
        if ($match > 0) {
            return new Argument($arg[1], $arg[2], true);
        }

        //~ Try to find "--argument value"
        if (!empty($next) && '-' !== substr($next, 0, 1)) {
            return new Argument(substr($current, 2), $next, true);
        }

        //~ Else, case is --argument
        return new Argument(substr($current, 2), true, true);
    }

    /**
     * @param string $current
     * @param string $next
     * @return Argument[]
     */
    private function getShortNamedArguments(string $current, string $next): array
    {
        $arg = substr($current, 1);
        $len = strlen($arg);

        //~ Try to find "-a value"
        if (1 == $len && $next !== '' && '-' != substr($next, 0, 1)) {
            return [new Argument($arg, $next, false)];
        }

        //~ Else, case is "-abc" (equivalent to -a -b -c)
        $arguments = [];
        for ($letter = 0; $letter < $len; $letter++) {
            $arguments[] = new Argument($arg[$letter], true, false);
        }

        return $arguments;
    }
}
