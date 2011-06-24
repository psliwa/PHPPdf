<?php

use PHPPdf\Glyph\BasicList;
use PHPPdf\Util\Point;
use PHPPdf\Glyph\BasicList\ImageEnumerationStrategy;

class ImageEnumerationStrategyTest extends TestCase
{
    private $imagePath;
    private $strategy;
    
    public function setUp()
    {
        $this->imagePath = __DIR__.'/../../resources/domek-min.jpg';
        $this->strategy = new ImageEnumerationStrategy();
    }
    
    /**
     * @test
     * @dataProvider enumerationProvider
     */
    public function drawEnumerationInValidPosition(Point $point, $position, $childMarginLeft, $fontSize)
    {
        $elementIndex = 1;
        
        $listMock = $this->getMock('PHPPdf\Glyph\BasicList', array('getChild', 'getAttribute', 'getRecurseAttribute', 'getImage'));
        $image = \Zend_Pdf_Image::imageWithPath($this->imagePath);
        
        $imageWidth = $image->getPixelWidth();
        $imageHeight = $image->getPixelHeight();
        
        if($imageWidth > $fontSize)
        {
            $imageHeight = $imageHeight * $fontSize/$imageWidth;
            $imageWidth = $fontSize;
        }
        
        if($imageHeight > $fontSize)
        {
            $imageWidth = $imageWidth * $fontSize/$imageHeight;
            $imageHeight = $fontSize;
        }
        
        $listMock->expects($this->atLeastOnce())
                 ->method('getImage')
                 ->will($this->returnValue($image));
                 
        $listMock->expects($this->atLeastOnce())
                 ->method('getAttribute')
                 ->with('position')
                 ->will($this->returnValue($position));
                 
        $listMock->expects($this->atLeastOnce())
                 ->method('getRecurseAttribute')
                 ->with('font-size')
                 ->will($this->returnValue($fontSize));
                 
        $child = $this->getMock('PHPPdf\Glyph\Container', array('getFirstPoint', 'getMarginLeft'));
        $child->expects($this->atLeastOnce())
              ->method('getFirstPoint')
              ->will($this->returnValue($point));
        $child->expects($this->atLeastOnce())
              ->method('getMarginLeft')
              ->will($this->returnValue($childMarginLeft));
              
        $listMock->expects($this->atLeastOnce())
                 ->method('getChild')
                 ->with($elementIndex)
                 ->will($this->returnValue($child));
              
        $xTranslation = 0;
        if($position === BasicList::POSITION_OUTSIDE)
        {
            $xTranslation = $imageWidth;
        }
                 
        $expectedX1Coord = $point->getX() - $childMarginLeft - $xTranslation;
        $expectedY1Coord = $point->getY() - $imageHeight;
        $expectedX2Coord = $point->getX() + $imageWidth - $childMarginLeft - $xTranslation;
        $expectedY2Coord = $point->getY();

        $gc = $this->getMock('PHPPdf\Glyph\GraphicsContext', array('drawImage'), array(), '', false, false);
        $gc->expects($this->once())
           ->method('drawImage')
           ->with($image, $expectedX1Coord, $expectedY1Coord, $expectedX2Coord, $expectedY2Coord);
           
        $this->strategy->setIndex($elementIndex);
        $this->strategy->drawEnumeration($listMock, $gc);
    }

    public function enumerationProvider()
    {
        return array(
            array(Point::getInstance(50, 200), BasicList::POSITION_INSIDE, 20, 10),
        );
    }
    
    /**
     * @test
     * @expectedException \LogicException
     */
    public function throwExceptionIfImageIsNotSet()
    {
        $elementIndex = 1;
        $listMock = $this->getMock('PHPPdf\Glyph\BasicList', array('getImage', 'getChild'));
        
        $listMock->expects($this->once())
                 ->method('getImage')
                 ->will($this->returnValue(null));
                 
        $child = $this->getMock('PHPPdf\Glyph\Container', array());
              
        $listMock->expects($this->atLeastOnce())
                 ->method('getChild')
                 ->with($elementIndex)
                 ->will($this->returnValue($child));
                 
        $gc = $this->getMock('PHPPdf\Glyph\GraphicsContext', array(), array(), '', false, false);
        $this->strategy->setIndex($elementIndex);
        $this->strategy->drawEnumeration($listMock, $gc);
    }
}