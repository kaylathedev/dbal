<?php
namespace DBAL\Database;

use DBAL\Condition;

interface ReadableInterface
{

    /**
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that will help with finding the records.
     * @return array|null One record from table, or null if there aren't any matching records.
     */
    public function find($table, Condition $criteria = null, array $options = []);

    /**
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that will help with finding the records.
     * @return array[] Multiple records from the table that match the criteria.
     */
    public function findAll($table, Condition $criteria = null, array $options = []);

    /**
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that help with counting the records.
     * @return int The number of records that match the criteria.
     */
    public function count($table, Condition $criteria = null, array $options = []);

    /**
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that will help with finding the records.
     * @return boolean A boolean value indicating if any record matches the criteria.
     */
    public function has($table, Condition $criteria = null, array $options = []);
}
