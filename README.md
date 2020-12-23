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
php migrator.php  --elasticsearch.host=my.domain.com --elasticsearch.port=9200 --indexes=index1,index2
```

Parameters
----------
* `indexes` - list of ES index names, separated by comma. Default:  migrate all indexes available
* `dryrun` - perform a dry run without migrating anything. It prints information about available ES indexes
* `elasticsearch.host` - ES host, default: 127.0.0.1
* `elasticsearch.port` - ES port, default: `92000`
* `elasticsearch.user` - ES username, no default 
* `elasticsearch.pass` - ES password, no default
* `elasticsearch.batch_size` - How many ES documents to retrieve per round (default 10000)  
* `manticoresearch.host` - Manticore host, default: 127.0.0.1
* `manticoresearch.port` - Manticore HTTP port, default: 9308
* `manticoresearch.batch_size` - How many documents to group in a single INSERT batch in Manticore (default 10000)
* `limit` - limit the number of documents from an index for migration (default 0 - migrate all )

* `types.*` - allows overriding settings for a type

Type transformation
-------------------
For a data type from ES there are 2 settings:
* the Manticore data type that will be used 
* a transform class

A transform class looks like:
```php
class IP
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

Custom transform classes can be passed in `types.name.transform` either by name or instance.

TODO
----

* add configuration file
* add more cli parameters
* multi-threading?
* structured types
