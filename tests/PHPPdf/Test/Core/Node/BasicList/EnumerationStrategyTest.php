<?php

namespace PHPPdf\Test\Core\Node\BasicList;

use PHPPdf\Core\Node\BasicList\EnumerationStrategy;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\BasicList;

abstract class EnumerationStrategyTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    protected $strategy;
    
    public function setUp()
    {
        $this->strategy = $this->createStrategy();
    }
    
    abstract protected function createStrategy();    
    
    /**
     * @test
     * @dataProvider integerProvider
     */
    public function drawEnumerationOnValidPosition($elementIndex, Point $point, $position, $childMarginLeft, $elementPattern, $paddingTop = 0)
    {
        $listMock = $this->getMockBuilder('PHPPdf\Core\Node\BasicList')
                         ->setMethods(array_merge($this->getListMockedMethod(), array('getChild', 'getAttribute', 'getEncoding', 'getFontSizeRecursively', 'getRecurseAttribute', 'getFontType', 'getFont')))
                         ->getMock();
        $fontTypeMock = $this->getMockBuilder('PHPPdf\Core\Engine\Font')
                             ->getMock();

        $colorStub = '#123456';
        
        $this->setElementPattern($listMock, $elementPattern);
        
        $fontSize = rand(10, 15);
        $encoding = 'utf-8';
        
        $expectedText = $this->getExpectedText($elementIndex, $elementPattern);
        
        $child = $this->getMock('PHPPdf\Core\Node\Container', array('getFirstPoint', 'getMarginLeft', 'getPaddingTop'));
        $child->expects($this->atLeastOnce())
              ->method('getFirstPoint')
              ->will($this->returnValue($point));
        $child->expects($this->once())
              ->method('getMarginLeft')
              ->will($this->returnValue($childMarginLeft));
        $child->expects($this->once())
              ->method('getPaddingTop')
              ->will($this->returnValue($paddingTop));
              
        $listMock->expects($this->once())
                       ->method('getChild')
                       ->with($elementIndex)
                       ->will($this->returnValue($child));

        $positionTranslation = 0;

        if($position == BasicList::LIST_POSITION_OUTSIDE)
        {
            $expectedWidth = rand(3, 7);
            $positionTranslation -= $expectedWidth;
            
            $fontTypeMock->expects($this->once())
                         ->method('getWidthOfText')
                         ->with($expectedText, $fontSize)
                         ->will($this->returnValue($expectedWidth));                       
        }
        else
        {
            $fontTypeMock->expects($this->atLeastOnce())
                         ->method('getWidthOfText');
        }

        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('getFont'))
                         ->disableOriginalConstructor()
                         ->getMock();
        
        $listMock->expects($this->atLeastOnce())
                       ->method('getAttribute')
                       ->with('list-position')
                       ->will($this->returnValue($position));
        $listMock->expects($this->atLeastOnce())
                       ->method('getFontSizeRecursively')
                       ->will($this->returnValue($fontSize));
        $listMock->expects($this->atLeastOnce())
                       ->method('getRecurseAttribute')
                       ->with('color')
                       ->will($this->returnValue($colorStub));
                       
        $listMock->expects($this->once())
                       ->method('getEncoding')
                       ->will($this->returnValue($encoding));
        $listMock->expects($this->atLeastOnce())
                 ->method('getFont')
                 ->with($document)
                 ->will($this->returnValue($fontTypeMock));
                       
        $gc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                       ->getMock();

        $expectedXCoord = $point->getX() + $positionTranslation - $childMarginLeft;
        //padding-top has influence also on position of enumeration symbol
        $expectedYCoord = $point->getY() - $fontSize - $paddingTop;
        
        $i = 0;
        $gc->expects($this->at($i++))
           ->method('saveGS');
        $gc->expects($this->at($i++))
           ->method('setLineColor')
           ->with($colorStub);
        $gc->expects($this->at($i++))
           ->method('setFillColor')
           ->with($colorStub);
        $gc->expects($this->at($i++))
           ->method('setFont')
           ->with($fontTypeMock, $fontSize);
        
        $gc->expects($this->at($i++))
           ->method('drawText')
           ->with($expectedText, $expectedXCoord, $expectedYCoord, $encoding);
        $gc->expects($this->at($i++))
           ->method('restoreGS');

        $this->strategy->setIndex($elementIndex);
        $this->strategy->setVisualIndex($elementIndex+1);
        $this->strategy->drawEnumeration($document, $listMock, $gc);
    }
    
    public function integerProvider()
    {
        return array(
            array(5, Point::getInstance(10, 30), BasicList::LIST_POSITION_OUTSIDE, 20, $this->getElementPattern(0)),
            array(12, Point::getInstance(100, 300), BasicList::LIST_POSITION_INSIDE, 40, $this->getElementPattern(1)),
            array(12, Point::getInstance(100, 300), BasicList::LIST_POSITION_INSIDE, 40, $this->getElementPattern(1), 5)
        );
    }
    
    abstract protected function getExpectedText($elementIndex, $elementPattern);
    
    abstract protected function getElementPattern($index);
    
    abstract protected function setElementPattern($list, $pattern);
    
    protected function getListMockedMethod()
    {
        return array();
    }
}