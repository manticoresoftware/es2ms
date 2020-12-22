#!/usr/bin/php
<?php
require 'vendor/autoload.php';

//$esi = $migrator->getESIndex(['index'=>'optimize_perf']);
$cli = new Garden\Cli\Cli();
$cli->opt('indexes', 'Indexes', false)
    ->opt('dryrun', 'Show only schemas,no real import', false, 'boolean')
    ->opt('elasticsearch.host', 'Elastic host', false)
    ->opt('elasticsearch.port', 'Elastic port', false)
    ->opt('elasticsearch.user', 'Elastic user', false)
    ->opt('elasticsearch.pass', 'Elastic pass', false)
    ->opt('elasticsearch.batch_size', 'Elastic get batch size', false)
    ->opt('limit', 'Limit number of documents to retrieve', false)
    ->opt('manticoresearch.host', 'Manticore host', false)
    ->opt('manticoresearch.port', 'Manticore port', false)
    ->opt('manticoresearch.batch_size', 'Manticore insert batch size', false);
$args = $cli->parse($argv, true);
if (isset($args['indexes']) && $args['indexes'] !== "") {
    $indexes = explode(',', $args['indexes']);
} else {
    $indexes = [];
}


$config = [];

$config['elasticsearch'] = [
    'host' => $args->getOpt('elasticsearch.host', 'http://127.0.0.1'),
    'port' => $args->getOpt('elasticsearch.port', 9200),
    'user' => $args->getOpt('elasticsearch.user', ''),
    'pass' => $args->getOpt('elasticsearch.pass', ''),
    'batch_size' => $args->getOpt('elasticsearch.batch_size', 10000),
];
$config['limit'] =$args->getOpt('limit', 0);
$config['manticoresearch'] = [
    'host' => $args->getOpt('manticoresearch.user', '127.0.0.1'),
    'port' => $args->getOpt('manticoresearch.port', 9308),
    'batch_size' => $args->getOpt('manticoresearch.batch_size', 10000)
];

if (isset($args['dryrun'])) {
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
