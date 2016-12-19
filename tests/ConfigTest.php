<?php

use PHPUnit\Framework\TestCase;

use VisualAppeal\Gitolite\Config;

class ConfigTest extends TestCase
{
	/**
	 * Read config fixture and test group and repository count
	 *
	 * @param string $path
	 * @return void
	 */
	protected function subtestConfig($path)
	{
		$config = new Config($path);
		$groups = $config->getGroups();
		$repositories = $config->getRepositories();

		$this->assertEquals(4, count($groups));
		$this->assertEquals(6, count($repositories));

		$groupCount = [3, 7, 6, 8];
		$i = 0;
		foreach ($groups as $group) {
			$this->assertEquals($groupCount[$i], count($group->getUsers()));
			$i++;
		}
	}

	/**
	 * Test if the parser works.
	 *
	 * @return void
	 */
	public function testParser()
	{
		$this->subtestConfig(__DIR__ . '/fixtures/test.conf');
	}

	/**
	 * Test if the writer works.
	 *
	 * @return void
	 */
	public function testWriter()
	{
		$config = new Config(__DIR__ . '/fixtures/test.conf');
		$config->saveAs(__DIR__ . '/fixtures/test_writer.conf');

		$this->subtestConfig(__DIR__ . '/fixtures/test_writer.conf');
	}

	/**
	 * Test if the public keys can be detected and validated.
	 *
	 * @return void
	 */
	public function testUserKeys()
	{
		$config = new Config(__DIR__ . '/fixtures/test.conf');
		$adminGroup = $config->getGroups()['gitolite-admin'];
		$tim = $adminGroup->getUsers()['tim'];
		$admin = $adminGroup->getUsers()['admin'];
		$guy = $adminGroup->getUsers()['guy'];

		$this->assertTrue($admin->hasKey());
		$this->assertFalse($guy->hasKey());
		$this->assertFalse($tim->hasKey());
	}
}
