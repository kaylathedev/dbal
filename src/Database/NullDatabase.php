<?php
namespace DBAL\Database;

use DBAL\Condition;

use \Exception;
use \mysqli;
use \mysqli_result;
use \DBAL\Exceptions\DatabaseException;

class NullDatabase extends AbstractDatabase
{

    /**
     * @return mixed
     */
    public function lastIdCreated()
    {
        return null;
    }

    /**
     * @return void
     */
    public function create($table, array $fields, array $options = [])
    {
        /* Noop */
    }

    /**
     * @return void
     */
    public function update($table, array $fields, Condition $criteria = null, array $options = [])
    {
        /* Noop */
    }

    /**
     * @return void
     */
    public function delete($table, Condition $criteria = null, array $options = [])
    {
        /* Noop */
    }

    /**
     * @return null
     */
    public function find($table, Condition $criteria = null, array $options = [])
    {
        return null;
    }

    /**
     * @return array
     */
    public function findAll($table, Condition $criteria = null, array $options = [])
    {
        return [];
    }

    /**
     * @return int
     */
    public function count($table, Condition $criteria = null, array $options = [])
    {
        return 0;
    }

    /**
     * @return boolean
     */
    public function has($table, Condition $criteria = null, array $options = [])
    {
        return false;
    }
}
