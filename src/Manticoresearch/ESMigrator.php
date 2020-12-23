<?php

namespace Manticoresearch;

use Elasticsearch\ClientBuilder;
use Manticoresearch\ESMigrator\DataType\DataType;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ESMigrator
{
    protected $logger;
    protected $elasticsearch;
    protected $manticoresearch;
    protected $config = [
        'dryrun' => false,
        'elasticsearch' =>
            [
                'host' => '127.0.0.1',
                'port' => 9200,
                'user' => '',
                'pass' => '',
                'batch_size' => 10000,
                'limit' => 0,
            ],
        'manticoresearch' => [
            'host' => '127.0.0.1',
            'port' => 9308,
            'batch_size' => 10000,
        ],


        'limit' => 0,
        'types' => [

            //Common types
            'boolean' => ['type' => 'integer', 'transform' => 'Native'],

            'keyword' => ['type' => 'string', 'transform' => 'Native'],
            'constant_keyword' => ['type' => 'string', 'transform' => 'Native'],
            'wildcard' => ['type' => 'string', 'transform' => 'Native'],

            'float' => ['type' => 'float', 'transform' => 'Native'],
            'long' => ['type' => 'bigint', 'transform' => 'Native'],
            'integer' => ['type' => 'integer', 'transform' => 'Native'],
            'short' => ['type' => 'integer', 'transform' => 'Native'],
            'byte' => ['type' => 'integer', 'transform' => 'Native'],
            'double' => ['type' => 'float', 'transform' => 'Native'],
            'half_float' => ['type' => 'float', 'transform' => 'Native'],
            'scaled_float' => ['type' => 'bigint', 'transform' => 'Native'],
            'unsigned_long' => ['type' => 'bigint', 'transform' => 'Native'],

            //Structured

            'range' => ['type' => 'json', 'transform' => 'Native'],
            'ip' => ['type' => 'integer', 'transform' => 'IP'],
            'version' => ['type' => 'string', 'transform' => 'Native'],
            'date' => ['type' => 'timestamp', 'transform' => 'Date'],
            // Text Search types

            'text' => ['type' => 'text', 'transform' => 'Native']

        ]
    ];

    public function __construct($config = [], LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->setConfig($config);
        $this->setup();
    }


    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }
    public static function getConfigKeys()
    {
        function flatten($array, $prefix = '') {
            $result = array();
            foreach($array as $key=>$value) {
                if(is_array($value)) {
                    $result += flatten($value, $prefix . $key . '.');
                }
                else {
                    $result[$prefix . $key] = $value;
                }
            }
            return $result;
        }
        $static = new static;
        return flatten($static->config);
    }
    public function setup()
    {
        $this->elasticsearch = ClientBuilder::create()->setHosts([$this->config['elasticsearch']])->build();
        $this->manticoresearch = new Client($this->config['manticoresearch'], $this->logger);
    }

    protected function transformDoc($doc, $transform)
    {
        foreach ($doc as $k => $v) {
            $doc[$k] = $transform[$k]($v);
        }
        return $doc;
    }

    protected function transformType($estype, $types)
    {
        if (isset($estype['type']) && isset($types[$estype['type']])) {
            if (is_string($types[$estype['type']]['transform'])) {
                if (class_exists('\\Manticoresearch\\ESMigrator\\DataType\\' . $types[$estype['type']]['transform'])) {
                    $translateClass = '\\Manticoresearch\\ESMigrator\\DataType\\' . $types[$estype['type']]['transform'];
                }elseif(class_exists($types[$estype['type']]['transform'])) {
                    $translateClass =$types[$estype['type']]['transform'];
                }
                $translate = new $translateClass();
            } elseif($types[$estype['type']]['transform'] instanceof DataType) {
                $translate = $types[$estype['type']]['transform'];
            }else{
                throw new \RuntimeException('Invalid transform class for data type '.$types[$estype['type']]);
            }

            return $translate->translate($estype, $types);
        }
        return ['type' => 'json', 'transform' => function ($field) {
            return $field;
        }];
    }


    public function migrateAll()
    {


        $indices = $this->elasticsearch->cat()->indices();
        foreach ($indices as $v => $index) {
            if ($index['index'][0] === '.') {
                unset($indices[$v]);
            }
        }
        $indices = array_values($indices);
        flush();
        foreach ($indices as $index) {
            $this->logger->debug('Start migration of ' . $index['index']);
            $start = microtime(true);
            $this->migrateIndex($index);
            $end = microtime(true);
            $took = (float)($end - $start);
            $this->logger->debug('Migration of ' . $index['index'] . ' took ' . number_format($took, 2) . 's');
        }
    }

    public function getESIndex($name)
    {
        return $this->elasticsearch->cat()->indices($name);
    }

    public function migrateIndex($index)
    {
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $index['mapping'] = $this->elasticsearch->indices()->getMapping(['index' => $index['index']])[$index['index']]['mappings'];
        $index['type_mapping'] = [];
        $type_transforms = [];
        $has_text = false;
        foreach ($index['mapping']['properties'] as $field => $map) {
            $type = $this->transformType($map, $this->config['types']);
            $index['type_mapping'][$field] = ['type' => $type['type']];
            $type_transforms[$field] = $type['transform'];
            if ($type['type'] === 'text') {
                $has_text = true;
            }
        }
        if ($this->config['dryrun'] === true) {
            echo $index['index'] . "\n";
            $output = null;

            print_r($index);
            return false;
        }
        $msindex_name = preg_replace('/[^a-z_\d]/i', '', $index['index']);
        $msIndex = $this->manticoresearch->index($msindex_name);
        $this->logger->debug('Creating index ' . $index['index']);
        $msIndex->drop(true);
        if ($has_text === true) {
            $msIndex->create($index['type_mapping']);
        } else {
            $msIndex->create(array_merge(['dummy' => ['type' => 'text']], $index['type_mapping']));
        }

        $this->logger->debug('Importing index' . $index['index']);
        $fh = proc_open("elasticdump --input=http://" .
            $this->config['elasticsearch']['user'] . ":" .
            $this->config['elasticsearch']['pass'] . "@" .
            $this->config['elasticsearch']['host'] . ":" .
            $this->config['elasticsearch']['port'] . "/" .
            $index['index'] .
            " --limit " . $this->config['elasticsearch']['batch_size'] . " --noRefresh --type=data --output=$", $descriptorspec,
            $pipes, realpath('./'), array());
        $batch = 0;

        $batch_docs = [];
        $i = 0;

        if (is_resource($fh)) {
            while ($line = fgets($pipes[1])) {
                $doc = json_decode($line, true);
                flush();
                if ($i > $this->config['limit'] && $this->config['limit'] !== 0) {
                    $batch_docs[] = $this->transformDoc($doc['_source'], $type_transforms);

                    $this->addToManticore($msIndex, $batch_docs);
                    return true;
                }
                if ($batch < $this->config['manticoresearch']['batch_size']) {
                    $batch_docs[] = $this->transformDoc($doc['_source'], $type_transforms);
                    $batch++;
                } else {
                    $batch_docs[] = $this->transformDoc($doc['_source'], $type_transforms);
                    $this->addToManticore($msIndex, $batch_docs);
                    $batch = 0;
                    $batch_docs = [];
                }

                $i++;
            }
            if (count($batch_docs) > 0) {

                $r = $this->addToManticore($msIndex, $batch_docs);

            }
            fclose($pipes[1]);
        } else {

            $this->logger->error('Invalid data received from dump tool');
        }

        return true;
    }

    public function addToManticore($index, $data)
    {
        return $index->addDocuments($data);
    }
}