<?php

namespace PHPPdf\Test\Core;

use PHPPdf\Core\AttributeBag;

class AttributeBagTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $bag;

    public function setUp()
    {
        $this->bag = new AttributeBag();
    }

    /**
     * @test
     */
    public function addingAttributes()
    {
        $this->assertEmpty(count($this->bag));

        $this->bag->add('name', 'value');
        $this->assertEquals(1, count($this->bag));
        $this->assertEquals('value', $this->bag->get('name'));

        $this->bag->add('name', 'value');
        $this->assertEquals(1, count($this->bag));

        $this->bag->add('name1', 'value1');
        $this->assertEquals(2, count($this->bag));

        $this->assertEquals(array('name' => 'value', 'name1' => 'value1'), $this->bag->getAll());
    }

    /**
     * @test
     */
    public function gettingNotExistsAttribute()
    {
        $this->assertNull($this->bag->get('someAttribute'));
    }

    /**
     * @test
     */
    public function mergeBags()
    {
        $bags = array();
        $expected = array();

        for($i=0; $i<10; $i++)
        {
            $bag = new AttributeBag();
            $attributes = array(
                'someName'.$i => $i,
                'anotherSomeName'.$i => $i,
            );

            foreach($attributes as $name => $value)
            {
                $bag->add($name, $value);
            }
            $bags[] = $bag;
            $expected = array_merge($attributes, $expected);
        }

        $bag = AttributeBag::merge($bags);

        $this->assertEquals($expected, $bag->getAll());
    }

    /**
     * @test
     */
    public function unserializeBagsSerializationFormIsCopyOfThisBag()
    {
        $this->bag->add('someAttribute1', 'someValue1');
        $this->bag->add('someAttribute2', 'someValue2');

        $unserializedBag = unserialize(serialize($this->bag));

        $this->assertEquals($this->bag->getAll(), $unserializedBag->getAll());
    }
    
    /**
     * @test
     */
    public function mergeArrayAttributes()
    {
        $originalValue = array('key1' => 'value1', 'key2' => 'value2');
        $newValue = array('key2' => 'value2a', 'key3' => 'value3');
        $name = 'array';
        
        $this->bag->add($name, $originalValue);
        
        $this->assertEquals($originalValue, $this->bag->get($name));

        $this->bag->add($name, $newValue);
        
        $expectedValue = array_merge($originalValue, $newValue);
        $this->assertEquals($expectedValue, $this->bag->get($name));
    }
}