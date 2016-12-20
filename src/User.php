<?php

namespace VisualAppeal\Gitolite;

use VisualAppeal\Gitolite\Config;
use VisualAppeal\Gitolite\PhpGitoliteException;

class User
{
	/**
	 * List of keys in keydir.
	 *
	 * @var array
	 */
	private static $_keys = [];

	/**
	 * Path to the key file of the user.
	 *
	 * @var string
	 */
	private static $_keyPath = null;

	/**
	 * Name of the user
	 *
	 * @var string
	 */
	private $_name;

	/**
	 * Create new user.
	 *
	 * @param string $name
	 */
	public function __construct($name, $path)
	{
		$this->_name = $name;

		if (self::$_keyPath === null) {
			self::$_keyPath = dirname(dirname($path)) . DIRECTORY_SEPARATOR . 'keydir';

			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::$_keyPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
			foreach($objects as $filename => $object) {
				if (!is_file($filename))
					continue;

				$pathInfo = pathinfo($filename);
				$keyname = $pathInfo['filename'];

				if (!isset(self::$_keys[$keyname]))
					self::$_keys[$keyname] = [];

				self::$_keys[$keyname][] = $filename;
			}
		}
	}

	/**
	 * Return repository name.
	 *
	 * @return string
	 */
	public function &getName()
	{
		return $this->_name;
	}

	/**
	 * Add new public key to user.
	 */
	public function addKey($path)
	{
		if (!isset(self::$_keys[$this->_name]))
			self::$_keys[$this->_name] = [];

		self::$_keys[$this->_name][] = $path;
	}

	/**
	 * Returns class as string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_name;
	}

	/**
	 * Check if the user has a valid key.
	 *
	 * @return boolean
	 */
	public function hasKey()
	{
		if (!isset(self::$_keys[$this->_name]))
			return false;

		return true;
	}

	/**
	 * Validate if public key is a real ssh key.
	 *
	 * @see https://gist.github.com/jupeter/3248095
	 * @param string $value
	 * @return boolean
	 */
	public function validateKey($index = 0)
	{
		if (!isset(self::$_keys[$this->_name][$index]))
			throw new PhpGitoliteException(sprintf('Unknown index %d for user %s!', $index, $this->_name), Config::ERROR_USER_KEY_INVALID_INDEX);

		// Get key from file
		$filename = self::$_keys[$this->_name][$index];
		if (!file_exists($filename) || !is_readable($filename))
			return false;

		$value = file_get_contents($filename);
		if ($value === false)
			return false;

		$key_parts = explode(' ', $value, 3);

		if (count($key_parts) < 2) {
			return false;
		}
		if (count($key_parts) > 3) {
			return false;
		}

		$algorithm = $key_parts[0];
		$key = $key_parts[1];
		if (!in_array($algorithm, ['ssh-rsa', 'ssh-dss'])) {
			return false;
		}

		$key_base64_decoded = base64_decode($key, true);
		if ($key_base64_decoded == FALSE) {
			return false;
		}

		$check = base64_decode(substr($key,0,16));
		$check = preg_replace("/[^\w\-]/","", $check);
		if ((string) $check !== (string) $algorithm) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the user has access to the repository.
	 *
	 * @param Repository $repository
	 * @return boolean
	 */
	public function hasAccess(Repository $repository, $allGroups)
	{
		// Get groups the user is in
		$groups = [];
		foreach ($allGroups as $group) {
			if (array_key_exists($this->getName(), $group->getUsers()))
				$groups[$group->getName()] = $group;
		}

		foreach ($repository->getPermissions() as $permission) {
			// Check every permission of each repository
			foreach ($permission->getUsers() as $userOrGroupWithPermission) {
				if (get_class($userOrGroupWithPermission) == 'VisualAppeal\Gitolite\User') {
					// Check if user has access to the repository
					if ($userOrGroupWithPermission->getName() === $this->getName())
						return true;
				} else {
					// Check if a group the user is member of has access to the repository
					foreach ($groups as $group) {
						if ($userOrGroupWithPermission->getName() === $group->getName())
							return true;
					}
				}
			}
		}

		return false;
	}
}
