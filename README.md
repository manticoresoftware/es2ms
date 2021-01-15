Elasticsearch -> Manticore Search data migration tool
===========

This tool automates copying data from [Elasticsearch](https://www.elastic.co/elasticsearch/) to [Manticore Search](https://manticoresearch.com/)

Requirements
------------
1. Elasticsearch dump tool : https://github.com/elasticsearch-dump/elasticsearch-dump
2. Elasticsearch and Manticore PHP clients

Install
-------

```bash
composer update
```


Usage
-----

Migrate all indexes:
```php
php migrator.php
```
Migrate certain indexes:
```php
php migrator.php  --elasticsearch.host=my.domain.com --elasticsearch.port=9200 --indexes=index1,index2
```

Parameters
----------
* `indexes` - list of ES index names, separated by comma. Default:  migrate all indexes available
* `dryrun` - perform a dry run without migrating anything. It prints information about available ES indexes
* `onlyschemas` - only create the index(es), no data migration
* `onlydata` - migrate the data, assume indexes are already created
* `elasticsearch.host` - ES host, default: 127.0.0.1
* `elasticsearch.port` - ES port, default: `92000`
* `elasticsearch.user` - ES username, no default 
* `elasticsearch.pass` - ES password, no default
* `elasticsearch.batch_size` - How many ES documents to retrieve per round (default 10000)  
* `manticoresearch.host` - Manticore host, default: 127.0.0.1
* `manticoresearch.port` - Manticore HTTP port, default: 9308
* `manticoresearch.batch_size` - How many documents to group in a single INSERT batch in Manticore (default 10000)
* `limit` - limit the number of documents from an index for migration (default 0 - migrate all )
* `threads` -  use multiple parallel workers to process the indexes (each worker process one index at a time), default is 1
* `types.*` - allows overriding settings for a data type ( see [Data type tranformation](docs/Data_type_transformation.md))
* `log`- log file path; default is 'stdout' - output to console
* `config` - read parameters from a config file in json format

Parameters read from a config file can be overridden by values provided as command line arguments

```php
php migrator.php  --config config.sample.json --threads=2
```

License
-------
This software is licensed under the [Apache v2.0 license](LICENSE).