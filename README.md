[![Build Status](https://travis-ci.org/thelfensdrfer/php-gitolite.svg?branch=master)](https://travis-ci.org/thelfensdrfer/php-gitolite)

## Read config

Parse simple gitolite configs.

### What can be parsed

* Users
* Groups
* Repositories and permissions

### What cannot be parsed

* @all
* Including another config file
* Rule accumulation

### Usage

Group[s] consist of of other Group[s] and User[s].

```php
$config = new VisualAppeal\Gitolite\Config($pathToConfig);

var_dump($config->getGroups());
var_dump($config->getRepositories());
```

## Write config

### Save config after a change was made
```php
$config = new VisualAppeal\Gitolite\Config($pathToConfig);

// Make changes
// ...

$config->saveAs($pathToNewOrOldConfig);
```

### Add user

```php
$config->getGroups()['admins']->addUser('tom', [
	'keys' => [
		'/absolute/path/to/public/key.pub' => 'relative/path/in/keydir.pub'
	],
]);
```
