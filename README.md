ES migrator
===========

Elastic to Manticore migration script.

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
* `threads` -  split the indexes among multiple parallel workers, default is 1
* `types.*` - allows overriding settings for a type
* `config` - read parameters from a config file in json format

Parameters read from a config file can be overridden by values provided as command line arguments

```php
php migrator.php  --config config.sample.json --threads=2
```


Type transformation
-------------------
For a data type from ES there are 2 settings:
* the Manticore data type that will be used 
* a transform class

A transform class looks like:
```php
namespace Manticoresearch\ESMigrator\DataType;

class IP implements DataType
{
    function translate($estype,$mstypes=null) {
        return  [
            'type' => 'bigint',
            'transform' => function ($field) {
                return ip2long($field);
            }
        ];
    }
}
```
The `translate()` method returns the Manticore data type and a transform function for the values.

The `Native` transform class returns same data type defined by `types.name.type` and a function that simply forwards
the value from ES without any modifications.

The transform class can overwrite the data type defined at `types.name.type`. An example is the `Date` transform class
which resets the data type depending on the ES defined date format.

Custom transform classes can be passed in `types.name.transform` either by name or instance. The class must implement interface `Manticoresearch\ESMigrator\DataType\DataType`.

Limitations
-----------

* No tokenization settings are migrated currently. To tweak the indexes on Manticore use `--onlyschemas` to create 
the indexes, tweak them and then use `--onlydata` to migrate the documents.

* IP field is converted to big integer
* structured fields should end up as JSON fields in Manticore

TODO
----

* add configuration file


