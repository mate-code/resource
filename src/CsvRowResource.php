<?php

namespace mate\Resource;

use mate\Resource\Exception\BadMethodCallException;
use mate\Resource\Exception\InvalidArgumentException;
use mate\Resource\Helper\Csv\FileOptions;
use mate\Resource\Helper\Csv\Keymap;
use mate\Resource\Helper\Path\Path;

/**
 * Class CsvRow
 * @package mate\Resource
 * @author Marius Teller <marius.teller@modotex.com>
 */
class CsvRowResource extends CsvResourceAbstract
{

    const EXCEPTION_RESOURCE_MUST_BE_ARRAY = 'Resource given to __construct() method of CsvRowResource must be of type array, %s given';
    const EXCEPTION_PROHIBITED_METHOD = 'Method %s should not be accessed in CsvRowResource, use it in CsvResource instead';
    const EXCEPTION_TOO_MANY_PATH_KEYS = 'Only one path key can be given to getRecursive in CsvRowResource, $d given';
    const EXCEPTION_SETTING_INVALID_VALUE = 'Only simple data types convertible to strings can be set, %s given';

    public function __construct(&$resource, $options = array())
    {
        if(!is_array($resource)) {
            throw new InvalidArgumentException(sprintf(self::EXCEPTION_RESOURCE_MUST_BE_ARRAY, gettype($resource)));
        }
        if(isset($options['keymap'])) {
            $this->setKeymap($options['keymap']);
        }

        if(isset($options['fileOptions']) && $options['fileOptions'] instanceof FileOptions) {
            $this->fileOptions = $options['fileOptions'];
        }

        $fileOptions = $this->getFileOptions();
        if(isset($options['csvDelimiter'])) {
            trigger_error("Deprecated config [csvDelimiter], use [file][delimiter] instead.", E_USER_DEPRECATED);
            $fileOptions->setDelimiter($options['csvDelimiter']);
        }
        if(isset($options['csvEnclosure'])) {
            trigger_error("Deprecated config [csvEnclosure], use [file][enclosure] instead.", E_USER_DEPRECATED);
            $fileOptions->setEnclosure($options['csvEnclosure']);
        }
        if(isset($options['csvLineFeed'])) {
            trigger_error("Deprecated config [csvLineFeed], use [file][lineFeed] instead.", E_USER_DEPRECATED);
            $fileOptions->setLineFeed($options['csvLineFeed']);
        }
        if(isset($options['encoding']['from'])) {
            trigger_error("Deprecated config [encoding][from], use [file][objectEncoding] instead.", E_USER_DEPRECATED);
            $fileOptions->setObjectEncoding($options['encoding']['from']);
        }
        if(isset($options['encoding']['to'])) {
            trigger_error("Deprecated config [encoding][to], use [file][fileEncoding] instead.", E_USER_DEPRECATED);
            $fileOptions->setObjectEncoding($options['encoding']['to']);
        }
        $this->setFileOptions($fileOptions);

        if(isset($options['separator'])) {
            $this->separator = $options['separator'];
        }
        foreach ($resource as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * copy the resource array
     */
    public function __clone()
    {
        $resource = $this->resource;
        unset($this->resource);
        $this->resource = $resource;
    }

    /**
     * <p>Should find a value by key</p>
     * <p>Always check if you should return resource object or actual value. To
     * do so, you can use the returnValue() method of ResourceAbstract</p>
     * @param string $key
     * @param mixed $default NULL
     * @return mixed|ResourceAbstract if returnResource=true and value is nested
     */
    public function get($key, $default = NULL)
    {
        $key = $this->resolveKey($key);
        if($key !== false && isset($this->resource[$key])) {
            return $this->resource[$key];
        }
        return $default;
    }

    /**
     * <p>should find a value by multiple keys</p>
     * <p>keys can be passed by an iterative array or a string with separators</p>
     * <p>Always check if you should return resource object or actual value. To
     * do so, you can use the returnValue() method of ResourceAbstract</p>
     * @param string|array $keys
     * @param mixed $default NULL
     * @throws InvalidArgumentException
     * @return mixed|ResourceAbstract if returnResource=true and value is nested
     */
    public function getRecursive($keys, $default = NULL)
    {
        $key = $this->getSingleKey($keys);
        return $this->get($key, $default);
    }

    /**
     * throws an exception if more than one key is passed, otherwise returns the single key
     * @param $keys
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function getSingleKey($keys)
    {
        if(is_string($keys) || is_numeric($keys)) {
            $count = substr_count($keys, $this->separator)+1;
            $key = $keys;
        } elseif($keys instanceof Path) {
            $count = count($keys->getPathArray());
            $key = $keys->current();
        } elseif(is_array($keys)) {
            $count = count($keys);
            $key = $keys[0];
        } else {
            throw new InvalidArgumentException(ResourceAbstract::INVALID_PATH);
        }
        if($count !== 1) {
            throw new InvalidArgumentException(sprintf(self::EXCEPTION_TOO_MANY_PATH_KEYS, $count));
        }
        return $key;
    }

    /**
     * <p>should set a value by key</p>
     * <p>Always check if you should return resource object or actual value. To
     * do so, you can use the returnValue() method of ResourceAbstract</p>
     * @param string $key
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function set($key, $value)
    {
        if(!is_scalar($value) && $value !== NULL && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new InvalidArgumentException(sprintf(self::EXCEPTION_SETTING_INVALID_VALUE, gettype($value)));
        }
        $numericKey = $this->resolveKey($key);
        if($numericKey === false) {
            $numericKey = $this->keymap->add($key);
        }
        $this->resource[$numericKey] = (string)$value;
    }

    /**
     * <p>should set a value by multiple keys</p>
     * <p>keys can be passed by an iterative array or a string with separators</p>
     * <p>Always check if you should return resource object or actual value. To
     * do so, you can use the returnValue() method of ResourceAbstract</p>
     * @param string|array $keys
     * @param mixed $value
     */
    public function setRecursive($keys, $value)
    {
        $key = $this->getSingleKey($keys);
        $this->set($key, $value);
    }

    /**
     * looks for a numeric key in the keymap if $key is not numeric
     * @param $key
     * @return null|int
     */
    protected function resolveKey($key) {
        if(is_numeric($key)) {
            return (int) $key;
        } else {
            return $this->keymap->pos($key);
        }
    }

    /**
     * finds values in all nodes and returns all results in an array
     * @param mixed $find
     * @return array
     */
    public function find($find)
    {
        return $this->get($find);
    }

    /**
     * empty, because a csv row will always return a string
     * @param boolean $returnResource
     */
    public function setReturnResource($returnResource)
    {
    }

    /**
     * false, because a csv row will always return a string
     */
    public function getReturnResource()
    {
        return false;
    }

    /**
     * get encoded csv row
     * @return string
     */
    public function encode()
    {
        // necessary in case of changed keymap
        $values = array();
        $keymap = $this->keymap->toArray();
        foreach ($keymap as $pos => $key) {
            $values[$pos] = isset($this->resource[$pos]) ? $this->resource[$pos] : '';
        }
        $values = $this->encodeCharset($values, true);

        $fileOptions = $this->getFileOptions();
        $delimiter = $fileOptions->getDelimiter();
        $enclosure = $fileOptions->getEnclosure();
        $forceEnclosure = $fileOptions->getForceEnclosure();

        if($forceEnclosure) {
            $implodeDelimiter = $enclosure . $delimiter . $enclosure;
            $csvString = $enclosure;
            $csvString .= implode($implodeDelimiter, $values);
            $csvString .= $enclosure;
        } else {
            $fp = fopen('php://temp', 'r+b');
            fputcsv($fp, $values, $delimiter, $enclosure);
            rewind($fp);
            $csvString = rtrim(stream_get_contents($fp), "\n");
            fclose($fp);
        }

        return $csvString;
    }

    public function write($file = NULL)
    {
        throw new BadMethodCallException(sprintf(self::EXCEPTION_PROHIBITED_METHOD, __FUNCTION__));
    }

    /**
     * converts resource into an array
     */
    public function toArray()
    {
        if($this->readingKeysFromKeymap) {
            $array = array();
            $keymap = $this->getKeymap();
            foreach ($this->resource as $pos => $value) {
                $key = $keymap->key($pos);
                $array[$key] = $value;
            }
            return $array;
        } else {
            return $this->resource;
        }
    }

    /**
     * checks if a value is set
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        $key = $this->resolveKey($key);
        return $key !== false && isset($this->resource[$key]);
    }

    /**
     * checks by multiple keys if a value is set
     * @param string|array $keys
     * @return boolean
     */
    public function hasRecursive($keys)
    {
        $keys = $this->resolvePath($keys);
        if(count($keys) == 1) {
            return $this->has($keys[0]);
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $key = $this->resolveKey($key);
        $this->resource[$key] = NULL;
    }
    
    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->resource);
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
     */
    public function current()
    {
        return current($this->resource);
    }

    /**
     * key - see PHPs \Iterator
     * @param bool $fromKeymap if true, returns the keymap value for the current key
     * @return mixed current key
     */
    public function key($fromKeymap = NULL)
    {
        $fromKeymap = $fromKeymap === NULL ? $this->readingKeysFromKeymap : (bool) $fromKeymap;
        $pos = key($this->resource);
        $returnKey = $pos;
        if($fromKeymap == true) {
            $returnKey = $this->getKeymap()->key($pos);
        }
        return $returnKey;
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
     * @return array
     */
    public function &getResource()
    {
        return $this->resource;
    }

    /**
     * WARNING! You should only set the keymap in CsvResource to change it for all rows!
     * @param Keymap $keymap
     */
    public function setKeymap(Keymap $keymap)
    {
        $this->keymap = $keymap;
    }

    /**
     * WARNING! You should only set the separator in CsvResource to change it for all rows!
     * @param string $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    /**
     * WARNING! You should only set the separator in CsvResource to change it for all rows!
     * defines if keys should be returned as position number or read from keymap
     * @return boolean
     */
    public function isReadingKeysFromKeymap()
    {
        return $this->readingKeysFromKeymap;
    }

    /**
     * WARNING! You should only set the separator in CsvResource to change it for all rows!
     * defines if keys should be returned as position number or read from keymap
     * @param boolean $readingKeysFromKeymap
     */
    public function setReadingKeysFromKeymap($readingKeysFromKeymap)
    {
        $this->readingKeysFromKeymap = (bool) $readingKeysFromKeymap;
    }

}