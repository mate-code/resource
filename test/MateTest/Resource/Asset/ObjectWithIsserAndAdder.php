<?php

namespace MateTest\Resource\Asset;

/**
 * Class ObjectWithIsser
 * @package MateTest\Resource\Asset
 * @author Marius Teller <marius.teller@modotex.com>
 */
class ObjectWithIsserAndAdder
{
    /**
     * @var bool
     */
    protected $check = false;
    /**
     * @var array
     */
    protected $nodes = array();

    /**
     * @return boolean
     */
    public function isCheck()
    {
        return $this->check;
    }

    /**
     * @param boolean $check
     */
    public function setCheck($check)
    {
        $this->check = $check;
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @param mixed $node
     */
    public function addNode($node)
    {
        $this->nodes[] = $node;
    }

}