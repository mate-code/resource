<?php

namespace mate\Resource;

class JsonResource extends ResourceAbstract implements WritableResourceInterface
{
    /**
     * file path the json was read from/will be written to
     * @var string
     */
    protected $filePath;
    
    protected $options = 0;
    
    /**
     * decodes json string to object and sets it as resource
     * @param string $resource
     * @param array $options
     */
    public function __construct(&$resource, $options = array())
    {
        if(is_string($resource) && is_file($resource)
            && pathinfo($resource, PATHINFO_EXTENSION) == 'json'
        ) {
            $this->setFilePath($resource);
            $resource = json_decode(file_get_contents($resource));
        }
        if(is_string($resource)) {
            $resource = json_decode($resource);
        }
        parent::__construct($resource, $options);
    }

    /**
     * <p>Returns property with name $property</p>
     * <p>NULL will be returned if property was not found</p>
     * @param string $property
     * @param mixed $default
     * @return mixed|ResourceAbstract if $returnResource = true
     */
    public function get($property, $default = NULL)
    {
        $resource = $this->getResource();
        if(isset($resource->$property)) {
            return $this->returnValue($resource->$property);
        }
        if(is_array($resource) && isset($resource[$property])) {
            return $this->returnValue($resource[$property]);
        }
        return $default;
    }

    /**
     * <p>Sets property with name $property</p>
     * <p>Looks for setters and public properties to do so</p>
     * <p>FALSE will be returned if no property could not be set</p>
     * @param string $property
     * @param mixed $value
     * @return boolean
     */
    public function set($property, $value)
    {
        $resource = $this->getResource();
        $resource->$property = $value;
        return true;
    }

    /**
     * checks if property exists
     * @param string $property
     * @return boolean
     */
    public function has($property)
    {
        $resource = $this->getResource();
        return isset($resource->$property);
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
        return count((array) $this->resource);
    }

    /**
     * returns the resource as a json string
     *
     * @param int $options json_encode options
     * @return string
     */
    public function encode($options = 0)
    {
        if($options !== 0) {
            trigger_error("Passing options to encode() is deprecated. Use setOptions() instead");
        }
        return json_encode($this->resource, $this->getOptions());
    }

    /**
     * write json string to file
     *
     * @param int $options json_encode options
     * @param string|null $file
     */
    public function write($file = NULL, $options = 0)
    {
        if($file === NULL) {
            $file = $this->getFilePath();
        }
        if($options !== 0) {
            $this->setOptions($options);
            trigger_error("Passing options to write() is deprecated. Use setOptions() instead");
        }
        file_put_contents($file, $this->encode());
    }

    /**
     * file path the json was read from/will be written to
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * file path the json was read from/will be written to
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return int
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param int $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

}