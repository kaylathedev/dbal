<?php
namespace DBAL\Database;

use DBAL\Condition;

use \Psr\Log\LoggerAwareInterface;
use \Psr\Log\LoggerInterface;
use \Psr\Log\NullLogger;

abstract class AbstractDatabase implements LoggerAwareInterface, ReadableInterface, WritableInterface
{

    private $logger;

    /**
     * Instantiates the AbstractDatabase. Should be called from inheritors.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that will help with finding the records.
     * @return array|null One record from table, or null if there aren't any matching records.
     */
    public function find($table, Condition $criteria = null, array $options = [])
    {
        $options['limit'] = 1;
        $data             = $this->findAll($table, $criteria, $options);
        $result           = reset($data);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    /**
     * Returns the current logger object.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Sets the logger to $value.
     *
     * @param LoggerInterface $value A logger to be used for this database.
     * @return void
     */
    public function setLogger(LoggerInterface $value)
    {
        if (null === $value) {
            $value = new NullLogger();
        }

        $this->logger = $value;
    }

    /**
     * Returns the id of the last record created, or null if no creation took place.
     *
     * Must be implemented by inheritors.
     *
     * @return mixed
     */
    abstract public function lastIdCreated();
}
