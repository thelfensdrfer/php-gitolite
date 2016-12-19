<?php

use PHPUnit\Framework\TestCase;

use VisualAppeal\Gitolite\Config;

class ConfigTest extends TestCase
{
	protected function subtestConfig($path)
	{
		$config = new Config($path);
		$groups = $config->getGroups();
		$repositories = $config->getRepositories();

		$this->assertEquals(4, count($groups));
		$this->assertEquals(6, count($repositories));

		$groupCount = [2, 7, 6, 8];
		$i = 0;
		foreach ($groups as $group) {
			$this->assertEquals($groupCount[$i], count($group->getUsers()));
			$i++;
		}
	}

	public function testParser()
	{
		$this->subtestConfig(__DIR__ . '/fixtures/test.conf');
	}

	public function testWriter()
	{
		$config = new Config(__DIR__ . '/fixtures/test.conf');
		$config->saveAs(__DIR__ . '/fixtures/test_writer.conf');

		$this->subtestConfig(__DIR__ . '/fixtures/test_writer.conf');
	}
}
