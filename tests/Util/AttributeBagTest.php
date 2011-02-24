<?php

use PHPPdf\Util\AttributeBag;

class AttributeBagTest extends PHPUnit_Framework_TestCase
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
}