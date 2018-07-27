<?php

namespace mate\Resource;

use mate\Resource\Exception\InvalidArgumentException;
use mate\Resource\Exception\RuntimeException;
use mate\Resource\Helper\Csv\FileOptions;
use mate\Resource\Helper\Csv\Keymap;

/**
 * Class CsvResource
 * @package mate\Resource
 * @author Marius Teller <marius.teller@modotex.com>
 */
class CsvResource extends CsvResourceAbstract implements WritableResourceInterface
{
    /**
     * @var CsvRowResource[]
     */
    protected $resource = array();

    /**
     * @var bool
     */
    protected $returnResource = true;

    const EXCEPTION_RESOURCE_MUST_BE_FILE = 'Resource given to __construct() method of CsvRowResource must be a valid path to a csv file, %s given';
    const EXCEPTION_SETTING_INVALID_DATA_TYPE = 'Value passed to set() must by type of array or CsvRowResource, %s given';
    const EXCEPTION_TOO_MANY_PATH_KEYS = 'Maximum of path keys given to getRecursive in CsvResource is 2, $d given';
    const EXCEPTION_KEYMAP_NOT_SET = 'The csv resource was read from an empty file, set the keymap manually';

    /**
     * @param mixed $resource
     * @param array $options
     * @throws RuntimeException
     */
    public function __construct(&$resource, $options = array())
    {
        if(isset($options['keymap'])) {
            $this->setKeymap($options['keymap']);
        } else {
            $this->setKeymap(array());
        }

        $this->fileOptions = new FileOptions();
        if(isset($options['fileOptions'])) {
            $this->setFileOptions($options['fileOptions']);
        }

        if(isset($options['csvDelimiter'])) {
            trigger_error("Deprecated config [csvDelimiter], use [fileOptions][delimiter] instead.", E_USER_DEPRECATED);
            $this->fileOptions->setDelimiter($options['csvDelimiter']);
        }
        if(isset($options['csvEnclosure'])) {
            trigger_error("Deprecated config [csvEnclosure], use [fileOptions][enclosure] instead.", E_USER_DEPRECATED);
            $this->fileOptions->setEnclosure($options['csvEnclosure']);
        }
        if(isset($options['csvLineFeed'])) {
            trigger_error("Deprecated config [csvLineFeed], use [fileOptions][lineFeed] instead.", E_USER_DEPRECATED);
            $this->fileOptions->setLineFeed($options['csvLineFeed']);
        }
        if(isset($options['encoding']['from'])) {
            trigger_error("Deprecated config [encoding][from], use [fileOptions][objectEncoding] instead.", E_USER_DEPRECATED);
            $this->fileOptions->setObjectEncoding($options['encoding']['from']);
        }
        if(isset($options['encoding']['to'])) {
            trigger_error("Deprecated config [encoding][to], use [fileOptions][fileEncoding] instead.", E_USER_DEPRECATED);
            $this->fileOptions->setObjectEncoding($options['encoding']['to']);
        }

        if(isset($options['separator'])) {
            $this->separator = $options['separator'];
        }

        if(is_string($resource) && is_file($resource)) {
            $this->readCsv($resource);
            if(!$this->fileOptions->getFilePath()) {
                $this->fileOptions->setFilePath($resource);
            }
        } else {
            throw new RuntimeException(sprintf(self::EXCEPTION_RESOURCE_MUST_BE_FILE, gettype($resource)));
        }
    }

    /**
     * clone the csv row objects and the resource as well
     */
    public function __clone()
    {
        foreach ($this->resource as $pos => $row) {
            $this->resource[$pos] = clone $row;
        }
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed|CsvRowResource
     */
    public function get($key, $default = NULL)
    {
        if(isset($this->resource[$key])) {
            return $this->returnResource === true
                ? $this->resource[$key]
                : $this->resource[$key]->getResource();
        }
        return $default;
    }

    /**
     * <p>finds a value by multiple keys</p>
     * <p>keys can be passed by an iterative array, Path object or a string with separators</p>
     * @param string|array $keys
     * @param mixed $default NULL
     * @throws InvalidArgumentException if more than two keys are given
     * @return mixed|ResourceAbstract if returnResource=true and value is nested
     */
    public function getRecursive($keys, $default = NULL)
    {
        $path = $this->resolvePath($keys);
        $pathEntries = count($path);
        switch ($pathEntries) {
            case 1:
                return $this->get($path[0]);
            case 2:
                $initialSetting = $this->returnResource;
                $this->returnResource = true;
                $row = $this->get($path[0], $default);
                if($row !== $default) {
                    $return = $row->get($path[1], $default);
                } else {
                    $return = $default;
                }
                $this->returnResource = $initialSetting;
                return $return;
            default:
                throw new InvalidArgumentException(sprintf(self::EXCEPTION_TOO_MANY_PATH_KEYS, $pathEntries));
        }
    }

    /**
     * <p>sets a row by key</p>
     * @param string $key
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function set($key, $value)
    {
        if(is_array($value)) {
            $this->resource[$key] = new CsvRowResource($value, array(
                'keymap'       => $this->keymap,
                'fileOptions' => $this->fileOptions,
                'separator'    => $this->separator,
            ));
        } elseif($value instanceof CsvRowResource) {
            $this->resource[$key] = $value;
        } else {
            throw new InvalidArgumentException(sprintf(self::EXCEPTION_SETTING_INVALID_DATA_TYPE, gettype($value)));
        }
    }

    /**
     * <p>sets a value by multiple keys</p>
     * <p>keys can be passed by an iterative array, Path object or a string with separators</p>
     * @param string|array $keys
     * @param mixed $value
     * @throws InvalidArgumentException if more than two keys are given
     */
    public function setRecursive($keys, $value)
    {
        $path = $this->resolvePath($keys);
        $pathEntries = count($path);
        switch ($pathEntries) {
            case 1:
                $this->set($path[0], $value);
                break;
            case 2:
                $initialSetting = $this->returnResource;
                $this->returnResource = true;
                $row = $this->get($path[0], false);
                if($row === false) {
                    $row = $this->setEmptyNode($path[0]);
                }
                $row->set($path[1], $value);
                $this->returnResource = $initialSetting;
                break;
            default:
                throw new InvalidArgumentException(sprintf(self::EXCEPTION_TOO_MANY_PATH_KEYS, $pathEntries));
        }
    }

    /**
     * set empty CsvRowResource to $position
     * @param int $position
     * @return CsvRowResource
     */
    protected function setEmptyNode($position)
    {
        $emptyRow = array();
        $rowOptions = array(
            'separator'    => $this->separator,
            'fileOptions' => $this->fileOptions,
            'keymap'       => $this->keymap,
        );
        $csvRow = new CsvRowResource($emptyRow, $rowOptions);
        $this->set($position, $csvRow);
        return $csvRow;
    }

    /**
     * finds values in all nodes and returns all results in an array
     * @param mixed $find
     * @return array
     */
    public function find($find)
    {
        $return = $this->get($find);
        if($return === NULL) {
            $found = array();
            foreach ($this->resource as $row) {
                $value = $row->get($find);
                if(!$value !== NULL && $value !== '') {
                    $found[] = $value;
                }
            }
            $return = !empty($found) ? $found : NULL;
        }
        return $return;
    }

    /**
     * if set to true (default) resources will return new resource objects
     * @param boolean $returnResource
     */
    public function setReturnResource($returnResource)
    {
        $this->returnResource = (bool)$returnResource;
    }

    /**
     * if set to true (default) resources will return new resource objects
     */
    public function getReturnResource()
    {
        return $this->returnResource;
    }

    /**
     * checks if a value is set
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return isset($this->resource[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->resource[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->resource);
    }

    /**
     * checks by multiple keys if a value is set
     * @param string|array $keys
     * @return boolean
     */
    public function hasRecursive($keys)
    {
        $path = $this->resolvePath($keys);
        switch (count($path)) {
            case 1:
                $has = isset($this->resource[$path[0]]);
                break;
            case 2:
                $row = $this->get($path[0]);
                if($row !== NULL && $row->get($path[1]) !== NULL) {
                    $has = true;
                } else {
                    $has = false;
                }
                break;
            default:
                $has = false;
        }
        return $has;
    }

    /**
     * converts resource into an array
     */
    public function toArray()
    {
        $array = array();
        foreach ($this->resource as $pos => $row) {
            $array[$pos] = $row->toArray();
        }
        return $array;
    }

    /**
     * rewind - see PHPs \Iterator
     */
    public function rewind()
    {
        reset($this->resource);
    }

    /**
     * current - see PHPs \Iterator
     * @return array|CsvRowResource depending on returnResource setting
     */
    public function current()
    {
        /** @var CsvRowResource $current */
        $current = current($this->resource);
        return $this->returnResource === true ? $current : $current->getResource();
    }

    /**
     * key - see PHPs \Iterator
     */
    public function key()
    {
        return key($this->resource);
    }

    /**
     * next - see PHPs \Iterator
     */
    public function next()
    {
        next($this->resource);
    }

    /**
     * valid - see PHPs \Iterator
     */
    public function valid()
    {
        return current($this->resource) !== false;
    }


    /**
     * read csv file and set keymap
     * @param $csvFile
     */
    protected function readCsv($csvFile)
    {
        $handle = fopen($csvFile, 'r');
        $row = 0;

        $fileOptions = $this->getFileOptions();
        $delimiter = $fileOptions->getDelimiter();
        $enclosure = $fileOptions->getEnclosure();

        $rowOptions = array(
            'separator'    => $this->separator,
            'fileOptions' => $fileOptions,
        );

        $keymapData = fgetcsv($handle, 0, $delimiter, $enclosure);
        if($keymapData) {
            $keymapData = $this->encodeCharset($keymapData);
            $this->setKeymap($keymapData);
            $rowOptions['keymap'] = $this->keymap;
        }

        while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
            $data = $this->encodeCharset($data);
            $this->resource[$row] = new CsvRowResource($data, $rowOptions);
            $row++;
        }

        fclose($handle);
    }

    /**
     * convert resource to CSV string
     * @return string
     */
    public function encode()
    {
        $keymap = $this->keymap->toArray();
        $fileOptions = $this->getFileOptions();
        $delimiter = $fileOptions->getDelimiter();
        $enclosure = $fileOptions->getEnclosure();
        $csvLineFeed = $fileOptions->getLineFeed();
        $forceEnclosure = $fileOptions->getForceEnclosure();

        $keymap = $this->encodeCharset($keymap, true);

        if($forceEnclosure) {
            $implodeDelimiter = $enclosure . $delimiter . $enclosure;
            $keymapString = $enclosure;
            $keymapString .= implode($implodeDelimiter, $keymap);
            $keymapString .= $enclosure;
        } else {
            $fp = fopen('php://temp', 'r+b');
            fputcsv($fp, $keymap, $delimiter, $enclosure);
            rewind($fp);
            $keymapString = rtrim(stream_get_contents($fp), "\n");
            fclose($fp);
        }

        $csv = $keymapString;
        foreach ($this->resource as $row) {
            $csv .= $csvLineFeed . $row->encode();
        }
        return $csv;
    }

    /**
     * writes the csv resource to file
     * @param null $file if null, use the file the csv was read from
     * @throws RuntimeException
     */
    public function write($file = NULL)
    {
        $keymap = $this->getKeymap();
        if($keymap === NULL) {
            throw new RuntimeException(self::EXCEPTION_KEYMAP_NOT_SET);
        }
        $file = $file === NULL ? $this->getFileOptions()->getFilePath() : $file;
        file_put_contents($file, $this->encode());
    }

    /**
     * @return array
     */
    public function getResource()
    {
        $return = array();
        /** @var CsvRowResource $row */
        foreach ($this->resource as $pos => $row) {
            $return[$pos] = $row->getResource();
        }
        return $return;
    }

    /**
     * set keymap for all rows
     * @param array|Keymap $keymap
     */
    public function setKeymap($keymap)
    {
        if(is_array($keymap)) {
            $keymap = new Keymap($keymap);
        }
        foreach ($this->resource as $row) {
            $row->setKeymap($keymap);
        }
        $this->keymap =& $keymap;
    }

    /**
     * set separator for paths to get or set recursive
     * @param string $separator
     */
    public function setSeparator($separator)
    {
        foreach ($this->resource as $row) {
            $row->setSeparator($separator);
        }
        $this->separator = $separator;
    }

    /**
     * defines if keys should be returned as position number or read from keymap
     * @return boolean
     */
    public function isReadingKeysFromKeymap()
    {
        return $this->readingKeysFromKeymap;
    }

    /**
     * defines if keys should be returned as position number or read from keymap
     * @param boolean $readingKeysFromKeymap
     */
    public function setReadingKeysFromKeymap($readingKeysFromKeymap)
    {
        foreach ($this->resource as $row) {
            $row->setReadingKeysFromKeymap($readingKeysFromKeymap);
        }
        $this->readingKeysFromKeymap = (bool)$readingKeysFromKeymap;
    }

    /**
     * @deprecated use getFileOptions()->getFilePath()
     * @return string
     */
    public function getFile()
    {
        return $this->getFileOptions()->getFilePath();
    }

    /**
     * @deprecated use getFileOptions()->setFilePath()
     * @param string $filePath
     */
    public function setFile($filePath)
    {
        $this->getFileOptions()->setFilePath($filePath);
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->getFileOptions()->getFilePath();
    }

    /**
     * @param string $file
     */
    public function setFilePath($file)
    {
        $this->getFileOptions()->setFilePath($file);
    }

}