<?php
namespace DBAL\Tests;

use PDO;
use DBAL\Condition;
use DBAL\Database\PDODatabase;

class PDODatabaseTest extends AbstractSQLDatabaseTest
{

    private $connection;

    const EXAMPLE_CONNECTION_STRING = 'mysql:host=example.com;dbname=myDatabase';
    const EXAMPLE_USERNAME = 'myUsername';
    const EXAMPLE_PASSWORD = 'myPassword';

    protected function buildDatabase()
    {
        $host         = $_ENV['test.mysql.host'];
        $username     = $_ENV['test.mysql.username'];
        $password     = $_ENV['test.mysql.password'];
        $port         = $_ENV['test.mysql.port'];
        $databaseName = $_ENV['test.mysql.database'];

        $this->connection = new PDO(
            'mysql:host=' . $host . ';port=' . $port,
            $username,
            $password
        );
        if (false === $this->connection->query('
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

        return new PDODatabase(
            'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $databaseName,
            $username,
            $password
        );
    }

    public function testConstructor()
    {
        $options = ['test' => 'test2'];
        $db = new PDODatabase(self::EXAMPLE_CONNECTION_STRING, self::EXAMPLE_USERNAME, self::EXAMPLE_PASSWORD, $options);

        $this->assertSame(self::EXAMPLE_CONNECTION_STRING, $db->getConnectionString());
        $this->assertSame(self::EXAMPLE_USERNAME, $db->getUsername());
        $this->assertSame(self::EXAMPLE_PASSWORD, $db->getPassword());
        $this->assertSame($options, $db->getOptions());
    }

    public function testDefaults()
    {
        $db = new PDODatabase(self::EXAMPLE_CONNECTION_STRING);

        $this->assertEquals(self::EXAMPLE_CONNECTION_STRING, $db->getConnectionString());
        $this->assertNull($db->getUsername());
        $this->assertNull($db->getPassword());
        $this->assertSame([], $db->getOptions());
    }

    public function testGetAllOptionsWithNothing()
    {
        $db = new PDODatabase(self::EXAMPLE_CONNECTION_STRING);

        $this->assertSame([], $db->getOptions());
    }

    public function testGetConnectionWithImaginaryServer()
    {
        $db = new PDODatabase('mysql:host=this.domain.must.not.ever.exist');

        $this->setExpectedException(
            'DBAL\Exceptions\DatabaseException'
        );
        $db->getConnection();
    }

    /* The tests starting from here must have a MySQL database available. */

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

        $this->assertInstanceOf('PDO', $raw);
    }

    protected function assertTableContainsOnly($tableName, $expectedData)
    {
        $result = $this->connection->query('select * from ' . $tableName);
        if (false === $result) {
            $this->fail($this->connection->errorInfo()[2]);
        }
        $data = $result->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame($data, array_values($expectedData));
    }
}
