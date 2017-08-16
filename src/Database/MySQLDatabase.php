<?php
namespace DBAL\Database;

use \Exception;
use \mysqli;
use \mysqli_result;
use \mysqli_sql_exception;
use \mysqli_stmt;
use \DBAL\Exceptions\DatabaseException;

/**
 * Defines a connection to a database using the mysqli extension.
 * Broken
 *
 * Please use PDO if you are able to.
 */
class MySQLDatabase extends AbstractSQLDatabase
{

    private static function bindToStatement(mysqli_stmt $statement, array $bindings)
    {
        $types = '';
        foreach ($bindings as $binding) {
            if (is_int($binding) || is_long($binding)) {
                $types .= 'i';
            } elseif (is_double($binding) || is_float($binding)) {
                $types .= 'd';
            } elseif (is_string($binding)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }

        $arguments = [$types];
        foreach ($bindings as &$binding) {
            $arguments[] =& $binding;
        }
        call_user_func_array([$statement, 'bind_param'], $arguments);
    }

    private $connection;
    private $host         = 'localhost';
    private $port         = 3306;
    private $username     = 'root';
    private $password     = '';
    private $databaseName = '';
    private $options      = [];

    /**
     * Initalizes this class, so a connection can be made to the database. The first argument is a
     * url. If url is not null, the connection parameters will be decoded from the url.
     *
     * The provided url will contain the username, password, hostname, port, and the database name.
     * Take the following url for example.
     *
     * root:secret@mysql.example.com:3603/administration
     *
     * It will connect to mysql.example.com at port 3603. It will use the username root, and use
     * the password secret. The database is named administration.
     * Please note that the scheme and fragment will be ignored.
     *
     * @param string|null $url
     */
    public function __construct($url = null)
    {
        if (null !== $url) {
            if (!filter_var($url, \FILTER_VALIDATE_URL)) {
                $url = 'mysql://' . $url;
            }
            $parsed = parse_url($url);
            if (isset($parsed['host'])) {
                $this->host = $parsed['host'];
            }
            if (isset($parsed['port'])) {
                $this->port = $parsed['port'];
            }
            if (isset($parsed['user'])) {
                $this->username = $parsed['user'];
            }
            if (isset($parsed['pass'])) {
                $this->password = $parsed['pass'];
            }
            if (isset($parsed['path'])) {
                $this->databaseName = trim($parsed['path'], '/');
            }
        }
        parent::__construct();
    }

    /**
     * @return mysqli|null
     */
    public function getConnection()
    {
        $this->initalizeConnection();

        return $this->connection;
    }

    /**
     * Returns the host address for the MySQL client.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setHost($value)
    {
        $this->host = $value;
    }

    /**
     * Returns the port number for the MySQL client.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $value
     *
     * @return void
     */
    public function setPort($value)
    {
        $this->port = $value;
    }

    /**
     * Returns the username for the MySQL client.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the username for the MySQL client.
     *
     * @param string $newUsername
     */
    public function setUsername($newUsername)
    {
        $this->username = $newUsername;
    }

    /**
     * Returns the password for the MySQL client.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Sets the password for the MySQL client.
     *
     * @param string $newPassword
     */
    public function setPassword($newPassword)
    {
        $this->password = $newPassword;
    }

    /**
     * Returns the database's name for the MySQL client.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * Sets the database's name for the MySQL client.
     *
     * @param string $databaseName
     */
    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        return $this->options;
    }

    /**
     * @return mixed
     */
    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    public function setOption($key, $newOption)
    {
        $this->options[$key] = $newOption;
    }

    /**
     * @return integer
     */
    public function lastIdCreated()
    {
        return (int) $this->getConnection()->insert_id;
    }

    /**
     * @return array[]
     */
    private static function processResult(mysqli_result $result)
    {
        $data = [];
        while (null !== ($row = $result->fetch_assoc())) {
            $data[] = $row;
        }
        $result->free();
        return $data;
    }

    /**
     * @param SqlStatementBuilder $statement
     * @param array               $options
     * @param bool                $returnResult
     * @return array[]|null
     *
     * @throws DatabaseException If there was an error connecting to the database.
     */
    protected function sendQueryToDatabase(SqlStatementBuilder $statement, array $options, $returnResult)
    {
        $compiled = self::compileQueriesAndBindings($statement, $options);
        $query    = $compiled[0];
        $bindings = $compiled[1];

        $this->initalizeConnection();
        if (count($bindings) !== 0) {
            // We are using prepared statements

            // Prepare
            $statement = $this->connection->prepare($query);
            if ($statement === false) {
                throw $this->logException(new DatabaseException(
                    'Unable to execute query! Error: ' . $this->connection->error
                ));
            }

            // Bind all of the variables
            self::bindToStatement($statement, $bindings);

            // Execute
            if (!$statement->execute()) {
                $message = 'Unable to execute statement! Error: ' . $this->connection->error;
                throw $this->logException(new DatabaseException($message));
            }

            if (!$returnResult) {
                return;
            }
            // Get result
            $result = $statement->get_result();
        } else {
            // There are no prepared values to bind.
            if (!$returnResult) {
                $this->connection->query($query);
                return;
            }
            $result = $this->connection->query($query);
        }
        if (!($result instanceof mysqli_result)) {
            $message = 'Unable to get result! Error: ' . $this->connection->error;
            throw $this->logException(new DatabaseException($message));
        }
        return self::processResult($result);
    }

    private function initalizeConnection()
    {
        if ($this->connection === null) {
            /* Instructs mysqli to throw exceptions instead of using errors. */
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                $this->connection = new mysqli(
                    $this->host,
                    $this->username,
                    $this->password,
                    $this->databaseName,
                    $this->port
                );
                if ($this->connection->connect_errno) {
                    $error            = $this->connection->connect_error;
                    $this->connection = null;
                    throw new DatabaseException('Unable to connect to database! Error: ' . $error);
                }
                foreach ($this->options as $key => $value) {
                    $this->connection->options($key, $value);
                }
            } catch (mysqli_sql_exception $e) {
                throw $this->logException(
                    new DatabaseException(
                        'Error while connecting to the database!',
                        0,
                        $e
                    )
                );
            }
        }
    }
}
