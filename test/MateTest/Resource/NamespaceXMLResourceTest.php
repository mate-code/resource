<?php

namespace MateTest\Resource;

include_once __DIR__ . '/ResourceTestAbstract.php';

use mate\Resource\ResourceFactory;

class NamespaceXMLResourceTest extends ResourceTestAbstract
{
    /**
     * @var \mate\Resource\XMLResource $resource
     */
    protected $resource;

    protected $resourceClass = 'mate\Resource\XMLResource';

    /**
     * @ToDo Make namespaces work without removing them
     */
    public function setUp()
    {
        $xmlFile = __DIR__.'/files/testNamespaces.xml';
        $this->resource = ResourceFactory::create($xmlFile, ["clearNamespaces" => true]);
    }

}