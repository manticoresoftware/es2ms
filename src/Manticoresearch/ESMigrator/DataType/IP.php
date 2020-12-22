<?php


namespace Manticoresearch\ESMigrator\DataType;


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