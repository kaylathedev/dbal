<?php
namespace DBAL\Database;

use DBAL\Condition;

use \DBAL\Exceptions\DatabaseException;

/**
 * A database that stores all of its data in the memory.
 * Still a work in progress.
 */
class MemoryDatabase extends AbstractDatabase
{

    public $tables = [];
    private $lastIdCreated;
    private $lastAutoId = 0;
    private $primaryKey;

    /**
     * @param string     $primaryKey
     * @param array|null $rawData
     */
    public function __construct($primaryKey = 'id', array $rawData = null)
    {
        $this->primaryKey = $primaryKey;
        if ($rawData !== null) {
            $this->tables = $rawData;
        }
        parent::__construct();
    }

    public function dumpRawData()
    {
        return $this->tables;
    }

    /**
     * @return mixed
     */
    public function lastIdCreated()
    {
        return $this->lastIdCreated;
    }

    private function createTableIfNotExists($table)
    {
        if (!isset($this->tables[$table])) {
            $this->tables[$table] = [];
        }
    }

    /**
     * @param string $table   The table that will be accessed and written.
     * @param array  $fields  A list of fields and values to be used when creating a new record.
     * @param array  $options The list of options that will help with creating the records.
     * @return void
     */
    public function create($table, array $fields, array $options = [])
    {
        $this->createTableIfNotExists($table);
        $primaryKey = $this->primaryKey;
        if (isset($fields[$primaryKey])) {
            // We will use the primary key already given.
            $id = $fields[$primaryKey];
        } else {
            // We have to make our own primary key.
            $id = $this->lastAutoId;
            do {
                $id++;
            } while (isset($this->tables[$table][$id]));
            $this->lastAutoId    = $id;
            $fields[$primaryKey] = $id;
        }
        $this->lastIdCreated       = $id;
        $this->tables[$table][$id] = $fields;
    }

    /**
     * If the condition matches the data in the record, then the return value is true. Otherwise,
     * the return value is false.
     *
     * @return bool Returns true if the condition matches the data in the record.
     */
    private function isRecordMatchingCondition($primaryKeyValue, $record, Condition $condition = null)
    {
        if ($condition === null) {
            return true;
        }
        $operator = $condition->getOperator();
        $field    = $condition->getLeft();
        $value    = $condition->getRight();
        if ($operator === 'and') {
            return $this->isRecordMatchingCondition($primaryKeyValue, $record, $field)
                && $this->isRecordMatchingCondition($primaryKeyValue, $record, $value);
        }
        if ($operator === 'or') {
            return $this->isRecordMatchingCondition($primaryKeyValue, $record, $field)
                || $this->isRecordMatchingCondition($primaryKeyValue, $record, $value);
        }
        if ($field === $this->primaryKey) {
            $actualValue = $primaryKeyValue;
        } elseif (isset($record[$field])) {
            $actualValue = $record[$field];
        } else {
            $actualValue = null;
        }
        if ($operator === '=') {
            // A weak comparison is done intentionally, as some SQL databases
            // use weak comparisons.
            return $actualValue == $value;
        }
        if ($operator === '!=') {
            return $actualValue != $value;
        }
        if ($operator === '>') {
            return $actualValue > $value;
        }
        if ($operator === '<') {
            return $actualValue < $value;
        }
        if ($operator === '>=') {
            return $actualValue >= $value;
        }
        if ($operator === '<=') {
            return $actualValue <= $value;
        }
        if ($operator === 'regex') {
            $result = preg_match($value, $actualValue);
            if ($result === false) {
                $message = 'The regular expression provided is invalid! Expression: ' . $value;
                throw new DatabaseException($message);
            }
            return $result === 1;
        }
        $message = 'Invalid operation! You must use one of the provided static methods!';
        throw new DatabaseException($message);
    }

    /**
     * @param string         $table    The table that will be accessed and written.
     * @param array          $fields   A list of fields and values to be used when updating a record.
     * @param Condition|null $criteria The criteria that will filter the data.
     * @param array          $options  The list of options that will help with updating the records.
     * @return void
     */
    public function update($table, array $fields, Condition $criteria = null, array $options = [])
    {
        if (isset($fields[$this->primaryKey])) {
            throw new DatabaseException(
                'Error when updating a record! You may NOT change the primary key of a record!'
            );
        }

        // The code below is included for increased efficiency.
        // It will check to see if a primary key is being compared.
        if ($criteria != null
            && $criteria->getOperator() === '='
            && $criteria->getLeft() === $this->primaryKey
        ) {
            $primaryKeyValue = $criteria->getRight();
            if (isset($this->tables[$table][$primaryKeyValue])) {
                foreach ($fields as $key => $value) {
                    $this->tables[$table][$primaryKeyValue][$key] = $value;
                }
            }
            return;
        }

        foreach ($this->tables[$table] as $primaryKeyValue => $record) {
            if ($this->isRecordMatchingCondition($primaryKeyValue, $record, $criteria)) {
                foreach ($fields as $key => $value) {
                    $this->tables[$table][$primaryKeyValue][$key] = $value;
                }
            }
        }
    }

    /**
     * @param string         $table    The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the data.
     * @param array          $options  The list of options that will help with deleting the records.
     * @return void
     */
    public function delete($table, Condition $criteria = null, array $options = [])
    {
        if (!isset($this->tables[$table])) {
            return;
        }

        if ($criteria === null) {
            $this->tables[$table] = [];
            return;
        }

        // The code below is included for increased efficiency.
        // It will check to see if a primary key is being compared.
        if ($criteria->getOperator() === '=' && $criteria->getLeft() === $this->primaryKey) {
            $primaryKeyValue = $criteria->getRight();
            if (isset($this->tables[$table][$primaryKeyValue])) {
                unset($this->tables[$table][$primaryKeyValue]);
            }
            return;
        }

        foreach ($this->tables[$table] as $primaryKeyValue => $record) {
            if ($this->isRecordMatchingCondition($primaryKeyValue, $record, $criteria)) {
                unset($this->tables[$table][$primaryKeyValue]);
            }
        }
        return;
    }

    /**
     * Returns all records that match the criteria.
     *
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that will help with finding the records.
     * @return array[] Multiple records from the table that match the criteria.
     */
    public function findAll($table, Condition $criteria = null, array $options = [])
    {
        $limit = null;
        if (isset($options['limit'])) {
            $limit = $options['limit'];
        }

        if (($limit !== null && $limit < 1) || !isset($this->tables[$table])) {
            return [];
        }

        // The code below is included for increased efficiency.
        // It will check to see if a primary key is being compared.
        if ($criteria !== null && $criteria->getOperator() === '=' && $criteria->getLeft() === $this->primaryKey) {
            $primaryKeyValue = $criteria->getRight();
            if (isset($this->tables[$table][$primaryKeyValue])) {
                return [$primaryKeyValue => $this->tables[$table][$primaryKeyValue]];
            }
            return [];
        }

        $count   = 0;
        $records = [];
        foreach ($this->tables[$table] as $primaryKeyValue => $record) {
            if ($limit !== null && $count >= $limit) {
                // We have reached our limit!
                break;
            }
            if ($this->isRecordMatchingCondition($primaryKeyValue, $record, $criteria)) {
                $records[$primaryKeyValue] = $record;
                $count++;
            }
        }
        return $records;
    }

    /**
     * Returns the number of records that match the criteria.
     *
     * If the crtieria is null, then the return value is the total number of
     * records in the table.
     *
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that help with counting the records.
     * @return int The number of records that match the criteria.
     */
    public function count($table, Condition $criteria = null, array $options = [])
    {
        if (!isset($this->tables[$table])) {
            return 0;
        }

        if ($criteria === null) {
            return count($this->tables[$table]);
        }

        $count = 0;
        foreach ($this->tables[$table] as $primaryKeyValue => $record) {
            if ($this->isRecordMatchingCondition($primaryKeyValue, $record, $criteria)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Returns true if there exists a record that matches the criteria.
     *
     * If the criteria is null and the table has data, then the return value is true. There are two
     * reasons for this result.
     *  - The table has stuff.
     *  - Running `$db->has('table_name')` is similar to saying,
          "does the database have table_name?"
     *
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that will help with finding the records.
     * @return boolean A boolean value indicating if any record matches the criteria.
     */
    public function has($table, Condition $criteria = null, array $options = [])
    {
        if (!isset($this->tables[$table])) {
            return false;
        }

        if ($criteria === null) {
            return !empty($this->tables[$table]);
        }

        foreach ($this->tables[$table] as $primaryKeyValue => $record) {
            if ($this->isRecordMatchingCondition($primaryKeyValue, $record, $criteria)) {
                return true;
            }
        }
        return false;
    }
}
