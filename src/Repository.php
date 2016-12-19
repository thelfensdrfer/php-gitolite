<?php

namespace VisualAppeal\Gitolite;

use VisualAppeal\Gitolite\Permission;

class Repository
{
	/**
	 * Repository prefix <prefix>/<suffix>
	 *
	 * @var string
	 */
	private $_prefix = null;

	/**
	 * Repository suffix <prefix>/<suffix>
	 *
	 * @var string
	 */
	private $_suffix;

	/**
	 * Repository permissions
	 *
	 * @var array
	 */
	private $_permissions = [];

	/**
	 * Create new repository.
	 *
	 * @param string $prefix
	 * @param string $suffix
	 */
	public function __construct($name)
	{
		if (strpos($name, '/') !== false) {
			list($prefix, $suffix) = explode('/', $name);
			$this->_prefix = $prefix;
			$this->_suffix = $suffix;
		} else {
			$this->_suffix = $name;
		}
	}

	/**
	 * Add permission to repository.
	 *
	 * @param Permission $permission
	 */
	public function addPermission(Permission $permission)
	{
		$this->_permissions[] = $permission;
	}

	/**
	 * Get permissions for this repository.
	 *
	 * @return string
	 */
	public function &getPermissions()
	{
		return $this->_permissions;
	}

	/**
	 * Return repository name.
	 *
	 * @return string
	 */
	public function getName()
	{
		if (!empty($this->_prefix))
			return $this->_prefix . '/' . $this->_suffix;
		else
			return $this->_suffix;
	}

	/**
	 * Returns class as string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$out = $this->getName() . PHP_EOL;
		$out .= '### Permissions' . PHP_EOL;

		foreach ($this->_permissions as $permission) {
			$out .= '* ' . (string) $permission . PHP_EOL;
		}

		return $out;
	}
}
