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
    public function drawEnumerationInValidPosition($elementIndex, Point $point, $position, $childMarginLeft, $elementPattern)
    {
        $listMock = $this->getMockBuilder('PHPPdf\Glyph\BasicList')
                         ->setMethods(array_merge($this->getListMockedMethod(), array('getChild', 'getAttribute', 'getEncoding', 'getFontSizeRecursively', 'getRecurseAttribute', 'getFontType', 'getFont')))
                         ->getMock();
        $fontTypeMock = $this->getMockBuilder('PHPPdf\Engine\Font')
                             ->getMock();

        $colorStub = $this->getMock('PHPPdf\Engine\Color');
        
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
            
            $fontTypeMock->expects($this->once())
                         ->method('getCharsWidth')
                         ->with($charCodes, $fontSize)
                         ->will($this->returnValue($expectedWidth));                       
        }
        else
        {
            $fontTypeMock->expects($this->atLeastOnce())
                         ->method('getCharsWidth');
        }

        $listMock->expects($this->atLeastOnce())
                       ->method('getAttribute')
                       ->with('position')
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
                 ->will($this->returnValue($fontTypeMock));
                       
//        $listMock->expects($this->atLeastOnce())
//             ->method('getFontType')
//             ->with(true)
//             ->will($this->returnValue($fontTypeMock));
                       
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                       ->getMock();

        $expectedXCoord = $point->getX() + $positionTranslation - $childMarginLeft;
        $expectedYCoord = $point->getY() - $fontSize;
        
        $gc->expects($this->at(0))
           ->method('saveGS');
        $gc->expects($this->at(1))
           ->method('setLineColor')
           ->with($colorStub);
        $gc->expects($this->at(2))
           ->method('setFont')
           ->with($fontTypeMock, $fontSize);
        
        $gc->expects($this->at(3))
           ->method('drawText')
           ->with($expectedText, $expectedXCoord, $expectedYCoord, $encoding);
        $gc->expects($this->at(4))
           ->method('restoreGS');
           
        $document = $this->getMockBuilder('PHPPdf\Document')
                         ->setMethods(array('getFont'))
                         ->getMock();

        $this->strategy->setIndex($elementIndex);
        $this->strategy->setVisualIndex($elementIndex+1);
        $this->strategy->drawEnumeration($document, $listMock, $gc);
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