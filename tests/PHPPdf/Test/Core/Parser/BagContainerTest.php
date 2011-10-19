<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\AttributeBag,
    PHPPdf\Core\Parser\BagContainer;

class BagContainerTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function mergeSeveralBagsIntoOne()
    {
        $attributes1 = array(
            'someName1' => 'someValue1',
            'someName2' => 'someValue2',
        );
        
        $attributes2 = array(
            'someName2' => 'anotherValue2',
            'someName3' => 'someValue3',
        );
        
        $containers = array(
            new BagContainer($attributes1),
            new BagContainer($attributes2),
        );

        $container = BagContainer::merge($containers);
        $expectedAttributes = array_merge($attributes1, $attributes2);
        $this->assertEquals($expectedAttributes, $container->getAll());
    }

    /**
     * @test
     */
    public function unserializedBagIsCopyOfSerializedBag()
    {
        $container = new BagContainer();
        $container->add('someName1', 'someValue1');
        $container->add('someName2', array('someKey' => 'someValue'));

        $unserializedContainer = unserialize(serialize($container));

        $expectedAttributes = array('someName1' => 'someValue1', 'someName2' => array('someKey' => 'someValue'));
        $this->assertEquals($expectedAttributes, $unserializedContainer->getAll());
    }
}