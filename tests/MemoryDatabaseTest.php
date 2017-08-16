<?php
namespace DBAL\Tests;

use DBAL\Condition;
use DBAL\Database\MemoryDatabase;

class MemoryDatabaseTest extends \PHPUnit_Framework_TestCase
{

    private $presidents;

    private function buildDatabase()
    {
        $presidents = [
            1 => [
                'id' => 1,
                'first_name' => 'George',
                'last_name' => 'Washington',
                'birth_place' => 'Virginia',
                'birth_date' => '2/22/1732',
            ],
            2 => [
                'id' => 2,
                'first_name' => 'John',
                'last_name' => 'Adams',
                'birth_place' => 'Massachusetts',
                'birth_date' => '10/30/1735',
            ],
            3 => [
                'id' => 3,
                'first_name' => 'Thomas',
                'last_name' => 'Jefferson',
                'birth_place' => 'Virginia',
                'birth_date' => '4/13/1743',
            ],
            16 => [
                'id' => 16,
                'first_name' => 'Abraham',
                'last_name' => 'Lincoln',
                'birth_place' => 'Kentucky',
                'birth_date' => '2/12/1809',
            ],
            28 => [
                'id' => 28,
                'first_name' => 'Woodrow',
                'last_name' => 'Wilson',
                'birth_place' => 'Virginia',
                'birth_date' => '12/28/1856',
            ],
            41 => [
                'id' => 4,
                'first_name' => 'George',
                'last_name' => 'Bush',
                'birth_place' => 'Massachusetts',
                'birth_date' => '6/12/1924',
            ],
            43 => [
                'id' => 4,
                'first_name' => 'George',
                'last_name' => 'Bush',
                'birth_place' => 'Connecticut',
                'birth_date' => '7/6/1946',
            ],
        ];
        $this->presidents = $presidents;
        return new MemoryDatabase('id', ['test' => $presidents]);
    }

    public function testDumpRawData()
    {
        $db = $this->buildDatabase();

        $this->assertSame(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testLastIdCreatedWithNothing()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->lastIdCreated());
    }

    public function testCreateWithNothing()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->create('test', ['name' => 'test']));

        $id = $db->lastIdCreated();
        $this->assertGreaterThan(0, $id);
        $this->assertInternalType('int', $id);

        $presidents = $this->presidents;
        $presidents[$id] = ['id' => $id, 'name' => 'test'];
        $this->assertEquals(['test' => $presidents], $db->dumpRawData());
    }

    public function testCreate()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->create('test', []));

        $id = $db->lastIdCreated();
        $this->assertGreaterThan(0, $id);
        $this->assertInternalType('int', $id);

        $presidents = $this->presidents;
        $presidents[$id] = ['id' => $id];
        $this->assertEquals(['test' => $presidents], $db->dumpRawData());
    }

    public function testUpdateWithNothing()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->update('test', []));
        $this->assertEquals(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testUpdate()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->update('test', ['birth_date' => '1/1/2000'], Condition::equals('last_name', 'Lincoln')));

        $this->presidents[16]['birth_date'] = '1/1/2000';
        $this->assertEquals(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testSpecificUpdate()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->update('test',
            ['birth_date' => '1/1/2000'],
            Condition::combineAnd(
                Condition::equals('first_name', 'George'),
                Condition::equals('birth_place', 'Massachusetts')
            )
        ));

        $this->presidents[41]['birth_date'] = '1/1/2000';
        $this->assertEquals(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testMultipleUpdate()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->update('test',
            ['birth_date' => '1/1/2000'],
            Condition::combineOr(
                Condition::equals('first_name', 'George'),
                Condition::equals('birth_place', 'Kentucky')
            )
        ));

        $this->presidents[1]['birth_date'] = '1/1/2000';
        $this->presidents[16]['birth_date'] = '1/1/2000';
        $this->presidents[41]['birth_date'] = '1/1/2000';
        $this->presidents[43]['birth_date'] = '1/1/2000';
        $this->assertEquals(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testUpdateAll()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->update('test', ['birth_date' => '1/1/2000']));

        $this->presidents[1]['birth_date'] = '1/1/2000';
        $this->presidents[2]['birth_date'] = '1/1/2000';
        $this->presidents[3]['birth_date'] = '1/1/2000';
        $this->presidents[16]['birth_date'] = '1/1/2000';
        $this->presidents[28]['birth_date'] = '1/1/2000';
        $this->presidents[41]['birth_date'] = '1/1/2000';
        $this->presidents[43]['birth_date'] = '1/1/2000';
        $this->assertEquals(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testDelete()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->delete('test', Condition::equals('last_name', 'Lincoln')));

        unset($this->presidents[16]);
        $this->assertEquals(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testSpecificDelete()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->delete('test', Condition::combineAnd(
            Condition::equals('first_name', 'George'),
            Condition::equals('birth_place', 'Massachusetts')
        )));

        unset($this->presidents[41]);
        $this->assertEquals(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testMultipleDelete()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->delete('test', Condition::combineOr(
            Condition::equals('first_name', 'George'),
            Condition::equals('birth_place', 'Kentucky')
        )));

        unset($this->presidents[1]);
        unset($this->presidents[16]);
        unset($this->presidents[41]);
        unset($this->presidents[43]);
        $this->assertEquals(['test' => $this->presidents], $db->dumpRawData());
    }

    public function testDeleteAll()
    {
        $db = $this->buildDatabase();
        $this->assertNull($db->delete('test'));
        $this->assertEquals(['test' => []], $db->dumpRawData());
    }

    public function testFindFirst()
    {
        $db = $this->buildDatabase();
        $this->assertEquals($this->presidents[1], $db->find('test'));
    }

    public function testFind()
    {
        $db = $this->buildDatabase();
        $this->assertEquals($this->presidents[16], $db->find('test', Condition::equals('last_name', 'Lincoln')));
    }

    public function testFindAll()
    {
        $db = $this->buildDatabase();
        $this->assertEquals($this->presidents, $db->findAll('test'));
    }

    public function testFindWithNullLimit()
    {
        $db = $this->buildDatabase();
        $this->assertEquals($this->presidents, $db->findAll('test', null, ['limit' => null]));
    }

    public function testFindWithZeroLimit()
    {
        $db = $this->buildDatabase();
        $this->assertEquals([], $db->findAll('test', null, ['limit' => 0]));
    }

    public function testFindWithOneLimit()
    {
        $db = $this->buildDatabase();
        $expected = [
            1 => $this->presidents[1],
        ];
        $this->assertEquals($expected, $db->findAll('test', null, ['limit' => 1]));
    }

    public function testFindWithTwoLimits()
    {
        $db = $this->buildDatabase();
        $expected = [
            1 => $this->presidents[1],
            2 => $this->presidents[2],
        ];
        $this->assertEquals($expected, $db->findAll('test', null, ['limit' => 2]));
    }

    public function testCountAll()
    {
        $db = $this->buildDatabase();
        $this->assertSame(count($this->presidents), $db->count('test'));
    }

    public function testHasAny()
    {
        $db = $this->buildDatabase();
        $this->assertTrue($db->has('test'));
    }

    public function testHas()
    {
        $db = $this->buildDatabase();
        $this->assertTrue($db->has('test', Condition::equals('last_name', 'Lincoln')));
    }

    public function testHasSomethingNonExistent()
    {
        $db = $this->buildDatabase();
        $this->assertFalse($db->has('test', Condition::equals('last_name', 'Lincoln2')));
    }
}
