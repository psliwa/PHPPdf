<?php

namespace PHPPdf\Test\Core\Parser;

use PHPPdf\Core\Parser\StylesheetConstraint;
use PHPPdf\Core\Parser\CachingStylesheetConstraint;
use PHPPdf\Core\Parser\BagContainer;
use PHPPdf\Cache\NullCache;

class CachingStylesheetConstraintTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function callParentFindMethodOnlyOnce()
    {
        $tag = 'tag';
        $classes = array('someClass');

        $sc = new CachingStylesheetConstraint();
        $bag = new BagContainer();

        $constraintMock = $this->getConstraintMock($tag, $classes, $bag);

        $sc->addConstraint($tag, $constraintMock);

        $query = array(array('tag' => $tag, 'classes' => $classes));
        $sc->find($query);
        $sc->find($query);
    }

    private function getConstraintMock($tag, array $classes, $bag)
    {
        $constraintMock = $this->getMock('PHPPdf\Core\Parser\StylesheetConstraint', array('getTag', 'getClasses', 'find'));

        $constraintMock->expects($this->any())
                       ->method('getTag')
                       ->will($this->returnValue($tag));
        //internally in StylesheetConstraint class $tag property is used insted of getTag() method (due to performance)
        $this->writeAttribute($constraintMock, 'tag', $tag);
        $constraintMock->expects($this->atLeastOnce())
                       ->method('getClasses')
                       ->will($this->returnValue($classes));
        $constraintMock->expects($this->once())
                       ->method('find')
                       ->will($this->returnValue($bag));

        return $constraintMock;
    }

    /**
     * @test
     */
    public function changeFlagOfInternalStatusIfResultsMapIsModified()
    {
        $sc = new CachingStylesheetConstraint();

        $tag = 'tag';
        $classes = array('someClass');
        $bag = new BagContainer();

        $this->assertFalse($sc->isResultMapModified());

        $constraintMock = $this->getConstraintMock($tag, $classes, $bag);

        $sc->addConstraint($tag, $constraintMock);

        $query = array(array('tag' => $tag, 'classes' => $classes));
        $sc->find($query);

        $this->assertTrue($sc->isResultMapModified());

        $this->invokeMethod($sc, 'setResultMapModified', array(false));

        $sc->find($query);

        $this->assertFalse($sc->isResultMapModified());
    }
    
    /**
     * @test
     */
    public function mergeConstraints()
    {
        $tag = 'tag';
        $class = 'someClass';
        $query = 'tag.someClass';
        
        $firstAttributes = array('attribute1' => 'value1', 'attribute2' => 'value2');
        $firstBag = new BagContainer($firstAttributes);
        
        $secondAttributes = array('attribute3' => 'value3', 'attribute2' => 'value2x');
        $secondBag = new BagContainer($secondAttributes);
        
        $firstStylesheetConstraint = new CachingStylesheetConstraint();        
        $firstConstraint = $this->createStylesheetConstraint($firstAttributes, $tag, $class);
        $firstStylesheetConstraint->addConstraint($tag, $firstConstraint);
        $this->writeAttribute($firstStylesheetConstraint, 'resultMap', array($query => $firstBag));

        $secondStylesheetConstraint = new CachingStylesheetConstraint();        
        $secondConstraint = $this->createStylesheetConstraint($secondAttributes, $tag, $class);        
        $secondStylesheetConstraint->addConstraint($tag, $secondConstraint);
        $this->writeAttribute($secondStylesheetConstraint, 'resultMap', array($query => $secondBag));
        
        $constraint = CachingStylesheetConstraint::merge(array($firstStylesheetConstraint, $secondStylesheetConstraint));
        
        $expectedAttributes = array_merge($firstAttributes, $secondAttributes);
        $expectedResultMap = array($query => new BagContainer($expectedAttributes));
        
        $this->assertEquals($expectedResultMap, $this->readAttribute($constraint, 'resultMap'));
        $this->assertEquals(array($firstConstraint, $secondConstraint), $constraint->getConstraints());
    }
    
    private function createStylesheetConstraint($attibutes, $tag, $class)
    {
        $constraint = new StylesheetConstraint($attibutes);
        $constraint->setTag($tag);
        $constraint->addClass($class);
        
        return $constraint;
    }
}