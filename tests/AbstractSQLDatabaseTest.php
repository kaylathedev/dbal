<?php
namespace DBAL\Tests;

use DBAL\Condition;
use DBAL\Database\MySQLDatabase;

abstract class AbstractSQLDatabaseTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $entryForObama = [
            'first_name' => 'Barack',
            'last_name' => 'Obama',
            'birth_place' => 'Hawaii',
            'birth_date' => '8/4/1961'
        ];

        $db = $this->buildDatabase();

        $db->create('presidents', $entryForObama);

        $expectedData = $this->initialTestData;
        $expectedData[5] = array_merge(['id' => '6'], $entryForObama);

        $this->assertTableContainsOnly('testing.presidents', $expectedData);
    }

    public function testLastIdCreated()
    {
        $entryForObama = [
            'first_name' => 'Barack',
            'last_name' => 'Obama',
            'birth_place' => 'Hawaii',
            'birth_date' => '8/4/1961'
        ];

        $db = $this->buildDatabase();

        $db->create('presidents', $entryForObama);

        $id = $db->lastIdCreated();
        $this->assertGreaterThan(0, $id);
        $this->assertInternalType('int', $id);
    }

    public function testSpecificLastIdCreated()
    {
        $entryForObama = [
            'id' => 9,
            'first_name' => 'Barack',
            'last_name' => 'Obama',
            'birth_place' => 'Hawaii',
            'birth_date' => '8/4/1961'
        ];

        $db = $this->buildDatabase();

        $db->create('presidents', $entryForObama);

        $this->assertSame(9, $db->lastIdCreated());
    }

    /* These are for SQL databases in general */

    public function testUpdate()
    {
        $dataToUpdate = [
            'birth_place' => 'New England'
        ];

        $db = $this->buildDatabase();

        $condition = Condition::equals('birth_date', '2/22/1732');
        $db->update('presidents', $dataToUpdate, $condition);

        $expectedData = $this->initialTestData;
        $expectedData[0]['birth_place'] = 'New England';

        $this->assertTableContainsOnly('testing.presidents', $expectedData);
    }

    public function testDelete()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('birth_date', '2/22/1732');
        $db->delete('presidents', $condition);

        $expectedData = $this->initialTestData;
        unset($expectedData[0]);

        $this->assertTableContainsOnly('testing.presidents', $expectedData);
    }

    /* Retrieving Data */

    public function testFind()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('birth_date', '2/22/1732');
        $entry = $db->find('presidents', $condition);

        $expected = $this->initialTestData[0];

        $this->assertEquals($entry, $expected);
    }

    public function testFindWithNoResults()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('birth_date', '12/26/1014');
        $entry = $db->find('presidents', $condition);

        $this->assertNull($entry);
    }

    public function testFindAll()
    {
        $db = $this->buildDatabase();

        $entries = $db->findAll('presidents');

        $expected = $this->initialTestData;

        $this->assertEquals($entries, $expected);
        foreach ($expected as $key => $row) {
            $this->assertSame($entries[$key], $row);
        }
    }

    public function testFindAllWithCondition()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('first_name', 'George');
        $entry = $db->findAll('presidents', $condition);

        $expected = [
            $this->initialTestData[0],
            $this->initialTestData[4]
        ];

        $this->assertEquals($entry, $expected);
    }

    public function testFindWithLimit()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('birth_place', 'Virginia');
        $entry = $db->findAll('presidents', $condition, ['limit' => 2]);

        $expected = [
            $this->initialTestData[0],
            $this->initialTestData[1]
        ];

        $this->assertEquals($entry, $expected);
    }

    public function testCount()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('last_name', 'Washington');
        $entry = $db->count('presidents', $condition);

        $this->assertSame($entry, 1);
    }

    public function testCountWithMany()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('first_name', 'George');
        $entry = $db->count('presidents', $condition);

        $this->assertSame($entry, 2);
    }

    public function testCountWithNoResults()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('last_name', 'Bob');
        $entry = $db->count('presidents', $condition);

        $this->assertSame($entry, 0);
    }

    public function testHas()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('last_name', 'Washington');
        $entry = $db->has('presidents', $condition);

        $this->assertTrue($entry);
    }

    public function testHasWithNoResults()
    {
        $db = $this->buildDatabase();

        $condition = Condition::equals('last_name', 'Bob');
        $entry = $db->has('presidents', $condition);

        $this->assertFalse($entry);
    }

}
