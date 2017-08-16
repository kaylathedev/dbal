<?php
namespace DBAL\Exceptions;

use \Exception;
use \RuntimeException;

class DatabaseException extends RuntimeException
{

    /**
     * @var Exception[]
     */
    private $ignored = [];

    /**
     * Adds an exception that was most likely ignored to reattempt execution.
     *
     * @param Exception $value The exception that was ignored.
     * @return void
     */
    public function addIgnoredException(Exception $value)
    {
        $this->ignored[] = $value;
    }

    /**
     * Returns an array of all of the ignored exceptions.
     *
     * @return Exception[] A list of all of the ignored Exception objects.
     */
    public function getIgnoredExceptions()
    {
        return $this->ignored;
    }

    /**
     * Returns the string reprensentation of this exception.
     *
     * The returned string may include...
     *  1. The class name.
     *  2. Message.
     *  3. The previous exception with __toString called on it, if it exists.
     *  4. All of the ignored exceptions with __toString called on each of them.
     *
     * @return string
     */
    public function __toString()
    {
        $info     = get_class() . ' - ' . $this->getMessage();
        $previous = $this->getPrevious();
        if (null !== $previous) {
            $info .= ' - Previous Exception: ' . (string) $this->getPrevious();
        }
        foreach ($this->ignored as $exception) {
            $info .= "\nIgnored Exception: " . (string) $exception;
        }

        return $info;
    }
}
