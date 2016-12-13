## Gitolite config parser for PHP

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

```php
$config = new VisualAppeal\Gitolite\Config($pathToConfig);

var_dump($config->getGroups());
var_dump($config->getRepositories());
```
