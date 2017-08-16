<?php
namespace DBAL\Tests;

use DBAL\Condition;
use DBAL\Database\SqlStatementBuilder;

class SqlStatementBuilderTest extends \PHPUnit_Framework_TestCase
{

    private function getStatementBuilder()
    {
        return new SqlStatementBuilder();
    }

    private function buildData()
    {
        return [
            'id' => 1,
            'name' => 'Abraham Lincoln',
            'birth_date' => '2/12/1809',
            'death_place' => ''
        ];
    }

    private function buildDataToUpdate()
    {
        return [
            'name' => 'Abe Lincoln',
            'death_place' => 'A theatre.'
        ];
    }

    public function testCreate()
    {
        $data = $this->buildData();

        $statement = $this->getStatementBuilder();
        $statement->create('people', $data);

        $this->assertSame($statement->queries, [
            'insert into people(id, name, birth_date, death_place) values(?, ?, ?, ?)'
        ]);
        $this->assertSame($statement->bindings, [
            $data['id'],
            $data['name'],
            $data['birth_date'],
            $data['death_place']
        ]);
    }

    public function testSelect()
    {
        $statement = $this->getStatementBuilder();
        $statement->select(['*'], 'people');

        $this->assertSame($statement->queries, [
            'select * from people'
        ]);
        $this->assertSame($statement->bindings, []);
    }

    public function testSelectWithCondition()
    {
        $statement = $this->getStatementBuilder();
        $statement->select(['*'], 'people', Condition::equals('name', 'Bob'));

        $this->assertSame($statement->queries, [
            'select * from people where name = ?'
        ]);
        $this->assertSame($statement->bindings, [
            'Bob'
        ]);
    }

    public function testSelectWithConditionAndLimit()
    {
        $statement = $this->getStatementBuilder();
        $statement->select(['*'], 'people', Condition::equals('name', 'Bob'), 68);

        $this->assertSame($statement->queries, [
            'select * from people where name = ? limit 68'
        ]);
        $this->assertSame($statement->bindings, [
            'Bob'
        ]);
    }

    public function testUpdate()
    {
        $data = $this->buildDataToUpdate();

        $statement = $this->getStatementBuilder();
        $statement->update('people', $data, Condition::equals('id', 1));

        $this->assertSame($statement->queries, [
            'update people set name = ?, death_place = ? where id = ?'
        ]);
        $this->assertSame($statement->bindings, [
            $data['name'],
            $data['death_place'],
            1
        ]);
    }

    public function testDelete()
    {
        $statement = $this->getStatementBuilder();
        $statement->delete('people', Condition::equals('id', 1));

        $this->assertSame($statement->queries, [
            'delete from people where id = ?'
        ]);
        $this->assertSame($statement->bindings, [
            1,
        ]);
    }
}
