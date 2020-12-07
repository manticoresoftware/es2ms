#!/usr/bin/php
<?php
require 'vendor/autoload.php';

$migrator = new Manticoresearch\ESMigrator();
//$esi = $migrator->getESIndex(['index'=>'optimize_perf']);

if(count($argv)>1) {
    $indexes = [];
    for($i=1;$i<count($argv);$i++) {
        $esi = $migrator->getESIndex(['index'=>$argv[$i]]);
        
        $migrator->migrateIndex($esi[0]);
    }
}else {
    $migrator->migrateAll();
}