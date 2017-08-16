<?php
namespace DBAL;

use \ICanBoogie\Inflector;
use \ReflectionClass;
use \RuntimeException;

/**
 * Developers may use this class to describe an entity in the database.
 * 
 * For example, your database may be used for a factory. Therefore, your entities
 * may be Factory, AssemblyLine, and Product.
 * 
 * The Factory may represent any specific factory, and could have an address, phone
 * number, and the owner's name.
 */
abstract class AbstractEntity
{

    /**
     * @param string $camelCasing
     * @param Inflector|null $inflector
     * 
     * @return string
     */
    protected static function tableize($camelCasing, $inflector = null)
    {
        if (null === $inflector) {
            $inflector = Inflector::get();
        }
        $underscored = $inflector->underscore($camelCasing);
        if ('_table' === substr($underscored, -6)) {
            $underscored = substr($underscored, 0, strlen($underscored) - 6);
        }
        return $inflector->pluralize($underscored);
    }

    private $tableName;
    private $messageStack = [];

    /**
     * @param string|null $tableName
     */
    public function __construct($tableName = null)
    {
        if (null === $tableName) {
            $shortClassName = (new ReflectionClass($this))->getShortName();
            $tableName      = self::tableize($shortClassName);
        }
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($value)
    {
        $this->tableName = $value;
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
     * @param mixed $value
     */
    public function addMessage($value)
    {
        $this->messageStack[] = $value;
    }
}
