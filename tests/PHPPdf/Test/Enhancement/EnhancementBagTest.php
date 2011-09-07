<?php

namespace PHPPdf\Test\Enhancement;

use PHPPdf\Enhancement\EnhancementBag;

class EnhancementBagTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $bag;

    public function setUp()
    {
        $this->bag = new EnhancementBag();
    }

    /**
     * @test
     */
    public function mergingParametersAfterReAdding()
    {
        $parameters1 = array('someParameter1' => 'someValue1', 'someParameter2' => 'someValue2');
        $this->bag->add('name', $parameters1);
        $this->assertEquals($parameters1, $this->bag->get('name'));

        $parameters2 = array('someParameter1' => 'anotherValue1', 'someParameter3' => 'someValue3');
        $this->bag->add('name', $parameters2);

        $this->assertEquals(array_merge($parameters1, $parameters2), $this->bag->get('name'));
    }

    public function merging()
    {
        $bags = array(
            $this->createBag(array(
                'name1' => array(
                    'someParameter1' => 'someValue1',
                    'someParameter2' => 'someValue2',
                ),
                'name2' => array(
                    'someParameter1' => 'someValue1',
                ),
            )),
            $this->createBag(array(
                'name1' => array(
                    'someParameter3' => 'someValue3',
                    'someParameter2' => 'anotherValue2',
                ),
                'name3' => array(
                    'someParameter3' => 'someValue3',
                ),
            )),
            $this->createBag(array(
                'name4' => array(
                    'someParameter1' => 'someValue1'
                ),
            ))
        );

        $bag = EnhancementBag::merge($bags);

        $this->assertEquals(array(
            'name1' => array(
                'someParameter1' => 'someValue1',
                'someParameter2' => 'anotherValue2',
                'someParameter3' => 'someValue3',
            ),
            'name2' => array(
                'someParameter1' => 'someValue1',
            ),
            'name3' => array(
                'someParameter3' => 'someValue3',
            ),
            'name4' => array(
                'someParameter1' => 'someValue1'
            ),
        ), $bag->getAll());
    }

    private function createBag($parameters)
    {
        $bag = new EnhancementBag();
        foreach($parameters as $name => $value)
        {
            $bag->add($name, $value);
        }

        return $bag;
    }

    /**
     * @test
     */
    public function unserializeBagsSerializationFormIsCopyOfThisBag()
    {
        $this->bag->add('someAttribute1', array('someKey1' => 'someValue1'));
        $this->bag->add('someAttribute2', array('someKey2' => 'someValue2'));

        $unserializedBag = unserialize(serialize($this->bag));

        $this->assertEquals($this->bag->getAll(), $unserializedBag->getAll());
    }
}