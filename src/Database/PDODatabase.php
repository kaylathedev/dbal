<?php
namespace DBAL\Database;

use \Exception;
use \PDO;
use \PDOException;
use \PDOStatement;
use \DBAL\Exceptions\DatabaseException;

class PDODatabase extends AbstractSQLDatabase
{

    private static function bindToStatement(PDOStatement $stmt, array $bindVariables)
    {
        if (count(array_filter(array_keys($bindVariables), 'is_string'))) {
            foreach ($bindVariables as $key => $variable) {
                $stmt->bindValue($key, $variable);
            }
        } else {
            $index = 0;
            $count = count($bindVariables);
            while ($index < $count) {
                $stmt->bindValue($index + 1, $bindVariables[$index]);
                $index++;
            }
        }
    }

    private $connection;
    private $dsn;
    private $username;
    private $password;
    private $options;
    private $defaults = [
        PDO::ATTR_STRINGIFY_FETCHES => true
    ];

    /**
     * Creates a new PDODatabase with the "Data Source Name", username, password, and extra options.
     *
     * The arguments MUST be compatiable with the PDO constructor.
     *
     * @param string      $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array       $options
     */
    public function __construct($dsn, $username = null, $password = null, array $options = [])
    {
        $this->dsn      = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->options  = $options;
        parent::__construct();
    }

    /**
     * @return PDO|null
     */
    public function getConnection()
    {
        $this->initalizeConnection();

        return $this->connection;
    }

    /**
     * Gets the connection string for PDO.
     *
     * @return string
     */
    public function getConnectionString()
    {
        return $this->dsn;
    }

    /**
     * Returns the username for PDO.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Returns the password for PDO.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return integer
     */
    public function lastIdCreated()
    {
        $this->initalizeConnection();
        return (int) $this->connection->lastInsertId();
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
        try {
            $stmt = $this->getConnection()->prepare($query);
            if ($stmt !== false) {
                self::bindToStatement($stmt, $bindings);
                if ($stmt->execute()) {
                    if (0 !== $stmt->columnCount()) {
                        /* We need to return some data. */
                        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($data !== false) {
                            return $data;
                        }
                    } else {
                        return null;
                    }
                }
                $errorInfo = $stmt->errorInfo();
                $error     = $errorInfo[2];

                $message = 'Unable to query the database! Error: {' . $error . '} SQL: {'
                    . $query . '}';
                throw $this->logException(new DatabaseException($message));
            }
        } catch (PDOException $e) {
            $this->getLogger()->error($e);
            throw $this->logException(new DatabaseException(
                'Unable to execute query! ' . $e->getMessage(),
                0,
                $e
            ));
        }
        $errorInfo = $this->connection->errorInfo();
        $error     = $errorInfo[2];
        throw $this->logException(new DatabaseException(
            'Unable to prepare query! Error: {' . $error . '}'
        ));
    }

    private function initalizeConnection()
    {
        if ($this->connection === null) {
            try {
                $this->connection = new PDO(
                    $this->getConnectionString(),
                    $this->getUsername(),
                    $this->getPassword(),
                    $this->defaults + $this->getOptions()
                );
            } catch (PDOException $error) {
                $this->getLogger()->error($error);
                $this->connection = null;
                throw $this->logException(
                    new DatabaseException('Unable to connect to database!', 0, $error)
                );
            }
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_TIMEOUT, 5);
        }
    }
}
