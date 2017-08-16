<?php
namespace DBAL\Database;

use DBAL\Condition;

interface WritableInterface
{

    /**
     * @param string         $table    The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the data.
     * @param array          $options  The list of options that will help with deleting the records.
     * @return void
     */
    public function delete($table, Condition $criteria = null, array $options = []);

    /**
     * @param string $table   The table that will be accessed and written.
     * @param array  $fields  A list of fields and values to be used when creating a new record.
     * @param array  $options The list of options that will help with creating the records.
     * @return void
     */
    public function create($table, array $fields, array $options = []);

    /**
     * @param string         $table    The table that will be accessed and written.
     * @param array          $fields   A list of fields and values to be used when updating a record.
     * @param Condition|null $criteria The criteria that will filter the data.
     * @param array          $options  The list of options that will help with updating the records.
     * @return void
     */
    public function update($table, array $fields, Condition $criteria = null, array $options = []);
}
