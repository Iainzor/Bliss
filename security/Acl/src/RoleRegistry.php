<?php
namespace Acl;

class RoleRegistry
{
	/**
	 * @var Role[]
	 */
	protected $roles = [];
	
	/**
	 * Registry multiple roles, clearing any existing roles.
	 * 
	 * @param array $roles
	 * @throws \UnexpectedValueException
	 */
	public function registerAll(array $roles)
	{
		$this->roles = [];
		
		foreach ($roles as $id => $config) {
			if ($config instanceof Role) {
				$role = $config;
			} else if (is_array($config)) {
				$role = Role::factory($config);
			} else {
				throw new \UnexpectedValueException("Invalid configuration for role #{$id}");
			}
			
			$role->id($id);
			$this->register($role);
		}
	}
	
	/**
	 * Register a role with the registry.  This will overwrite any existing role
	 * by the same ID
	 * 
	 * @param \Acl\Role $role
	 */
	public function register(Role $role)
	{
		$role->registry($this);
		
		$this->roles[$role->id()] = $role;
	}
	
	/**
	 * Attempt to get a Role instance by its ID
	 * 
	 * @param int $roleId
	 * @return \Acl\Role
	 * @throws \Exception
	 */
	public function role($roleId)
	{
		if (!isset($this->roles[$roleId])) {
			throw new \Exception("Invalid role ID #{$roleId}");
		}
		return $this->roles[$roleId];
	}
}