<?php

namespace MateTest\Resource;

include_once __DIR__ . '/ResourceTestAbstract.php';

use mate\Resource\Exception\BadMethodCallException;
use mate\Resource\ObjectResource;
use mate\Resource\ResourceAbstract;
use mate\Resource\ResourceFactory;
use MateTest\Resource\Asset\IteratorObject;
use MateTest\Resource\Asset\ObjectWithAutomagicGetterAndSetter;
use MateTest\Resource\Asset\ObjectWithIsserAndAdder;

class ObjectResourceTest extends ResourceTestAbstract
{

    protected $resourceClass = 'mate\Resource\ObjectResource';

    public function setUp()
    {
        parent::setUp();
        $objectSource = new \stdClass();
        $this->fillObjectSource($objectSource, self::$testData);
        $this->resource = ResourceFactory::create($objectSource);
    }

    protected function fillObjectSource($objectSource, $data)
    {
        foreach ($data as $prop => $val) {
            if(is_array($val)) {
                $objectSource->$prop = new \stdClass();
                $this->fillObjectSource($objectSource->$prop, $val);
            } else {
                $objectSource->$prop = $val;
            }
        }
    }

    public function testResourceDataType()
    {
        $this->assertTrue(is_object($this->resource->getResource()),
            'resource is not an object');
    }

    public function testResourceFactoryCondition()
    {
        $objectSource = new \stdClass();
        $this->fillObjectSource($objectSource, self::$testData);
        $check = ResourceFactory::getResourceClass($objectSource);
        $this->assertEquals($this->resourceClass, $check,
            'ResourceFactory::getResourceClass does not return ObjectResource if provided with simple object');
    }

    public function testGetWithIsser()
    {
        $object = new ObjectWithIsserAndAdder();
        $object->setCheck(true);
        $resource = new ObjectResource($object);
        $this->assertEquals(true, $resource->get("check"),
            "ObjectResource does not check for issers");
    }

    public function testSetWithAdder()
    {
        $object = new ObjectWithIsserAndAdder();
        $resource = new ObjectResource($object);
        $resource->set('node', true);
        $resource->setReturnResource(false);
        $this->assertEquals(array(true), $resource->get("nodes"),
            "ObjectResource does not check for adders");
    }

    /**
     * @depends testGetWithIsser
     */
    public function testSetWithSetter()
    {
        $object = new ObjectWithIsserAndAdder();
        $resource = new ObjectResource($object);
        $expected = true;
        $resource->set("check", $expected);
        $this->assertEquals($expected, $resource->get("check"),
            "Unable to set property by setter");
    }

    public function testGetWithAutomagicGetter()
    {
        $object = new ObjectWithAutomagicGetterAndSetter();
        $expected = "value";
        $object->property = $expected;
        $resource = new ObjectResource($object);
        $this->assertEquals($expected, $resource->get("property"),
            "Unable to get a value by an automagic getter");
    }

    /**
     * @depends testGetWithAutomagicGetter
     */
    public function testSetWithAutomagicGetter()
    {
        $object = new ObjectWithAutomagicGetterAndSetter();
        $resource = new ObjectResource($object);
        $expected = "value";
        $resource->set("property", $expected);
        $this->assertEquals($expected, $resource->get("property"),
            "Unable to get a value by an automagic getter");
    }

    public function testEncodeReturnSerializedObject()
    {
        $expected = serialize($this->resource->getResource());
        $this->assertEquals($expected, $this->resource->encode(),
            "encode() does not return the serialized object");
    }

    public function testWriteThrowsException()
    {
        $message = sprintf(ResourceAbstract::EXCEPTION_PROHIBITED_METHOD, 'mate\Resource\ObjectResource::write');
        $this->setExpectedException(BadMethodCallException::class, $message);
        $this->resource->write();
    }

    public function testCountWithCountableObjects()
    {
        $countableObject = $this->getMockBuilder(\Countable::class)
            ->getMock();
        $countableObject->method("count")
            ->will($this->returnValue(11));

        $objectResource = new ObjectResource($countableObject);
        $this->assertEquals(11, $objectResource->count(),
            "count() does not use the objects count() method if the Countable interface is implemented");
    }

    /* ******************************************
     * Use Iterator methods of containing object
     ******************************************** */

    public function testIteratingUsesCurrentMethodOfObject()
    {
        $this->iteratorMethodIsUsedTest('current');
    }

    public function testIteratingUsesNextMethodOfObject()
    {
        $this->iteratorMethodIsUsedTest('next');
    }

    public function testIteratingUsesValidMethodOfObject()
    {
        $this->iteratorMethodIsUsedTest('valid');
    }

    public function testIteratingUsesRewindMethodOfObject()
    {
        $this->iteratorMethodIsUsedTest('rewind');
    }

    public function testIteratingUsesKeyMethodOfObject()
    {
        $this->iteratorMethodIsUsedTest('key');
    }

    protected function iteratorMethodIsUsedTest($method)
    {
        $iterator = new IteratorObject(array());
        $objRes = new ObjectResource($iterator, array('debug' => true));
        $objRes->$method();
        $this->assertTrue($iterator->called[$method],
            $method . '() method of containing object was not called');
    }

    /* ****************************************************
     * Use aggregated Iterator methods of containing object
     ****************************************************** */

    public function testIteratingUsesCurrentMethodOfObjectsIterator()
    {
        $this->iteratorAggregateIsUsedTest('current');
    }

    public function testIteratingUsesNextMethodOfObjectsIterator()
    {
        $this->iteratorAggregateIsUsedTest('next');
    }

    public function testIteratingUsesValidMethodOfObjectsIterator()
    {
        $this->iteratorAggregateIsUsedTest('valid');
    }

    public function testIteratingUsesRewindMethodOfObjectsIterator()
    {
        $this->iteratorAggregateIsUsedTest('rewind');
    }

    public function testIteratingUsesKeyMethodOfObjectsIterator()
    {
        $this->iteratorAggregateIsUsedTest('key');
    }

    protected function iteratorAggregateIsUsedTest($method)
    {
        $called = array($method => false);

        $mock = $this->getMock('IteratorAggregate',
            array('getIterator'));
        $iteratorMock = $this->getMock('ArrayIterator',
            array($method), array(array()));

        $mock->expects($this->exactly(1))
            ->method('getIterator')
            ->will($this->returnValue($iteratorMock));
        $iteratorMock->expects($this->exactly(1))
            ->method($method)
            ->willReturnCallback(function () use (&$called, $method) {
                $called[$method] = true;
            });
        $objRes = new ObjectResource($mock);
        $objRes->$method();
        $this->assertTrue($called[$method],
            $method . '() method of containing objects iterator was not called');
    }
}