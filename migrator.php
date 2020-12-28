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
    if (is_bool($default_value)) {
        $cli->opt($key, $default_value, false, 'boolean');
    } else {
        $cli->opt($key, $default_value, false);
    }
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
$threads = $args['threads'];


echo "Threads $threads\n";
if (is_array($indexes) && count($indexes) > 0) {
    $iMax = count($indexes);
    $i = 0;
    for ($j = 0; $j < $threads; $j++) {
        echo "Launch new fork $j------------------\n";
        $pid = pcntl_fork();
        $fork = $j;
        if ($pid === -1) {
            die("could not fork");
        } elseif ($pid) {
        } else {
            $i = $fork;
            while ($i < $iMax) {
                $index = $indexes[$i];

                $i = $i + $threads;
                $migrator = new Manticoresearch\ESMigrator($config);
                $esi = $migrator->getESIndex(['index' => $index]);
                $migrator->migrateIndex($esi[0]);
            }
            exit();
        }
    }
    while (pcntl_waitpid(0, $status) !== -1) {
    }
} else {
    $catindex = new Manticoresearch\ESMigrator($config);
    $indices = $catindex->getESIndexes();
    foreach ($indices as $v => $index) {
        if ($index['index'][0] === '.') {
            unset($indices[$v]);
        }
    }
    $indices = array_values($indices);

    $i = 0;
    $iMax = count($indices);
    for ($j = 0; $j < $threads; $j++) {
        echo "Launch new fork $j------------------\n";
        $pid = pcntl_fork();
        $fork = $j;
        if ($pid === -1) {
            die("could not fork");
        } elseif ($pid) {
        } else {
            $i = $fork;
            while ($i < $iMax) {
                $index = $indices[$i];
                $i = $i + $threads;
                $migrator = new Manticoresearch\ESMigrator($config);
                $esi = $migrator->getESIndex(['index' => $index['index']]);
                $migrator->migrateIndex($esi[0]);
            }
            exit();
        }
    }
    while (pcntl_waitpid(0, $status) !== -1) {
    }
}
