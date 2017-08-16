<?php
namespace DBAL\Tests;

use mysqli;
use DBAL\Condition;
use DBAL\Database\MySQLDatabase;

class MySQLDatabaseTest extends AbstractSQLDatabaseTest
{

    private $connection;

    protected function buildDatabase()
    {
        $host         = $_ENV['test.mysql.host'];
        $username     = $_ENV['test.mysql.username'];
        $password     = $_ENV['test.mysql.password'];
        $port         = $_ENV['test.mysql.port'];
        $databaseName = $_ENV['test.mysql.database'];

        $connection = new mysqli(
            $host,
            $username,
            $password,
            '',
            $port
        );
        $this->connection = $connection;
        if (false === $this->connection->multi_query('
            create database if not exists testing;
            drop table if exists testing.presidents;
            create table if not exists testing.presidents (
                id int(11) not null auto_increment,
                first_name varchar(255) not null,
                last_name varchar(255) not null,
                birth_place varchar(255) not null,
                birth_date varchar(255) not null,
                primary key(id)
            );
            insert into testing.presidents
            (first_name, last_name, birth_place, birth_date) values
            ("George", "Washington", "Virginia", "2/22/1732"),
            ("Thomas", "Jefferson", "Virginia", "4/13/1742"),
            ("Abraham", "Lincoln", "Kentucky", "2/12/1809"),
            ("Woodrow", "Wilson", "Virginia", "12/28/1856"),
            ("George", "Bush", "Connecticut", "7/6/1946");
        ')) {
            $this->fail('Failed to set up the database! Error: ' . $this->connection->error);
        }

        /* We must cycle through all of the "results",
           or we will get an "Command's out of sync" error. */
        while ($this->connection->more_results()) {
            $this->connection->next_result(); /* No-op */
        }

        $db = new MySQLDatabase($username . ':'
                                . $password . '@'
                                . $host . ':'
                                . $port
                                . '/' . $databaseName);
        return $db;
    }

    public function testConstructor()
    {
        $db = new MySQLDatabase('myUsername:myPassword@example.com/myDatabase');

        $this->assertSame('myUsername', $db->getUsername());
        $this->assertSame('myPassword', $db->getPassword());
        $this->assertSame('example.com', $db->getHost());
        $this->assertSame(3306, $db->getPort());
        $this->assertSame('myDatabase', $db->getDatabaseName());
    }

    public function testDefaults()
    {
        $db = new MySQLDatabase();

        $this->assertSame('root', $db->getUsername());
        $this->assertSame('', $db->getPassword());
        $this->assertSame('localhost', $db->getHost());
        $this->assertSame(3306, $db->getPort());
        $this->assertSame('', $db->getDatabaseName());
    }

    public function testHost()
    {
        $db = new MySQLDatabase();
        $db->setHost('example.com');

        $this->assertSame('example.com', $db->getHost());
    }

    public function testPort()
    {
        $db = new MySQLDatabase();
        $db->setPort(3306);

        $this->assertSame(3306, $db->getPort());
    }

    public function testUsername()
    {
        $db = new MySQLDatabase();
        $db->setUsername('myUsername');

        $this->assertSame('myUsername', $db->getUsername());
    }

    public function testPassword()
    {
        $db = new MySQLDatabase();
        $db->setPassword('myPassword');

        $this->assertSame('myPassword', $db->getPassword());
    }

    public function testDatabaseName()
    {
        $db = new MySQLDatabase();
        $db->setDatabaseName('myDatabase');

        $this->assertSame('myDatabase', $db->getDatabaseName());
    }

    public function testGetAllOptionsWithNothing()
    {
        $db = new MySQLDatabase();

        $this->assertSame([], $db->getAllOptions());
    }

    public function testGetOptionWithNothing()
    {
        $db = new MySQLDatabase();

        $this->assertNull($db->getOption('keyHere'));
        $this->assertNull($db->getOption(null));
    }

    public function testSetOption()
    {
        $keyName  = 'keyHere';
        $keyValue = 'Value Here';

        $db = new MySQLDatabase();
        $db->setOption($keyName, $keyValue);

        $this->assertSame($keyValue, $db->getOption($keyName));
    }

    public function testGetConnectionWithImaginaryServer()
    {
        $db = new MySQLDatabase();
        $db->setHost('this.domain.must.not.ever.exist');

        $this->setExpectedException(
            'DBAL\Exceptions\DatabaseException'
        );
        $db->getConnection();
    }

    /* The tests from here on out must have a MySQL database available. */

    protected $initialTestData = [
        0 => [
            'id' => '1',
            'first_name' => 'George',
            'last_name' => 'Washington',
            'birth_place' => 'Virginia',
            'birth_date' => '2/22/1732'
        ],
        1 => [
            'id' => '2',
            'first_name' => 'Thomas',
            'last_name' => 'Jefferson',
            'birth_place' => 'Virginia',
            'birth_date' => '4/13/1742'
        ],
        2 => [
            'id' => '3',
            'first_name' => 'Abraham',
            'last_name' => 'Lincoln',
            'birth_place' => 'Kentucky',
            'birth_date' => '2/12/1809'
        ],
        3 => [
            'id' => '4',
            'first_name' => 'Woodrow',
            'last_name' => 'Wilson',
            'birth_place' => 'Virginia',
            'birth_date' => '12/28/1856'
        ],
        4 => [
            'id' => '5',
            'first_name' => 'George',
            'last_name' => 'Bush',
            'birth_place' => 'Connecticut',
            'birth_date' => '7/6/1946'
        ]
    ];

    public function testGetConnection()
    {
        $db = $this->buildDatabase();
        $raw = $db->getConnection();

        $this->assertInstanceOf('mysqli', $raw);
    }

    protected function assertTableContainsOnly($tableName, $expectedData)
    {
        $result = $this->connection->query('select * from ' . $tableName);
        if (false === $result) {
            $this->fail($this->connection->errorInfo()[2]);
        }
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $this->assertSame($data, array_values($expectedData));
    }
}
