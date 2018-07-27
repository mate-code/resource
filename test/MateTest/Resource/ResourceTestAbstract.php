<?php

namespace MateTest\Resource;
use mate\Resource\Helper\Path\Path;

/**
 * Class ResourceFactoryTest
 * these tests should be passed to all resource objects in order to make sure they all act in the same way
 * @package MateTest\Resource
 */
abstract class ResourceTestAbstract extends \PHPUnit_Framework_TestCase
{

    protected static $testData = array(
        'total'        => 3,
        'averagePrice' => 18.23,
        'owner'        => 'Flourish and Blotts',
        'isOpen'       => true,
        'books'        => array(
            array(
                'title'   => 'Lord Of The Rings',
                'author'  => 'J.R.R. Tolkien',
                'year'    => 1954,
                'genre'   => 'Fantasy',
                'language' => null,
                'price'   => 24.90,
                'inStock' => true,

            ),
            array(
                'title'   => 'The Hitchhiker\'s Guide To The Galaxy',
                'author'  => 'Douglas Adams',
                'year'    => 1979,
                'genre'   => 'Science Fiction, Comedy',
                'language' => null,
                'price'   => 19.90,
                'inStock' => false,
            ),
            array(
                'title'    => 'Faust',
                'author'   => 'Johann Wolfgang Von Goethe',
                'year'     => 1808,
                'genre'    => 'Classic',
                'language' => 'German',
                'price'    => 9.90,
                'inStock'  => true,
            )
        )
    );
    /**
     * @var \mate\Resource\ResourceInterface $resource
     */
    protected $resource;
    /**
     * @var string class of resource to test
     */
    protected $resourceClass;

    protected $skipFurtherTests = false;

    public function setUp()
    {
        if($this->skipFurtherTests === true) {
            $this->markTestSkipped();
        }
    }

    public function tearDown()
    {
        unset($this->resource);
    }

    /**
     * Access protected or private method of resource
     *
     * @param $method
     * @param array $params
     * @return mixed
     */
    protected function accessMethod($method, array $params = array())
    {
        $reflectionMethod = new \ReflectionMethod($this->resource, $method);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($this->resource, $params);
    }

    /**
     * test if factory returns correct instance
     */
    public function testReturnInstance()
    {
        $this->assertInstanceOf('\mate\Resource\ResourceInterface', $this->resource,
            'Return of factory does not implement \mate\Resource\ResourceInterface');
        $this->assertInstanceOf($this->resourceClass, $this->resource,
            'Factory did not return instance of ' . $this->resourceClass);
        $this->skipFurtherTests = true;
    }

    /**
     * test if resource datatype is array
     * @depends testReturnInstance
     */
    public function testResourceDataType()
    {
    }

    /**
     * test get functions of resource
     * @depends testReturnInstance
     */
    public function testGeneralResourceGetters()
    {
        $correctValue = 'Flourish and Blotts';
        $get = $this->resource->get('owner');
        $this->assertEquals($correctValue, $get,
            'get() does not return the correct value');
        $correctRecursiveValue = 'Lord Of The Rings';
        $recursivePath = 'books/0/title';
        $getRecursive = $this->resource->getRecursive($recursivePath);
        $this->assertEquals($correctRecursiveValue, $getRecursive,
            'getRecursive() does not return the correct value');
        $correctArrayValue = self::$testData['books'][0];
        $resourceValue = $this->resource->getRecursive('books/0')->toArray();
        $this->assertEquals($correctArrayValue, $resourceValue,
            'getRecursive() does not return the correct value if its not the final node');
        $pathObj = new Path($recursivePath);
        $this->assertEquals($correctRecursiveValue, $this->resource->getRecursive($pathObj),
            'getRecursive() does not return the correct value if its searched by a Path object');
    }

    /**
     * test set functions of resource
     * @depends testReturnInstance
     */
    public function testGeneralResourceSetters()
    {
        $valueForLibrary = 'Hannover Stadtbibliothek';
        $this->resource->set('library', $valueForLibrary);
        $newValue = $this->resource->get('library');
        $this->assertEquals($valueForLibrary, $newValue,
            'set() does not fill in values, resource might not be reference');
        $valueForAuthor = 'John Ronald Reuel Tolkien';
        $this->resource->setRecursive('books/0/author', $valueForAuthor);
        $newRecursiveValue = $this->resource->getRecursive('books/0/author');
        $this->assertEquals($valueForAuthor, $newRecursiveValue,
            'setRecursive() does not fill in values, resource might not be reference');
        $marvinIsTiredOfYourTests = 'I am not in the mood for this... sight...';
        $this->resource->setRecursive('books/1', $marvinIsTiredOfYourTests);
        $marvinsValue = $this->resource->getRecursive('books/1');
        $this->assertEquals($marvinIsTiredOfYourTests, $marvinsValue,
            'setRecursive() does not fill in values if they are not the final node');
        $newPrice = 32.90;
        $pricePath = 'books/0/price';
        $pathObj = new Path($pricePath);
        $this->resource->setRecursive($pathObj, $newPrice);
        $this->assertEquals($newPrice, $this->resource->getRecursive($pricePath),
            'setRecursive() does not fill in values if its written by a Path object');
    }

    /**
     * @depends testGeneralResourceGetters
     */
    public function testCount()
    {
        $this->assertEquals(5, $this->resource->count(),
            "count() does not return the correct item count in a casual resource");
        $this->assertEquals(3, $this->resource->get("books")->count(),
            "count() does not return the correct item count of an iterative resource");
    }

    public function testSettingNonExistingPath()
    {
        $value = '30175';
        $path = 'customers/0/postcode';
        $this->resource->setRecursive($path, $value);
        $get = $this->resource->getRecursive($path);
        $this->assertEquals($value, $get,
            'Values can not be set by setRecursive() if path was not set');
    }

    /**
     * test handling of empty parameters
     * @depends testReturnInstance
     */
    public function testEmptyResourceValues()
    {
        $getNull = $this->resource->get('cockroaches');
        $getNullRecursive = $this->resource->getRecursive('books/3/author');
        $this->assertNull($getNull,
            'Getting a non existing value without default should return NULL');
        $this->assertNull($getNullRecursive,
            'Getting a non existing value recursively without default should return NULL');
        $getDefaultVal = 'Hannover Stadtbibliothek';
        $getDefault = $this->resource->get('library', $getDefaultVal);
        $this->assertEquals($getDefaultVal, $getDefault,
            'Getting a non existing value with default should return default');
        $getDefaultRecursiveVal = 'Shakespeare';
        $getDefaultRecursive = $this->resource->getRecursive('books/4/author', $getDefaultRecursiveVal);
        $this->assertEquals($getDefaultRecursiveVal, $getDefaultRecursive,
            'Getting a non existing value recursively with default should return default');
    }

    /**
     * test if resource objects are returned by default and not if returnResource is set to false
     * @depends testReturnInstance
     */
    public function testReturnResourceObjects()
    {
        $resInterface = '\mate\Resource\ResourceInterface';
        $getResourceObject = $this->resource->get('books');
        $getResourceObjectRecursive = $this->resource->getRecursive('books/0');
        $this->assertInstanceOf($resInterface, $getResourceObject,
            'get() should return resource by default');
        $this->assertInstanceOf($resInterface, $getResourceObjectRecursive,
            'getRecursive() should return resource by default');
        $this->resource->setReturnResource(false);
        $getValue = $this->resource->get('books');
        $getValueRecursive = $this->resource->getRecursive('books/0');
        $this->assertFalse($getValue instanceof $resInterface,
            'get() should not return resource if returnResource is set to false');
        $this->assertFalse($getValueRecursive instanceof $resInterface,
            'getRecursive() should not return resource if returnResource is set to false');
    }

    /**
     * test if simple data types are returned as they were passed (int, string, float, boolean)
     * @depends testReturnInstance
     */
    public function testSimpleDataTypes()
    {
        // get INT
        $getInt = $this->resource->get('total');
        $getIntRecursive = $this->resource->getRecursive('books/2/year');
        $this->assertTrue(is_int($getInt),
            'Integers in resource should be returned as integers by get()');
        $this->assertTrue(is_int($getIntRecursive),
            'Integers in resource should be returned as integers by getRecursive()');
        // get STRING
        $getString = $this->resource->get('owner');
        $getStringRecursive = $this->resource->getRecursive('books/2/author');
        $this->assertTrue(is_string($getString),
            'Strings in resource should be returned as strings by get()');
        $this->assertTrue(is_string($getStringRecursive),
            'Strings in resource should be returned as strings by getRecursive()');
        // get FLOAT
        $getFloat = $this->resource->get('averagePrice');
        $getFloatRecursive = $this->resource->getRecursive('books/2/price');
        $this->assertTrue(is_float($getFloat),
            'Floats in resource should be returned as floats by get()');
        $this->assertTrue(is_float($getFloatRecursive),
            'Floats in resource should be returned as floats by getRecursive()');
        // get BOOLEAN
        $getBool = $this->resource->get('isOpen');
        $getBoolRecursive = $this->resource->getRecursive('books/2/inStock');
        $this->assertTrue(is_bool($getBool),
            'Booleans in resource should be returned as booleans by get()');
        $this->assertTrue(is_bool($getBoolRecursive),
            'Booleans in resource should be returned as booleans by getRecursive()');
    }

    /**
     * test the has() method
     * @depends testReturnInstance
     */
    public function testResourceHasKey()
    {
        $this->assertTrue($this->resource->has('books'),
            'has() does not return true if param is set');
        $this->assertFalse($this->resource->has('dwarfs'),
            'has() does not return false if param is not set');
        $this->assertTrue($this->resource->hasRecursive('books/0/title'),
            'hasRecursive() does not return true if param is set');
        $this->assertTrue($this->resource->hasRecursive('books/0'),
            'hasRecursive() does not return true if param is set and not at the and of the path');
        $this->assertFalse($this->resource->hasRecursive('books/0/soldCount'),
            'hasRecursive() does not return false if param is not set');
        $this->assertFalse($this->resource->hasRecursive('dwarfs/0/gimli'),
            'hasRecursive() does not return false if param is not set and not at the end of a valid path');
    }

    public function testFindWithSingleKey()
    {
        $find = $this->resource->find('title');
        $expected = array(
            'Lord Of The Rings',
            'The Hitchhiker\'s Guide To The Galaxy',
            'Faust'
        );
        $this->assertEquals($expected, $find,
            'find() method does not return the expected values if searched by key');
    }

    /**
     * test the toArray() method
     * @depends testReturnInstance
     */
    public function testToArrayMethod()
    {
        $this->assertEquals(self::$testData, $this->resource->toArray(),
            'toArray() does not return array');
    }

    /**
     * test the functions required by implementation of \Iterator
     */
    public function testIteratableResource()
    {
        $firstKey = 'total';
        $firstValue = 3;
        $secondValue = 18.23;
        $this->assertInstanceOf('\Iterator', $this->resource,
            'Resources need to implement \Iterator');
        $this->resource->rewind();
        $this->assertEquals($firstKey, $this->resource->key(),
            'key() does not return the correct key');
        $this->assertEquals($firstValue, $this->resource->current(),
            'current() does not return the correct value');
        $this->resource->next();
        $this->assertEquals($secondValue, $this->resource->current(),
            'next() does not jump to the next entry');
        $this->resource->rewind();
        $this->assertEquals($firstValue, $this->resource->current(),
            'rewind() does not jump to the last entry');
        $this->assertTrue($this->resource->valid(),
            'valid() does not return true if the entry is set');
    }

    public function testCustomSeparator()
    {
        $this->resource->setSeparator('//');
        $path = 'books//0//title';
        $expected = 'Lord Of The Rings';
        $value = $this->resource->getRecursive($path);
        $this->assertEquals($expected, $value,
            'Getting values recursive with a custom separator does not return the expected value.');
    }

    public function testCustomSeparatorIsPassedToFollowingResourceObjects()
    {
        $newSeparator = '//';
        $this->resource->setSeparator('//');
        $value = $this->resource->get('books')->getSeparator();
        $this->assertEquals($newSeparator, $value,
            'Custom separator is not used by new resource objects returned by get()');
    }

    public function testCloningResource()
    {
        $clone = clone $this->resource;
        $clone->set('foo', 'bar');
        $this->assertNotSame($clone->getResource(), $this->resource->getResource(),
            '__clone() must clone the containing resource as well');
    }

    /**
     * @depends testGeneralResourceGetters
     */
    public function testRemove()
    {
        $key = "total";
        $this->resource->remove($key);
        $this->assertNull($this->resource->get($key),
            "Unable to remove an entry");
    }

    public function testRemoveInvalidKey()
    {
        $this->resource->remove("invalid");
    }

}