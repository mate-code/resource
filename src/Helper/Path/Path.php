<?php

namespace mate\Resource\Helper\Path;

use mate\Resource\Exception\InvalidArgumentException;

/**
 * Class to manage a path in a resource
 * @author Marius Teller <marius.teller@modotex.com>
 */
class Path implements \Iterator, \ArrayAccess
{
    /**
     * @var string
     */
    protected $separator;
    /**
     * @var array
     */
    protected $pathArray;
    /**
     * @var string
     */
    protected $initialString;
    /**
     * @var Iterators
     */
    protected $iterators;
    /**
     * starting point for the path
     * @var int
     */
    protected $start = 0;
    /**
     * @var int
     */
    protected $pointer = 0;

    const EXCEPTION_OFFSET_MUST_BE_INTEGER = 'All keys in the path object must be integers or convertible to integers';

    /**
     * @param array|string $pathArrayOrString path to read from
     * @param string $separator separator to separate different path keys
     */
    public function __construct($pathArrayOrString, $separator = '/')
    {
        $this->setSeparator($separator);
        if(is_array($pathArrayOrString)) {
            $string = implode($this->getSeparator(), $pathArrayOrString);
            $array = $pathArrayOrString;
        } else {
            $string = $pathArrayOrString;
            $array = explode($this->getSeparator(), $pathArrayOrString);
        }
        $this->setInitialString($string);
        $this->setPathArray($array);
        $this->setIterators(new Iterators($this));
    }

    /**
     * returns the current path, starts at $this->start
     * @return string
     */
    public function __toString()
    {
        $path = array();
        $pointerStorage = $this->getPointer();
        foreach ($this->pathArray as $pos => $value) {
            if($pos >= $this->start) {
                $path[] = $value;
            }
        }
        $this->setPointer($pointerStorage);
        $pathString = implode($this->getSeparator(), $path);
        return $pathString;
    }

    /**
     * set a single key in the path on position $pos
     * @param int $pos position of the key
     * @param mixed $value new key value
     */
    public function setPathKey($pos, $value)
    {
        $this->pathArray[$pos] = $value;
    }

    /**
     * get a single key in the path on position $pos
     * @param int $pos position of the key
     * @return mixed return false if key was not found
     */
    public function getPathKey($pos)
    {
        if(isset($this->pathArray[$pos])) {
            return $this->pathArray[$pos];
        } else {
            return false;
        }
    }

    /**
     * @see Iterator
     */
    public function rewind()
    {
        $this->pointer = $this->start;
    }

    /**
     * @see Iterator
     */
    public function current()
    {
        if(isset($this->pathArray[$this->pointer])) {
            return $this->pathArray[$this->pointer];
        } else {
            return false;
        }
    }

    /**
     * @see Iterator
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * @see Iterator
     */
    public function next()
    {
        $this->pointer++;
    }

    /**
     * @see Iterator
     */
    public function prev()
    {
        $this->pointer--;
    }

    /**
     * @see Iterator
     */
    public function valid()
    {
        return isset($this->pathArray[$this->pointer]);
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->pathArray[$offset]);
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     * @return mixed
     * @throws InvalidArgumentException if offset is not numeric
     */
    public function offsetGet($offset)
    {
        if(!is_numeric($offset)) {
            throw new InvalidArgumentException(self::EXCEPTION_OFFSET_MUST_BE_INTEGER);
        }
        return $this->pathArray[$offset];
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     * @param mixed $value
     * @throws InvalidArgumentException if offset is not numeric
     */
    public function offsetSet($offset, $value)
    {
        if(!is_numeric($offset)) {
            throw new InvalidArgumentException(self::EXCEPTION_OFFSET_MUST_BE_INTEGER);
        }
        $this->pathArray[(int)$offset] = $value;
    }

    /**
     * @see ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->pathArray[$offset]);
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @param string $separator
     */
    protected function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    /**
     * @return array
     */
    public function getPathArray()
    {
        return $this->pathArray;
    }

    /**
     * @param array $pathArray
     */
    protected function setPathArray(array $pathArray)
    {
        $this->pathArray = $pathArray;
    }

    /**
     * @return string
     */
    public function getInitialString()
    {
        return $this->initialString;
    }

    /**
     * @param string $initialString
     */
    protected function setInitialString($initialString)
    {
        $this->initialString = $initialString;
    }

    /**
     * @return Iterators
     */
    public function getIterators()
    {
        return $this->iterators;
    }

    /**
     * @param Iterators $iterators
     */
    protected function setIterators($iterators)
    {
        $this->iterators = $iterators;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param int $start
     */
    public function setStart($start)
    {
        $this->start = (int)$start;
        if($this->pointer < $this->start) {
            $this->pointer = $this->start;
        }
    }

    /**
     * @return int
     */
    public function getPointer()
    {
        return $this->pointer;
    }

    /**
     * @param int $pointer
     */
    public function setPointer($pointer)
    {
        $this->pointer = $pointer;
    }

}