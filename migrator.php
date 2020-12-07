#!/usr/bin/php
<?php
require 'vendor/autoload.php';

$migrator = new Manticoresearch\ESMigrator();
//$esi = $migrator->getESIndex(['index'=>'optimize_perf']);
$cli =  new Garden\Cli\Cli();
$cli->opt('indexes', 'Indexes', false)
    ->opt('es_host', 'Elastic host', false)
    ->opt('es_port', 'Elastic port', false)
    ->opt('es_user', 'Elastic user', false)
    ->opt('es_pass', 'Elastic pass', false)
    ->opt('ms_host', 'Manticore host', false)
    ->opt('ms_port', 'Manticore port', false);
$args = $cli->parse($argv, true);

$indexes = $args['indexes'];
if (isset($args['es_host'])) {
    $migrator->setElasticConfig([
        'host' => $args['es_host'],
        'port' => $args['es_port'],
        'user' => $args['es_user'],
        'pass' => $args['es_pass']
    ]);
}
if (isset($args['ms_host'])) {
    $migrator->setManticoreConfig([
        'host' => $args['ms_host'],
        'port' => $args['ms_port']
    ]);
}

$migrator->setup();
if (is_array($indexes) && count($indexes) > 1) {
    $indexes = [];
    for ($i = 1; $i < count($indexes); $i++) {
        $esi = $migrator->getESIndex(['index' => $indexes[$i]]);

        $migrator->migrateIndex($esi[0]);
    }
} else {
    $migrator->migrateAll();
}
