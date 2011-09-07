<?php

namespace PHPPdf\Test\Parser;

use PHPPdf\Util\AttributeBag,
    PHPPdf\Enhancement\EnhancementBag,
    PHPPdf\Parser\BagContainer;

class BagContainerTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function createObjectAndGettingAndSettingBags()
    {
        $attributeBag = new AttributeBag();
        $enhancementBag = new EnhancementBag();
        $container = new BagContainer($attributeBag, $enhancementBag);

        $this->assertTrue($container->getAttributeBag() === $attributeBag);
        $this->assertTrue($container->getEnhancementBag() === $enhancementBag);

        $anotherAttributeBag = new AttributeBag();
        $anotherEnhancementBag = new EnhancementBag();

        $container->setAttributeBag($anotherAttributeBag);
        $container->setEnhancementBag($anotherEnhancementBag);

        $this->assertTrue($container->getAttributeBag() === $anotherAttributeBag);
        $this->assertTrue($container->getEnhancementBag() === $anotherEnhancementBag);
    }

    /**
     * @test
     */
    public function mergeSeveralBagsIntoOne()
    {
        $attributeBag1 = $this->getAttributeBagMock(array(
            'someName1' => 'someValue1',
            'someName2' => 'someValue2',
        ));

        $attributeBag2 = $this->getAttributeBagMock(array(
            'someName2' => 'anotherValue2',
            'someName3' => 'someValue3',
        ));

        $containers = array(
            new BagContainer($attributeBag1),
            new BagContainer($attributeBag2),
        );

        $container = BagContainer::merge($containers);
        $this->assertEquals(array(
            'someName1' => 'someValue1',
            'someName2' => 'anotherValue2',
            'someName3' => 'someValue3',
        ), $container->getAttributeBag()->getAll());
    }

    private function getAttributeBagMock($attributes)
    {
        $mock = $this->getMock('PHPPdf\Util\AttributeBag', array('getAll'));
        $mock->expects($this->once())
             ->method('getAll')
             ->will($this->returnValue($attributes));

        return $mock;
    }

    /**
     * @test
     */
    public function unserializedBagIsCopyOfSerializedBag()
    {
        $attributeBag = new AttributeBag();
        $attributeBag->add('someName', 'someValue');

        $enhancementBag = new EnhancementBag();
        $enhancementBag->add('someName', array('someKey' => 'someValue'));

        $container = new BagContainer($attributeBag, $enhancementBag);

        $unserializedContainer = unserialize(serialize($container));

        $this->assertEquals($container->getAttributeBag()->getAll(), $unserializedContainer->getAttributeBag()->getAll());
        $this->assertEquals($container->getEnhancementBag()->getAll(), $unserializedContainer->getEnhancementBag()->getAll());
    }
}