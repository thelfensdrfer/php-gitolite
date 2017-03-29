<?php

namespace VisualAppeal\Gitolite;

use VisualAppeal\Gitolite\Group;
use VisualAppeal\Gitolite\PhpGitoliteException;
use VisualAppeal\Gitolite\Repository;
use VisualAppeal\Gitolite\User;

class Config
{
	/**
	 * Contents of config file.
	 *
	 * @var string
	 */
	protected $config;

	/**
	 * Path to config.
	 *
	 * @var string
	 */
	private $_path;

	/**
	 * List of all repostitories.
	 *
	 * @var array
	 */
	private $_repositories = [];

	/**
	 * List of all groups.
	 *
	 * @var array
	 */
	private $_groups = [];

	/**
	 * List of all users without a group.
	 *
	 * @var array
	 */
	private $_users = [];

	/**
	 * Check if the parser is currently parsing a repository.
	 *
	 * @var array
	 */
	private $_parsingRepositories = [];

	/**
	 * Git repository
	 *
	 * @var Cz\Git\GitRepository
	 */
	private $_git;

	/**
	 * Error codes for exceptions.
	 */
	const ERROR_CONFIG_NOT_EXISTS = 100;
	const ERROR_CONFIG_NOT_READABLE = 110;
	const ERROR_CONFIG_NO_REPOSITORY = 120;
	const ERROR_CONFIG_NOT_OPENED = 130;
	const ERROR_CONFIG_NOT_WRITABLE = 140;
	const ERROR_PARSER_GROUP = 200;
	const ERROR_PARSER_PERMISSION_LINE = 210;
	const ERROR_PARSER_PERMISSION_TYPE = 215;
	const ERROR_PARSER_PERMISSION_UNOKWN_TYPE = 218;
	const ERROR_USER_KEY_INVALID_INDEX = 300;
	const ERROR_GROUP_USER_ALREADY_EXISTS = 400;
	const ERROR_GROUP_USER_KEY_NOT_FOUND = 410;
	const ERROR_GROUP_USER_NOT_MOVABLE = 420;

	/**
	 * Create new parser instance.
	 *
	 * @param string $path Path to config
	 * @param boolean $pull If the repository should be pulled before parsing
	 * @param string $remote
	 * @param array $params Git pull parameters
	 */
	public function __construct($path, $pull = true, $remote = null, array $params = null)
	{
		if (!file_exists($path))
			throw new PhpGitoliteException(sprintf('Gitolite config file %s does not exist!', $path), self::ERROR_CONFIG_NOT_EXISTS);

		if (!is_readable($path))
			throw new PhpGitoliteException(sprintf('Gitolite config file %s is not readable!', $path), self::ERROR_CONFIG_NOT_READABLE);

		$this->_path = $path;
		$this->config = file_get_contents($path);

		try {
			$this->_git = new \Cz\Git\GitRepository(dirname(dirname($path)));

			if ($pull)
				$this->pull($remote, $params);
		} catch (Cz\Git\GitException $e) {
			throw new PhpGitoliteException(sprintf('Invalid repository %s: %s', $path, $e->getMessage()), self::ERROR_CONFIG_NO_REPOSITORY);
		}

		if ($this->config === false)
			throw new PhpGitoliteException(sprintf('Gitolite config file %s could not opened!', $path), self::ERROR_CONFIG_NOT_OPENED);

		$i = 1;
		foreach (preg_split("/((\r?\n)|(\r\n?))/", $this->config) as $line) {
			$this->parseLine($line, $i);
			$i++;
		}
	}

	/**
	 * Create group if it does not exist.
	 *
	 * @param string $name
	 * @return Group
	 */
	protected function createOrFindGroup($name)
	{
		if (isset($this->_groups[$name]))
			return $this->_groups[$name];

		$this->_groups[$name] = new Group($name, $this->_path);
		return $this->_groups[$name];
	}

	/**
	 * Create user if it does not exist.
	 *
	 * @param string $name
	 * @return User
	 */
	protected function createOrFindUser($name)
	{
		if (isset($this->_users[$name]))
			return $this->_users[$name];

		$this->_users[$name] = new User($name, $this->_path);
		return $this->_users[$name];
	}

	/**
	 * Create repository if it does not exist.
	 *
	 * @param string $name
	 * @return Repository
	 */
	public function createOrFindRepository($name)
	{
		if (isset($this->_repositories[$name]))
			return $this->_repositories[$name];

		$this->_repositories[$name] = new Repository($name);
		return $this->_repositories[$name];
	}

	/**
	 * Find repository.
	 *
	 * @param string $name
	 * @return Repository|null
	 */
	public function findRepository($name)
	{
		if (isset($this->_repositories[$name]))
			return $this->_repositories[$name];

		return null;
	}

	/**
	 * Delete repository.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function deleteRepository($name)
	{
		if (!isset($this->_repositories[$name]))
			return false;

		unset($this->_repositories[$name]);
		return true;
	}

	/**
	 * Parse user and add it to a group
	 *
	 * @param string $user
	 * @return void
	 */
	protected function parseUserlist($user)
	{
		$users = [];

		if (substr($user, 0, 1) === '@') {
			$groupExpanded = $this->createOrFindGroup(trim(substr($user, 1)));
			$users[] = $groupExpanded;
		} else {
			$users[] = trim($user);
		}

		return $users;
	}

	/**
	 * Parse all groups or users and assign them to a group.
	 *
	 * @param string $line
	 * @param Group $group
	 * @return void
	 */
	protected function parseUsers($line, Group $group, $i)
	{
		$line = trim($line);

		$users = preg_split('/\s+/', $line);

		if (count($users) > 0) {
			foreach ($users as $user) {
				$userNames = $this->parseUserlist($user);

				foreach ($userNames as $userOrGroup) {
					if (is_string($userOrGroup))
						$group->createOrFindUser($userOrGroup);
					else
						$group->createOrFindGroup($userOrGroup);
				}
			}
		} else {
			$userNames = $this->parseUserlist($line);

			foreach ($userNames as $user) {
				$group->createOrFindUser($user);
			}
		}
	}

	/**
	 * Parse a group from config.
	 *
	 * @param string $line
	 * @param int $i Line number
	 * @return void
	 */
	protected function parseGroup($line, $i)
	{
		if (preg_match('/@([a-zA-z\-0-9]+)\s*?=\s*?(.*)/', $line, $matches) !== 1) {
			throw new PhpGitoliteException(sprintf('Could not parse group in line #%d: %s', $i, $line), self::ERROR_PARSER_GROUP);
		}

		$group = $this->createOrFindGroup(trim($matches[1]));
		$this->parseUsers($matches[2], $group, $i);
	}

	/**
	 * Parse permission of repository
	 *
	 * @param string $line
	 * @param int $i Line number
	 * @return Permission
	 */
	protected function parsePermission($line, $i)
	{
		$permission = new Permission;

		if (preg_match('/\s*?(.*)\s*?=\s*?(.*)/', $line, $matches) !== 1) {
			throw new PhpGitoliteException(sprintf('Could not parse permission in line #%d: %s', $i, $line), self::ERROR_PARSER_PERMISSION_LINE);
		}

		$left = trim($matches[1]);
		$userList = trim($matches[2]);

		if (preg_match('/([\-\+RW]+)\s*?(.*?)/', $left, $matches) !== 1) {
			throw new PhpGitoliteException(sprintf('Could not parse permission type in line #%d: %s', $i, $line), self::ERROR_PARSER_PERMISSION_TYPE);
		}

		$permission->setRef(trim($matches[2]));

		$permissionRaw = strtoupper($matches[1]);
		switch ($permissionRaw) {
			case '-':
				$permission->setPermission(Permission::PERMISSION_DENY);
				break;
			case 'R':
				$permission->setPermission(Permission::PERMISSION_READ);
				break;
			case 'RW':
				$permission->setPermission(Permission::PERMISSION_READ_WRITE);
				break;
			case 'RW+':
				$permission->setPermission(Permission::PERMISSION_READ_WRITE_PLUS);
				break;
			default:
				throw new PhpGitoliteException(sprintf('Unknown permission type in line #%d: %s', $i, $permissionRaw), self::ERROR_PARSER_PERMISSION_UNOKWN_TYPE);
				break;
		}

		$users = $this->parseUserlist($userList);
		foreach ($users as $userOrGroup) {
			if (is_string($userOrGroup))
				$permission->addUser($this->createOrFindUser($userOrGroup));
			else
				$permission->addGroup($this->createOrFindGroup($userOrGroup->getName()));
		}

		return $permission;
	}

	/**
	 * Parse a group from config.
	 *
	 * @param string $line
	 * @param int $i Line number
	 * @return array
	 */
	protected function parseRepositories($line, $i)
	{
		$repos = [];

		if (substr($line, 0, 4) === 'repo') {
			$repositoryList = trim(substr($line, 4));

			$repositories = preg_split('/\s+/', $repositoryList);
			if (count($repositories) > 0) {
				foreach ($repositories as $repository) {
					$repos[] = $this->createOrFindRepository($repository);
				}
			} else {
				$repos[] = $this->createOrFindRepository($repositoryList);
			}
		} else {
			$repos = $this->_parsingRepositories;

			foreach ($repos as &$repository) {
				$permission = $this->parsePermission($line, $i);
				$repository->addPermission($permission);
			}
		}

		return $repos;
	}

	/**
	 * Parse a config line.
	 *
	 * @param string $line
	 * @return void
	 */
	protected function parseLine($line, $i)
	{
		// Skipt empty lines and comments
		if (strlen($line) === 0 || substr($line, 0, 1) === '#')
			return;

		// Only parse line until comment
		if (strpos($line, '#') !== false)
			$line = substr($line, 0, strpos($line, '#'));

		// Lines starting with @ are groups
		if (substr($line, 0, 1) === '@') {
			$this->_parsingRepo = [];
			return $this->parseGroup($line, $i);
		}

		// Lines starting with "repo" are repostitories
		if (substr($line, 0, 4) === 'repo' || count($this->_parsingRepositories) > 0) {
			$this->_parsingRepositories = $this->parseRepositories($line, $i);
		}
	}

	/**
	 * Generate config contents based on currently stored settings.
	 *
	 * @return string
	 */
	protected function generateConfig()
	{
		$config = '';
		foreach ($this->_groups as $name => $group) {
			$config .= '@' . $name;
			$users = $group->getUsers();
			if (count($users) > 0) {
				$config .= ' = ' . implode(' ', array_map(function($userOrGroup) {
					if (get_class($userOrGroup) == 'VisualAppeal\Gitolite\User')
						return $userOrGroup->getName();
					else
						return '@' . $userOrGroup->getName();
				}, $users));
			}
			$config .= "\n";
		}

		if (count($this->_groups))
			$config .= "\n";

		foreach ($this->_repositories as $name => $repository) {
			$config .= 'repo ' . $name . "\n";
			$permissions = $repository->getPermissions();
			foreach ($permissions as $permission) {
				$config .= '  ' . implode(' ', array_filter([
						$permission->getPermissionString(),
						$permission->getRef(),
						'=',
						implode(' ', array_map(function($userOrGroup) {
							if (get_class($userOrGroup) == 'VisualAppeal\Gitolite\User')
								return $userOrGroup->getName();
							else
								return '@' . $userOrGroup->getName();
						}, $permission->getUsers())),
					], function($filter) {
						return !empty(trim($filter));
					})) . "\n";
			}
			$config .= "\n";
		}

		return $config;
	}

	/**
	 * Save config changes.
	 *
	 * @return bool
	 */
	public function save()
	{
		$config = $this->generateConfig();

		if (!is_writable($this->_path))
			throw new PhpGitoliteException(sprintf('Gitolite config file %s is not writable!', $this->_path), self::ERROR_CONFIG_NOT_WRITABLE);

		return file_put_contents($this->_path, $config) !== false;
	}

	/**
	 * Save config as new file.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function saveAs($path)
	{
		$config = $this->generateConfig();

		if (file_exists($path) && !is_writable($path))
			throw new PhpGitoliteException(sprintf('Gitolite config file %s is not writable!', $path), self::ERROR_CONFIG_NOT_WRITABLE);

		return file_put_contents($path, $config) !== false;
	}

	/**
	 * Return groups.
	 *
	 * @return array
	 */
	public function &getGroups()
	{
		return $this->_groups;
	}

	/**
	 * Return repositories.
	 *
	 * @return array
	 */
	public function &getRepositories()
	{
		return $this->_repositories;
	}

	/**
	 * Get repositories the user has access to.
	 *
	 * @param User $user
	 * @return array
	 */
	public function &getRepositoriesForUser(User $user)
	{
		$repositories = [];

		foreach ($this->getRepositories() as $repository) {
			if ($user->hasAccess($repository, $this->getGroups()))
				$repositories[$repository->getName()] = $repository;
		}

		return $repositories;
	}

	/**
	 * Pull changes.
	 *
	 * @param string $remote
	 * @param array $params
	 * @return void
	 */
	public function pull($remote = null, array $params = null)
	{
		$this->_git->pull($remote, $params);
	}

	/**
	 * Commit latest changes.
	 *
	 * @param string $message
	 * @return boolean
	 */
	public function commit($message = '[php-gitolite] Updated config')
	{
		$this->_git->addAllChanges();
		$this->_git->commit($message);

		return true;
	}

	/**
	 * Push changes.
	 *
	 * @param string $remote
	 * @param array $params
	 * @return void
	 */
	public function push($remote = null, array $params = null)
	{
		$this->_git->push($remote, $params);

		return true;
	}

	/**
	 * Commit latest changes and push.
	 *
	 * @param string $message
	 * @param string $remote
	 * @param array $params
	 * @return void
	 */
	public function saveAndPush($message = '[php-gitolite] Updated config', $remote = null, array $params = null)
	{
		if (!$this->save())
			return false;

		if (!$this->commit($message))
			return false;

		if (!$this->push($remote, $params))
			return false;

		return true;
	}

	/**
	 * Returns class as string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$out = $this->_path . ':' . PHP_EOL . PHP_EOL;

		$out .= '## GROUPS' . PHP_EOL . PHP_EOL;
		foreach ($this->_groups as $group) {
			$out .= (string) $group . PHP_EOL;
		}

		$out .= PHP_EOL . '## USERS' . PHP_EOL . PHP_EOL;
		foreach ($this->_users as $user) {
			$out .= (string) $user . PHP_EOL;
		}

		$out .= PHP_EOL . '## REPOSITORIES' . PHP_EOL . PHP_EOL;
		foreach ($this->_repositories as $repository) {
			$out .= (string) $repository . PHP_EOL;
		}

		return $out;
	}
}
