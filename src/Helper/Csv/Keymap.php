<?php

namespace mate\Resource\Helper\Csv;

use mate\Resource\Exception\RuntimeException;

/**
 * @package mate\Resource\Helper\Csv
 */
class Keymap
{
    const EXCEPTION_INVALID_KEYMAP = "Keys of keymap must be numeric positions of the CSV column keys";
    /**
     * @var array
     */
    protected $keymap;
    /**
     * @var array
     */
    protected $inversed;

    /**
     * Keymap constructor.
     * @param array $keymap
     * @throws RuntimeException
     */
    public function __construct(array $keymap)
    {
        if(!empty($keymap) && !is_numeric(key($keymap))) {
            throw new RuntimeException("Keys of keymap must be numeric positions of the CSV column keys");
        }
        $this->keymap = array_values($keymap);
        $this->inversed = array_flip($keymap);
    }

    /**
     * returns the position of the given key or false
     *
     * @param string $key
     * @return int|bool
     */
    public function pos($key)
    {
        return isset($this->inversed[$key]) ? $this->inversed[$key] : false;
    }

    /**
     * returns the key of the given position or false
     *
     * @param int $pos
     * @return string|bool
     */
    public function key($pos)
    {
        return isset($this->keymap[$pos]) ? $this->keymap[$pos] : false;
    }

    /**
     * adds a key to the keymap and returns its position
     *
     * @param string $key
     * @return int
     */
    public function add($key)
    {
        $pos = count($this->keymap);
        $this->keymap[$pos] = $key;
        $this->inversed[$key] = $pos;
        return $pos;
    }

    /**
     * return the keymap as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->keymap;
    }

}