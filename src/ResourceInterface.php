<?php
namespace mate\Resource;
/**
 * Resource interface
 * @author marius.teller
 */
interface ResourceInterface extends \Iterator, \Countable
{
    /**
     * should set the resource and initiate the resource object
     * @param mixed $resource
     * @param array $options
     */
    public function __construct(&$resource, $options = array());

    /**
     * clone the current object and break the reference to its resource
     * @return ResourceInterface
     */
    public function __clone();

    /**
     * <p>Should find a value by key</p>
     * <p>Always check if you should return resource object or actual value. To
     * do so, you can use the returnValue() method of ResourceAbstract</p>
     * @param string $key
     * @param mixed $default NULL
     * @return mixed|ResourceAbstract if returnResource=true and value is nested
     */
    public function get($key, $default = NULL);

    /**
     * <p>should find a value by multiple keys</p>
     * <p>keys can be passed by an iterative array, a string with separators or Path object</p>
     * <p>Always check if you should return resource object or actual value. To
     * do so, you can use the returnValue() method of ResourceAbstract</p>
     * @param string|array $keys
     * @param mixed $default NULL
     * @return mixed|ResourceAbstract if returnResource=true and value is nested
     */
    public function getRecursive($keys, $default = NULL);

    /**
     * <p>should set a value by key</p>
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value);

    /**
     * <p>should set a value by multiple keys</p>
     * <p>keys can be passed by an iterative array, a string with separators or Path object</p>
     * @param string|array $keys
     * @param mixed $value
     */
    public function setRecursive($keys, $value);

    /**
     * finds values in all nodes and returns all results in an array
     * @param mixed $find
     * @return array
     */
    public function find($find);

    /**
     * if set to true (default) resources will return new resource objects
     * @param boolean $returnResource
     */
    public function setReturnResource($returnResource);

    /**
     * if set to true (default) resources will return new resource objects
     */
    public function getReturnResource();

    /**
     * checks if a value is set
     * @param string $key
     */
    public function has($key);

    /**
     * removes the give key
     * @param $key
     * @return void
     */
    public function remove($key);

    /**
     * <p>checks by multiple keys if a value is set</p>
     * <p>keys can be passed by an iterative array, a string with separators or Path object</p>
     * @param string|array $keys
     */
    public function hasRecursive($keys);

    /**
     * converts resource into an array
     */
    public function toArray();

    /**
     * resource getter
     */
    public function getResource();


    /**
     * get separator for paths to get or set recursive
     * @return string
     */
    public function getSeparator();

    /**
     * set separator for pathes to get or set recursive
     * @param string $separator
     */
    public function setSeparator($separator);

    /**
     * encodes the resource
     * @deprecated move to WritableResourceInterface
     *
     * @return mixed
     */
    public function encode();

    /**
     * write resource to file
     * @deprecated move to WritableResourceInterface
     *
     * @param string $file
     * @return void
     */
    public function write($file = NULL);

}
