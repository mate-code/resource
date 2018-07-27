<?php

namespace MateTest\Resource;

include_once __DIR__ . '/ResourceTestAbstract.php';

use mate\Resource\ArrayResource;
use mate\Resource\ResourceFactory;

class ArrayResourceTest extends ResourceTestAbstract
{

    protected $resourceClass = 'mate\Resource\ArrayResource';

    public function setUp()
    {
        parent::setUp();
        $array = self::$testData;
        $this->resource = ResourceFactory::create($array);
    }

    /**
     * test if resource datatype is array
     */
    public function testResourceDataType()
    {
        $this->assertTrue(is_array($this->resource->getResource()),
            'getResource() does not return array');
    }

    public function testResourceFactoryCondition()
    {
        $check = ResourceFactory::getResourceClass(self::$testData);
        $this->assertEquals($this->resourceClass, $check,
            'ResourceFactory::getResourceClass does not return ArrayResource if provided with an array');
    }

    public function testConstructWithObject()
    {
        $object = new \stdClass();
        $message = sprintf(ArrayResource::INVALID_RESOURCE_TYPE_EXCEPTION, "instance of ".get_class($object));
        $this->setExpectedException("InvalidArgumentException", $message);
        new ArrayResource($object);
    }

    public function testConstructWithString()
    {
        $string = "string";
        $message = sprintf(ArrayResource::INVALID_RESOURCE_TYPE_EXCEPTION, gettype($string));
        $this->setExpectedException("InvalidArgumentException", $message);
        new ArrayResource($string);
    }

    public function testArrayResourceImplementsArrayAccess()
    {
        $this->assertInstanceOf('ArrayAccess', $this->resource,
            'ArrayResource should implement ArrayAccess');
    }

    /**
     * @depends testArrayResourceImplementsArrayAccess
     */
    public function testOffsetExists()
    {
        /** @var ArrayResource $res */
        $res = $this->resource;
        $this->assertTrue($res->offsetExists('total'));
        $this->assertFalse($res->offsetExists('Bielefeld'));
    }

    /**
     * @depends testArrayResourceImplementsArrayAccess
     */
    public function testOffsetGet()
    {
        /** @var ArrayResource $res */
        $res = $this->resource;
        $this->assertEquals(self::$testData['total'], $res->offsetGet('total'));
    }

    /**
     * @depends testArrayResourceImplementsArrayAccess
     * @depends testOffsetGet
     */
    public function testOffsetSet()
    {
        /** @var ArrayResource $res */
        $res = $this->resource;
        $key = 'total';
        $newVal = 5;
        $res->offsetSet($key, $newVal);
        $this->assertEquals($newVal, $res->offsetGet($key));
    }

    /**
     * @depends testArrayResourceImplementsArrayAccess
     * @depends testOffsetExists
     */
    public function testOffsetUnset()
    {
        /** @var ArrayResource $res */
        $res = $this->resource;
        $key = 'total';
        $res->offsetUnset($key);
        $this->assertFalse($res->offsetExists($key));
    }

    public function testEncode()
    {
        $code = $this->resource->encode();
        $this->assertEquals("array", substr($code, 0, 5),
            "encode() does not encode the array to a valid php code");
    }

    /**
     * @depends testEncode
     */
    public function testWrite()
    {
        $file = __DIR__."/files/test.php";
        file_put_contents($file, "");
        $this->resource->write($file);
        $this->assertEquals(self::$testData, include $file,
            "write() does not write a php file that returns the correct array");
    }
}