<?php

namespace mate\Resource;

use mate\Resource\Exception\BadMethodCallException;

class ObjectResource extends ResourceAbstract
{

    protected $publicProperties;

    /**
     * @var \Iterator
     */
    protected $iterator;
    /**
     * @var boolean
     */
    protected $implementsIterator = false;

    /**
     * @var boolean
     */
    protected $implementsIteratorAggregate = false;

    /**
     *
     * @param mixed $resource
     * @param array $options
     */
    public function __construct(&$resource, $options = array())
    {
        if($resource instanceof \Iterator) {
            $this->implementsIterator = true;
        } elseif($resource instanceof \IteratorAggregate) {
            $this->implementsIteratorAggregate = true;
            $this->iterator = $resource->getIterator();
        }
        parent::__construct($resource, $options);
    }


    /**
     * <p>Returns property with name $property</p>
     * <p>Looks for getters and public properties to do so.</p>
     * <p>NULL will be returned if property was not found</p>
     * @param string $property
     * @param mixed $default
     * @return \mate\Resource\ResourceAbstract if $returnResource = true
     */
    public function get($property, $default = NULL)
    {
        $resource = $this->getResource();
        $getter = 'get' . ucfirst($property);
        if(is_callable(array($resource, $getter))) {
            $value = $resource->$getter();
            return $this->returnValue($value);
        }
        $isser = 'is' . ucfirst($property);
        if(is_callable(array($resource, $isser))) {
            $value = $resource->$isser();
            return $this->returnValue($value);
        }
        if(is_callable(array($resource, 'get'))) {
            $value = $resource->get($property);
            return $this->returnValue($value);
        }
        if($this->propertyIsPublic($property)) {
            return $this->returnValue($resource->$property);
        }
        return $default;
    }

    /**
     * <p>Sets property with name $property</p>
     * <p>Looks for setters and public properties to do so</p>
     * <p>FALSE will be returned if property could not be set</p>
     * @param string $property
     * @param mixed $value
     */
    public function set($property, $value)
    {
        $resource = $this->getResource();
        $setter = 'set' . ucfirst($property);
        $adder = 'add' . ucfirst($property);
        if(is_callable(array($resource, $setter))) {
            $resource->$setter($value);
        } elseif(is_callable(array($resource, $adder))) {
            $resource->$adder($value);
        } elseif(is_callable(array($resource, 'set'))) {
            $resource->set($property, $value);
        } else {
            $resource->$property = $value;
            if(!$this->propertyIsPublic($property)) {
                $this->findPublicProperties();
            }
        }
    }

    /**
     * checks if property exists
     * @param string $property
     * @return boolean
     */
    public function has($property)
    {
        $value = $this->get($property);
        if($value === NULL) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->resource->$key);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if($this->resource instanceof \Countable || method_exists($this->resource, "count")) {
            return $this->resource->count();
        } elseif($this->resource instanceof \stdClass) {
            return count((array) $this->resource);
        } else {
            return count(get_object_vars($this->resource));
        }
    }

    /**
     * checks if property with name $property is public
     * @param $property
     * @return bool
     */
    public function propertyIsPublic($property)
    {
        if(!isset($this->publicProperties)) {
            $this->findPublicProperties();
        }
        if(isset($this->publicProperties[$property])) {
            return true;
        }
        return false;
    }

    /**
     * finds public properties in resource and sets them in $this->publicProperties
     */
    protected function findPublicProperties()
    {
        $resource = $this->getResource();
        $reflect = new \ReflectionObject($resource);
        $this->publicProperties = array();
        foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $this->publicProperties[$prop->getName()] = true;
        }
    }

    /**
     * nothing to do here
     * @return object
     */
    public function encode()
    {
        return serialize($this->resource);
    }

    /**
     * nothing to do here
     * @param string $file
     * @return void
     * @throws BadMethodCallException
     */
    public function write($file = NULL)
    {
        throw new BadMethodCallException(sprintf(self::EXCEPTION_PROHIBITED_METHOD, __METHOD__));
    }

    /**
     * current
     * @see Iterator
     */
    public function current()
    {
        if($this->implementsIterator) {
            $current = $this->resource->current();
            return $this->returnValue($current);
        } elseif($this->implementsIteratorAggregate) {
            $current = $this->iterator->current();
            return $this->returnValue($current);
        }
        return parent::current();
    }

    /**
     * key
     * @see Iterator
     */
    public function key()
    {
        if($this->implementsIterator) {
            return $this->resource->key();
        } elseif($this->implementsIteratorAggregate) {
            return $this->iterator->key();
        }
        return parent::key();
    }

    /**
     * next
     * @see Iterator
     */
    public function next()
    {
        if($this->implementsIterator) {
            $this->resource->next();
        } elseif($this->implementsIteratorAggregate) {
            $this->iterator->next();
        } else {
            parent::next();
        }
    }

    /**
     * valid
     * @see Iterator
     */
    public function valid()
    {
        if($this->implementsIterator) {
            return $this->resource->valid();
        } elseif($this->implementsIteratorAggregate) {
            return $this->iterator->valid();
        }
        return parent::valid();
    }

    /**
     * rewind
     * @see Iterator
     */
    public function rewind()
    {
        if($this->implementsIterator) {
            $this->resource->rewind();
        } elseif($this->implementsIteratorAggregate) {
            $this->iterator->rewind();
        } else {
            parent::rewind();
        }
    }
}