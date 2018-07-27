<?php


namespace MateTest\Resource\Helper\Path;

use mate\Resource\Helper\Path\Iterators;
use mate\Resource\Helper\Path\Path;


/**
 * Class IteratorsTest
 * @package MateTest\Resource\Helper
 * @author Marius Teller <marius.teller@modotex.com>
 */
class IteratorsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var string
     */
    protected $testPath = 'books/1/customers/2/name';
    /**
     * @var array
     */
    protected $testPathArray = array();
    /**
     * @var array
     */
    protected $testIteratorsArray = array();
    /**
     * @var string
     */
    protected $separator = '/';
    /**
     * @var Path
     */
    protected $path;
    /**
     * @var Iterators
     */
    protected $iterators;

    public function setUp()
    {
        $this->testPathArray = explode($this->separator, $this->testPath);
        $this->testIteratorsArray = array(1, 2);
        $this->path = new Path($this->testPath);
        $this->iterators = $this->path->getIterators();

    }

    public function testIteratorsObjectImplementIterator()
    {
        $this->assertInstanceOf('Iterator', $this->iterators,
            'Iterators object must implement Iterator');
        $iteratorsArray = array();
        foreach ($this->iterators as $key => $value) {
            $iteratorsArray[$key] = $value;
        }
        $this->assertEquals($this->testIteratorsArray, $iteratorsArray,
            'Looping through the iterators object does not work properly, check the iterator functions');
    }

    public function testConstructWithFormat()
    {
        $testPath = str_replace(1, '%d', $this->testPath);
        $iteratorsObject = new Iterators(new Path($testPath));
        $iteratorsKeys = [1, 3];
        $this->assertEquals($iteratorsKeys, $iteratorsObject->getIterativeKeys());
    }

    public function testGetIteratorPosition()
    {
        $pos = $this->iterators->getIteratorPos(1);
        $this->assertEquals(3, $pos,
            'getIteratorPos() does not return the correct position of an iterator');
    }

    /**
     * @depends testGetIteratorPosition
     */
    public function testGetIteratorPositionWithInvalidPosition()
    {
        $this->assertFalse($this->iterators->getIteratorPos(234),
            "getIteratorPos does not return false if provided with an invalid key");
    }

    public function testSetAndGetPointer()
    {
        $newPosition = 2;
        $this->iterators->setPointer($newPosition);
        $this->assertEquals($newPosition, $this->iterators->getPointer(),
            "Unable to get or set pointer");
    }

    /**
     * @depends testSetAndGetPointer
     */
    public function testCurrentReturnsFalseOnInvalidPointer()
    {
        $this->iterators->setPointer(234);
        $this->assertFalse($this->iterators->current(),
            "current() does not return false if the pointer is at an invalid position");
    }

    /**
     * @depends testIteratorsObjectImplementIterator
     */
    public function testPrev()
    {
        $expectedEntry = $this->iterators->current();
        $this->iterators->next();
        $this->iterators->prev();
        $entryAfterCallingPrev = $this->iterators->current();
        $this->assertEquals($expectedEntry, $entryAfterCallingPrev,
            "prev() does not go back one key entry");
    }

    public function testGetValue()
    {
        $this->assertEquals(1, $this->iterators->getValue(),
            'getValue() does not return the value of the current iterator');
        $this->assertEquals(2, $this->iterators->getValue(1),
            'getValue($iNumber) does not return the value of a selected iterator');
    }

    public function testSetValue()
    {
        $this->iterators->setValue(3);
        $this->assertEquals(3, $this->path[1],
            'setValue() does not set the value of the current iterator');
        $this->iterators->setValue(4, 1);
        $this->assertEquals(4, $this->path[3],
            'setValue($iNumber) does not set the value of a selected iterator');
    }

    public function testIncrement()
    {
        $this->iterators->increment();
        $this->assertEquals(2, $this->iterators->current(),
            'increment does not increment the current iterator');
        $this->iterators->increment(1);
        $this->iterators->next();
        $this->assertEquals(3, $this->iterators->current(),
            'increment does not increment an iterator by position');
    }

    public function testDecrement()
    {
        $this->iterators->decrement();
        $this->assertEquals(0, $this->iterators->current(),
            'increment does not decrement the current iterator');
        $this->iterators->decrement(1);
        $this->iterators->next();
        $this->assertEquals(1, $this->iterators->current(),
            'increment does not decrement an iterator by position');
    }

    public function testIsFirst()
    {
        $this->assertTrue($this->iterators->isFirst(),
            'isFirst() does not return true if the first iterator is selected');
    }

    public function testIsLast()
    {
        $this->iterators->next();
        $this->assertTrue($this->iterators->isLast(),
            'isLast() does not return true if the last iterator is selected');
    }

    public function testPathBeforeIterator()
    {
        $expected = 'books';
        $this->assertEquals($expected, $this->iterators->pathBeforeIterator(),
            'pathBeforeIterator() does not return the path before the current iterator');
    }

    public function testPathAfterIterator()
    {
        $expected = 'customers/2/name';
        $this->assertEquals($expected, $this->iterators->pathAfterIterator(),
            'pathAfterIterator() does not return the path after the current iterator');
    }

    public function testGetIteratorValues()
    {
        $this->assertEquals($this->testIteratorsArray, $this->iterators->getIteratorValues(),
            'getIteratorValues() does not return the values of all iterative keys');
    }

    public function testFillIterators()
    {
        $fill = array(3,5);
        $this->iterators->fillIterators($fill);
        $this->assertEquals($fill, $this->iterators->getIteratorValues(),
            'fillIterators() does not fill in an array of integers into the iterators');
    }

}
