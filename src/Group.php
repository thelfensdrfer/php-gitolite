<?php

namespace VisualAppeal\Gitolite;

use VisualAppeal\Gitolite\User;

class Group
{
	/**
	 * Name of the group
	 *
	 * @var string
	 */
	private $_name = null;

	/**
	 * Users in group.
	 *
	 * @var array
	 */
	private $_users = [];

	/**
	 * Path to the config file.
	 *
	 * @var string
	 */
	private $_path;

	/**
	 * Create new group.
	 *
	 * @param string $name
	 */
	public function __construct($name, $path)
	{
		$this->_name = $name;
		$this->_path = $path;
	}

	/**
	 * Return repository name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Get users in group.
	 *
	 * @return array
	 */
	public function &getUsers()
	{
		return $this->_users;
	}

	/**
	 * Add new user to group.
	 *
	 * @param string $name
	 * @param array $options
	 */
	public function addUser($name, array $options = [])
	{
		if (isset($this->_users[$name]))
			throw new PhpGitoliteException(sprintf('User %s already exists in group %s!', $name, $this->_name), Config::ERROR_GROUP_USER_ALREADY_EXISTS);

		$user = new User($name, $this->_path);
		if (isset($options['keys'])) {
			$keyPath = dirname(dirname($this->_path)) . DIRECTORY_SEPARATOR . 'keydir';

			foreach ($options['keys'] as $path => $newName) {
				if (!file_exists($path))
					throw new PhpGitoliteException(sprintf('Key %s does not exist!', $path), Config::ERROR_GROUP_USER_KEY_NOT_FOUND);

				$newPath = $keyPath . DIRECTORY_SEPARATOR . $newName;
				if (!copy($path, $newPath))
					throw new PhpGitoliteException(sprintf('Could not copy key from %s to %s!', $path, $newPath), Config::ERROR_GROUP_USER_NOT_MOVABLE);

				$user->addKey($newPath);
			}
		}

		$this->_users[$name] = $user;
	}

	/**
	 * Create or find user in group.
	 *
	 * @param string $name
	 *
	 * @return User
	 */
	public function createOrFindUser($name)
	{
		if (isset($this->_users[$name]))
			return $this->_users[$name];

		$this->_users[$name] = new User($name, $this->_path);
		return $this->_users[$name];
	}

	/**
	 * Create or find group in group.
	 *
	 * @param string $name
	 *
	 * @return User
	 */
	public function createOrFindGroup(Group $group)
	{
		if (isset($this->_users[$group->getName()]))
			return $this->_users[$group->getName()];

		$this->_users[$group->getName()] = $group;
		return $this->_users[$group->getName()];
	}

	/**
	 * Returns class as string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$out = '### ' . $this->_name . PHP_EOL;

		$out .= '#### Users' . PHP_EOL;
		foreach ($this->_users as $user) {
			$out .= '* ' . ((string) $user) . PHP_EOL;
		}

		return $out;
	}
}
