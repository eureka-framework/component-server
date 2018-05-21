<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Server\Process;

use Psr\Log\LoggerAwareTrait;

/**
 * Retry process class.
 *
 * @author Romain Cottard
 * @todo   Improve this class to catch list of given exception to retry & a way to retry
 */
class Retry
{
    /** Use logger trait to set logger. */
    use LoggerAwareTrait;

    /** @var int RETRY_DEFAULT_MAX Default maximum number of retry. */
    const RETRY_DEFAULT_MAX = 5;

    /** @var int RETRY_DEFAULT_TIME_WAIT Default time to wait before retrying process  (in seconds) */
    const RETRY_DEFAULT_TIME_WAIT = 30;

    /** @var int $maxRetry Maximum number of retry when an error occurred. */
    protected $maxRetry = 0;

    /** @var int $timeBeforeRetry Waiting time before retry (in seconds) */
    protected $timeBeforeRetry = 0;

    /** @var int $nbRetry Number of retry when an error occurred. */
    protected $nbRetry = 0;

    /** @var string $syslogName Name of syslog where the retrying class will be write. */
    protected $syslogName = '';

    /** @var int $time Time when an db error occurred */
    protected $time = 0;

    /**
     * Retry constructor.
     *
     * @param int $maxRetry
     * @param int $timeBeforeRetry
     */
    public function __construct($maxRetry = self::RETRY_DEFAULT_MAX, $timeBeforeRetry = self::RETRY_DEFAULT_TIME_WAIT)
    {
        $this->setMaxRetry($maxRetry);
        $this->setTimeBeforeRetry($timeBeforeRetry);
        $this->setTime(microtime(true));
    }

    /**
     * Set max retry.
     *
     * @param  int $maxRetry
     * @return $this
     * @throws \UnderflowException
     */
    public function setMaxRetry($maxRetry)
    {
        $maxRetry = (int) $maxRetry;

        if ($maxRetry < 0) {
            throw new \UnderflowException('Max retry number must be equals or greater than 0!');
        }

        $this->maxRetry = $maxRetry;

        return $this;
    }

    /**
     * Set time to wait before retry.
     *
     * @param  int $timeBeforeRetry
     * @return $this
     * @throws \UnderflowException
     */
    public function setTimeBeforeRetry($timeBeforeRetry)
    {
        $timeBeforeRetry = (int) $timeBeforeRetry;

        if ($timeBeforeRetry < 0) {
            throw new \UnderflowException('Waiting time before retry must be equals or greater than 0!');
        }

        $this->timeBeforeRetry = $timeBeforeRetry;

        return $this;
    }

    /**
     * Set time for check.
     *
     * @param  int $time (timestamp in second)
     * @return $this
     * @throws \UnderflowException
     */
    public function setTime($time)
    {
        $time = (int) $time;

        if ($time < 0) {
            throw new \UnderflowException('Current time must be greater than 0!');
        }

        $this->time = $time;

        return $this;
    }

    /**
     * Retry loop
     *
     * @param  \Exception $exception
     * @return void
     * @throws \Exception
     */
    public function retry(\Exception $exception)
    {
        //~ Example of retry reconnection
        /*
        if (!($exception instanceof \PDOException)) {
            throw $exception;
        }

        if (!$this->tryReconnect()) {
            throw $exception;
        }
        */

        $this->updateNbRetry();

        if ($this->nbRetry > $this->maxRetry) {
            throw $exception;
        }
    }

    /**
     * Try to reconnect db.
     *
     * @return bool
     * @throws \Exception
     */
    protected function tryReconnect()
    {
        //~ Waiting X seconds before retry connection.
        sleep($this->timeBeforeRetry);

        try {

            //~ Here, handle reconnection.

            return true;

        } catch (\Exception $exception) {
            if (!($exception instanceof \PDOException)) {
                throw $exception;
            }

            $this->nbRetry++;

            if ($this->nbRetry > self::RETRY_DEFAULT_MAX) {
                throw $exception;
            }

            return $this->tryReconnect();
        }
    }

    /**
     * Update number of retry. Decrease it for each range of 5min without db error.
     *
     * @return void
     */
    protected function updateNbRetry()
    {
        $timeDiff   = (int) (microtime(true) - $this->time);
        $this->time = microtime(true);

        //~ Decrease nb retry for each 5minute range
        //~ If time diff (between to db errors) = 90 seconds, will decrease nb retry of 3. Nb retry min is 0.
        $diff = (int) floor($timeDiff / (self::RETRY_DEFAULT_TIME_WAIT + 1));
        if ($diff > 0) {
            $this->nbRetry = max(0, ($this->nbRetry - $diff));
        }

        $this->nbRetry++;
    }
}
