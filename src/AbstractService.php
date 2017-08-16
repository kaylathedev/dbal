<?php
namespace DBAL;

use \ICanBoogie\Inflector;
use \ReflectionClass;
use \RuntimeException;

use DBAL\Database\AbstractDatabase;

/**
 * Represents a collection of a specific type of entity.
 *
 * This class contains methods for writing and reading data to and from an
 * AbstractDatabase.
 */
abstract class AbstractService
{

    private $dataSource;
    private $messageStack = [];

    protected $table;

    public function __construct(AbstractDatabase $source)
    {
        $this->dataSource = $source;
    }

    /**
     * @return AbstractDatabase
     */
    protected function getDatabase()
    {
        return $this->dataSource;
    }

    /**
     * @return mixed
     */
    public function getLastMessage()
    {
        return array_shift($this->messageStack);
    }

    /**
     * @return array[mixed]
     */
    public function getAllMessages()
    {
        $data               = $this->messageStack;
        $this->messageStack = [];
        return $data;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function addMessage($value)
    {
        $this->messageStack[] = $value;
    }

    /**
     * @param mixed $data
     *
     * @return boolean
     */
    protected function isValidEmail($data)
    {
        return filter_var($data, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @param mixed $data
     *
     * @return boolean
     */
    protected function isValidUrl($data)
    {
        return filter_var($data, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param mixed $data
     *
     * @return boolean
     */
    protected function isValidInteger($data)
    {
        return filter_var($data, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * @param mixed $data
     *
     * @return boolean
     */
    protected function isValidDecimal($data)
    {
        return filter_var($data, FILTER_VALIDATE_FLOAT) !== false;
    }

    /**
     * @param mixed $data
     *
     * @return boolean
     */
    protected function isValidBoolean($data)
    {
        return filter_var($data, FILTER_VALIDATE_BOOLEAN) !== false;
    }

    /**
     * @return void
     */
    public function delete(Condition $criteria = null)
    {
        $this->dataSource->delete($this->table, $criteria);
    }

    /**
     * @return void
     */
    protected function create(array $fields)
    {
        $this->dataSource->create($this->table, $fields);
    }

    /**
     * @return void
     */
    protected function update(array $fields, Condition $criteria = null)
    {
        $this->dataSource->update($this->table, $fields, $criteria);
    }

    /**
     * @return array|null
     */
    protected function find(Condition $criteria = null)
    {
        return $this->dataSource->find($this->table, $criteria);
    }

    /**
     * @return array|null
     */
    protected function findWithLimit($limit, Condition $criteria = null)
    {
        return $this->dataSource->findWithLimit($this->table, $limit, $criteria);
    }

    /**
     * @return array|null
     */
    protected function findAll(Condition $criteria = null)
    {
        return $this->dataSource->findAll($this->table, $criteria);
    }

    /**
     * @return int
     */
    protected function count(Condition $criteria = null)
    {
        return $this->dataSource->count($this->table, $criteria);
    }

    /**
     * @return boolean
     */
    protected function has(Condition $criteria = null)
    {
        return $this->dataSource->has($this->table, $criteria);
    }

    /**
     * @return mixed
     */
    protected function lastIdCreated()
    {
        return $this->dataSource->lastIdCreated();
    }
}
