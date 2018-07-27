<?php

namespace mate\Resource;

/**
 * Interface WritableResourceInterface
 * @package mate\Resource
 */
interface WritableResourceInterface extends ResourceInterface
{

    /**
     * encodes the resource
     *
     * @return mixed
     */
    public function encode();

    /**
     * write resource to file
     *
     * @param string $file
     * @return void
     */
    public function write($file = null);

    /**
     * set file path in which the resource is written or should be written
     *
     * @param string $filename
     * @return void
     */
    public function setFilePath($filename);

    /**
     * set file path in which the resource is written or should be written
     *
     * @return string
     */
    public function getFilePath();

}