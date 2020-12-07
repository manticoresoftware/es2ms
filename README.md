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
php migrator.php  --es_host 127.0.0.1 --es_port 9200 my-index-000001
```
 

TODO
----

* add configuration file
* add more cli parameters
* multi-threading?
* structured types
