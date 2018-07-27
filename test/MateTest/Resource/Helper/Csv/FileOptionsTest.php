<?php

namespace MateTest\Resource\Helper\Csv;

use mate\Resource\Helper\Csv\FileOptions;

/**
 * @package MateTest\Resource\Helper\Csv
 */
class FileOptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileOptions
     */
    protected $fileOptions;

    public function setUp()
    {
        $this->fileOptions = new FileOptions();
    }

    public function provideTestSettingProperties()
    {
        return array(
            ["delimiter", ","],
            ["enclosure", "'"],
            ["lineFeed", "\r\n"],
            ["forceEnclosure", true],
            ["fileEncoding", "windows-1252"],
            ["objectEncoding", "UTF-8"],
            ["filePath", "feed/file.csv"],
        );
    }

    /**
     * @dataProvider provideTestSettingProperties
     * @param string $property
     * @param string $value
     */
    public function testSettingProperties($property, $value)
    {
        $setter = "set" . ucfirst($property);
        $getter = "get" . ucfirst($property);

        if(method_exists($this->fileOptions, $setter)) {
            $this->fileOptions->$setter($value);
        }
        if(method_exists($this->fileOptions, $getter)) {
            $actualValue = $this->fileOptions->$getter();
        } else {
            $actualValue = null;
        }
        $this->assertSame($value, $actualValue,
            "unable to get or set file setting $property");
    }

    public function testExchangeArrayAndToArray()
    {
        $testData = $this->provideTestSettingProperties();
        $exchangeArray = array();
        foreach ($testData as $data) {
            $property = $data[0];
            $value = $data[1];
            $exchangeArray[$property] = $value;
        }
        $this->fileOptions->exchangeArray($exchangeArray);
        $this->assertEquals($exchangeArray, $this->fileOptions->toArray(),
            "exchangeArray() does not set the given data or toArray() does not return it properly");
    }

    /**
     * @depends testSettingProperties
     */
    public function testSetFileEncodingSetsObjectEncoding()
    {
        $internalEncoding = mb_internal_encoding();
        $fileEncoding = "windows-1252";
        $this->fileOptions->setFileEncoding($fileEncoding);
        $objectEncoding = $this->fileOptions->getObjectEncoding();
        $this->assertEquals($internalEncoding, $objectEncoding,
            "setFileEncoding() does not set the object encoding to the internal encoding if it's not set");
    }

}
