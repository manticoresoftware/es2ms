#!/usr/bin/php
<?php
require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

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
$cli->opt('config', 'Config file', false);
$cli->opt('log', 'Log to file or stdout', false);
$args = $cli->parse($argv, true);


$indexes = [];
$config = [];
$threads = 1;

$log_file = $args->getOpt('log', 'stdout');
$dateFormat = "Y-m-d\TH:i:s";
$output = "[%datetime%]   %channel% %message%\n";
$formatter = new LineFormatter($output,$dateFormat);
if ($log_file === 'stdout') {
    $log_file = 'php://stdout';
}
$streamHandler = new StreamHandler($log_file,Logger::INFO);
$streamHandler->setFormatter($formatter);


$config_file = $args->getOpt('config', '');
if ($config_file !== '') {
    $config = array_replace_recursive(
        \Manticoresearch\ESMigrator::getDefaultConfig(),
        json_decode(file_get_contents($config_file), true)
    );
    if (isset($config['threads'])) {
        $threads = $config['threads'];
    }
    if (isset($config['indexes'])) {
        $indexes = $config['indexes'];
    }
}

foreach ($flatkeys as $key => $default_value) {
    $ancestors = explode('.', $key);
    $value = $args->getOpt($key, $default_value);
    if ($value !== "") {
        set_nested_value($config, $ancestors, $value);
    }
}
if (isset($args['indexes']) && $args['indexes'] !== "") {
    $indexes = explode(',', $args['indexes']);
}
$threads = $args->getOpt('threads', $threads);

if (is_array($indexes) && count($indexes) > 0) {
    $iMax = count($indexes);
    $i = 0;
    for ($j = 0; $j < $threads; $j++) {
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
                $logger = new Logger('Thread '.$fork.": Index ".$index.":");
                $logger->pushHandler($streamHandler);
                $migrator = new Manticoresearch\ESMigrator($config, $logger);
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
                $logger = new Logger('Thread '.$fork.": Index ".$index['index'].":");
                $logger->pushHandler($streamHandler);
                $migrator = new Manticoresearch\ESMigrator($config, $logger);
                $esi = $migrator->getESIndex(['index' => $index['index']]);
                $migrator->migrateIndex($esi[0]);
            }
            exit();
        }
    }
    while (pcntl_waitpid(0, $status) !== -1) {
    }
}
