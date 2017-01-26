<?php

namespace VisualAppeal\Gitolite;

use VisualAppeal\Gitolite\User;

class Permission
{
	const PERMISSION_DENY = 1;
	const PERMISSION_READ = 2;
	const PERMISSION_READ_WRITE = 4;
	const PERMISSION_READ_WRITE_PLUS = 8;

	/**
	 * Permission type.
	 *
	 * @var int
	 */
	private $_permission = null;

	/**
	 * Specify the ref.
	 *
	 * @var string
	 */
	private $_ref = null;

	/**
	 * List of users for this permission.
	 *
	 * @var array
	 */
	private $_users = [];

	/**
	 * Set permission type.
	 *
	 * @param int $permission
	 */
	public function setPermission($permission)
	{
		$this->_permission = $permission;
	}

	/**
	 * Get permission type.
	 *
	 * @return int
	 */
	public function &getPermission()
	{
		return $this->_permission;
	}

	/**
	 * Get string representation of the permission type.
	 *
	 * @return string
	 */
	public function getPermissionString()
	{
		switch ($this->_permission) {
			case self::PERMISSION_DENY:
				return '-';
			case self::PERMISSION_READ:
				return 'R';
			case self::PERMISSION_READ_WRITE:
				return 'RW';
			case self::PERMISSION_READ_WRITE_PLUS:
				return 'RW+';
			default:
				throw new \Exception(sprintf('Unknown permission type %s', $this->_permission));
		}
	}

	/**
	 * Set ref.
	 *
	 * @param string $ref
	 */
	public function setRef($ref)
	{
		$this->_ref = $ref;
	}

	/**
	 * Get ref.
	 *
	 * @return string
	 */
	public function &getRef()
	{
		return $this->_ref;
	}

	/**
	 * Add user.
	 *
	 * @param User $user
	 */
	public function addUser(User $user)
	{
		$this->_users[] = $user;
	}

	/**
	 * Add group.
	 *
	 * @param Group $group
	 */
	public function addGroup(Group $group)
	{
		$this->_users[] = $group;
	}

	/**
	 * Get the users.
	 *
	 * @return array
	 */
	public function &getUsers()
	{
		return $this->_users;
	}

	/**
	 * Returns class as string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getPermissionString() . ' ' . $this->_ref . implode(', ', $this->_users);
	}
}
