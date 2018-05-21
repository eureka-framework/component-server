<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Socket;

/**
 * Socket class. Wrapper for native socket_* php functions.
 *
 * @author Romain Cottard
 */
class Socket
{
    /** @var resource $socket Socket resource */
    protected $socket = null;

    /** @var string $buffer Socket buffer */
    protected $buffer = '';

    /**
     * Socket constructor.
     *
     * @param  int $domain
     * @param  int $type
     * @param  int $protocol
     * @throws SocketException
     */
    public function __construct($domain = AF_INET, $type = SOCK_STREAM, $protocol = SOL_TCP)
    {
        if (is_resource($domain)) {
            $this->socket = $domain;
        } else {
            $this->socket = socket_create($domain, $type, $protocol);
        }

        if ($this->socket === false) {
            throw new SocketException(__METHOD__ . '|Unable to create socket - error:' . $this->getError(false) . ' !');
        }
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if (is_resource($this->socket)) {
            socket_close($this->socket);
        }
    }

    //-

    /**
     * Accept socket connection.
     *
     * @return Socket
     */
    public function accept()
    {
        $socket = false;

        if (is_resource($this->socket)) {
            $socket = socket_accept($this->socket);
        }

        if (is_resource($socket)) {
            return new Socket($socket);
        }

        return null;
    }

    /**
     * Bind an address & port to socket.
     *
     * @param string $address
     * @param integer $port
     * @return $this
     * @throws SocketException
     * @throws \UnderflowException
     */
    public function bind($address, $port = 0)
    {
        $port = (int) $port;

        if ($port < 0) {
            throw new \UnderflowException(__METHOD__ . '|Port must be equals or greater than 0 !');
        }

        $isBinded = socket_bind($this->socket, $address, $port);
        if (!$isBinded) {
            throw new SocketException(__METHOD__ . '|Unable to bind address/port to socket (address: ' . $address . ', port: ' . $port . ') - socket error:' . $this->getError() . ' !');
        }

        return $this;
    }

    /**
     * Close socket connection.
     *
     * @return $this
     */
    public function close()
    {
        if (is_resource($this->socket)) {
            socket_close($this->socket);
        }

        return $this;
    }

    /**
     * Connect socket
     *
     * @param string $address
     * @param integer $port
     * @return $this
     * @throws SocketException
     * @throws \Exception
     */
    public function connect($address, $port = 0)
    {
        $port = (int) $port;

        if ($port < 0) {
            throw new \UnderflowException(__METHOD__ . '|Port must be equals or greater than 0 !');
        }

        $isConnected = socket_connect($this->socket, $address, $port);
        if (!$isConnected) {
            throw new SocketException(__METHOD__ . '|Unable to connect socket - socket error:' . $this->getError() . ' !');
        }

        return $this;
    }

    /**
     * Enable non block socket connection.
     *
     * @return $this
     */
    public function enableNonBlock()
    {
        if (is_resource($this->socket)) {
            socket_set_nonblock($this->socket);
        }

        return $this;
    }

    /**
     * Listen socket.
     *
     * @param  int $backlog
     * @return $this
     * @throws SocketException
     * @throws \Exception
     */
    public function listen($backlog = 4096)
    {
        $backlog = (int) $backlog;

        if ($backlog < 0) {
            throw new \UnderflowException(__METHOD__ . '|Backlog (max connections) must be equals or greater than 0 !');
        }

        $isListened = socket_listen($this->socket, $backlog);
        if (!$isListened) {
            throw new SocketException(__METHOD__ . '|Unable to listen (backlog: ' . $backlog . ') - socket error:' . $this->getError() . ' !');
        }

        return $this;
    }

    /**
     * Read data
     *
     * @param int $length
     * @param int $type
     * @return null|string
     * @throws SocketException
     */
    public function read($length = 4096, $type = PHP_BINARY_READ)
    {
        $buffer = socket_read($this->socket, $length, $type);

        if ($buffer === false) {
            throw new SocketException(__METHOD__ . '|Unable to read data - socket error:' . $this->getError() . ' !');
        }

        $buffer = trim($buffer);
        if ($buffer === '') {
            return null;
        }

        return $buffer;
    }

    /**
     * Receive data
     *
     * @param  int $length
     * @param  int $flag
     * @return bool
     * @throws SocketException
     */
    public function receive($length = 4096, $flag = MSG_DONTWAIT)
    {
        $bytes = socket_recv($this->socket, $this->buffer, $length, $flag);

        if ($bytes === false) {
            throw new SocketException(__METHOD__ . '|Unable to read data - socket error:' . $this->getError() . ' !');
        }

        return (bool) $bytes;
    }

    /**
     * Write data.
     *
     * @param  string $buffer
     * @return $this
     * @throws SocketException
     */
    public function write($buffer = '')
    {
        $isWritten = socket_write($this->socket, $buffer, mb_strlen($buffer));

        if (!$isWritten) {
            throw new SocketException(__METHOD__ . '|Unable to write buffer - socket error:' . $this->getError() . ' !');
        }

        return $this;
    }

    /**
     * Get buffered data
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Get last error from socket
     *
     * @param  bool $hasContext
     * @return string
     */
    public function getError($hasContext = true)
    {
        if ($hasContext) {
            $error = socket_strerror(socket_last_error());
        } else {
            $error = socket_strerror(socket_last_error($this->socket));
        }

        return $error;
    }

    /**
     * Set a timeout for socket interactions
     *
     * @param int $seconds
     * @param int $microseconds
     */
    public function setTimeout($seconds, $microseconds = 0)
    {
        $this->setOption(SOL_SOCKET, SO_RCVTIMEO, ['sec' => $seconds, 'usec' => $microseconds]);
        $this->setOption(SOL_SOCKET, SO_SNDTIMEO, ['sec' => $seconds, 'usec' => $microseconds]);
    }

    /**
     * Set an option
     *
     * @param int $level
     * @param int $option
     * @param mixed $value
     */
    public function setOption($level, $option, $value)
    {
        socket_set_option($this->socket, $level, $option, $value);
        socket_set_option($this->socket, $level, $option, $value);
    }
}
