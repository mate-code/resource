<?php

namespace mate\Resource;

use mate\Resource\Exception\InvalidArgumentException;
use mate\Resource\Helper\Path\Path;

/**
 * <p>Class for resource objects. Resource objects will always show the exact same behaviour
 * no matter with which kind of data they were builded.</p>
 * <p>IMPORTANT! If you create new resource classes make use of references!
 * See http://php.net/manual/en/language.references.php for information</p>
 */
abstract class ResourceAbstract implements ResourceInterface
{
    /**
     * resource that was initially passed to create the object
     * @var mixed
     */
    public $resource;
    /**
     * <p>defines if get() should return a resource object or the actual value</p>
     * <p>TRUE by default, set $options['returnResource']  in constructor
     * to change default value</p>
     * @var bool
     */
    protected $returnResource = true;
    /**
     * separator for pathes to get or set recursive
     * @var string
     */
    protected $separator = '/';

    const INVALID_PATH = 'path to find values recursive needs to be either string or array';
    const EXCEPTION_PROHIBITED_METHOD = '%s should not be accessed';

    /**
     *
     * @param mixed $resource
     * @param array $options
     */
    public function __construct(&$resource, $options = array())
    {
        if(!isset($options['breakReference']) || $options['breakReference'] == true) {
            $tmp = $resource;
            unset($resource);
            $resource = $tmp;
        }
        if(isset($options['returnResource'])) {
            $this->returnResource = $options['returnResource'];
        }
        if(isset($options['separator'])) {
            $this->separator = $options['separator'];
        }
        if(isset($options['start'])) {
            $tmp = ResourceFactory::create($resource);
            $resource =& $tmp->getRecursive($options['start'])->getResource();
        }
        $this->setResource($resource);
    }

    /**
     * clone resource as well
     */
    public function __clone()
    {
        if(is_object($this->resource)) {
            $this->resource = clone $this->resource;
        }
    }

    /**
     * <p>gets value recursive by given path.</p>
     * <p>keys can be passed by an iterative array, Path object or a string with separators</p>
     * <p>$default will be returned if no property was found</p>
     * @param mixed $keys
     * @param mixed $default
     * @return mixed|ResourceAbstract if $returnResource = true
     */
    public function getRecursive($keys, $default = NULL)
    {
        $keyArray = $this->resolvePath($keys);
        $initialReturnSetting = $this->returnResource;
        $this->returnResource = true;
        $temp = $this;
        foreach ($keyArray as $property) {
            $temp = $temp->get($property);
            if($temp === NULL) {
                return $default;
            }
            if(!$temp instanceof ResourceInterface) {
                break;
            }
        }
        $this->returnResource = $initialReturnSetting;
        return ($temp instanceof ResourceInterface
            ? $this->returnValue($temp->getResource())
            : $this->returnValue($temp));
    }

    /**
     * <p>Sets property in given path</p>
     * <p>keys can be passed by an iterative array or a string with separators</p>
     * <p>FALSE will be returned if no property could be set</p>
     * @param mixed $keys
     * @param mixed $value
     * @return boolean
     */
    public function setRecursive($keys, $value)
    {
        $keyArray = $this->resolvePath($keys);
        $initialReturnSetting = $this->returnResource;
        $this->returnResource = true;
        $temp = $this;
        $count = count($keyArray);
        $i = 0;
        foreach ($keyArray as $property) {
            $i++;
            if($i < $count) {
                if($temp->get($property) === NULL) {
                    $temp = $temp->setEmptyNode($property);
                } else {
                    $temp = $temp->get($property);
                }
            } elseif($temp instanceof ResourceInterface) {
                $this->returnResource = $initialReturnSetting;
                return $temp->set($property, $value);
            }
        }
        $this->returnResource = $initialReturnSetting;
        return false;
    }

    /**
     * converts resource into an array
     * @return array
     */
    public function toArray()
    {
        $this->returnResource = true;
        $resource = (array)$this->getResource();
        $array = array();
        foreach ($resource as $key => $node) {
            $nodeRes = $this->returnValue($node);
            if($nodeRes instanceof ResourceInterface) {
                $array[$key] = $nodeRes->toArray();
            } else {
                $array[$key] = $nodeRes;
            }
        }
        return $array;
    }

    /**
     * returns Resource object if returnResource=true (default)
     * @param mixed $value
     * @param array $options
     * @return mixed|ResourceAbstract
     */
    protected function returnValue(&$value, array $options = array())
    {
        if(!isset($options['breakReference'])) {
            $options['breakReference'] = false;
        }
        if(!isset($options['separator'])) {
            $options['separator'] = $this->separator;
        }
        if( !is_scalar($value) &&
            $this->returnResource === true &&
            !$value instanceof ResourceInterface) {
            return ResourceFactory::create($value, $options);
        }
        return $value;
    }

    /**
     * @param $arrayOrStringPath
     * @return array
     * @throws InvalidArgumentException
     */
    protected function resolvePath($arrayOrStringPath)
    {
        if(is_array($arrayOrStringPath) || $arrayOrStringPath instanceof Path) {
            return $arrayOrStringPath;
        } elseif(is_scalar($arrayOrStringPath)) {
            return explode($this->separator, $arrayOrStringPath);
        } else {
            throw new InvalidArgumentException(self::INVALID_PATH);
        }
    }

    /**
     * set a new empty node to resource
     * @param string $property
     * @return mixed|ResourceAbstract
     */
    protected function setEmptyNode($property)
    {
        $this->set($property, array());
        $return = $this->get($property);
        return $this->returnValue($return);
    }

    /**
     * <p>Checks if property exists by given path</p>
     * <p>Keys can be passed by an iterative array or a string with separators</p>
     * @param mixed $keys
     * @return bool
     */
    public function hasRecursive($keys)
    {
        $return = $this->getRecursive($keys);
        return ($return === NULL ? false : true);
    }

    /**
     * finds values in all nodes and returns all results in an array
     * @param string $find
     * @return array
     */
    public function find($find)
    {
        $result = array();
        foreach ($this as $key => $value) {
            if($key === $find) {
                $result[] = $value;
            }
            if($value instanceof ResourceInterface) {
                $resultToPush = $value->find($find);
                foreach ($resultToPush as $singleResult) {
                    $result[] = $singleResult;
                }
            }
        }
        return $result;
    }

    /**
     * rewind
     * @see Iterator
     */
    public function rewind()
    {
        reset($this->getResource());
    }

    /**
     * current
     * @see Iterator
     */
    public function current()
    {
        $var = current($this->getResource());
        return $this->returnValue($var);
    }

    /**
     * key
     * @see Iterator
     */
    public function key()
    {
        $var = key($this->getResource());
        return $var;
    }

    /**
     * next
     * @see Iterator
     */
    public function next()
    {
        $var = next($this->getResource());
        return $this->returnValue($var);
    }

    /**
     * valid
     * @see Iterator
     */
    public function valid()
    {
        $var = $this->current() !== false;
        return $var;
    }

    /**
     * return resource that was initially passed to create the object
     * @return mixed
     */
    public function &getResource()
    {
        return $this->resource;
    }

    /**
     * set resource
     * @param mixed $resource
     */
    protected function setResource(&$resource)
    {
        $this->resource =& $resource;
    }

    /**
     * <p>defines if get() should return a resource object or the actual value</p>
     * <p>TRUE by default, set $options['returnResource']  in constructor
     * to change default value</p>
     * @return bool
     */
    public function getReturnResource()
    {
        return $this->returnResource;
    }

    /**
     * <p>defines if get() should return a resource object or the actual value</p>
     * <p>TRUE by default, set $options['returnResource']  in constructor
     * to change default value</p>
     * @param bool $returnResource
     */
    public function setReturnResource($returnResource)
    {
        $setting = (bool)$returnResource;
        $this->returnResource = $setting;
    }

    /**
     * get separator for paths to get or set recursive
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * set separator for pathes to get or set recursive
     * @param string $separator
     */
    public function setSeparator($separator)
    {
        $separatorString = (string)$separator;
        $this->separator = $separatorString;
    }

}