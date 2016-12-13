<?php

namespace VisualAppeal\Gitolite;

class User
{
	/**
	 * Name of the user
	 *
	 * @var string
	 */
	private $_name = null;

	/**
	 * Create new user.
	 *
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->_name = $name;
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
	 * Returns class as string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_name;
	}
}
