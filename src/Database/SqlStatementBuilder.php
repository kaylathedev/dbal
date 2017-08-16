<?php
namespace DBAL\Database;

use DBAL\Condition;

class SqlStatementBuilder
{

    public $bindings;
    public $queries;

    public function __construct()
    {
        $this->bindings = [];
        $this->queries  = [];
    }

    public function create($table, $fields)
    {
        $query = 'insert into ' . $table . '(';

        $setValues = $this->compileFields($fields, function () {
            return '?';
        });

        $segmentFields = implode(', ', array_keys($fields));
        $segmentValues = implode(', ', $setValues);

        $this->queries[] = $query . $segmentFields . ') values(' . $segmentValues . ')';
    }

    public function update($table, array $fields, Condition $criteria = null, $limit = null)
    {
        $limitPart = '';
        if (null !== $limit) {
            $limitPart = ' limit ' . $limit;
        }

        $query = 'update ' . $table . ' set ';

        $setValues = $this->compileFields($fields, function ($field) {
            return $field . ' = ?';
        });

        $this->queries[] = $query . implode(', ', $setValues)
                . $this->getWhereClause($criteria)
                . $limitPart;
    }

    public function delete($table, Condition $criteria = null)
    {
        $this->queries[] = 'delete from ' . $table . $this->getWhereClause($criteria);
    }

    public function select(array $columns, $table, Condition $criteria = null, $limit = null)
    {
        $limitPart = '';
        if (null !== $limit) {
            $limitPart = ' limit ' . $limit;
        }

        $query = 'select ' . implode(', ', $columns)
                . ' from ' . $table
                . $this->getWhereClause($criteria)
                . $limitPart;

        $this->queries[] = $query;
    }

    /**
     * @param array $fields
     * @param callable $iterationCallback
     */
    private function compileFields(array $fields, $iterationCallback)
    {
        $index    = 0;
        $compiled = [];
        foreach ($fields as $field => $value) {
            ++$index;
            $this->bindings[] = $value;

            $compiled[] = call_user_func($iterationCallback, $field);
        }
        return $compiled;
    }

    private function getWhereClause(Condition $criteria = null)
    {
        if (null !== $criteria) {
            return ' where ' . $this->compileCondition($criteria);
        }
        return null;
    }

    /**
     * @param string $value
     * @param boolean $shouldEscape
     *
     * @return string
     */
    private function processValue($value, $shouldEscape)
    {
        if ($value instanceof Condition) {
            $value = '(' . $this->compileCondition($value) . ')';
        } elseif ($shouldEscape) {
            $this->bindings[] = $value;

            $value = '?';
        }
        return $value;
    }

    private function compileCondition(Condition $criteria = null)
    {
        if (null === $criteria) {
            return null;
        }
        $operator = $criteria->getOperator();
        if ('regex' === $operator) {
            $operator = 'regexp';
        }

        $segments = [
            $this->processValue($criteria->getLeft(), $criteria->shouldEscapeLeft()),
            $operator,
            $this->processValue($criteria->getRight(), $criteria->shouldEscapeRight())
        ];

        return implode(' ', $segments);
    }
}
