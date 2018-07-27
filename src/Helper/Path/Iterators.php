<?php

namespace mate\Resource\Helper\Path;

/**
 * Class Iterators
 * @package mate\Resource\Helper\Path
 * @author Marius Teller <marius.teller@modotex.com>
 */
class Iterators implements \Iterator
{

    /**
     * attributes of the path, e.g.
     * @var array
     */
    protected $iterativeKeys;
    /**
     * path object
     * @var Path
     */
    protected $path;
    /**
     * current iterator number
     * @var int
     */
    protected $pointer = 0;

    public function __construct(Path $path)
    {
        $this->setPath($path);
        $iterativeKeys = array();
        foreach ($path as $pos => $key) {
            if(is_numeric($key)) {
                $iterativeKeys[] = $pos;
            } elseif($key == '%d') {
                $path->setPathKey($pos, 0);
                $iterativeKeys[] = $pos;
            }
        }
        $path->rewind();
        $this->setIterativeKeys($iterativeKeys);
    }

    /**
     * increments the current iterator (or iterator number $iNumber) by one
     * @param int $iNumber iterator number to increment
     */
    public function increment($iNumber = NULL)
    {
        $path = $this->getPath();
        $pos = $iNumber === NULL ? $this->currentIteratorPos() : $this->getIteratorPos($iNumber);
        $iterator = (int)$path[$pos];
        $path[$pos] = $iterator + 1;
    }

    /**
     * decrements the current iterator (or iterator number $iNumber) by one
     * @param int $iNumber iterator number to increment
     */
    public function decrement($iNumber = NULL)
    {
        $path = $this->getPath();
        $pos = $iNumber === NULL ? $this->currentIteratorPos() : $this->getIteratorPos($iNumber);
        $iterator = (int)$path[$pos];
        $path[$pos] = $iterator - 1;
    }

    /**
     * sets an iterators value to $value (either current iterator or iterator $iNumber)
     * @param int $value
     * @param null $iNumber
     */
    public function setValue($value, $iNumber = NULL)
    {
        $pos = $iNumber === NULL ? $this->currentIteratorPos() : $this->getIteratorPos($iNumber);
        $this->getPath()[$pos] = (int) $value;
    }

    /**
     * gets an iterators value (either current iterator or iterator $iNumber)
     * @param null $iNumber
     * @return int
     */
    public function getValue($iNumber = NULL)
    {
        $pos = $iNumber === NULL ? $this->currentIteratorPos() : $this->getIteratorPos($iNumber);
        return (int) $this->getPath()[$pos];
    }

    /**
     * checks if currently selected iterator is the last of all iterators
     * @return bool
     */
    public function isLast()
    {
        return ($this->pointer + 1 === count($this->iterativeKeys));
    }

    /**
     * checks if currently selected iterator is the first of all iterators
     * @return bool
     */
    public function isFirst()
    {
        return ($this->pointer === 0);
    }

    /**
     * returns the path before the current iterator
     * @return string
     */
    public function pathBeforeIterator()
    {
        $iteratorPos = $this->getIteratorPos($this->pointer);
        $pathBefore = array();
        $path = $this->getPath();
        for ($pos = $path->getStart(); $pos < $iteratorPos; $pos++) {
            $pathBefore[] = $path[$pos];
        }
        return implode($path->getSeparator(), $pathBefore);
    }

    /**
     * returns the path after the current iterator
     * @return string
     */
    public function pathAfterIterator()
    {
        $iteratorPos = $this->getIteratorPos($this->pointer);
        $pathAfter = array();
        $path = $this->getPath();
        $count = count($path->getPathArray());
        for ($pos = $iteratorPos + 1; $pos < $count; $pos++) {
            $pathAfter[] = $path[$pos];
        }
        return implode($path->getSeparator(), $pathAfter);
    }

    /**
     * fills an array of iterative values into the iterative keys of the path
     * @param array $iterativeValues
     */
    public function fillIterators(array $iterativeValues)
    {
        $path = $this->getPath();
        foreach ($iterativeValues as $iteratorNumber => $int) {
            $pos = $this->getIteratorPos($iteratorNumber);
            $path[$pos] = (int)$int;
        }
    }

    /**
     * get array of current iterator values
     * @return array
     */
    public function getIteratorValues()
    {
        $path = $this->getPath();
        $values = array();
        $iterativeKeys = $this->getIterativeKeys();
        foreach ($iterativeKeys as $pos) {
            $values[] = $path[$pos];
        }
        return $values;
    }

    /**
     * returns the number of the current iterator
     * @return mixed
     */
    public function currentIteratorPos()
    {
       return $this->getIteratorPos($this->pointer);
    }

    /**
     * returns position of an iterator in the path array
     * @param $iteratorNumber number of iterator
     * @return int|bool returns false if iterator could not be found
     */
    public function getIteratorPos($iteratorNumber){
        if(isset($this->iterativeKeys[$iteratorNumber])) {
            return $this->iterativeKeys[$iteratorNumber];
        } else {
            return false;
        }
    }

    /**
     * @see Iterator
     * @return void
     */
    public function rewind()
    {
        $this->pointer = 0;
    }

    /**
     * @see Iterator
     * @return int current iterator value
     */
    public function current()
    {
        if(isset($this->iterativeKeys[$this->pointer])) {
            $pos = $this->iterativeKeys[$this->pointer];
            return $this->path[$pos];
        } else {
            return false;
        }
    }

    /**
     * @see Iterator
     * @return int current path position
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * @see Iterator
     * @return int next path position
     */
    public function next()
    {
        $this->pointer++;
    }

    /**
     * @see Iterator
     * @return int previous path position
     */
    public function prev()
    {
        $this->pointer--;
    }

    /**
     * @see Iterator
     * @return bool
     */
    public function valid()
    {
        return isset($this->iterativeKeys[$this->pointer]);
    }


    /**
     * @return array
     */
    public function getIterativeKeys()
    {
        return $this->iterativeKeys;
    }

    /**
     * @param array $iterativeKeys
     */
    protected function setIterativeKeys(array $iterativeKeys)
    {
        $this->iterativeKeys = $iterativeKeys;
    }

    /**
     * @return Path
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param Path $path
     */
    protected function setPath(Path $path)
    {
        $this->path = $path;
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
        $this->pointer = (int) $pointer;
    }

}