#!/usr/bin/php
<?php
require 'vendor/autoload.php';

//$esi = $migrator->getESIndex(['index'=>'optimize_perf']);
$cli = new Garden\Cli\Cli();
$cli->opt('indexes', 'Indexes', false)
    ->opt('dryrun','Show only schemas,no real import',false,'boolean')
    ->opt('es_host', 'Elastic host', false)
    ->opt('es_port', 'Elastic port', false)
    ->opt('es_user', 'Elastic user', false)
    ->opt('es_pass', 'Elastic pass', false)
    ->opt('ms_host', 'Manticore host', false)
    ->opt('ms_port', 'Manticore port', false);
$args = $cli->parse($argv, true);
if(isset($args['indexes']) && $args['indexes'] !=="") {
    $indexes = explode(',', $args['indexes']);
}else{
    $indexes = [];
}


$config = [];
if (isset($args['es_host'])) {
    $config['elasticsearch'] = [
        'host' => $args['es_host'],
        'port' => $args['es_port'],
        'user' => $args['es_user'],
        'pass' => $args['es_pass']
    ];
}
if (isset($args['ms_host'])) {
    $config['manticoresearch'] = [
        'host' => $args['ms_host'],
        'port' => $args['ms_port']
    ];
}
if(isset($args['dryrun'])) {
    $config['dryrun'] = $args['dryrun'];
}
$migrator = new Manticoresearch\ESMigrator($config);

if (is_array($indexes) && count($indexes) > 0) {
    $iMax = count($indexes);
    for ($i = 0; $i < $iMax; $i++) {

        $esi = $migrator->getESIndex(['index' => $indexes[$i]]);

        $migrator->migrateIndex($esi[0]);
    }
} else {
    $migrator->migrateAll();
}
