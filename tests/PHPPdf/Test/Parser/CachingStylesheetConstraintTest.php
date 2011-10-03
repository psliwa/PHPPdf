<?php

namespace PHPPdf\Test\Parser;

use PHPPdf\Parser\CachingStylesheetConstraint,
    PHPPdf\Parser\BagContainer,
    PHPPdf\Cache\NullCache;

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
        $constraintMock = $this->getMock('PHPPdf\Parser\StylesheetConstraint', array('getTag', 'getClasses', 'find'));

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
}