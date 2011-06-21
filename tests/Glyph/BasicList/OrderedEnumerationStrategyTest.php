<?php

use PHPPdf\Glyph\Container;
use PHPPdf\Glyph\BasicList\OrderedEnumerationStrategy;

class OrderedEnumerationStrategyTest extends TestCase
{
    private $strategy;
    
    private $listMock;
    private $fontMock;
    
    public function setUp()
    {
        $this->listMock = $this->getMock('PHPPdf\Glyph\BasicList', array('getChildren', 'getAttribute'));
        $this->fontMock = $this->getMock('PHPPdf\Font\Font', array(), array(), '', false);
        
        $this->strategy = new OrderedEnumerationStrategy($this->listMock, $this->fontMock);
    }
    
    /**
     * @test
     * @dataProvider integerProvider
     */
    public function lastEnumerationCharsAreNumberOfListElements($numberOfListElements)
    {
        $children = array();
        
        for($i=0; $i<$numberOfListElements; $i++)
        {
            $children[] = new Container();
        }
        
        $this->listMock->expects($this->once())
                       ->method('getChildren')
                       ->will($this->returnValue($children));
        
        $expectedLastEnumerationText = $numberOfListElements.'.';
        
        $charCodes = array();
        foreach(str_split($expectedLastEnumerationText) as $char)
        {
            $charCodes[] = ord($char);
        }

        $expectedWidth = rand(3, 7);
        $fontSize = rand(10, 15);

        $this->fontMock->expects($this->once())
                       ->method('getCharsWidth')
                       ->with($charCodes, $fontSize)
                       ->will($this->returnValue($expectedWidth));
        $this->listMock->expects($this->atLeastOnce())
                       ->method('getAttribute')
                       ->with('font-size')
                       ->will($this->returnValue($fontSize));
                       
        $this->assertEquals($expectedWidth, $this->strategy->getWidthOfLastEnumerationChars());        
    }
    
    public function integerProvider()
    {
        return array(
            array(5),
            array(12),
        );
    }
    
    /**
     * @test
     */
    public function navigateThroughtEnumerationTexts()
    {
        $fontSize = rand(10, 15);
        
        $max = 5;
        for($i=1, $at=0; $i<$max; $i++, $at++)
        {
            $chars = str_split($i.'.');
            array_walk($chars, function(&$value){
                $value = ord($value);
            });
            
            $this->fontMock->expects($this->at($at))
                           ->method('getCharsWidth')
                           ->with($chars, $fontSize)
                           ->will($this->returnValue($i));
        }
        
        $this->listMock->expects($this->atLeastOnce())
                       ->method('getAttribute')
                       ->with('font-size')
                       ->will($this->returnValue($fontSize));
                   
        for($i=1; $i<$max; $i++)
        {
            $this->assertEquals($i, $this->strategy->getWidthOfCurrentEnumerationChars());
            $this->assertEquals($i.'.', $this->strategy->getCurrentEnumerationText());
            $this->strategy->next();
        }
    }
}