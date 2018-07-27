<?php

namespace MateTest\Resource\Asset;

/**
 * Class ObjectWithAutomagicGetterAndSetter
 * @package MateTest\Resource\Asset
 * @author Marius Teller <marius.teller@modotex.com>
 */
class ObjectWithAutomagicGetterAndSetter
{

    public $property;

    public function get($property)
    {
        return $this->$property;
    }

    public function set($property, $value)
    {
        $this->$property = $value;
    }

}