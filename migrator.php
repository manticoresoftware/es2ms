#!/usr/bin/php
<?php
require 'vendor/autoload.php';
function set_nested_value(array &$arr, array $ancestors, $value)
{
    $current = &$arr;
    foreach ($ancestors as $key) {

        // To handle the original input, if an item is not an array,
        // replace it with an array with the value as the first item.
        if (!is_array($current)) {
            $current = array($current);
        }

        if (!array_key_exists($key, $current)) {
            $current[$key] = array();
        }
        $current = &$current[$key];
    }

    $current = $value;
}

$flatkeys = \Manticoresearch\ESMigrator::getConfigKeys();

$cli = new Garden\Cli\Cli();
foreach ($flatkeys as $key => $default_value) {
    $cli->opt($key, $default_value, false);
}
$cli->opt('indexes', 'Indexes', false);
$args = $cli->parse($argv, true);


$config = [];
foreach ($flatkeys as $key => $default_value) {
    $ancestors = explode('.', $key);
    set_nested_value($config, $ancestors, $args->getOpt($key, $default_value));

}
if (isset($args['indexes']) && $args['indexes'] !== "") {
    $indexes = explode(',', $args['indexes']);
} else {
    $indexes = [];
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
