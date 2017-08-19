<?php
namespace DBAL\Service;

use \RuntimeException;
use \DBAL\AbstractDatabase;
use \DBAL\Condition;
use \DBAL\AbstractService;

/**
 * This class recognizes these tables.
 *
 * roles
 * -> id [int] "The phrase referring to what's allowed."
 * -> name [string] "The role to add the permission to."
 *
 * permissions_of_roles
 * -> role [string] "The role to add the permission to."
 * -> permission [string] "The phrase referring to what's allowed."
 * -> allowed [int] "1 to allow, and 0 to deny."
 *
 * permissions_of_entities
 * -> entity_id [string] "The entity to add the permission to."
 * -> permission [string] "The phrase referring to what's allowed."
 * -> allowed [int] "1 to allow, and 0 to deny."
 *
 * roles_of_entities
 * -> entity_id [string] "A unique id referring to any entity in a given system."
 * -> role [string] "The role being given to the entity."
 */

class AccessControlListService extends AbstractService
{

    /* TODO: Allow developer to change the table name. */
    private $prefix;
    private $permissionsOfRolesTableName    = 'permissions_of_roles';
    private $permissionsOfEntitiesTableName = 'permissions_of_entities';
    private $rolesOfEntitiesTableName       = 'roles_of_entities';

    /**
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string|null     $prefix
     */
    public function setPrefix($prefix = null)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return array[mixed]
     */
    public function getRolesOfEntity($id)
    {
        $data    = [];
        $entries = $this->findAll(
            $this->getRolesOfEntitiesTableName(),
            Condition::equals('entity_id', $id)
        );

        foreach ($entries as $entry) {
            $data[] = $entry['role'];
        }

        return $data;
    }

    /**
     * @return array[mixed]
     */
    public function getAllRolesUsed()
    {
        $roles1 = $this->findAll($this->getPermissionsOfRolesTableName());
        $roles2 = $this->findAll($this->getRolesOfEntitiesTableName());
        $used   = [];
        foreach ($roles1 as $entry) {
            $used[] = $entry['role_name'];
        }
        foreach ($roles2 as $entry) {
            $used[] = $entry['role'];
        }
        return $used;
    }

    public function isRoleAllowed($role, $permission)
    {
        /**
         * Checks if the role is explicitly allowed a permission.
         */
        $permissions = $this->getPermissionsOfRole($role);
        foreach ($permissions as $entry) {
            if ($this->isPermissionMatching($permission, $entry['permission'])) {
                return (bool) $entry['allowed'];
            }
        }

        return false;
    }

    public function isAllowed($id, $permission)
    {
        $permissions = $this->getPermissionsOfEntity($id);
        foreach ($permissions as $entry) {
            if ($this->isPermissionMatching($permission, $entry['permission'])) {
                return (bool) $entry['allowed'];
            }
        }

        return $this->isAtLeastOneRoleAllowed($this->getRolesOfEntity($id), $permission);
    }

    /**
     * @return boolean
     */
    public function isEntityHoldingRole($id, $role)
    {
        return $this->has(
            $this->getRolesOfEntitiesTableName(),
            Condition::combineAnd(
                Condition::equals('entity_id', $id),
                Condition::equals('role', $role)
            )
        );
    }

    public function giveRoleToEntity($id, $role)
    {
        if (!$this->isEntityHoldingRole($id, $role)) {
            $this->create(
                $this->getRolesOfEntitiesTableName(),
                [
                    'entity_id' => $id,
                    'role' => $role
                ]
            );
        }
    }

    /**
     * @return mixed
     */
    private function getPermissionsOfEntity($id)
    {
        return $this->findAll(
            $this->getPermissionsOfEntitiesTableName(),
            Condition::equals('entity_id', $id)
        );
    }

    /**
     * @return mixed
     */
    private function getPermissionsOfRole($roleName)
    {
        return $this->findAll(
            $this->permissionsOfRolesTableName,
            Condition::equals('role_name', $roleName)
        );
    }

    /*
     * -- Expected Results --
     * Subject    = users.create
     * Expression = users
     * Result     = true
     *
     * Subject    = houses.create
     * Expression = houses.doors
     * Result     = false
     *
     * Subject    = houses.lock
     * Expression = houses.lock
     * Result     = true
     *
     * Subject    = cable.channels.edit
     * Expression = cable.channels
     * Result     = true
     *
     * Subject    = services.cable
     * Expression = services.cable.channels
     * Result     = false
     *
     * Subject    = services.cable.channels
     * Expression = services.cable.channels
     * Result     = true
     *
     */
    /**
     * @param string $subject
     * @param string $expression
     * 
     * @return boolean
     */
    private static function isPermissionMatching($subject, $expression)
    {
        $subjectSegments    = explode('.', $subject);
        $expressionSegments = explode('.', $expression);
        $expressionCount    = count($expressionSegments);

        if ($expressionCount > count($subjectSegments)) {
            return false;
        }
        for ($i = 0; $i < $expressionCount; $i++) {
            if ($expressionSegments[$i] !== $subjectSegments[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $roles
     * 
     * @return boolean
     */
    private function isAtLeastOneRoleAllowed(array $roles, $permission)
    {
        foreach ($roles as $role) {
            if ($this->isRoleAllowed($role, $permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    private function getPermissionsOfEntitiesTableName()
    {
        return $this->prefix . $this->permissionsOfEntitiesTableName;
    }

    /**
     * @return string
     */
    private function getPermissionsOfRolesTableName()
    {
        return $this->prefix . $this->permissionsOfRolesTableName;
    }

    /**
     * @return string
     */
    private function getRolesOfEntitiesTableName()
    {
        return $this->prefix . $this->rolesOfEntitiesTableName;
    }
}
