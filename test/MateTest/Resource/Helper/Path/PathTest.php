<?php

namespace MateTest\Resource\Helper\Path;

use mate\Resource\Exception\InvalidArgumentException;
use mate\Resource\Helper\Path\Path;

/**
 * Class PathTest
 * @author Marius Teller <marius.teller@modotex.com>
 */
class PathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $testPath = 'books/1/customers/0/name';
    /**
     * @var array
     */
    protected $testPathArray = array();
    /**
     * @var string
     */
    protected $separator = '/';
    /**
     * @var Path
     */
    protected $path;

    public function setUp()
    {
        $this->testPathArray = explode($this->separator, $this->testPath);
        $this->path = new Path($this->testPath);
    }

    public function testStringConversion()
    {
        $this->assertEquals($this->testPath, (string)$this->path,
            'Path object could not be converted to string');
    }

    public function testConstructWithArray()
    {
        $pathArray = ["my", "path"];
        $pathObject = new Path($pathArray);
        $pathString = implode("/", $pathArray);
        $this->assertEquals($pathArray, $pathObject->getPathArray(),
            "constructing a Path object with an array does not set the correct path array");
        $this->assertEquals($pathString, $pathObject->getInitialString(),
            "constructing a Path object with an array does not set the correct initial string");
    }

    public function testIterator()
    {
        $this->assertInstanceOf('Iterator', $this->path,
            'Path object must implement Iterator');
        $pathArray = array();
        foreach ($this->path as $key => $value) {
            $pathArray[$key] = $value;
        }
        $this->assertEquals($this->testPathArray, $pathArray,
            'Looping through the path object does not work properly, check the iterator functions');
    }

    /**
     * @depends testIterator
     */
    public function testPrev()
    {
        $expected = $this->path->current();
        $this->path->next();
        $this->path->prev();
        $actual = $this->path->current();
        $this->assertEquals($expected, $actual,
            "prev() does not jump to the last position");
    }

    /**
     * @depends testIterator
     */
    public function testCurrentReturnsFalseWithInvalidPointer()
    {
        $this->path->setPointer(384);
        $this->assertFalse($this->path->current(),
            "current() does not return false if a pointer is invalid");
    }

    public function testArrayAccess()
    {
        $this->assertInstanceOf('ArrayAccess', $this->path,
            'Path object does not implement ArrayAccess');
    }

    /**
     * @depends testArrayAccess
     */
    public function testOffsetExists()
    {
        $this->assertTrue($this->path->offsetExists(1),
            "offsetExists does not return true on an existing key");
        $this->assertFalse($this->path->offsetExists(394),
            "offsetExists does not return false on a non existing key");
    }

    /**
     * @depends testOffsetExists
     * @depends testStringConversion
     */
    public function testOffsetUnset()
    {
        $unsetKey = 1;
        $this->path->offsetUnset($unsetKey);
        $this->assertFalse($this->path->offsetExists($unsetKey),
            "offsetUnset does not remove a path key");
        $testPath = $this->testPathArray;
        unset($testPath[$unsetKey]);
        $expectedString = implode("/", $testPath);
        $this->assertEquals($expectedString, (string) $this->path,
            "String conversion of Path object does not return the expected string after a key has been unset");
    }

    public function testPathKeyMethods()
    {
        $this->path->setPathKey(1, '2');
        $this->assertEquals('2', $this->path->getPathKey(1),
            'Either getPathKey or setPathKey does not work properly');
    }

    /**
     * @depends testPathKeyMethods
     */
    public function testGetInvalidPathKeyReturnsFalse()
    {
        $this->assertFalse($this->path->getPathKey(2874),
            "getPathKey does not return false if a key is not set");
    }

    /**
     * @depends testStringConversion
     */
    public function testStringConversionWithStartingPoint()
    {
        $this->path->setStart(2);
        $this->assertEquals('customers/0/name', (string)$this->path,
            'Path object does not cut the converted string if a starting point was given');
    }

    /**
     * @depends testIterator
     */
    public function testIteratingWithStartingPoint()
    {
        $this->path->setStart(2);
        $pathArray = array();
        foreach ($this->path as $value) {
            $pathArray[] = $value;
        }
        $this->assertEquals('customers/0/name', implode('/', $pathArray),
            'Path object does not cut the converted string if a starting point was given');
    }

    /**
     * @depends testArrayAccess
     */
    public function testSettingNonNumericKeyThrowsException()
    {
        $this->setExpectedException(InvalidArgumentException::class, Path::EXCEPTION_OFFSET_MUST_BE_INTEGER);
        $this->path['string'] = 'path';
    }

    /**
     * @depends testArrayAccess
     */
    public function testGettingNonNumericKeyThrowsException()
    {
        $this->setExpectedException(InvalidArgumentException::class, Path::EXCEPTION_OFFSET_MUST_BE_INTEGER);
        $this->path['string'];
    }

}