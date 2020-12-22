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
php migrator.php  --es_host=127.0.0.1 --es_port=9200 --indexes=index1,index2
```

Parameters
----------
* `indexes` - list of ES index names, separated by comma. Default:  migrate all indexes available
* `dryrun` - perform a dry run without migrating anything. It prints information about available ES indexes
* `es_host` - ES host, default: 127.0.0.1
* `es_port` - ES port, default: `92000`
* `es_user` - ES username, no default 
* `es_pass` - ES password, no default
* `es_batch_size` - How many ES documents to retrieve per round (default 10000)  
* `ms_host` - Manticore host, default: 127.0.0.1
* `ms_port` - Manticore HTTP port, default: 9308
* `ms_batch_size` - How many documents to group in a single INSERT batch in Manticore (default 10000)
* `limit` - limit the number of documents from an index for migration (default 0 - migrate all )

TODO
----

* add configuration file
* add more cli parameters
* multi-threading?
* structured types
