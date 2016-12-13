<?php

use PHPUnit\Framework\TestCase;

use VisualAppeal\Gitolite\Config;

class ConfigTest extends TestCase
{
	public function testConstruct()
	{
		$config = new Config(__DIR__ . '/fixtures/test.conf');
		echo $config;
	}
}
