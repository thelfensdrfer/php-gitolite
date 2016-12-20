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
		$config = new Config($path, false);
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

	public function testGit()
	{
		$config = new Config(__DIR__ . '/fixtures/test.conf');
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
		$config = new Config(__DIR__ . '/fixtures/test.conf', false);
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
		$config = new Config(__DIR__ . '/fixtures/test.conf', false);
		$adminGroup = $config->getGroups()['gitolite-admin'];
		$tim = $adminGroup->getUsers()['tim'];
		$admin = $adminGroup->getUsers()['admin'];
		$guy = $adminGroup->getUsers()['guy'];

		$this->assertTrue($tim->hasKey());
		$this->assertTrue($admin->hasKey());
		$this->assertFalse($guy->hasKey());

		$this->assertFalse($tim->validateKey());
		$this->assertTrue($admin->validateKey());

		$this->expectException(\VisualAppeal\Gitolite\PhpGitoliteException::class);
		$guy->validateKey();
	}

	/**
	 * Test if a user can be added.
	 *
	 * @return void
	 */
	public function testAddUser()
	{
		$config = new Config(__DIR__ . '/fixtures/test.conf', false);
		$adminGroup = &$config->getGroups()['gitolite-admin'];
		$adminGroup->addUser('tom', [
			'keys' => [
				__DIR__ . '/fixtures/tom.pub' => 'tom.pub'
			]
		]);
		$config->saveAs(__DIR__ . '/fixtures/test_add_user.conf');
		$config = new Config(__DIR__ . '/fixtures/test_add_user.conf');
		$adminGroup = &$config->getGroups()['gitolite-admin'];
		$this->assertEquals(4, count($adminGroup->getUsers()));
	}

	/**
	 * Test if the permissions are paresed correctly.
	 *
	 * @return void
	 */
	public function testRepositoryAccess()
	{
		$config = new Config(__DIR__ . '/fixtures/test.conf', false);
		$adminGroup = &$config->getGroups()['gitolite-admin'];
		$admin = $adminGroup->getUsers()['admin'];

		$group1 = &$config->getGroups()['group1'];
		$a = $group1->getUsers()['a'];

		$this->assertEquals(6, count($config->getRepositoriesForUser($admin)));
		$this->assertEquals(2, count($config->getRepositoriesForUser($a)));
	}
}
