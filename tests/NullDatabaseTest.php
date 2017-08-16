<?php
namespace DBAL\Tests;

use DBAL\Database\NullDatabase;

class NullDatabaseTest extends \PHPUnit_Framework_TestCase
{

    private function buildDatabase()
    {
        return new NullDatabase();
    }

    public function testLastIdCreated()
    {
        $db = $this->buildDatabase();
        $this->assertSame(null, $db->lastIdCreated());
    }

    public function testCreated()
    {
        $db = $this->buildDatabase();
        $this->assertSame(null, $db->create('test', []));
    }

    public function testUpdate()
    {
        $db = $this->buildDatabase();
        $this->assertSame(null, $db->update('test', []));
    }

    public function testDelete()
    {
        $db = $this->buildDatabase();
        $this->assertSame(null, $db->delete('test'));
    }

    public function testFind()
    {
        $db = $this->buildDatabase();
        $this->assertSame(null, $db->find('test'));
    }

    public function testFindAll()
    {
        $db = $this->buildDatabase();
        $this->assertSame([], $db->findAll('test'));
    }

    public function testFindWithLimit()
    {
        $db = $this->buildDatabase();
        $this->assertSame([], $db->findWithLimit('test'));
    }

    public function count()
    {
        $db = $this->buildDatabase();
        $this->assertSame(0, $db->count('test'));
    }

    public function has()
    {
        $db = $this->buildDatabase();
        $this->assertSame(false, $db->has('test'));
    }
}
