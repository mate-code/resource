<?php

namespace mate\Resource;

use mate\Resource\Exception\InvalidArgumentException;
use mate\Resource\Helper\Csv\FileOptions;
use mate\Resource\Helper\Csv\Keymap;
use mate\Resource\Helper\Path\Path;

/**
 * Class CsvResourceAbstract
 * @package mate\Resource
 * @author Marius Teller <marius.teller@modotex.com>
 * @author Stefan Konarski <stefan.konarski@modotex.com>
 */
abstract class CsvResourceAbstract implements ResourceInterface
{

    /**
     * @var bool
     */
    protected $readingKeysFromKeymap = true;

    /**
     * @var FileOptions
     */
    protected $fileOptions;

    /**
     * @var Keymap
     */
    protected $keymap;

    /**
     * @var array
     */
    protected $resource = array();

    /**
     * @var string
     */
    protected $separator = '/';

    /**
     * @param $arrayOrStringPath
     * @return array
     * @throws InvalidArgumentException
     */
    protected function resolvePath($arrayOrStringPath)
    {
        if(is_array($arrayOrStringPath) || $arrayOrStringPath instanceof Path) {
            $path = array();
            // loop through the path to make sure the keys are iterative and without skipped numbers
            foreach ($arrayOrStringPath as $pathKey) {
                $path[] = $pathKey;
            }
            return $path;
        } elseif(is_scalar($arrayOrStringPath)) {
            return explode($this->separator, (string)$arrayOrStringPath);
        } else {
            throw new InvalidArgumentException(ResourceAbstract::INVALID_PATH);
        }
    }

    /**
     * encode file data to objects encoding
     * @param array $data
     * @param bool $toFile
     * @return array
     */
    protected function encodeCharset(array $data, $toFile = false)
    {
        $fileOptions = $this->getFileOptions();

        if($toFile) {
            $to = $fileOptions->getFileEncoding();
            $from = $fileOptions->getObjectEncoding();
        } else {
            $from = $fileOptions->getFileEncoding();
            $to = $fileOptions->getObjectEncoding();
        }

        if(!$from || !$to) {
            return $data;
        }
        foreach ($data as $key => $value) {
            $data[$key] = mb_convert_encoding($value, $to, $from);
        }
        return $data;
    }

    /**
     * @return FileOptions
     */
    public function getFileOptions()
    {
        return $this->fileOptions;
    }

    /**
     * @param FileOptions|array $fileOptions
     */
    public function setFileOptions($fileOptions)
    {
        if($fileOptions instanceof FileOptions) {
            $fileOptions = $fileOptions->toArray();
        }
        if(is_array($fileOptions)) {
            $existingFileOptions = $this->getFileOptions();
            $existingFileOptions->exchangeArray($fileOptions);
        }
    }

    /**
     * @return Keymap
     */
    public function getKeymap()
    {
        return $this->keymap;
    }

    /**
     * @deprecated use FileOptions object instead
     * @return array
     */
    public function getEncoding()
    {
        $fileOptions = $this->getFileOptions();
        return array(
            "to"   => $fileOptions->getFileEncoding(),
            "from" => $fileOptions->getObjectEncoding(),
        );
    }

    /**
     * @deprecated use FileOptions object instead
     * @param array|ArrayResource $encoding
     */
    public function setEncoding($encoding)
    {
        if($encoding instanceof ArrayResource) {
            $encoding = $encoding->toArray();
        }
        $fileOptions = $this->getFileOptions();
        if(isset($encoding["from"])) {
            $fileOptions->setObjectEncoding($encoding["from"]);
        }
        if(isset($encoding["to"])) {
            $fileOptions->setFileEncoding($encoding["to"]);
        }
    }

    /**
     * @deprecated use getFileOptions()->getFileEncoding()
     * @return string|null
     */
    public function getEncodingTo()
    {
        return $this->getFileOptions()->getFileEncoding();
    }

    /**
     * @deprecated use getFileOptions()->getObjectEncoding()
     * @return string|null
     */
    public function getEncodingFrom()
    {
        return $this->getFileOptions()->getObjectEncoding();
    }

    /**
     * @deprecated use getFileOptions()->setFileEncoding()
     * @param string $charset
     */
    public function setEncodingTo($charset)
    {
        $this->getFileOptions()->setFileEncoding($charset);
    }

    /**
     * @deprecated use getFileOptions()->setObjectEncoding()
     * @param string $charset
     */
    public function setEncodingFrom($charset)
    {
        $this->getFileOptions()->setObjectEncoding($charset);
    }

    /**
     * get separator for paths to get or set recursive
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @deprecated use getFileSettings()->getDelimiter()
     * @return string
     */
    public function getCsvDelimiter()
    {
        return $this->getFileOptions()->getDelimiter();
    }

    /**
     * set delimiter for encoding to csv string
     * @deprecated use getFileOptions()->setDelimiter()
     * @param string $csvDelimiter
     */
    public function setCsvDelimiter($csvDelimiter)
    {
        $this->getFileOptions()->setDelimiter($csvDelimiter);
    }

    /**
     * @deprecated use getFileOptions()->getEnclosure()
     * @return string
     */
    public function getCsvEnclosure()
    {
        return $this->getFileOptions()->getEnclosure();
    }

    /**
     * set enclosure for encoding to csv string
     * @deprecated use getFileOptions()->setEnclosure()
     * @param string $csvEnclosure
     */
    public function setCsvEnclosure($csvEnclosure)
    {
        $this->getFileOptions()->setEnclosure($csvEnclosure);
    }
}