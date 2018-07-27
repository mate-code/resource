<?php

namespace MateTest\Resource;

use mate\Resource\CsvResource;
use mate\Resource\CsvRowResource;
use mate\Resource\Exception\BadMethodCallException;
use mate\Resource\Exception\InvalidArgumentException;
use mate\Resource\Exception\RuntimeException;
use mate\Resource\Helper\Csv\FileOptions;
use mate\Resource\Helper\Csv\Keymap;
use mate\Resource\Helper\Path\Path;
use mate\Resource\ResourceAbstract;
use mate\Resource\ResourceFactory;
use mate\Resource\ResourceInterface;
use org\bovigo\vfs\vfsStream;

class CsvResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CsvResource
     */
    protected $resource;
    /**
     * resource class name
     * @var string
     */
    protected $resourceClass = 'mate\Resource\CsvResource';
    /**
     * @var string path to test csv file
     */
    protected $csvFile = __DIR__ . '/files/test.csv';
    /**
     * @var Keymap keymap of test csv
     */
    protected $keymap;
    /**
     * @var array content of csv file
     */
    protected $expected;

    public function setUp()
    {
        $this->resource = ResourceFactory::create($this->csvFile);
        $this->expected = $this->readCsv($this->csvFile);
        vfsStream::setup("csv");
    }

    protected function readCsv($csvFile)
    {
        $handle = fopen($csvFile, 'r+');
        $csvArray = array();
        $row = 0;
        $first = true;
        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
            if($first === true) {
                $this->keymap = new Keymap($data);
                $first = false;
                continue;
            }
            $csvArray[$row] = $data;
            $row++;
        }
        fclose($handle);
        return $csvArray;
    }

    public function testResourceFactoryCondition()
    {
        $check = ResourceFactory::getResourceClass($this->csvFile);
        $this->assertEquals($this->resourceClass, $check,
            'ResourceFactory::getResourceClass does not return CsvResource if provided with a CSV file path');
    }

    public function testFactoryReturnsCsvResourceObject()
    {
        $this->assertInstanceOf('mate\Resource\ResourceInterface', $this->resource,
            'Return of factory does not implement mate\Resource\ResourceInterface');
        $this->assertInstanceOf($this->resourceClass, $this->resource,
            'Factory did not return instance of ' . $this->resourceClass);
    }

    public function testConstructCsvRowWithAssociativeArray()
    {
        $resourceArray = array(
            "id"   => 2,
            "name" => "Urmeli"
        );
        $keymap = new Keymap(["id", "name"]);
        $csvRowResource = new CsvRowResource($resourceArray, ["keymap" => $keymap]);
        $expectedResource = array(2, "Urmeli");
        $this->assertEquals($expectedResource, $csvRowResource->getResource(),
            "__construct() does not initializes the resource correctly if an associative array was given instead of an indexed array");
    }


    public function provideTestCsvResourceThrowsExceptionIfConstructedWithInvalidResource()
    {
        return array(
            ['I/Am/Invalid'],
            [array('invalid' => 'value')],
            ['I am a string!'],
        );
    }

    /**
     * @dataProvider provideTestCsvResourceThrowsExceptionIfConstructedWithInvalidResource
     * @param mixed $resource
     */
    public function testCsvResourceThrowsExceptionIfConstructedWithInvalidResource($resource)
    {
        $message = sprintf(CsvResource::EXCEPTION_RESOURCE_MUST_BE_FILE, gettype($resource));
        $this->setExpectedException(RuntimeException::class, $message);
        new CsvResource($resource);
    }

    public function testGetFile()
    {
        $this->assertEquals($this->csvFile, $this->resource->getFilePath(),
            "getFile() does not return the file the CsvResource was created with");
    }

    public function testSetAndGetFile()
    {
        $newFile = vfsStream::url("csv/new.csv");
        $this->resource->setFilePath($newFile);
        $this->assertEquals($newFile, $this->resource->getFilePath(),
            "setFile() does not set a new file path");
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     */
    public function testGetRow()
    {
        $expected = $this->expected[0];
        $actual = $this->resource->get(0);
        $this->assertInstanceOf('mate\Resource\CsvRowResource', $actual,
            'get() does not return the selected csv row as CsvRowResource object');
        $this->assertEquals($expected, $actual->getResource(),
            'get() does not return the selected csv row');
    }

    public function testCountCsvResource()
    {
        $this->assertEquals(3, $this->resource->count(),
            "count() does not return the correct amount of rows in the CSV");
    }

    /**
     * @depends testGetRow
     */
    public function testCountCsvRowResource()
    {
        $this->assertEquals(7, $this->resource->get(0)->count(),
            "count() does not return the correct amount of columns in the CSV row");
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     */
    public function testChangeCsvDelimiterInCsvResourceViaMethod()
    {
        $newCsvDelimiter = ',';
        $this->resource->getFileOptions()->setDelimiter($newCsvDelimiter);
        $this->assertEquals($newCsvDelimiter, $this->resource->getFileOptions()->getDelimiter(),
            'Failed to set a custom csv delimiter via setCsvDelimiter()');
    }

    // set file options

    public function testSetFileOptionsViaOptionsAsArray()
    {
        $options = array(
            "delimiter"      => ",",
            "enclosure"      => "'",
            "lineFeed"       => "\r\n",
            "fileEncoding"   => "windows-1252",
            "objectEncoding" => "UTF-8",
            "filePath"       => "another/path.csv",
        );
        $expectedOptions = new FileOptions();
        $expectedOptions->exchangeArray($options);

        $resource = new CsvResource(
            $this->csvFile,
            array(
                "fileOptions" => $options
            )
        );
        $this->assertEquals($expectedOptions, $resource->getFileOptions(),
            "Failed to set file options by array");
    }

    public function testSetFileOptionsViaOptionsAsObject()
    {
        $options = array(
            "delimiter"      => ",",
            "enclosure"      => "'",
            "lineFeed"       => "\r\n",
            "fileEncoding"   => "windows-1252",
            "objectEncoding" => "UTF-8",
        );
        $optionsObject = new FileOptions();
        $optionsObject->exchangeArray($options);

        $expectedOptions = new FileOptions();
        $expectedOptions->exchangeArray($options);
        $expectedOptions->setFilePath($this->csvFile);

        $resource = new CsvResource(
            $this->csvFile,
            array(
                "fileOptions" => $optionsObject
            )
        );
        $this->assertEquals($expectedOptions, $resource->getFileOptions(),
            "Failed to set file options by array");
    }

    /**
     * @depends testSetFileOptionsViaOptionsAsArray
     */
    public function testSetFileOptionsForAllRows()
    {
        $options = array(
            "delimiter"      => ",",
            "enclosure"      => "'",
            "lineFeed"       => "\r\n",
            "fileEncoding"   => "windows-1252",
            "objectEncoding" => "UTF-8",
        );
        $optionsObject = new FileOptions();
        $optionsObject->exchangeArray($options);
        $optionsObject->setFilePath($this->csvFile);

        $resource = new CsvResource($this->csvFile);
        $resource->setFileOptions($optionsObject);
        $this->assertEquals($optionsObject, $resource->get(2)->getFileOptions(),
            "Failed to set file options for all rows");
    }

    // options via method

    /**
     * @depends testChangeCsvDelimiterInCsvResourceViaMethod
     * @depends testGetRow
     */
    public function testChangeCsvDelimiterForAllRowsViaMethod()
    {
        $newCsvDelimiter = ',';
        $this->resource->getFileOptions()->setDelimiter($newCsvDelimiter);
        $this->assertEquals($newCsvDelimiter, $this->resource->get(2)->getFileOptions()->getDelimiter(),
            'Failed to set a custom csv delimiter via setCsvDelimiter() for all rows');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     */
    public function testChangeCsvEnclosureInCsvResourceViaMethod()
    {
        $newCsvEnclosure = ',';
        $this->resource->getFileOptions()->setEnclosure($newCsvEnclosure);
        $this->assertEquals($newCsvEnclosure, $this->resource->getFileOptions()->getEnclosure(),
            'Failed to set a custom csv delimiter via setCsvEnclosure()');
    }

    /**
     * @depends testChangeCsvEnclosureInCsvResourceViaMethod
     * @depends testGetRow
     */
    public function testChangeCsvEnclosureForAllRowsViaMethod()
    {
        $newCsvEnclosure = ',';
        $this->resource->getFileOptions()->setEnclosure($newCsvEnclosure);
        $this->assertEquals($newCsvEnclosure, $this->resource->get(2)->getFileOptions()->getEnclosure(),
            'Failed to set a custom csv delimiter via setCsvEnclosure() for all rows');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testGetReturnsDefaultIfRowIsNotSet()
    {
        $default = 'Default Value';
        $actual = $this->resource->get(5, $default);
        $this->assertEquals($default, $actual,
            'get() does not return default value if the row was not set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testReturnResource()
    {
        $actual = $this->resource->get(0);
        $this->assertInstanceOf('mate\Resource\ResourceInterface', $actual,
            'getting a row does not return resource by default');
        $this->assertInstanceOf('mate\Resource\CsvRowResource', $actual,
            'getting a row does not return instance of CsvRowResource by default');
        $this->resource->setReturnResource(false);
        $actual = $this->resource->get(0);
        $this->assertNotInstanceOf('mate\Resource\ResourceInterface', $actual,
            'get() returns resource if returnResource was set to false');
    }

    /**
     * @depends testGetRow
     */
    public function testSetKeymapForAllRows()
    {
        $keymap = array(
            'bookTitle', 'author', 'release', 'genre', 'originalLanguage', 'price', 'inStock'
        );
        $expectedKeymap = new Keymap($keymap);
        $this->resource->setKeymap($keymap);
        $this->assertEquals($expectedKeymap, $this->resource->get(1)->getKeymap(),
            "setKeymap does not set a the keymap correctly to all rows");
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testGetRowEntryByNumericKey()
    {
        $row = 1;
        $numericKey = 2;
        $expected = $this->expected[$row][$numericKey];
        $actual = $this->resource->get($row)->get($numericKey);
        $this->assertEquals($expected, $actual,
            'get() in CsvRowResource was unable to return a value by its numeric key');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testSetKeymapForAllRows
     */
    public function testGetRowEntryByKeymapValue()
    {
        $row = 1;
        $numericKey = 2;
        $key = $this->keymap->key($numericKey);
        $expected = $this->expected[$row][$numericKey];
        $actual = $this->resource->get($row)->get($key);
        $this->assertEquals($expected, $actual,
            'get() in CsvRowResource was unable to return a value by its key set in the keymap');
    }

    /**
     * @depends testGetRow
     */
    public function testGetRecursiveEntryWithInvalidArgumentTypeThrowsException()
    {
        $message = ResourceAbstract::INVALID_PATH;
        $this->setExpectedException("InvalidArgumentException", $message);
        $this->resource->get(1)->getRecursive(new \stdClass());
    }

    public function testGetRecursiveWithInvalidArgumentTypeThrowsException()
    {
        $message = ResourceAbstract::INVALID_PATH;
        $this->setExpectedException("InvalidArgumentException", $message);
        $this->resource->getRecursive(new \stdClass());
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testGetRowEntryByNumericKey
     */
    public function testGetRowEntryByNumericKeyReturnsDefaultIfEntryIsNotSet()
    {
        $row = 1;
        $invalidKey = 10;
        $default = 'Default Value';
        $actual = $this->resource->get($row)->get($invalidKey, $default);
        $this->assertEquals($default, $actual,
            'get() in CsvRowResource does not return a default value if an invalid numeric key was passed');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testGetRowEntryByKeymapValue
     */
    public function testGetRowEntryByKeymapValueReturnsDefaultIfEntryIsNotSet()
    {
        $row = 1;
        $invalidKey = 'InvalidKeymapKey';
        $default = 'Default Value';
        $actual = $this->resource->get($row)->get($invalidKey, $default);
        $this->assertEquals($default, $actual,
            'get() in CsvRowResource does not return a default value if an invalid keymap value was passed');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     */
    public function testChangeSeparatorInCsvResourceViaMethod()
    {
        $newSeparator = '%';
        $this->resource->setSeparator($newSeparator);
        $this->assertEquals($newSeparator, $this->resource->getSeparator(),
            'Failed to set a custom separator via setSeparator()');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     */
    public function testChangeSeparatorInCsvResourceViaOptions()
    {
        $newSeparator = '%';
        $resource = new CsvResource($this->csvFile, array('separator' => $newSeparator));
        $this->assertEquals($newSeparator, $resource->getSeparator(),
            'Failed to set a custom separator by setting options[separator]');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testChangeSeparatorInCsvResourceViaOptions
     * @depends testGetRow
     */
    public function testChangeSeparatorForAllRowsViaMethod()
    {
        $newSeparator = '%';
        $this->resource->setSeparator($newSeparator);
        $this->assertEquals($newSeparator, $this->resource->get(2)->getSeparator(),
            'Failed to set a custom separator via setSeparator() for all rows');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testChangeSeparatorInCsvResourceViaOptions
     * @depends testGetRow
     */
    public function testChangeSeparatorForAllRowsViaOption()
    {
        $newSeparator = '%';
        $resource = new CsvResource($this->csvFile, array('separator' => $newSeparator));
        $this->assertEquals($newSeparator, $resource->get(2)->getSeparator(),
            'Failed to set a custom separator by setting options[separator] for all rows');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testSetKeymapForAllRows
     */
    public function testRecursiveGetterByArray()
    {
        $row = 1;
        $numericKey = 2;
        $expected = $this->expected[$row][$numericKey];
        $actual = $this->resource->getRecursive(array($row, $numericKey));
        $this->assertEquals($expected, $actual,
            'getRecursive() does not return the selected csv entry if selected by array');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     */
    public function testRecursiveGetterByString()
    {
        $row = 1;
        $numericKey = 2;
        $expected = $this->expected[$row][$numericKey];
        $actual = $this->resource->getRecursive($row . '/' . $numericKey);;
        $this->assertEquals($expected, $actual,
            'getRecursive() does not return the selected csv entry if selected by string');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     */
    public function testRecursiveGetterByPathObject()
    {
        $row = 1;
        $numericKey = 2;
        $expected = $this->expected[$row][$numericKey];
        $path = new Path(array($row, $numericKey));
        $actual = $this->resource->getRecursive($path);
        $this->assertEquals($expected, $actual,
            'getRecursive() does not return the selected csv entry if  selected by Path object');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     */
    public function testRecursiveGetterByKeymapValue()
    {
        $row = 1;
        $numericKey = 2;
        $key = $this->keymap->key($numericKey);
        $expected = $this->expected[$row][$numericKey];
        $actual = $this->resource->getRecursive(array($row, $key));
        $this->assertEquals($expected, $actual,
            'getRecursive() does not return the selected csv entry if  selected by keymap value');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testSetKeymapForAllRows
     * @depends testRecursiveGetterByArray
     */
    public function testRecursiveGetterByArrayReturnsDefaultIfNotSet()
    {
        $row = 1;
        $invalidKey = 10;
        $invalidRow = 5;
        $default = 'default value';
        $actual = $this->resource->getRecursive(array($row, $invalidKey), $default);
        $this->assertEquals($default, $actual,
            'getRecursive() does not return default value if selected by array and the entry is not set');
        $actual = $this->resource->getRecursive(array($invalidRow, $invalidKey), $default);
        $this->assertEquals($default, $actual,
            'getRecursive() does not return default value if selected by array and the row is not set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveGetterByString
     */
    public function testRecursiveGetterByStringReturnsDefaultIfNotSet()
    {
        $row = 1;
        $invalidKey = 10;
        $invalidRow = 5;
        $default = 'default value';
        $actual = $this->resource->getRecursive($row . '/' . $invalidKey, $default);
        $this->assertEquals($default, $actual,
            'getRecursive() does not return default value if selected by string and the entry is not set');
        $actual = $this->resource->getRecursive($invalidRow . '/' . $invalidKey, $default);
        $this->assertEquals($default, $actual,
            'getRecursive() does not return default value if selected by string and the row is not set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveGetterByPathObject
     */
    public function testRecursiveGetterByPathObjectReturnsDefaultIfNotSet()
    {
        $row = 1;
        $invalidKey = 10;
        $invalidRow = 5;
        $default = 'default value';
        $path = new Path(array($row, $invalidKey));
        $actual = $this->resource->getRecursive($path, $default);
        $this->assertEquals($default, $actual,
            'getRecursive() does not return default value if selected by path object and the entry is not set');
        $path = new Path(array($invalidRow, $invalidKey));
        $actual = $this->resource->getRecursive($path, $default);
        $this->assertEquals($default, $actual,
            'getRecursive() does not return default value if selected by path object and the row is not set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveGetterByKeymapValue
     */
    public function testRecursiveGetterByKeymapValueReturnsDefaultIfNotSet()
    {
        $row = 1;
        $invalidRow = 5;
        $key = 'invalidKey';
        $default = 'default value';
        $actual = $this->resource->getRecursive(array($row, $key), $default);
        $this->assertEquals($default, $actual,
            'getRecursive() does not return default value if selected by keymap value and the entry is not set');
        $actual = $this->resource->getRecursive(array($invalidRow, $key), $default);
        $this->assertEquals($default, $actual,
            'getRecursive() does not return default value if selected by keymap value and the row is not set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     */
    public function testThrowExceptionIfMoreThanTwoKeysAreGivenToGetRecursiveOfCsvResource()
    {
        $message = sprintf(CsvResource::EXCEPTION_TOO_MANY_PATH_KEYS, 3);
        $this->setExpectedException(InvalidArgumentException::class, $message);
        $this->resource->getRecursive(array(1, 2, 'tooMuch'));
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testRecursiveGetterOnCsvRowByArray()
    {
        $expected = $this->expected[2][4];
        $actual = $this->resource->get(2)->getRecursive(array(4));
        $this->assertEquals($expected, $actual,
            'getRecursive() in CsvRow does not return the selected csv entry if selected by array');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testRecursiveGetterOnCsvRowByPath()
    {
        $expected = $this->expected[2][4];
        $path = new Path(array(4));
        $actual = $this->resource->get(2)->getRecursive($path);
        $this->assertEquals($expected, $actual,
            'getRecursive() in CsvRow does not return the selected csv entry if selected by Path object');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testRecursiveGetterOnCsvRowByString()
    {
        $expected = $this->expected[2][4];
        $actual = $this->resource->get(2)->getRecursive('4');
        $this->assertEquals($expected, $actual,
            'getRecursive() in CsvRow does not return the selected csv entry if selected by string');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testRecursiveGetterOnCsvRowByInteger()
    {
        $expected = $this->expected[2][4];
        $actual = $this->resource->get(2)->getRecursive(4);
        $this->assertEquals($expected, $actual,
            'getRecursive() in CsvRow does not return the selected csv entry if selected by integer');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     */
    public function testThrowExceptionIfMoreThanOneKeyIsGivenToGetRecursiveOfCsvRowResourceByArray()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->resource->get(2)->getRecursive(array(1, 'tooMuch'));
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterOnCsvRowByPath
     */
    public function testThrowExceptionIfMoreThanOneKeyIsGivenToGetRecursiveOfCsvRowResourceByPath()
    {
        $this->setExpectedException('InvalidArgumentException');
        $path = new Path(array(1, 'tooMuch'));
        $this->resource->get(2)->getRecursive($path);
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterOnCsvRowByString
     */
    public function testThrowExceptionIfMoreThanOneKeyIsGivenToGetRecursiveOfCsvRowResourceByString()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->resource->get(2)->getRecursive('1/tooMuch');
    }

    /**
     * @depends testGetRow
     */
    public function testReturnResourceIsAlwaysFalseInRowResource()
    {
        $row = $this->resource->get(1);
        $row->setReturnResource(true);
        $this->assertFalse($row->getReturnResource(),
            "returnResource must always be false in CsvRowResource");
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testReturnResource
     */
    public function testUpdateCsvRowAsArray()
    {
        $this->resource->setReturnResource(false);
        $manipulatedRow = $this->resource->get(0);
        $manipulatedRow[0] = 'LOTR';
        $manipulatedRow[1] = 'John Ronald Reuel Tolkien';
        $this->resource->set(0, $manipulatedRow);
        $this->assertEquals($manipulatedRow, $this->resource->get(0),
            'set() method in CsvResource does not replace an existing row');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testReturnResource
     */
    public function testUpdateCsvRowAsObject()
    {
        $this->resource->setReturnResource(false);
        $manipulatedRow = $this->resource->get(0);
        $manipulatedRow[0] = 'LOTR';
        $manipulatedRow[1] = 'John Ronald Reuel Tolkien';
        $newRowObject = new CsvRowResource($manipulatedRow);
        $this->resource->set(0, $newRowObject);
        $this->assertEquals($manipulatedRow, $this->resource->get(0),
            'set() method in CsvResource does not replace an existing row if a CsvRowResource object was set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testReturnResource
     * @depends testUpdateCsvRowAsArray
     */
    public function testUpdatedCsvRowAsArrayUpdatesCsvRowResource()
    {
        $this->resource->setReturnResource(false);
        $manipulatedRow = $this->resource->get(0);
        $manipulatedRow[0] = 'LOTR';
        $manipulatedRow[1] = 'John Ronald Reuel Tolkien';
        $this->resource->set(0, $manipulatedRow);
        $this->resource->setReturnResource(true);
        $newRow = $this->resource->get(0);
        $this->assertInstanceOf('mate\Resource\CsvRowResource', $newRow,
            'set() method in CsvResource does not replace an existing row as CsvRowResource object');
        $this->assertEquals($manipulatedRow, $newRow->getResource(),
            'set() method in CsvResource does not replace an existing row as CsvRowResource object');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testReturnResource
     */
    public function testInsertNewCsvRowAsArray()
    {
        $this->resource->setReturnResource(false);
        $manipulatedRow = $this->resource->get(1);
        $this->resource->set(4, $manipulatedRow);
        $this->assertEquals($manipulatedRow, $this->resource->get(4),
            'set() method in CsvResource does not insert a new row');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testReturnResource
     */
    public function testInsertNewCsvRowAsObject()
    {
        $this->resource->setReturnResource(false);
        $manipulatedRow = $this->resource->get(1);
        $newRowObject = new CsvRowResource($manipulatedRow);
        $this->resource->set(4, $newRowObject);
        $this->assertEquals($manipulatedRow, $this->resource->get(4),
            'set() method in CsvResource does not insert a new row if a CsvRowResource object was set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testReturnResource
     * @depends testInsertNewCsvRowAsArray
     */
    public function testInsertNewCsvRowAsArrayUpdatesCsvRowResource()
    {
        $this->resource->setReturnResource(false);
        $manipulatedRow = $this->resource->get(1);
        $this->resource->set(4, $manipulatedRow);
        $this->resource->setReturnResource(true);
        $this->assertInstanceOf('mate\Resource\CsvRowResource', $this->resource->get(4),
            'set() method in CsvResource does not insert a new row as CsvRowResource object');
    }

    public function provideTestThrowExceptionOnSettingInvalidValue()
    {
        return array(
            ['invalid'],
            [2],
            [new \stdClass()],
            [false],
            [null],
            [1.462],
        );
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testUpdateCsvRowAsArray
     * @depends testUpdateCsvRowAsObject
     *
     * @param mixed $value
     */
    public function testThrowExceptionOnSettingInvalidValue($value)
    {
        $message = sprintf(CsvResource::EXCEPTION_SETTING_INVALID_DATA_TYPE, gettype($value));
        $this->setExpectedException(InvalidArgumentException::class, $message);
        $this->resource->set(1, $value);
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testGetRowEntryByNumericKey
     */
    public function testSetRowEntryByNumericKey()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $this->resource->get($row)->set($numericKey, $newValue);
        $actual = $this->resource->get($row)->get($numericKey);
        $this->assertEquals($newValue, $actual,
            'set() in CsvRowResource was unable to set a value by its numeric key');
        $numericKey = '1';
        $newValue = 'LOTR';
        $this->resource->get($row)->set($numericKey, $newValue);
        $actual = $this->resource->get($row)->get($numericKey);
        $this->assertEquals($newValue, $actual,
            'set() in CsvRowResource was unable to set a value by a string containing its numeric key');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testGetRowEntryByNumericKey
     * @depends testSetKeymapForAllRows
     */
    public function testSetRowEntryByKeymapValue()
    {
        $row = 0;
        $numericKey = 1;
        $key = $this->keymap->key($numericKey);
        $newValue = 'John Ronald Reuel Tolkien';
        $this->resource->get($row)->set($key, $newValue);
        $actual = $this->resource->get($row)->get($numericKey);
        $this->assertEquals($newValue, $actual,
            'set() in CsvRowResource was unable to set a value by its key set in the keymap');
    }

    /**
     * @depends testSetRowEntryByKeymapValue
     * @depends testGetRowEntryByKeymapValue
     */
    public function testSetRowEntryByUnknownKeymapValue()
    {
        $key = 'rating';
        $value = 4.5;
        $row = 0;
        $this->resource->get($row)->set($key, $value);
        $actual = $this->resource->get($row)->get($key);
        $this->assertEquals($value, $actual,
            'set() does not add a new column if a value was set by an unknown keymap value');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testGetRowEntryByNumericKey
     */
    public function testSetObjectWithToStringMethodToRowEntry()
    {
        $row = 0;
        $numericKey = 1;
        $path = new Path(array('Object', 'With', 'toString', 'Method'));
        $this->resource->get($row)->set($numericKey, $path);
        $expected = (string)$path;
        $actual = $this->resource->get($row)->get($numericKey);
        $this->assertSame($expected, $actual,
            'set() in CsvRowResource was unable to set an object with toString method as value');
    }

    /**
     * @depends testSetRowEntryByNumericKey
     */
    public function testSettingCsvRowEntryUpdatesCsvResource()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $this->resource->get($row)->set($numericKey, $newValue);
        $expected = $this->expected;
        $expected[$row][$numericKey] = $newValue;
        $this->assertEquals($expected, $this->resource->getResource(),
            'Values set in csv row are not set in resource of csv. Resources in CsvRowResource might not be referenced to resource in CsvResource');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testGetRowEntryByNumericKey
     * @depends testSetRowEntryByNumericKey
     */
    public function testThrowExceptionOnSettingArrayToCsvRowEntry()
    {
        $message = sprintf(CsvRowResource::EXCEPTION_SETTING_INVALID_VALUE, 'array');
        $this->setExpectedException(InvalidArgumentException::class, $message);
        $row = 0;
        $numericKey = 1;
        $this->resource->get($row)->set($numericKey, array('I', 'Am', 'Invalid'));
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testGetRowEntryByNumericKey
     * @depends testSetRowEntryByNumericKey
     */
    public function testThrowExceptionOnSettingObjectToCsvRowEntry()
    {
        $message = sprintf(CsvRowResource::EXCEPTION_SETTING_INVALID_VALUE, 'object');
        $this->setExpectedException(InvalidArgumentException::class, $message);
        $row = 0;
        $numericKey = 1;
        $this->resource->get($row)->set($numericKey, new \stdClass());
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     */
    public function testRecursiveSetterByArray()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $this->resource->setRecursive(array($row, $numericKey), $newValue);
        $actual = $this->resource->getRecursive(array($row, $numericKey));
        $this->assertEquals($newValue, $actual,
            'setRecursive() does not set the selected csv entry if set by array');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveSetterByArray
     */
    public function testRecursiveSetterByString()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $this->resource->setRecursive($row . '/' . $numericKey, $newValue);
        $actual = $this->resource->getRecursive(array($row, $numericKey));
        $this->assertEquals($newValue, $actual,
            'setRecursive() does not set the selected csv entry if set by string');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveSetterByArray
     */
    public function testRecursiveSetterByPathObject()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $path = new Path(array($row, $numericKey));
        $this->resource->setRecursive($path, $newValue);
        $actual = $this->resource->getRecursive($path);
        $this->assertEquals($newValue, $actual,
            'setRecursive() does not return the selected csv entry if set by Path object');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveSetterByArray
     */
    public function testRecursiveSetterByKeymapValue()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $key = $this->keymap->key($numericKey);
        $this->resource->setRecursive(array($row, $key), $newValue);
        $actual = $this->resource->getRecursive(array($row, $numericKey));
        $this->assertEquals($newValue, $actual,
            'setRecursive() does not return the selected csv entry if set by keymap value');
    }

    /**
     * @depends testRecursiveSetterByArray
     */
    public function testRecursiveSetterWithOneKey()
    {
        $row = 0;
        $newValue = [];
        $this->resource->setRecursive([$row], $newValue);
        $actual = $this->resource->getRecursive([$row]);
        $actual = $actual instanceof ResourceInterface ? $actual->getResource() : $actual;
        $this->assertEquals($newValue, $actual,
            'setRecursive() does not set the selected csv row');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveSetterByArray
     */
    public function testThrowExceptionIfMoreThanTwoKeysAreGivenToSetRecursiveOfCsvResource()
    {
        $message = sprintf(CsvResource::EXCEPTION_TOO_MANY_PATH_KEYS, 3);
        $this->setExpectedException(InvalidArgumentException::class, $message);
        $this->resource->setRecursive(array(1, 2, 'tooMuch'), 'value');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     */
    public function testRecursiveSetterOnCsvRowByArray()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $this->resource->get($row)->setRecursive(array($numericKey), $newValue);
        $actual = $this->resource->getRecursive(array($row, $numericKey));
        $this->assertEquals($newValue, $actual,
            'setRecursive() in CsvRow does not set the selected csv entry if selected by array');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveSetterOnCsvRowByArray
     */
    public function testRecursiveSetterOnCsvRowByPath()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $path = new Path(array($numericKey));
        $this->resource->get($row)->setRecursive($path, $newValue);
        $actual = $this->resource->getRecursive(array($row, $numericKey));
        $this->assertEquals($newValue, $actual,
            'setRecursive() in CsvRow does not set the selected csv entry if selected by Path object');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveSetterOnCsvRowByArray
     */
    public function testRecursiveSetterOnCsvRowByString()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $this->resource->get($row)->setRecursive((string)$numericKey, $newValue);
        $actual = $this->resource->getRecursive(array($row, $numericKey));
        $this->assertEquals($newValue, $actual,
            'setRecursive() in CsvRow does not set the selected csv entry if selected by string');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveGetterByArray
     * @depends testRecursiveSetterOnCsvRowByArray
     */
    public function testRecursiveSetterOnCsvRowByInteger()
    {
        $row = 0;
        $numericKey = 1;
        $newValue = 'John Ronald Reuel Tolkien';
        $this->resource->get($row)->setRecursive($numericKey, $newValue);
        $actual = $this->resource->getRecursive(array($row, $numericKey));
        $this->assertEquals($newValue, $actual,
            'setRecursive() in CsvRow does not set the selected csv entry if selected by integer');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveSetterByArray
     */
    public function testThrowExceptionIfMoreThanOneKeyIsGivenToSetRecursiveOfCsvRowResourceByArray()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->resource->get(2)->setRecursive(array(1, 'tooMuch'), 'value');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveSetterOnCsvRowByPath
     */
    public function testThrowExceptionIfMoreThanOneKeyIsGivenToSetRecursiveOfCsvRowResourceByPath()
    {
        $this->setExpectedException('InvalidArgumentException');
        $path = new Path(array(1, 'tooMuch'));
        $this->resource->get(2)->setRecursive($path, 'value');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testRecursiveSetterOnCsvRowByString
     */
    public function testThrowExceptionIfMoreThanOneKeyIsGivenToSetRecursiveOfCsvRowResourceByString()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->resource->get(2)->setRecursive('1/tooMuch', 'value');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testReturnResource
     */
    public function testGetRowByFindFunction()
    {
        $expected = $this->expected[1];
        $this->resource->setReturnResource(false);
        $actual = $this->resource->find(1);
        $this->assertEquals($expected, $actual,
            'find() method was unable to find a csv row');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testSetKeymapForAllRows
     */
    public function testGetRowEntriesByFindFunctionUsingTheKeymapValue()
    {
        $expected = array(
            $this->expected[0][1],
            $this->expected[1][1],
            $this->expected[2][1],
        );
        $actual = $this->resource->find($this->keymap->key(1));
        $this->assertEquals($expected, $actual,
            'find() method was unable to find csv row entries by their keymap value');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testGetEntryByFindFunctionInCsvRow()
    {
        $row = 0;
        $numericKey = 1;
        $expected = $this->expected[$row][$numericKey];
        $actual = $this->resource->get($row)->find($numericKey);
        $this->assertEquals($expected, $actual,
            'find() method in CsvRowResource was unable to find entry');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     */
    public function testCsvResourceHasMethod()
    {
        $has = $this->resource->has(2);
        $this->assertTrue($has, 'has() does not return true if row is set');
        $hasNot = $this->resource->has(4);
        $this->assertFalse($hasNot, 'has() does not return false if row is not set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     */
    public function testCsvResourceHasMethodWithTooManyKeys()
    {
        $hasNot = $this->resource->hasRecursive([2, 3, 5]);
        $this->assertFalse($hasNot, 'has() does not return false if too many keys were set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     */
    public function testCsvRowResourceHasMethodWithNumericKey()
    {
        $has = $this->resource->get(1)->has(2);
        $this->assertTrue($has, 'has() in CsvRowResource does not return true if entry is set');
        $hasNot = $this->resource->get(1)->has(20);
        $this->assertFalse($hasNot, 'has() in CsvRowResource does not return false if entry is not set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testSetKeymapForAllRows
     */
    public function testCsvRowResourceHasMethodWithKeymapValue()
    {
        $key = $this->keymap->key(2);
        $has = $this->resource->get(1)->has($key);
        $this->assertTrue($has, 'has() in CsvRowResource does not return true if entry is set');
        $hasNot = $this->resource->get(1)->has('invalid');
        $this->assertFalse($hasNot, 'has() in CsvRowResource does not return false if entry is not set');
    }

    /**
     * @depends testCsvRowResourceHasMethodWithKeymapValue
     */
    public function testCsvRowResourceHasRecursiveMethodWithSingleKey()
    {
        $key = $this->keymap->key(2);
        $this->assertTrue($this->resource->get(1)->hasRecursive(array($key)),
            'hasRecursive() in CsvRowResource does not return true if a single key was given that is set');
        $this->assertFalse($this->resource->get(1)->hasRecursive(array('invalid')),
            'hasRecursive() in CsvRowResource does not return false if a single key was given that is not set');
        $this->assertFalse($this->resource->get(1)->hasRecursive(array('some', 'path')),
            'hasRecursive() in CsvRowResource does not return false if multiple keys were given');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testCsvResourceHasMethod
     */
    public function testCsvResourceHasRecursiveMethodOnRow()
    {
        $has = $this->resource->hasRecursive(2);
        $this->assertTrue($has, 'hasRecursive() does not return true if row is set');
        $hasNot = $this->resource->hasRecursive(4);
        $this->assertFalse($hasNot, 'hasRecursive() does not return false if row is not set');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testCsvResourceHasMethod
     * @depends testCsvRowResourceHasMethodWithNumericKey
     */
    public function testCsvResourceHasRecursiveMethodOnRowEntry()
    {
        $has = $this->resource->hasRecursive(array(1, 2));
        $this->assertTrue($has, 'hasRecursive() does not return true if entry is set');
        $hasNot = $this->resource->hasRecursive(array(1, 20));
        $this->assertFalse($hasNot, 'hasRecursive() does not return false if entry is not set');
    }

    public function testSetAndIsReadingKeysFromKeymap()
    {
        $this->resource->setReadingKeysFromKeymap(false);
        $this->assertFalse($this->resource->isReadingKeysFromKeymap(),
            "unable to set or get isReadingKeysFromKeymap");
    }

    /**
     * @depends testSetAndIsReadingKeysFromKeymap
     */
    public function testIsReadingKeysFromKeymapIsTrueByDefault()
    {
        $this->assertTrue($this->resource->isReadingKeysFromKeymap(),
            "isReadingKeysFromKeymap is not true by default");
    }

    /**
     * @depends testGetRow
     * @depends testIsReadingKeysFromKeymapIsTrueByDefault
     */
    public function testSetReadingKeysFromKeymapForAllRows()
    {
        $this->resource->setReadingKeysFromKeymap(false);
        $this->assertFalse($this->resource->get(1)->isReadingKeysFromKeymap(),
            "setReadingKeysFromKeymap does not set the value for all rows");
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testSetAndIsReadingKeysFromKeymap
     */
    public function testToArrayMethodInCsvRowResource()
    {
        $rowArray = array(
            "id"    => 1,
            "value" => 2847,
        );
        $testRowResource = new CsvRowResource($rowArray, array(
            "keymap" => new Keymap(["id", "value"]),
        ));
        $toArrayResult = $testRowResource->toArray();
        $this->assertEquals($rowArray, $toArrayResult,
            "toArray() method in CsvRowResource does not return the CSV row as array with keymap values if readingKeysFromKeymap is true");
        $testRowResource->setReadingKeysFromKeymap(false);
        $iterativeRowArray = array(1, 2847);
        $toArrayResult = $testRowResource->toArray();
        $this->assertEquals($iterativeRowArray, $toArrayResult,
            'toArray() method in CsvRowResource does not return the CSV row as array with numeric keys if readingKeysFromKeymap is false');
    }

    /**
     * @depends testSetKeymapForAllRows
     * @depends testSetRowEntryByNumericKey
     * @depends testToArrayMethodInCsvRowResource
     * @depends testSetReadingKeysFromKeymapForAllRows
     */
    public function testToArrayMethodInCsvResource()
    {
        $testCsvFile = vfsStream::url("csv/toArrayTest.csv");
        fclose(fopen($testCsvFile, "w"));
        $testResource = new CsvResource($testCsvFile);
        $testResource->setKeymap(["id", "value"]);
        $rowArray = array(
            "id"    => 1,
            "value" => 2847,
        );
        $csvArray = [$rowArray];
        $testResource->set(0, $rowArray);
        $toArrayResult = $testResource->toArray();
        $this->assertEquals($csvArray, $toArrayResult,
            'toArray() method in CsvResource does not return the CSV as array with keymap values if readingKeysFromKeymap is true');
        $testResource->setReadingKeysFromKeymap(false);
        $toArrayResult = $testResource->toArray();
        $iterativeRowArray = [[1, 2847]];
        $this->assertEquals($iterativeRowArray, $toArrayResult,
            'toArray() method in CsvResource does not return the CSV as array with numeric keys if readingKeysFromKeymap is false');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testReturnResource
     */
    public function testIteratingThroughCsvResource()
    {
        $this->assertInstanceOf('Iterator', $this->resource,
            'CsvResource does not implement the Iterator interface');
        $this->assertInstanceOf('mate\Resource\CsvRowResource', $this->resource->current(),
            'current() in CsvResource does not return the current row as CsvRowResource if returnResource is true');
        $this->resource->setReturnResource(false);
        $this->assertNotInstanceOf('mate\Resource\CsvRowResource', $this->resource->current(),
            'current() in CsvResource returns the current row as CsvRowResource if returnResource is false');
        $this->assertEquals($this->expected[0], $this->resource->current(),
            'current() in CsvResource does not return the current row');
        $this->resource->next();
        $this->resource->setReturnResource(true);
        $this->assertEquals($this->expected[1], $this->resource->current()->getResource(),
            'next() in CsvResource does not move to the next CsvRowResource object');
        $this->resource->setReturnResource(false);
        $this->assertEquals($this->expected[1], $this->resource->current(),
            'next() in CsvResource does not move to the next csv row array');
        $this->assertEquals(1, $this->resource->key(),
            'key() in CsvResource does not return the current key');
        $this->assertTrue($this->resource->valid(),
            'valid() does not return true if the current entry is valid');
        $this->resource->next();
        $this->resource->next();
        $this->assertFalse($this->resource->valid(),
            'valid() does not return false if the current entry is not valid');
        $this->resource->rewind();
        $this->assertEquals($this->expected[0], $this->resource->current(),
            'rewind() does not move the pointer back to the first entry');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testSetKeymapForAllRows
     */
    public function testIteratingThroughCsvRowResource()
    {
        $rowKey = 0;
        $expected = $this->expected[$rowKey];
        $rowRes = $this->resource->get(0);
        $this->assertInstanceOf('Iterator', $rowRes,
            'CsvRowResource does not implement the Iterator interface');
        $this->assertEquals($expected[0], $rowRes->current(),
            'current() in CsvRowResource does not return the current entry');
        $rowRes->next();
        $this->assertEquals($expected[1], $rowRes->current(),
            'next() in CsvRowResource does not move to the next entry');
        $this->assertEquals(1, $rowRes->key(false),
            'key() in CsvResource does not return the current position if $fromKeymap parameter is false');
        $this->assertEquals($this->keymap->key(1), $rowRes->key(true),
            'key() in CsvResource does not return the current keymay value if $fromKeymap parameter is true');
        $this->assertTrue($rowRes->valid(),
            'valid() does not return true if the current entry is valid');
        $rowRes->next(); // 2
        $rowRes->next(); // 3
        $rowRes->next(); // 4
        $rowRes->next(); // 5
        $rowRes->next(); // 6
        $rowRes->next(); // 7 - invalid
        $this->assertFalse($rowRes->valid(),
            'valid() does not return false if the current entry is not valid');
        $rowRes->rewind();
        $this->assertEquals($expected[0], $rowRes->current(),
            'rewind() does not move the pointer back to the first entry');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testUpdateCsvRowAsObject
     * @depends testGetRow
     */
    public function testCloningCsvResource()
    {
        $clone = clone $this->resource;
        $manipulatedRow = $clone->get(1);
        $manipulatedRow->set(0, 'foobar');
        $clone->set(1, $manipulatedRow);
        $this->assertNotSame($clone->getResource(), $this->resource->getResource(),
            '__clone() must clone the containing csv array as well');
        $this->assertNotSame($clone->get(1), $this->resource->get(1),
            '__clone() must clone the containing csv row objects as well');
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testGetRow
     * @depends testSetRowEntryByNumericKey
     */
    public function testCloningCsvRowResource()
    {
        $row = $this->resource->get(0);
        $clone = clone $row;
        $clone->set(1, 'foobar');
        $this->assertNotSame($clone->getResource(), $row->getResource(),
            '__clone() must clone the containing csv row as well');
    }

    /**
     * @depends testGetRow
     */
    public function testEncodeCsvRow()
    {
        $csvHandle = fopen($this->csvFile, 'r+');
        fgets($csvHandle); // keymap line
        $expected = trim(fgets($csvHandle));
        $actual = trim($this->resource->get(0)->encode());
        $this->assertEquals($expected, $actual,
            'encode() does not return the csv row as encoded csv string');
    }

    /**
     * @depends testEncodeCsvRow
     */
    public function testEncodeCsvRowWithAddedKeymapValue()
    {
        $csvHandle = fopen($this->csvFile, 'r+');
        fgets($csvHandle); // keymap line
        $this->resource->getKeymap()->add("comment");
        $expected = trim(fgets($csvHandle)) . ";";
        $actual = trim($this->resource->get(0)->encode());
        $this->assertEquals($expected, $actual,
            'encode() does not return the csv row as encoded csv string');
    }

    /**
     * @depends testEncodeCsvRow
     */
    public function testEncodeCsv()
    {
        $testCsvString = "key;value\n";
        $testCsvString .= "1;12345";

        $testCsv = vfsStream::url("csv/encodingTest.csv");
        file_put_contents($testCsv, $testCsvString);
        $testResource = new CsvResource($testCsv);

        $actual = $testResource->encode();
        $this->assertEquals($testCsvString, $actual,
            'encode() does not return the csv as encoded csv string');
    }

    /**
     * @depends testEncodeCsvRow
     */
    public function testEncodeCsvWithForcedEnclosure()
    {
        $testCsvString = "\"key\";\"value\"\n";
        $testCsvString .= "\"1\";\"12345\"";

        $testCsv = vfsStream::url("csv/encodingTest.csv");
        file_put_contents($testCsv, $testCsvString);
        $testResource = new CsvResource($testCsv);
        $testResource->getFileOptions()->setForceEnclosure(true);

        $actual = $testResource->encode();
        $this->assertEquals($testCsvString, $actual,
            'encode() does not return the csv as encoded csv string');
    }

    /**
     * @depends testGetRow
     * @throws BadMethodCallException
     */
    public function testWriteInCsvRowThrowsException()
    {
        $message = sprintf(CsvRowResource::EXCEPTION_PROHIBITED_METHOD, "write");
        $this->setExpectedException(BadMethodCallException::class, $message);
        $this->resource->get(1)->write();
    }

    /**
     * @depends testFactoryReturnsCsvResourceObject
     * @depends testToArrayMethodInCsvResource
     * @depends testInsertNewCsvRowAsArray
     * @depends testSettingCsvRowEntryUpdatesCsvResource
     */
    public function testWritingCsv()
    {
        $testCsv = vfsStream::url("csv/writingTest.csv");
        file_put_contents($testCsv, '');
        $this->resource->write($testCsv);
        $writtenRes = new CsvResource($testCsv);
        $writtenRes->setReadingKeysFromKeymap(false);
        $actual = $writtenRes->toArray();
        $this->assertEquals($this->expected, $actual,
            'write method does not write csv file');
    }

    /**
     * @depends testEncodeCsv
     * @depends testSetRowEntryByKeymapValue
     */
    public function testWriteCsvToSourceFile()
    {
        $csvFile = vfsStream::url("csv/writingTest.csv");
        file_put_contents($csvFile, $this->resource->encode());

        $row = 1;
        $key = 'language';
        $val = 'English';

        $csvRes = new CsvResource($csvFile);
        $csvRes->get($row)->set($key, $val);
        $csvRes->write();
        $actualCsv = new CsvResource($csvFile);
        $actual = $actualCsv->toArray();
        $expected = $csvRes->toArray();
        $this->assertEquals($expected, $actual,
            'write method does not write to initial csv file if no file path was given as parameter 1');
    }

    /**
     * @depends testWritingCsv
     * @depends testSetRowEntryByUnknownKeymapValue
     */
    public function testWriteCsvWithNewKeymapValues()
    {
        $testCsv = vfsStream::url("csv/writingTest.csv");
        file_put_contents($testCsv, '');

        $rowPos = 0;
        $newKey = 'rating';
        $value = 4.5;
        $rowPos2 = 1;
        $newKey2 = 'comment';
        $value2 = 'Recommended for nerds';

        $this->resource->get($rowPos)->set($newKey, $value);
        $this->resource->get($rowPos2)->set($newKey2, $value2);
        $expected = $this->expected;
        foreach ($expected as $key => &$rowArray) {
            if($key === $rowPos) {
                $rowArray[] = $value;
            } else {
                $rowArray[] = NULL;
            }
            if($key === $rowPos2) {
                $rowArray[] = $value2;
            } else {
                $rowArray[] = NULL;
            }
        }

        $this->resource->write($testCsv);
        $writtenRes = new CsvResource($testCsv);
        $writtenRes->setReadingKeysFromKeymap(false);
        $actual = $writtenRes->toArray();

        $this->assertEquals($expected, $actual,
            'write method does not write csv file properly if new columns were set during runtime');
    }

    public function testSetAndGetReturnResource()
    {
        $returnResource = false;
        $this->resource->setReturnResource($returnResource);
        $this->assertEquals($this->resource->getReturnResource(), $returnResource,
            "unable to get or set returnResource");
    }

    public function testSetAndGetFileEncoding()
    {
        $fileEncoding = "ISO-8859-1";
        $options = $this->resource->getFileOptions();
        $options->setFileEncoding($fileEncoding);
        $this->assertEquals($fileEncoding, $options->getFileEncoding(),
            "unable to get or set fileEncoding");
    }

    public function testSetAndGetObjectEncoding()
    {
        $objectEncoding = "ISO-8859-1";
        $options = $this->resource->getFileOptions();
        $options->setObjectEncoding($objectEncoding);
        $this->assertEquals($objectEncoding, $options->getObjectEncoding(),
            "unable to get or set objectEncoding");
    }

    /**
     * @depends testSetAndGetObjectEncoding
     * @depends testSetAndGetFileEncoding
     */
    public function testObjectEncodingIsInternalByDefault()
    {
        $options = $this->resource->getFileOptions();
        $options->setFileEncoding("ISO-8859-1");
        $this->assertEquals(mb_internal_encoding(), $options->getObjectEncoding(),
            "objectEncoding is not equal to mb_internal_encoding by default");
    }

    public function testSetAndGetEncoding()
    {
        $options = $this->resource->getFileOptions();
        $options->setObjectEncoding("ISO-8859-1");
        $options->setFileEncoding("UTF-8");
        $encoding = array(
            "from" => $options->getObjectEncoding(),
            "to"   => $options->getFileEncoding(),
        );
        $this->assertEquals($encoding, $this->resource->getEncoding(),
            "unable to get or set encoding");
    }

    /**
     * @depends testSetAndGetEncoding
     */
    public function testEncodingIsUpdatedInAllRows()
    {
        $options = $this->resource->getFileOptions();
        $options->setObjectEncoding("ISO-8859-1");
        $options->setFileEncoding("UTF-8");
        $encoding = array(
            "from" => $options->getObjectEncoding(),
            "to"   => $options->getFileEncoding(),
        );

        $rowOptions = $this->resource->get(1)->getFileOptions();
        $rowEncoding = array(
            "from" => $rowOptions->getObjectEncoding(),
            "to"   => $rowOptions->getFileEncoding(),
        );
        $this->assertEquals($encoding, $rowEncoding,
            "encoding is not updated in CsvRowResources when calling setEncoding in CsvResource");
    }

    /**
     * @depends testGetRow
     */
    public function testSetEncodingFromInOneRowUpdatesAllRows()
    {
        $objectEncoding = "ISO-8859-1";
        $this->resource->get(1)->getFileOptions()->setObjectEncoding($objectEncoding);
        $this->assertEquals($objectEncoding, $this->resource->get(2)->getFileOptions()->getObjectEncoding(),
            "setting object encoding in one row does not update the others");
    }

    // encoding file and object

    protected function detectCsvEncoding($file)
    {
        $internal = mb_internal_encoding();
        $fileContent = file_get_contents($file);
        $fileEnc = mb_detect_encoding($fileContent, "UTF-8,ISO-8859-1,Windows-1251,Windows-1252,UTF-16,SJIS");
        if($internal == $fileEnc) {
            $to = $fileEnc == "UTF-8" ? "ISO-8859-1" : "UTF-8";
            $fileContent = mb_convert_encoding($fileContent, $to, $fileEnc);
            file_put_contents($file, $fileContent);
        }
        $encoding = array(
            "file"   => $fileEnc,
            "object" => $internal,
        );
        return $encoding;
    }

    /**
     * @depends testGetRowEntryByKeymapValue
     * @depends testSetKeymapForAllRows
     */
    public function testUmlautKeysAreEncoded()
    {
        $umlautCsvFile = __DIR__ . "/files/umlaut.csv";
        $encoding = $this->detectCsvEncoding($umlautCsvFile);

        $resource = new CsvResource($umlautCsvFile, array(
            "fileOptions" => array(
                "fileEncoding"   => $encoding["file"],
                "objectEncoding" => $encoding["object"],
            ),
        ));
        $handle = fopen($umlautCsvFile, "r");
        $keymap = fgetcsv($handle, 0, ";");
        fclose($handle);
        $encodedKeymap = $keymap;
        foreach ($encodedKeymap as $key => $value) {
            $encodedKeymap[$key] = mb_convert_encoding($value, $encoding["object"], $encoding["file"]);
        }

        $this->assertEquals($encodedKeymap, $resource->getKeymap()->toArray(),
            "The keymap is not encoded to the given charset");
    }

    /**
     * @depends testUmlautKeysAreEncoded
     */
    public function testCsvContentIsEncoded()
    {
        $umlautCsvFile = __DIR__ . "/files/umlaut.csv";
        $encoding = $this->detectCsvEncoding($umlautCsvFile);
        $resource = new CsvResource($umlautCsvFile, array(
            "fileOptions" => array(
                "fileEncoding"   => $encoding["file"],
                "objectEncoding" => $encoding["object"],
            ),
        ));

        $csvData = $this->readCsv($umlautCsvFile);
        foreach ($csvData as $rowKey => $row) {
            foreach ($row as $key => $value) {
                $row[$key] = mb_convert_encoding($value, $encoding["object"], $encoding["file"]);
            }
            $csvData[$rowKey] = $row;
        }

        $this->assertEquals($csvData, $resource->getResource(),
            "The csv contents are not encoded to the given charset");
    }

    /**
     * @depends testUmlautKeysAreEncoded
     * @depends testCsvContentIsEncoded
     */
    public function testWriteFileEncoded()
    {
        $umlautCsvFile = __DIR__ . "/files/umlaut.csv";
        $encoding = $this->detectCsvEncoding($umlautCsvFile);
        $resource = new CsvResource($umlautCsvFile, array(
            "fileOptions" => array(
                "fileEncoding"   => $encoding["file"],
                "objectEncoding" => $encoding["object"],
            ),
        ));

        $newCsvFile = vfsStream::url("csv/new.csv");
        $resource->write($newCsvFile);

        $old = new CsvResource($umlautCsvFile);
        $new = new CsvResource($newCsvFile);
        $new->getFileOptions()->setFilePath($umlautCsvFile);

        $this->assertEquals($old, $new,
            "string returned by encode() does not have the correct charset");
    }

    // test remove

    /**
     * @depends testGetRow
     */
    public function testRemoveRow()
    {
        $row = 1;
        $this->resource->remove($row);
        $this->assertNull($this->resource->get($row),
            "Unable to remove a row from CsvResource");
    }

    /**
     * @depends testGetRow
     * @depends testGetRowEntryByNumericKey
     */
    public function testRemoveRowEntryByInteger()
    {
        $row = 1;
        $key = 1;
        $this->resource->get($row)->remove($key);
        $this->assertNull($this->resource->get($row)->get($key),
            "Unable to remove a row entry by numeric key");
    }

    /**
     * @depends testRemoveRowEntryByInteger
     * @depends testGetRowEntryByKeymapValue
     */
    public function testRemoveRowEntryByKeymap()
    {
        $row = 1;
        $key = "author";
        $this->resource->get($row)->remove($key);
        $this->assertNull($this->resource->get($row)->get($key),
            "Unable to remove a row entry by keymap value");
    }

    public function testCsvResourceWithoutKeymap()
    {
        $csvPath = vfsStream::url("csv/blank.csv");
        fclose(fopen($csvPath, "w"));
        $resource = new CsvResource($csvPath);

        $resource->setRecursive("0/id", 1);
        $resource->setRecursive("0/title", "Life of Brian");
        $resource->write();

        $expectedCsvString = "id;title\n1;\"Life of Brian\"";
        $this->assertEquals($expectedCsvString, file_get_contents($csvPath),
            "Unable to write a csv resource without keymap properly");
    }

}