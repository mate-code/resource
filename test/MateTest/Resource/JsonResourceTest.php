<?php

namespace MateTest\Resource;

include_once __DIR__ . '/ResourceTestAbstract.php';

use mate\Resource\JsonResource;
use mate\Resource\ResourceFactory;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class JsonResourceTest extends ResourceTestAbstract
{
    /**
     * @var string
     */
    protected $resourceClass = 'mate\Resource\JsonResource';

    /**
     * @var JsonResource
     */
    protected $resource;

    /**
     * @var vfsStreamDirectory
     */
    protected $vfsRoot;

    /**
     * @var string
     */
    protected $jsonFile;

    public function setUp()
    {
        parent::setUp();
        $this->vfsRoot = vfsStream::setup("files");
        $this->jsonFile = vfsStream::url("files/test.json");

        $jsonSource = json_encode(self::$testData);
        $this->resource = ResourceFactory::create($jsonSource);
        file_put_contents($this->jsonFile, '');
    }
    /**
     * test if resource datatype is array
     */
    public function testResourceDataType()
    {
        $source = json_encode(self::$testData);
        $resourceEntry = json_encode($this->resource->toArray());
        $this->assertEquals($source, $resourceEntry,
            'resource can not be converted to equal source');
    }

    public function testResourceFactoryConditionCallbackWithString()
    {
        $json = json_encode(self::$testData);
        $this->assertEquals(JsonResource::class, ResourceFactory::getResourceClass($json),
            'getResourceClass() does not return JsonResource class name on if a json string is passed');
    }

    public function testResourceFactoryConditionCallbackWithFile()
    {
        $json = json_encode(self::$testData);
        $file = $this->jsonFile;
        file_put_contents($file, $json);
        $this->assertEquals(JsonResource::class, ResourceFactory::getResourceClass($file),
            'getResourceClass() does not return JsonResource class name on if a json file is passed');
    }

    public function testSetAndGetOptions()
    {
        $options = JSON_FORCE_OBJECT | JSON_PRETTY_PRINT;
        $this->resource->setOptions($options);
        $this->assertSame($options, $this->resource->getOptions());
    }

    /**
     * @depends testResourceFactoryConditionCallbackWithFile
     */
    public function testConstructorWithJsonFile()
    {
        file_put_contents($this->jsonFile, json_encode(self::$testData));
        $resource = new JsonResource($this->jsonFile);
        $expected = self::$testData;
        $actual = $resource->toArray();
        $this->assertEquals($expected, $actual,
            'JsonResource does not contain the correct data if its constructed with a file path');
    }

    public function testGetArrayInJsonResource()
    {
        $json = json_encode([["id" => 1],["id" => 2]]);
        $jsonResource = new JsonResource($json);
        $expected = $jsonResource->getResource()[1];
        $jsonResource->setReturnResource(false);
        $this->assertEquals($expected, $jsonResource->get(1),
            "get() does not return the correct value if the first node of a json is converted to an array");
    }

    public function testEncodeJson()
    {
        $actual = $this->resource->encode();
        $expected = json_encode(self::$testData);
        $this->assertEquals($expected, $actual,
            'encode() method does not encode json object');
    }

    /**
     * @depends testEncodeJson
     * @depends testSetAndGetOptions
     */
    public function testEncodeWithOptions()
    {
        $options = JSON_FORCE_OBJECT;
        $this->resource->setOptions($options);
        $actual = $this->resource->encode();
        $expected = json_encode(self::$testData, $options);
        $this->assertEquals($expected, $actual,
            'encode() does not use given options');
    }

    /**
     * @depends testEncodeJson
     */
    public function testWriteJsonFile()
    {
        $file = $this->jsonFile;
        $this->resource->write($file);
        $expected = $this->resource->encode();
        $actual = file_get_contents($file);
        $this->assertEquals($expected, $actual,
            'write() method does not write json string to the given file');
    }

    /**
     * @depends testWriteJsonFile
     * @depends testEncodeWithOptions
     */
    public function testWriteJsonWithOptions()
    {
        $options = JSON_FORCE_OBJECT;
        $file = $this->jsonFile;
        $this->resource->setOptions($options);
        $this->resource->write($file);
        $expected = $this->resource->encode();
        $actual = file_get_contents($file);
        $this->assertEquals($expected, $actual,
            'write() method does not use the given options for encoding');
    }

    /**
     * @depends testEncodeJson
     * @depends testConstructorWithJsonFile
     */
    public function testWriteJsonToSourceFile()
    {
        $file = $this->jsonFile;
        file_put_contents($file, json_encode(self::$testData));
        $resource = new JsonResource($file);
        $resource->set('owner', 'changed');
        $resource->write();
        $expected = $resource->encode();
        $actual = file_get_contents($this->jsonFile);
        $this->assertEquals($expected, $actual,
            'write() method does not write json string to the initial source file');
    }
}