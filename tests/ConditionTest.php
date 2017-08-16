<?php
namespace DBAL\Tests;

use DBAL\Condition;

class ConditionTest extends \PHPUnit_Framework_TestCase
{

    public function testEquals()
    {
        $condition = Condition::equals('age', 51);

        $this->assertSame($condition->getLeft(), 'age');
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), '=');
        $this->assertSame($condition->getRight(), 51);
        $this->assertTrue($condition->shouldEscapeRight());
    }

    public function testNotEquals()
    {
        $condition = Condition::notEquals('age', 51);

        $this->assertSame($condition->getLeft(), 'age');
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), '!=');
        $this->assertSame($condition->getRight(), 51);
        $this->assertTrue($condition->shouldEscapeRight());
    }

    public function testRegex()
    {
        $condition = Condition::regex('age', 51);

        $this->assertSame($condition->getLeft(), 'age');
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), 'regex');
        $this->assertSame($condition->getRight(), 51);
        $this->assertTrue($condition->shouldEscapeRight());
    }

    public function testLessThan()
    {
        $condition = Condition::lessThan('age', 51);

        $this->assertSame($condition->getLeft(), 'age');
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), '<');
        $this->assertSame($condition->getRight(), 51);
        $this->assertTrue($condition->shouldEscapeRight());
    }

    public function testLessThanOrEquals()
    {
        $condition = Condition::lessThanOrEquals('age', 51);

        $this->assertSame($condition->getLeft(), 'age');
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), '<=');
        $this->assertSame($condition->getRight(), 51);
        $this->assertTrue($condition->shouldEscapeRight());
    }

    public function testGreaterThan()
    {
        $condition = Condition::greaterThan('age', 51);

        $this->assertSame($condition->getLeft(), 'age');
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), '>');
        $this->assertSame($condition->getRight(), 51);
        $this->assertTrue($condition->shouldEscapeRight());
    }

    public function testGreaterThanOrEquals()
    {
        $condition = Condition::greaterThanOrEquals('age', 51);

        $this->assertSame($condition->getLeft(), 'age');
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), '>=');
        $this->assertSame($condition->getRight(), 51);
        $this->assertTrue($condition->shouldEscapeRight());
    }

    public function testAnd()
    {
        $conditionA = Condition::greaterThanOrEquals('age', 51);
        $conditionB = Condition::lessThan('weight', 170);
        $condition = Condition::combineAnd($conditionA, $conditionB);

        $this->assertSame($condition->getLeft(), $conditionA);
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), 'and');
        $this->assertSame($condition->getRight(), $conditionB);
        $this->assertFalse($condition->shouldEscapeRight());
    }

    public function testOr()
    {
        $conditionA = Condition::greaterThanOrEquals('age', 51);
        $conditionB = Condition::lessThan('weight', 170);
        $condition = Condition::combineOr($conditionA, $conditionB);

        $this->assertSame($condition->getLeft(), $conditionA);
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), 'or');
        $this->assertSame($condition->getRight(), $conditionB);
        $this->assertFalse($condition->shouldEscapeRight());
    }

    public function testManyAnd()
    {
        $conditionA = Condition::greaterThanOrEquals('age', 51);
        $conditionB = Condition::lessThan('weight', 170);
        $conditionC = Condition::regex('temperature', '([^\d]|^)98(.\d+)?');
        $condition = Condition::combineManyAnd([$conditionA, $conditionB, $conditionC]);

        $this->assertSame($condition->getLeft(), $conditionC);
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), 'and');
        $this->assertEquals($condition->getRight(), Condition::combineAnd($conditionB, $conditionA));
        $this->assertFalse($condition->shouldEscapeRight());
    }

    public function testManyOr()
    {
        $conditionA = Condition::greaterThanOrEquals('age', 51);
        $conditionB = Condition::lessThan('weight', 170);
        $conditionC = Condition::regex('temperature', '([^\d]|^)98(.\d+)?');
        $condition = Condition::combineManyOr([$conditionA, $conditionB, $conditionC]);

        $this->assertSame($condition->getLeft(), $conditionC);
        $this->assertFalse($condition->shouldEscapeLeft());
        $this->assertSame($condition->getOperator(), 'or');
        $this->assertEquals($condition->getRight(), Condition::combineOr($conditionB, $conditionA));
        $this->assertFalse($condition->shouldEscapeRight());
    }
}
