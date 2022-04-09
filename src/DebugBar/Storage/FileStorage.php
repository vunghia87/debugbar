<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Storage;

/**
 * Stores collected data into files
 */
class FileStorage implements StorageInterface
{
    protected $dirname;

    /**
     * @param string $dirname Directories where to store files
     */
    public function __construct($dirname)
    {
        $this->dirname = rtrim($dirname, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data)
    {
        if (!file_exists($this->dirname)) {
            mkdir($this->dirname, 0777, true);
        }
        file_put_contents($this->makeFilename($id), json_encode($data));
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        return json_decode(file_get_contents($this->makeFilename($id)), true);
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $filters = array(), $max = 20, $offset = 0)
    {
        //Loop through all .json files and remember the modified time and id.
        $files = array();
        foreach (new \DirectoryIterator($this->dirname) as $file) {
            if ($file->getExtension() == 'json') {
                $files[] = array(
                    'time' => $file->getMTime(),
                    'id' => $file->getBasename('.json')
                );
            }
        }

        //Sort the files, newest first
        usort($files, function ($a, $b) {
            return $a['time'] < $b['time'];
        });

        //Load the metadata and filter the results.
        $results = array();
        $i = 0;
        foreach ($files as $index => $file) {
            //When filter is empty, skip loading the offset
            if ($i++ < $offset && empty($filters)) {
                $results[] = null;
                continue;
            }
            $data = $this->get($file['id']);
            if (!isset($filters['type'])) {
                $type = '__meta';
                $meta = $data[$type];
                if ($this->filter($meta, $filters)) {
                    $results[] = $meta;
                }
            } elseif ($filters['type'] == 'monitor') {
                if ($result = $this->monitor($data, $filters['monitors'] ?? [])) {
                    $results[$index] = $result;
                }
            } else {
                $results[$index]['__meta'] = $data['__meta'];
                $meta = $data[$filters['type']];
                if ($this->filter($meta, $filters)) {
                    $results[$index]['response'] = $meta;
                }
            }

            unset($data);
            if (count($results) >= ($max + $offset)) {
                break;
            }
        }

        return array_slice($results, $offset, $max);
    }

    /**
     * Filter the metadata for matches.
     *
     * @param array $meta
     * @param array $filters
     * @return bool
     */
    protected function filter($meta, $filters)
    {
        foreach ($filters as $key => $value) {
            if (!isset($meta[$key])) {
                continue;
            }
            if (fnmatch($value, $meta[$key]) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        foreach (new \DirectoryIterator($this->dirname) as $file) {
            if (substr($file->getFilename(), 0, 1) !== '.') {
                unlink($file->getPathname());
            }
        }
    }

    /**
     * @param string $id
     * @return string
     */
    public function makeFilename($id)
    {
        return $this->dirname . basename($id) . ".json";
    }

    /**
     * Hardcode to filter collection from debugBar
     * @param array $filters
     * @param array $data
     * @return mixed
     */
    private function monitor(array $data, array $filters = [])
    {
        $fnCheck = function ($value, array $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($value, $keyword)) {
                    return true;
                }
            }
            return false;
        };

        $dataType = [];
        $dataKey = '';
        foreach ($filters as $type => $item) {
            $keywords = is_array($item)
                ? $item
                : explode(' ', preg_replace('!\s+!', ' ', $item));
            switch ($type) {
                case 'request':
                    $dataType = $data['request']['data'] ?? [];
                    $dataKey = function ($value) {
                        return strip_tags($value['value'] ?? '');
                    };
                    break;
                case 'db':
                    $dataType = $data['pdo']['statements'] ?? [];
                    $dataKey = 'sql';
                    break;
                case 'memcache':
                    $dataType = $data['memcache']['memcaches'] ?? [];
                    $dataKey = 'key';
                    break;
                case 'command':
                    $dataType = $data['command']['commands'] ?? [];
                    $dataKey = 'arguments';
                    break;
                case 'response':
                    $dataType = $data['response'] ?? [];
                    $dataKey = function ($value) {
                        return strip_tags($value ?? '');
                    };
                    break;
            }

            foreach ($dataType as $value) {
                $content = is_callable($dataKey)
                    ? $dataKey($value)
                    : $value[$dataKey] ?? '';

                if ($fnCheck($content, $keywords)) {
                    return [
                        'target' => implode(',', $keywords),
                        'type' => $type,
                        'value' => $content,
                        '__meta' => $data['__meta']
                    ];
                }
            }
        }

        return false;
    }
}
