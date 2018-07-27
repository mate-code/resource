<?php

namespace MateTest\Resource\Asset;

/**
 * Class IteratorObject
 * @package MateTest\Resource\Asset
 * @author Marius Teller <marius.teller@modotex.com>
 */
class IteratorObject implements \Iterator
{
    /**
     * called functions
     * @var array
     */
    public $called = array(
        'current' => false,
        'next'    => false,
        'key'     => false,
        'valid'   => false,
        'rewind'  => false,
    );
    /**
     * @var array
     */
    protected $array;

    /**
     * @param array $array
     */
    public function __construct(array $array = array())
    {
        $this->array = $array;
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        $this->called[__FUNCTION__] = true;
        return current($this->array);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->called[__FUNCTION__] = true;
        next($this->array);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        $this->called[__FUNCTION__] = true;
        return key($this->array);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        $this->called[__FUNCTION__] = true;
        return current($this->array) !== false;
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->called[__FUNCTION__] = true;
        reset($this->array);
    }


}