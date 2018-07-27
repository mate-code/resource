<?php

namespace MateTest\Resource\Asset;
use Traversable;

/**
 * Class IteratorAggregateObject
 * @package MateTest\Resource\Asset
 * @author Marius Teller <marius.teller@modotex.com>
 */
class IteratorAggregateObject implements \IteratorAggregate
{


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
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new IteratorObject($this->array);
    }


}