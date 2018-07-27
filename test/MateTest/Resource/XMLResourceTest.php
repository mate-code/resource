<?php

namespace MateTest\Resource;

include_once __DIR__ . '/ResourceTestAbstract.php';

use mate\Resource\ResourceFactory;
use mate\Resource\ResourceInterface;
use mate\Resource\XMLResource;
use org\bovigo\vfs\vfsStream;

class XMLResourceTest extends ResourceTestAbstract
{
    
    /**
     * @var \mate\Resource\XMLResource $resource
     */
    protected $resource;

    protected $resourceClass = 'mate\Resource\XMLResource';

    protected static $filePath = __DIR__ . '/files/test.xml';

    protected $writingXmlFile;

    public function setUp()
    {
        parent::setUp();

        vfsStream::setup("xml");
        $this->writingXmlFile = vfsStream::url("xml/writingTest.xml");
        file_put_contents($this->writingXmlFile, "");

        $xmlString = $this->getTestXmlString();
        $this->resource = ResourceFactory::create($xmlString);
    }

    protected function getTestXmlString()
    {
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xmlString .= '<root>';
        $xmlString .= $this->getXmlString(self::$testData);
        $xmlString .= '</root>';
        return $xmlString;
    }

    protected function getXmlString($data, $xmlString = '')
    {
        foreach ($data as $key => $val) {
            if($val === null || $val === "") {
                $xmlString .= "<$key/>";
                continue;
            }
            if(is_bool($val)) {
                $val = $val ? 'true' : 'false';
            }
            $xmlString .= is_int($key) ? "<book>" : "<$key>";
            $xmlString .= is_array($val) ? $this->getXmlString($val) : $val;
            $xmlString .= is_int($key) ? "</book>" : "</$key>";
        }
        return $xmlString;
    }

    public function testResourceDataType()
    {
        $this->assertInstanceOf('\SimpleXmlElement', $this->resource->getResource(),
            'resource is not an instance of \SimpleXmlElement');
    }

    public function testResourceFactoryConditionWithString()
    {
        $xmlString = $this->getTestXmlString();
        $checkString = ResourceFactory::getResourceClass($xmlString);
        $this->assertEquals($this->resourceClass, $checkString,
            'ResourceFactory::getResourceClass does not return XmlResource if provided with an xml string');
    }

    public function testResourceFactoryConditionWithXmlIterator()
    {
        $xmlString = $this->getTestXmlString();
        $xmlIterator = new \SimpleXMLIterator($xmlString);
        $checkIterator = ResourceFactory::getResourceClass($xmlIterator);
        $this->assertEquals($this->resourceClass, $checkIterator,
            'ResourceFactory::getResourceClass does not return XmlResource if provided with an instance of SimpleXmlIterator');

    }

    public function testResourceFactoryConditionWithXmlElement()
    {
        $xmlString = $this->getTestXmlString();
        $xmlElement = new \SimpleXMLElement($xmlString);
        $checkElement = ResourceFactory::getResourceClass($xmlElement);
        $this->assertEquals($this->resourceClass, $checkElement,
            'ResourceFactory::getResourceClass does not return XmlResource if provided with an instance of SimpleXmlElement');

    }

    public function testResourceFactoryConditionWithXmlFile()
    {
        $xmlFile = self::$filePath;
        $checkFile = ResourceFactory::getResourceClass($xmlFile);
        $this->assertEquals($this->resourceClass, $checkFile,
            'ResourceFactory::getResourceClass does not return XmlResource if provided with a path to an XML file');
    }

    public function testConstructWithSimpleXmlIterator()
    {
        $object = new \SimpleXMLIterator(file_get_contents(self::$filePath));
        $resource = new XMLResource($object);
        $this->assertEquals($object, $resource->getResource(),
            "Unable to get an XMLResource out of a SimpleXMLIterator object");
    }

    public function testConstructWithFilePath()
    {
        $xmlString = $this->getTestXmlString();
        file_put_contents($this->writingXmlFile, $xmlString);
        $object = new \SimpleXMLIterator($xmlString);
        $resource = new XMLResource($this->writingXmlFile);
        $this->assertEquals($this->writingXmlFile, $resource->getFilePath(),
            "Creating an XML resource with a file does not set the file path");
        $this->assertEquals($object, $resource->getResource(),
            "Unable to create XMLResource with file path");
    }

    public function testClearNamespaces()
    {
        $xmlFile = __DIR__ . '/files/testNamespaces.xml';
        $resource = new XMLResource($xmlFile, ["clearNamespaces" => true]);
        $withoutNamespaces = simplexml_load_file(self::$filePath);
        $expected = $withoutNamespaces->getNamespaces(true);
        $actual = $resource->getResource()->getNamespaces(true);
        $this->assertEquals($expected, $actual,
            "setting the option clearNamespaces does not remove the namespaces from an XML string");
    }

    public function testAutoAddDefaultChildIfNotSet()
    {
        $file = self::$filePath;
        $resource = new XMLResource($file, [
            "defaultChildren" => [
                "books" => "book",
            ]
        ]);
        $default = "child";
        $this->assertEquals($default, $resource->getDefaultChild(),
            "No default default child node was set if not given in the options");
    }

    public function testAutoFindDefaultChild()
    {
        $path = 'books/0';
        $expectedChild = 'book';
        $this->resource->getRecursive($path);
        $child = $this->resource->getDefaultChild('books');
        $this->assertEquals($expectedChild, $child,
            'Getters do not automatically find the default child of a parent node if it has multiple children with the same name.');
    }

    public function testGetFilePath()
    {
        $path = self::$filePath;
        $resource = new XMLResource($path);
        $this->assertEquals($path, $resource->getFilePath(),
            "getFilePath does not return the path given to the constructor");
    }

    public function testSettingNonExistingPathIsXml()
    {
        $value = '30175';
        $path = 'customers/0/postcode';
        $checkPath = 'customers/0';
        $this->resource->setRecursive($path, $value);
        $result = $this->resource->getRecursive($checkPath)->getResource();
        $this->assertInstanceOf('\SimpleXMLIterator', $result,
            'Setting a non existing path does not set nodes as instances of \SimpleXMLIterator');
    }

    public function testSetComplexValueThrowsException()
    {
        $key = "books";
        $value = array(
            array("1984", "George Orwell", 1949),
        );
        $message = sprintf(XMLResource::ERROR_SETTING_COMPLEX_VALUE, gettype($value), $key);
        $this->setExpectedException("InvalidArgumentException", $message);
        $this->resource->set($key, $value);
    }

    public function testChildGeneration()
    {
        $value = '000';
        $parentNodeName = 'customers';
        $childNodeName = 'customer';
        $iterativePath = $parentNodeName . '/0/postcode';
        $checkPath = $parentNodeName . '/0';
        $this->resource->addDefaultChild($parentNodeName, $childNodeName);
        $this->resource->setRecursive($iterativePath, $value);
        $result = $this->resource->getRecursive($checkPath);
        $resultNodeName = $result->getResource()->getName();
        $this->assertEquals($childNodeName, $resultNodeName,
            'Setting values with iterative keys does not generate child tags or does not give them the correct name');
    }

    public function testSetSimpleXMLIterator()
    {
        $expected = array(
            'street'   => 'Cantina Place 1',
            'city'     => 'Mos Eisley',
            'postcode' => 'T923C593',
            'phone'    => array(
                'fixed'           => '01928375829',
                'inCaseOfHanSolo' => '110',
            ),
        );
        $key = 'address';
        $xmlString = '<?xml version="1.0" encoding="UTF-8" ?><root>';
        $xmlString .= $this->getXmlString($expected);
        $xmlString .= '</root>';
        $xmlIterator = new \SimpleXMLIterator($xmlString);
        $this->resource->set($key, $xmlIterator);
        $actual = $this->resource->get($key);
        if($actual instanceof ResourceInterface) {
            $actual = $actual->toArray();
        }
        $this->assertEquals($expected, $actual,
            'set() does not fill a SimpleXMLIterator object correctly');
    }

    public function testSetSimpleXMLElement()
    {
        $expected = array(
            'street'   => 'Cantina Place 1',
            'city'     => 'Mos Eisley',
            'postcode' => 'T923C593',
            'phone'    => array(
                'fixed'           => '01928375829',
                'inCaseOfHanSolo' => '110',
            ),
        );
        $key = 'address';
        $xmlString = '<?xml version="1.0" encoding="UTF-8" ?><root>';
        $xmlString .= $this->getXmlString($expected);
        $xmlString .= '</root>';
        $xmlIterator = new \SimpleXMLElement($xmlString);
        $this->resource->set($key, $xmlIterator);
        $actual = $this->resource->get($key);
        if($actual instanceof ResourceInterface) {
            $actual = $actual->toArray();
        }
        $this->assertEquals($expected, $actual,
            'set() does not fill a SimpleXMLElement object correctly');
    }

    /**
     * @depends testSetSimpleXMLElement
     */
    public function testSetSimpleXMLElementInIterativePath()
    {
        $book = array(
            'title'   => 'The Physician',
            'author'  => 'Noah Gordon',
            'genre'   => 'Historical novel',
            'year'    => 1986,
            'price'   => 14.90,
            'inStock' => true,
        );
        $xmlString = '<?xml version="1.0" encoding="UTF-8" ?><root>';
        $xmlString .= $this->getXmlString($book);
        $xmlString .= '</root>';
        $expected = new \SimpleXMLIterator($xmlString);
        $this->resource->setRecursive('books/3', $expected);
        $actual = $this->resource->get('books')->getResource()->book{3};
        $this->assertEquals($expected, $actual,
            'set() does not fill a SimpleXMLElement to an iterative path');
    }

    /**
     * @depends testSetSimpleXMLIterator
     */
    public function testSetXmlResource()
    {
        $expected = array(
            'street'   => 'Cantina Place 1',
            'city'     => 'Mos Eisley',
            'postcode' => 'T923C593',
            'phone'    => array(
                'fixed'           => '01928375829',
                'inCaseOfHanSolo' => '110',
            ),
        );
        $key = 'address';
        $xmlString = '<?xml version="1.0" encoding="UTF-8" ?><root>';
        $xmlString .= $this->getXmlString($expected);
        $xmlString .= '</root>';
        $xmlResource = new XMLResource($xmlString);
        $this->resource->set($key, $xmlResource);
        $actual = $this->resource->get($key);
        if($actual instanceof ResourceInterface) {
            $actual = $actual->toArray();
        }
        $this->assertEquals($expected, $actual,
            'set() does not fill an XMLResource object correctly');
    }

    /**
     * @depends testSetXmlResource
     */
    public function testSetXmlResourceInIterativePath()
    {
        $book = array(
            'title'   => 'The Physician',
            'author'  => 'Noah Gordon',
            'genre'   => 'Historical novel',
            'year'    => 1986,
            'price'   => 14.90,
            'inStock' => true,
        );
        $xmlString = '<?xml version="1.0" encoding="UTF-8" ?><root>';
        $xmlString .= $this->getXmlString($book);
        $xmlString .= '</root>';
        $xmlResource = new XMLResource($xmlString);
        $this->resource->setRecursive('books/3', $xmlResource);
        $expected = $xmlResource->getResource();
        $actual = $this->resource->get('books')->getResource()->book{3};
        $this->assertEquals($expected, $actual,
            'set() does not fill a XmlResource to an iterative path');
    }

    public function testEncodeXmlResource()
    {
        $expected = $this->getTestXmlString();
        $actual = $this->resource->encode();
        $this->assertEquals($expected, $actual,
            'encode() method does not return the correct encoded xml string');
    }

    /**
     * @depends testEncodeXmlResource
     */
    public function testWriteXml()
    {
        $this->resource->write($this->writingXmlFile);
        $actual = file_get_contents($this->writingXmlFile);
        $expected = $this->resource->encode();
        $this->assertEquals($expected, $actual,
            'write() method does not write the correct xml string to file');
    }

    /**
     * @depends testEncodeXmlResource
     */
    public function testWriteToXmlSourceFile()
    {
        $file = $this->writingXmlFile;
        file_put_contents($file, $this->getTestXmlString());
        $resource = new XMLResource($file);
        $resource->set('owner', 'changed');
        $resource->write();
        $expected = $resource->encode();
        $actual = file_get_contents($this->writingXmlFile);
        $this->assertEquals($expected, $actual,
            'write() method does not write xml string to the initial source file');
    }

    public function testSetNodeAttribute()
    {
        $attrValue = "inventory";
        $this->resource->setRecursive("books/@type", $attrValue);
        $actual = $this->resource->getResource()->books[0]["type"];
        $this->assertEquals($attrValue, $actual,
            "Unable to set a nodes attribute");
    }

    // test convert data types

    public function provideTestFilterDataType()
    {
        return array(
            ["false", false],
            ["true", true],
            ["42", 42],
            ["42.5", 42.5],
            ["0152", "0152"],
            ["string", "string"],
            [new \SimpleXMLIterator("<value>value</value>"), "value"],
        );
    }

    
    /**
     * @dataProvider provideTestFilterDataType
     *
     * @param mixed $inputValue
     * @param mixed $expectedReturn
     */
    public function testFilterDataType($inputValue, $expectedReturn)
    {
        $actualReturn = $this->accessMethod("filterDataTypes", [$inputValue]);
        $this->assertSame($expectedReturn, $actualReturn);
    }

    public function testKeyInIterativePath()
    {
        $this->resource->addDefaultChild("books", "book");
        $books = $this->resource->get("books");
        $expectedKeys = [0, 1, 2];
        $actualKeys = array();
        foreach ($books as $key => $book) {
            $actualKeys[] = $key;
        }
        $this->assertEquals($expectedKeys, $actualKeys,
            "key() does not return the correct numeric position of a stacked node");
    }

    // test validate()

    public function testValidateReturnsTrue()
    {
        $xsdFile = __DIR__ . '/files/validation.xsd';
        $validation = $this->resource->schemaValidate($xsdFile);
        $this->assertTrue($validation,
            "schemaValidate() does not return true if the XML schema is correct");
    }

    public function testValidateReturnsErrors()
    {
        $xsdFile = __DIR__ . '/files/validation.xsd';
        $this->resource->setRecursive("books/0/invalidNode", null);
        $validation = $this->resource->schemaValidate($xsdFile);
        $message = isset($validation[0]) ? trim($validation[0]->message) : $validation;
        $expectedMessage = "Element 'invalidNode': This element is not expected.";
        $this->assertEquals($expectedMessage, $message,
            "schemaValidate() does not return an array if LibXMLError if the XML schema is invalid");
    }
}