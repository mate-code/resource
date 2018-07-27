<?php

namespace mate\Resource;

use mate\Resource\Exception\InvalidArgumentException;
use mate\Resource\Exception\RuntimeException;

/**
 * <p>Possible resources are: SimpleXMLIterator, SimpleXMLIterator, XML String, path to XML file</p>
 * <p>Pay special attention to the possibillity of multiple child nodes with
 * the same name if you route through the xml via get() or getRecursive()</p>
 * <p>Possible options:
 * - start : the starting point to read the xml from
 * - reload (boolean) : If false, does not reload the XML. Only if resource is SimpleXMLIterator.
 * - defaultChildren (array) : array of tag names to create if key is iterative. Parent names
 * - clearNamespaces (boolean) : If false, does not remove all namespaces from xml
 * are keys, child names are value</p>
 * @param mixed $resource
 * @param array $options
 */
class XMLResource extends ResourceAbstract implements WritableResourceInterface
{
    /**
     * setting for clearing all namespaces in xml (for stability reasons)
     * @var bool
     */
    protected $clearNamespaces = false;
    /**
     * @var array
     */
    protected $defaultChildren = array(
        'default' => 'child',
    );
    /**
     * keeps track of the current position on iteration
     * @var int
     */
    protected $pos = 0;
    /**
     * file path the xml was written from/will be written to
     * @var string
     */
    protected $filePath;

    const ERROR_TOO_MANY_DEFAULT_CHILDREN = 'Too many default children found , make sure the children of the node have only one name or define a default child';

    const ERROR_SETTING_COMPLEX_VALUE = 'Parameter passed to set() must be scalar, tried to set %s in key %s';

    /**
     * <p>Possible resources are: SimpleXMLIterator, XML String, path to XML file</p>
     * <p>Pay special attention to the possibillity of multiple child nodes with
     * the same name if you route through the xml via get() or getRecursive()</p>
     * <p>Possible options:
     * - start : the starting point to read the xml from
     * - reload (boolean) : If false, does not reload the XML. Only if resource is SimpleXMLIterator.
     * - defaultChildren (array) : array of tag names to create if key is iterative. Parent names
     * - clearNamespaces (boolean) : If false, does not remove all namespaces from xml
     * are keys, child names are value</p>
     * @param mixed $resource
     * @param array $options
     */
    public function __construct(&$resource, $options = array())
    {
        if(isset($options['clearNamespaces'])) {
            $clearNamespaces = (bool) $options['clearNamespaces'];
            $this->setClearNamespaces($clearNamespaces);
        }
        if(isset($options['reload']) && $options['reload'] === false
            && $resource instanceof \SimpleXMLIterator) {
            $this->setResource($resource);
            $xmlResource = $this->getResource();
        } else {
            $xmlResource = $this->readXml($resource);
        }
        if(isset($options['defaultChildren']) && is_array($options['defaultChildren'])) {
            $this->setDefaultChildren($options['defaultChildren']);
        }
        parent::__construct($xmlResource, $options);
    }

    /**
     * <p>gets xml node $key</p>
     * <p>If key is int, return the n'th child (e.g. key=3 will return the third child)</p>
     * <p>If selected node has no children, return the containing string<p>
     * <p>If element has more than one node with name $key, return an iterative
     * array that contains all of them</p>
     * <p>If there is only one selected node with child elements, return this node</p>
     * @param string $key
     * @param mixed $default
     * @return \mate\Resource\ResourceAbstract if $returnResource = true
     */
    public function get($key, $default = NULL)
    {
        $return = NULL;
        if(!is_numeric($key)) {
            $return = $this->getByXPath($key);
        }
        if(is_numeric($key)) {
            // if key is numeric try to get the n'th child of resource or return default
            $childNode = $this->getDefaultChild();
            $pos = (int) $key;
            $return = $this->getByXPath($childNode, $pos);
        }
        $return = isset($return) ? $this->returnValue($return) : $default;
        return $return;
    }

    /**
     * get node by xpath while auto-routing through every defined namespace
     * @param string $key child node name
     * @param int $pos number of child
     * @return null|\SimpleXMLElement
     */
    protected function getByXPath($key, $pos = 0) {
        $xpathArray = $this->getResource()->xpath('*[local-name()="' . trim($key) . '"]');
        if(isset($xpathArray[$pos])) {
            return $xpathArray[$pos];
        }
        return null;
    }

    /**
     * <p>Sets value to the selected node with name $key</p>
     * @param string $key child node name
     * @param mixed $value
     * @throws InvalidArgumentException
     * @return void
     */
    public function set($key, $value)
    {
        // resolve iterative key
        if(is_numeric($key) === true) {
            $pos = $key;
            $childNode = $this->getDefaultChild();
        } else {
            $childNode = $key;
            $pos = 0;
        }
        // set complex value
        if($value instanceof XMLResource) {
            $value = $value->getResource();
        }
        if($value instanceof \SimpleXMLElement) {
            $newChild = $this->getResource()->addChild($childNode);
            $this->setByXmlResource($value, $newChild);
            return;
        }
        if(!is_scalar($value) && $value !== NULL) {
            throw new InvalidArgumentException(sprintf(self::ERROR_SETTING_COMPLEX_VALUE, gettype($value), $key));
        }
        // set simple value
        $this->setByXPath($childNode, $value, $pos);
    }

    /**
     * @param \SimpleXMLElement $XMLIterator
     * @param \SimpleXMLIterator $target
     */
    protected function setByXmlResource(\SimpleXMLElement $XMLIterator, $target = NULL)
    {
        $target = $target === NULL ? $this->getResource() : $target;
        foreach ($XMLIterator as $key => $value) {
            if('' !== trim((string)$value)) {
                $target->$key = (string)$value;
                $attributes = $XMLIterator->attributes();
                foreach ($attributes as $attrName => $attrValue) {
                    $target[$attrName] = (string) $attrValue;
                }
            } else {
                $child = $target->addChild($key);
                $this->setByXmlResource($value, $child);
            }
        }
    }

    /**
     * set variable with xpath
     * @param string $key child node name
     * @param mixed $value
     * @param int $pos number of child
     */
    protected function setByXPath($key, $value, $pos = 0) {
        $isAttribute = strpos($key, "@") === 0;
        $select = $isAttribute ? $this->getResource() : $this->getByXPath($key, $pos);
        if($select === NULL) {
            $this->setEmptyNode($key);
            $select = $this->getByXPath($key);
        } else {
            $pos = 0;
        }
        if($isAttribute === false) {
            $select->{$pos} = $value;
        } else {
            $attr = substr($key, 1, strlen($key)-1);
            $select[$attr] = $value;
        }
    }

    /**
     * get child node name of current resource
     * @param string $parent if set, looks for this parent name instead current node name
     * @return string
     */
    public function getDefaultChild($parent = NULL){
        if($parent === NULL) {
            $parent = $this->getResource()->getName();
        }
        $defaults = $this->getDefaultChildren();
        if(isset($defaults[$parent])) {
            return $defaults[$parent];
        }
        $findDefaultChild = $this->findDefaultChild($parent);
        if($findDefaultChild) {
            $this->addDefaultChild($parent, $findDefaultChild);
            return $findDefaultChild;
        }
        return $defaults['default'];
    }

    public function findDefaultChild($parent = NULL)
    {
        $defaultChild = NULL;
        if($parent == $this->getResource()->getName() || $parent === NULL) {
            $children = $this->getResource()->children();
        } else {
            $findNode = $this->find($parent);
            if(isset($findNode[0]) && $findNode[0] instanceof XMLResource) {
                /** @var XMLResource $found */
                $found = $findNode[0];
                $children = $found->getResource()->children();
            }
        }
        if(isset($children)) {
            $childCounts = array();
            /** @var \SimpleXMLIterator $node */
            foreach ($children as $node) {
                $nodeName = $node->getName();
                if(!isset($childCounts[$nodeName])) {
                    $childCounts[$nodeName] = 0;
                }
                $childCounts[$nodeName]++;
            }
            $multipleChildren = array_filter($childCounts, function($var) { return $var > 1; });
            if(count($multipleChildren) > 1) {
                throw new RuntimeException(self::ERROR_TOO_MANY_DEFAULT_CHILDREN);
            } elseif(count($multipleChildren) == 1) {
                $defaultChild = key($multipleChildren);
            }
        }
        return $defaultChild;
    }

    /**
     * sets options[reload] = false, so xml will not be re-rendered to
     * make references work
     * @param mixed $value
     * @param array $options
     * @return mixed
     */
    protected function returnValue(&$value, array $options = array())
    {
        $options['reload'] = false;
        $options['defaultChildren'] = $this->getDefaultChildren();
        $value = $this->filterDataTypes($value);
        return parent::returnValue($value, $options);
    }

    /**
     * because we will always get strings by SimpleXML we need to set a filter
     * to get other data types
     * @param $return
     * @return float|int|mixed|string
     */
    protected function filterDataTypes($return)
    {
        if(is_string($return) && (trim($return) === 'false' || trim($return) === 'true')) {
            return filter_var($return, FILTER_VALIDATE_BOOLEAN);
        }
        if(!is_bool($return) && filter_var($return, FILTER_VALIDATE_INT)) {
            return (int)$return;
        }
        if(!is_bool($return) && filter_var($return, FILTER_VALIDATE_FLOAT) && substr($return, 0, 1) != "0") {
            return (float)$return;
        }
        if($return instanceof \SimpleXMLIterator && '' !== trim((string)$return)) {
            $nodeValue = (string)$return;
            return $this->filterDataTypes($nodeValue);
        }
        return $return;
    }

    /**
     * set a new empty node to resource
     * @param string $property
     * @return mixed
     */
    protected function setEmptyNode($property)
    {
        $namespace = NULL;
        $currentNamespace = $this->getResource()->getNamespaces();
        if(!empty($currentNamespace)) {
            reset($currentNamespace);
            $namespace = key($currentNamespace);
        }
        if(is_numeric($property)) {
            $childName = $this->getDefaultChild();
            $return = $this->getResource()->addChild($childName, $namespace);
        } else {
            $return = $this->getResource()->addChild($property, $namespace);
        }
        return $this->returnValue($return);
    }

    /**
     * checks if node with name $key exits
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        $result = $this->get($key);
        return $result === NULL ? false : true;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        unset($this->resource->$key);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->getResource()->count();
    }

    /**
     * return XML as array
     * @return array
     */
    public function toArray()
    {
        $this->setReturnResource(true);
        $array = $this->simpleXMLToArray($this->getResource());
        return $array;
    }

    public function encode()
    {
        return trim($this->getResource()->asXML());
    }

    public function write($file = NULL)
    {
        if($file === NULL) {
            $file = $this->filePath;
        }
        file_put_contents($file, $this->encode());
    }

    /**
     * @param $xsdFile
     * @return true|\LibXMLError[]
     */
    public function schemaValidate($xsdFile)
    {
        libxml_use_internal_errors(true);
        $xmlDom = new \DOMDocument();
        $xmlDom->loadXML($this->encode());
        $isValid = $xmlDom->schemaValidate($xsdFile);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if($isValid === true) {
            return true;
        } else {
            return $errors;
        }
    }

    /**
     * @param \SimpleXMLIterator $simpleXml
     * @param null $parent
     * @return array
     */
    protected function simpleXMLToArray(\SimpleXMLIterator $simpleXml, $parent = NULL)
    {
        $array = array();
        $i = 0;
        foreach ($simpleXml as $key => $node) {
            if($key == $this->getDefaultChild($parent)) {
                $array[$i] = array();
                /** @var \SimpleXMLIterator $arrNode */
                foreach ($node as $arrKey => $arrNode) {
                    $array[$i][$arrKey] = $this->getValueForToArrayMethod($arrKey, $arrNode);
                }
                $i++;
            } else {
                $array[$key] = $this->getValueForToArrayMethod($key, $node);
            }
        }
        return $array;
    }

    /**
     * @param $arrKey
     * @param $arrNode
     * @return array|float|int|mixed|string
     */
    protected function getValueForToArrayMethod($arrKey, $arrNode)
    {
        if('' !== trim((string)$arrNode)) {
            $value = $this->filterDataTypes($arrNode);
        } else {
            $value = $this->simpleXMLToArray($arrNode, $arrKey);
            if(is_array($value) && empty($value)) {
                $value = null;
            }
        }
        return $value;
    }

    /**
     * <p>reads xml and clears all namespaces to route through it withour any problems</p>
     * <p>Possible resources are: SimpleXMLIterator, XML String, path to XML file</p>
     * @param mixed $resource
     * @return \SimpleXMLIterator
     */
    protected function readXml($resource)
    {
        if($resource instanceof \SimpleXMLIterator) {
            $xmlString = $resource->asXML();
        } elseif(is_file($resource)) {
            $this->setFilePath($resource);
            $xmlString = file_get_contents($resource);
        } else {
            $xmlString = $resource;
        }
        if($this->getClearNamespaces() === true) {
            $xmlString = $this->clearNamespaces($xmlString);
            $xml = new \SimpleXMLIterator($xmlString);
        } else {
            $xml = new \SimpleXMLIterator($xmlString);
            $namespaces = $xml->getNamespaces(true);
            foreach ($namespaces as $prefix => $ns) {
                $xml->registerXPathNamespace($prefix, $ns);
            }
        }
        return $xml;
    }

    /**
     * clears all elements of given xml strings
     * @param string $xmlString
     * @return string
     */
    protected function clearNamespaces($xmlString)
    {
        $xmlString = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xmlString);
        $xmlString = preg_replace("/[a-zA-Z0-9]+:([a-zA-Z0-9]+[=>|\s|\/|\"])/", '$1', $xmlString);
        $xmlString = preg_replace('/\s[a-zA-Z0-9]+:([a-zA-Z0-9]+)=/', ' $1=', $xmlString);
        return $xmlString;
    }

    /**
     * rewind - see PHPs \Iterator
     */
    public function rewind()
    {
        $this->pos = 0;
        $this->getResource()->rewind();
    }

    /**
     * current - see PHPs \Iterator
     */
    public function current()
    {
        $var = $this->getResource()->current();
        return $this->returnValue($var);
    }

    /**
     * key - see PHPs \Iterator
     */
    public function key()
    {
        $var = $this->getResource()->key();
        if(in_array($var, $this->defaultChildren)) {
            return $this->pos;
        }
        return $var;
    }

    /**
     * next - see PHPs \Iterator
     */
    public function next()
    {
        $this->pos++;
        $this->getResource()->next();
    }

    /**
     * valid - see PHPs \Iterator
     */
    public function valid()
    {
        return $this->getResource()->valid();
    }

    /**
     * @return \SimpleXMLIterator
     */
    public function &getResource(){
        return parent::getResource();
    }

    /**
     * @return array
     */
    public function getDefaultChildren()
    {
        return $this->defaultChildren;
    }

    /**
     * @param array $defaultChildren
     */
    protected function setDefaultChildren($defaultChildren)
    {
        if(!isset($defaultChildren['default'])) {
            $defaultChildren['default'] = 'child';
        }
        $this->defaultChildren = $defaultChildren;
    }

    public function addDefaultChild($parentName, $childName)
    {
        $this->defaultChildren[$parentName] = $childName;
    }

    /**
     * @return boolean
     */
    public function getClearNamespaces()
    {
        return $this->clearNamespaces;
    }

    /**
     * @param boolean $clearNamespaces
     */
    public function setClearNamespaces($clearNamespaces)
    {
        $this->clearNamespaces = $clearNamespaces;
    }

    /**
     * file path the xml was written from/will be written to
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * file path the xml was written from/will be written to
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

}