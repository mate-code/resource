<?php

namespace mate\Resource\Helper\Csv;

/**
 * @package mate\Resource\Helper\Csv
 */
class FileOptions
{
    /**
     * CSV column delimiter
     * @var string
     */
    protected $delimiter = ';';
    /**
     * CSV text enclosure
     * @var string
     */
    protected $enclosure = '"';
    /**
     * CSV line separator
     * @var string
     */
    protected $lineFeed = "\n";
    /**
     * enclose all csv values
     * @var bool
     */
    protected $forceEnclosure = false;
    /**
     * encoding of CSV feed
     * @var string
     */
    protected $fileEncoding;
    /**
     * encoding in resource object
     * @var string
     */
    protected $objectEncoding;
    /**
     * file path to CSV feed
     * @var string
     */
    protected $filePath;

    /**
     * @param array $options
     */
    public function exchangeArray(array $options)
    {
        if(isset($options["delimiter"])) {
            $this->setDelimiter($options["delimiter"]);
        }
        if(isset($options["enclosure"])) {
            $this->setEnclosure($options["enclosure"]);
        }
        if(isset($options["lineFeed"])) {
            $this->setLineFeed($options["lineFeed"]);
        }
        if(isset($options["forceEnclosure"])) {
            $this->setForceEnclosure($options["forceEnclosure"]);
        }
        if(isset($options["fileEncoding"])) {
            $this->setFileEncoding($options["fileEncoding"]);
        }
        if(isset($options["objectEncoding"])) {
            $this->setObjectEncoding($options["objectEncoding"]);
        }
        if(isset($options["filePath"])) {
            $this->setFilePath($options["filePath"]);
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            "delimiter"      => $this->getDelimiter(),
            "enclosure"      => $this->getEnclosure(),
            "lineFeed"       => $this->getLineFeed(),
            "forceEnclosure" => $this->getForceEnclosure(),
            "fileEncoding"   => $this->getFileEncoding(),
            "objectEncoding" => $this->getObjectEncoding(),
            "filePath"       => $this->getFilePath(),
        );
    }

    /**
     * CSV column delimiter
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * CSV column delimiter
     * @param string $delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * CSV text enclosure
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * CSV text enclosure
     * @param string $enclosure
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
    }

    /**
     * @return boolean
     */
    public function getForceEnclosure()
    {
        return $this->forceEnclosure;
    }

    /**
     * @param boolean $forceEnclosure
     */
    public function setForceEnclosure($forceEnclosure)
    {
        $this->forceEnclosure = (bool)$forceEnclosure;
    }

    /**
     * CSV line separator
     * @return string
     */
    public function getLineFeed()
    {
        return $this->lineFeed;
    }

    /**
     * CSV line separator
     * @param string $lineFeed
     */
    public function setLineFeed($lineFeed)
    {
        $this->lineFeed = $lineFeed;
    }

    /**
     * encoding of CSV feed
     * @return string
     */
    public function getFileEncoding()
    {
        return $this->fileEncoding;
    }

    /**
     * encoding of CSV feed
     * @param string $fileEncoding
     */
    public function setFileEncoding($fileEncoding)
    {
        if(!$this->getObjectEncoding()) {
            $this->setObjectEncoding(mb_internal_encoding());
        }
        $this->fileEncoding = $fileEncoding;
    }

    /**
     * encoding in resource object
     * @return string
     */
    public function getObjectEncoding()
    {
        return $this->objectEncoding;
    }

    /**
     * encoding in resource object
     * @param string $objectEncoding
     */
    public function setObjectEncoding($objectEncoding)
    {
        $this->objectEncoding = $objectEncoding;
    }

    /**
     * file path to CSV feed
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * file path to CSV feed
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }
}