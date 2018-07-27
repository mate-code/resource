<?php

namespace mate\Resource;

use mate\Resource\Exception\InvalidArgumentException;

class ArrayResource extends ResourceAbstract implements \ArrayAccess, WritableResourceInterface
{

    const INVALID_RESOURCE_TYPE_EXCEPTION = "Invalid resource given to constructor of ArrayResource- Expected array, got %s instead";

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @param mixed $resource
     * @param array $options
     * @throws InvalidArgumentException
     */
    public function __construct(&$resource, $options = array())
    {
        if(!is_array($resource)) {
            if(is_object($resource)) {
                $type = "instance of ".get_class($resource);
            } else {
                $type = gettype($resource);
            }
            throw new InvalidArgumentException(sprintf(self::INVALID_RESOURCE_TYPE_EXCEPTION, $type));
        }
        parent::__construct($resource, $options);
    }
    /**
     * search for $key
     * @param string $key
     * @param mixed $default
     * @return \mate\Resource\ResourceAbstract if $returnResource = true
     */
    public function get($key, $default = NULL)
    {
        if(isset($this->resource[$key])) {
            return $this->returnValue($this->resource[$key]);
        } else {
            return $default;
        }
    }

    /**
     * set $key
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function set($key, $value)
    {
        $this->resource[$key] = $value;
        return true;
    }

    /**
     * checks if array key exists
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
     * @return mixed
     */
    public function encode()
    {
        return var_export($this->resource, true);
    }

    /**
     * @param string $file
     * @return void
     */
    public function write($file = NULL)
    {
        if($file == null) {
            $file = $this->getFilePath();
        }
        $arrayString = $this->encode();
        $content = "<?php \nreturn $arrayString;";
        file_put_contents($file, $content);
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
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
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->resource[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->resource[$offset]);
    }


}