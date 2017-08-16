<?php
namespace DBAL\Database;

use DBAL\Condition;

use \Exception;
use \mysqli_stmt;

abstract class AbstractSQLDatabase extends AbstractDatabase
{

    /**
     * @param SqlStatementBuilder $statement
     * @param array               $options
     * @param bool                $returnResult
     * @return array[]|null
     */
    abstract protected function sendQueryToDatabase(SqlStatementBuilder $statement, array $options, $returnResult);

    /**
     * @return SqlStatementBuilder
     */
    protected static function getSqlStatement()
    {
        return new SqlStatementBuilder();
    }

    /**
     * @param string $table   The table that will be accessed and written.
     * @param array  $fields  A list of fields and values to be used when creating a new record.
     * @param array  $options The list of options that will help with creating the records.
     * @return void
     */
    public function create($table, array $fields, array $options = [])
    {
        $expr = self::getSqlStatement();
        $expr->create($table, $fields);
        $this->sendQueryToDatabase($expr, $options, false);
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
        $expr = self::getSqlStatement();
        $expr->update($table, $fields, $criteria);
        $this->sendQueryToDatabase($expr, $options, false);
    }

    /**
     * @param string         $table    The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the data.
     * @param array          $options  The list of options that will help with deleting the records.
     * @return void
     */
    public function delete($table, Condition $criteria = null, array $options = [])
    {
        $expr = self::getSqlStatement();
        $expr->delete($table, $criteria);
        $this->sendQueryToDatabase($expr, $options, false);
    }

    /**
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that will help with finding the records.
     * @return array[] Multiple records from the table that match the criteria.
     */
    public function findAll($table, Condition $criteria = null, array $options = [])
    {
        $expr = self::getSqlStatement();

        if (isset($options['fields'])) {
            $fields = $options['fields'];
            if (!is_array($fields)) {
                $fields = [$fields];
            }
        } else {
            $fields = ['*'];
        }

        if (isset($options['limit'])) {
            $limit = $options['limit'];
        } else {
            $limit = null;
        }

        $expr->select($fields, $table, $criteria, $limit);
        return $this->sendQueryToDatabase($expr, $options, true);
    }

    /**
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that help with counting the records.
     * @return int The number of records that match the criteria.
     */
    public function count($table, Condition $criteria = null, array $options = [])
    {
        $data = $this->selectOnlyOneValue('count(*) as x', $table, $criteria, $options);
        return (int) $data[0]['x'];
    }

    /**
     * @param string         $table The table that will be accessed and written.
     * @param Condition|null $criteria The criteria that will filter the records.
     * @param array          $options The list of options that will help with finding the records.
     * @return boolean A boolean value indicating if any record matches the criteria.
     */
    public function has($table, Condition $criteria = null, array $options = [])
    {
        $data = $this->selectOnlyOneValue('1 as x', $table, $criteria, $options);
        return isset($data[0]);
    }

    /**
     * @param string         $columns
     * @param string         $table
     * @param Condition|null $criteria
     * @param array          $options
     * @return array
     */
    private function selectOnlyOneValue($columns, $table, Condition $criteria = null, array $options = [])
    {
        $expr = self::getSqlStatement();
        $expr->select([$columns], $table, $criteria);
        return $this->sendQueryToDatabase($expr, $options, true);
    }

    /**
     * Logs the given exception, and returns it.
     *
     * @param Exception $error The exception to log and return.
     * @return Exception The exception that was passed in as an argument.
     */
    protected function logException(Exception $error)
    {
        $this->getLogger()->error($error);
        return $error;
    }

    /**
     * @param SqlStatementBuilder $statement
     * @param array               $options
     * @return array<string|array> An array that contains the query and the bindings.
     */
    protected static function compileQueriesAndBindings(SqlStatementBuilder $statement, array $options)
    {
        $queries  = $statement->queries;
        $bindings = $statement->bindings;

        foreach ($queries as $key => $query) {
            if (isset($options['beforeSQL'])) {
                $queries[$key] = $options['beforeSQL'] . $query;
            }
            if (isset($options['afterSQL'])) {
                $queries[$key] = $query . $options['afterSQL'];
            }
        }

        if (isset($options['beforeBindings'])) {
            $bindings = array_merge($options['beforeBindings'], $bindings);
        }

        if (isset($options['afterBindings'])) {
            $bindings = array_merge($bindings, $options['afterBindings']);
        }

        $query = implode(';', $queries);

        return [$query, $bindings];
    }
}
