<?php


namespace MateTest\Resource\Helper\Csv;
use mate\Resource\Exception\RuntimeException;
use mate\Resource\Helper\Csv\Keymap;


/**
 * @package MateTest\Resource\Helper
 */
class KeymapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $keymapArray = array(
        "id", "name",
    );
    /**
     * @var Keymap
     */
    protected $keymap;

    public function setUp()
    {
        $this->keymap = new Keymap($this->keymapArray);
    }

    public function testThrowExceptionIfKeymapIsInversed()
    {
        $message = Keymap::EXCEPTION_INVALID_KEYMAP;
        $this->setExpectedException(RuntimeException::class, $message);
        $inversed = array_flip($this->keymapArray);
        new Keymap($inversed);
    }

    public function testToArray()
    {
        $this->assertEquals($this->keymapArray, $this->keymap->toArray(),
            "toArray() does not return the given keymap");
    }

    /**
     * @depends testToArray
     */
    public function testNormalizeIndexedArray()
    {
        $keymapArray = $this->keymapArray;
        $keymapArray[2] = "name";
        unset($keymapArray[1]);
        $keymap = new Keymap($keymapArray);
        $this->assertEquals($this->keymapArray, $keymap->toArray(),
            "__construct() does not correct an irregular indexed array");
    }

    public function testPos()
    {
        $this->assertEquals(1, $this->keymap->pos("name"),
            "pos() does not return the position of the given key");
    }

    public function testKey()
    {
        $this->assertEquals("name", $this->keymap->key(1),
            "key() does not return the key of a given position");
    }

    /**
     * @depends testToArray
     */
    public function testAdd()
    {
        $newKey = "firstName";
        $newKeymap = $this->keymapArray;
        $newKeymap[] = $newKey;
        $this->keymap->add($newKey);
        $this->assertEquals($newKeymap, $this->keymap->toArray(),
            "add() does not add a new key to the map");
    }

    /**
     * @depends testPos
     * @depends testAdd
     */
    public function testPosWithNewKey()
    {
        $newKey = "firstName";
        $this->keymap->add($newKey);
        $this->assertEquals(2, $this->keymap->pos($newKey),
            "pos() does not return the correct position of an added key");
    }

    /**
     * @depends testKey
     * @depends testAdd
     */
    public function testKeyWithNewPos()
    {
        $newKey = "firstName";
        $this->keymap->add($newKey);
        $this->assertEquals($newKey, $this->keymap->key(2),
            "key() does not return the correct key of an added key");
    }

}
