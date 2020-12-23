<?php


namespace Manticoresearch\ESMigrator\DataType;


class Native implements DataType
{
    function translate($estype,$mstypes=null) {
        return  [
            'type' => $mstypes[$estype['type']]['type'],
            'transform' => function ($field) {
                return $field;
            }
        ];
    }
}