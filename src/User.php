<?php

namespace VisualAppeal\Gitolite;

use VisualAppeal\Gitolite\Config;
use VisualAppeal\Gitolite\PhpGitoliteException;

class User
{
	/**
	 * Name of the user
	 *
	 * @var string
	 */
	private $_name;

	/**
	 * Path to the key file of the user.
	 *
	 * @var string
	 */
	private $_keyPath;

	/**
	 * Create new user.
	 *
	 * @param string $name
	 */
	public function __construct($name, $path)
	{
		$this->_name = $name;
		$this->_keyPath = dirname(dirname($path)) . DIRECTORY_SEPARATOR .
			'keydir' . DIRECTORY_SEPARATOR .
			$this->_name . '.pub';
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
	 * Returns class as string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_name;
	}

	/**
	 * Validate if public key is a real ssh key.
	 *
	 * @see https://gist.github.com/jupeter/3248095
	 * @param string $value
	 * @return boolean
	 */
	protected function validateKey($value)
	{
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
	 * Check if the user has a valid key.
	 *
	 * @return boolean
	 */
	public function hasKey()
	{
		if (!file_exists($this->_keyPath))
			return false;

		return $this->validateKey(file_get_contents($this->_keyPath));
	}
}
