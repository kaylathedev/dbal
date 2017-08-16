<?php
namespace DBAL;

final class Condition
{

    private $left;
    private $escapeLeft;
    private $operator;
    private $right;
    private $escapeRight;

    /**
     * @param mixed   $left
     * @param boolean $escapeLeft
     * @param string  $operator
     * @param mixed   $right
     * @param boolean $escapeRight
     */
    private function __construct($left, $escapeLeft, $operator, $right, $escapeRight)
    {
        $this->left        = $left;
        $this->escapeLeft  = $escapeLeft;
        $this->operator    = $operator;
        $this->right       = $right;
        $this->escapeRight = $escapeRight;
    }

    /**
     * @param string $field
     */
    public static function equals($field, $value)
    {
        return new Condition($field, false, '=', $value, true);
    }

    /**
     * @param string $field
     */
    public static function regex($field, $value)
    {
        return new Condition($field, false, 'regex', $value, true);
    }

    /**
     * @param string $field
     */
    public static function notEquals($field, $value)
    {
        return new Condition($field, false, '!=', $value, true);
    }

    /**
     * @param string $field
     */
    public static function lessThan($field, $value)
    {
        return new Condition($field, false, '<', $value, true);
    }

    /**
     * @param string $field
     */
    public static function lessThanOrEquals($field, $value)
    {
        return new Condition($field, false, '<=', $value, true);
    }

    /**
     * @param string $field
     */
    public static function greaterThan($field, $value)
    {
        return new Condition($field, false, '>', $value, true);
    }

    /**
     * @param string $field
     */
    public static function greaterThanOrEquals($field, $value)
    {
        return new Condition($field, false, '>=', $value, true);
    }

    /**
     * @param Condition $left
     * @param Condition $right
     */
    public static function combineOr(Condition $left, Condition $right)
    {
        return new Condition($left, false, 'or', $right, false);
    }

    /**
     * @param Condition $left
     * @param Condition $right
     */
    public static function combineAnd(Condition $left, Condition $right)
    {
        return new Condition($left, false, 'and', $right, false);
    }

    /**
     * @param Condition[] $values
     */
    public static function combineManyAnd(array $values)
    {
        return self::combineMany('and', $values);
    }

    /**
     * @param Condition[] $values
     */
    public static function combineManyOr(array $values)
    {
        return self::combineMany('or', $values);
    }

    /**
     * @param string      $operation
     * @param Condition[] $values
     */
    public static function combineMany($operation, array $values)
    {
        $valuesCount = count($values);
        if ($valuesCount > 1) {
            $left  = array_pop($values);
            $right = self::combineMany($operation, $values);
            return new Condition($left, false, $operation, $right, false);
        }
        if (1 === $valuesCount) {
            return $values[0];
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @return boolean
     */
    public function shouldEscapeLeft()
    {
        return $this->escapeLeft;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @return boolean
     */
    public function shouldEscapeRight()
    {
        return $this->escapeRight;
    }
}
