ES migrator
===========

Elastic to Manticore migration script.

Currently only handles 1-level documents.

Requirements
------------

Elastic dump tool : https://github.com/elasticsearch-dump/elasticsearch-dump

Elastic and Manticore PHP clients

Install
-------

```bash
composer update
```


Usage
-----

CLI:
```php
php migrator.php  #migrate all indexes
```

```php
php migrator.php index1 index2
```

Code:

```php
require 'vendor/autoload.php';

$migrator = new Manticoresearch\ESMigrator();
$migrator->migrateAll();
```

TODO
----

* add configuration file
* multi-threading?
* structured types
