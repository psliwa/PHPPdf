<?php

use PHPPdf\Glyph\BasicList\EnumerationStrategy;
use PHPPdf\Util\Point;
use PHPPdf\Glyph\BasicList;

abstract class EnumerationStrategyTest extends TestCase
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
    public function lastEnumerationCharsAreNumberOfListElements($elementIndex, Point $point, $position, $childMarginLeft, $elementPattern)
    {
        $listMock = $this->getMock('PHPPdf\Glyph\BasicList', array_merge($this->getListMockedMethod(), array('getChild', 'getAttribute', 'getEncoding', 'getRecurseAttribute', 'getFontType')));
        $fontMock = $this->getMock('PHPPdf\Font\Font', array(), array(), '', false);
        
        $this->setElementPattern($listMock, $elementPattern);
        
        $fontSize = rand(10, 15);
        $encoding = 'utf-8';
        
        $expectedText = $this->getExpectedText($elementIndex, $elementPattern);
        $charCodes = $this->convertTextToCharsCodes($expectedText);
        
        $child = $this->getMock('PHPPdf\Glyph\Container', array('getFirstPoint', 'getMarginLeft'));
        $child->expects($this->once())
              ->method('getFirstPoint')
              ->will($this->returnValue($point));
        $child->expects($this->once())
              ->method('getMarginLeft')
              ->will($this->returnValue($childMarginLeft));
              
        $listMock->expects($this->once())
                       ->method('getChild')
                       ->with($elementIndex)
                       ->will($this->returnValue($child));

        $positionTranslation = 0;

        if($position == BasicList::POSITION_OUTSIDE)
        {
            $expectedWidth = rand(3, 7);
            $positionTranslation -= $expectedWidth;
            
            $fontMock->expects($this->once())
                           ->method('getCharsWidth')
                           ->with($charCodes, $fontSize)
                           ->will($this->returnValue($expectedWidth));                       
        }
        else
        {
            $fontMock->expects($this->never())
                           ->method('getCharsWidth');
        }
                       
        $listMock->expects($this->atLeastOnce())
                       ->method('getAttribute')
                       ->with('position')
                       ->will($this->returnValue($position));
        $listMock->expects($this->atLeastOnce())
                       ->method('getRecurseAttribute')
                       ->with('font-size')
                       ->will($this->returnValue($fontSize));
                       
        $listMock->expects($this->once())
                       ->method('getEncoding')
                       ->will($this->returnValue($encoding));
                       
        $listMock->expects($this->once())
             ->method('getFontType')
             ->with(true)
             ->will($this->returnValue($fontMock));
                       
        $gc = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawText'), array(), '', false);

        $expectedXCoord = $point->getX() + $positionTranslation - $childMarginLeft;
        $expectedYCoord = $point->getY() - $fontSize;
        
        $gc->expects($this->once())
           ->method('drawText')
           ->with($expectedText, $expectedXCoord, $expectedYCoord, $encoding);

        $this->strategy->drawEnumeration($listMock, $gc, $elementIndex);
    }
    
    public function integerProvider()
    {
        return array(
            array(5, Point::getInstance(10, 30), BasicList::POSITION_OUTSIDE, 20, $this->getElementPattern(0)),
            array(12, Point::getInstance(100, 300), BasicList::POSITION_INSIDE, 40, $this->getElementPattern(1))
        );
    }
    
    abstract protected function getExpectedText($elementIndex, $elementPattern);
    
    abstract protected function getElementPattern($index);
    
    abstract protected function setElementPattern($list, $pattern);

    protected function convertTextToCharsCodes($text)
    {
        $chars = $this->invokeMethod($this->strategy, 'splitTextIntoChars', array($text));
        $charCodes = array();
        foreach($chars as $char)
        {
            $charCodes[] = ord($char);
        }
        
        return $charCodes;
    }
    
    protected function getListMockedMethod()
    {
        return array();
    }
}