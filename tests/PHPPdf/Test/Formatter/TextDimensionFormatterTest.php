<?php

namespace PHPPdf\Test\Formatter;

use PHPPdf\Document;
use PHPPdf\Node\Node;
use PHPPdf\Node\Container;
use PHPPdf\Node\Page;
use PHPPdf\Formatter\TextDimensionFormatter;

class TextDimensionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextDimensionFormatter();
        $this->document = new Document();
    }
    
    /**
     * @test
     * @dataProvider textProvider
     */
    public function calculateSizeOfEachWord($text, $expectedWords, $fontSize)
    {
        $textMock = $this->getMockBuilder('PHPPdf\Node\Text')
                         ->setMethods(array('setWordsSizes', 'getText', 'getFont', 'getRecurseAttribute', 'getFontSizeRecursively'))
                         ->getMock();
                     
        $fontMock = $this->getMockBuilder('PHPPdf\Engine\Font')
                         ->getMock();
                     
        $textMock->expects($this->atLeastOnce())
                 ->method('getText')
                 ->will($this->returnValue($text));
        $textMock->expects($this->atLeastOnce())
                 ->method('getFont')
                 ->will($this->returnValue($fontMock));
        $textMock->expects($this->atLeastOnce())
                 ->method('getFontSizeRecursively')
                 ->will($this->returnValue($fontSize));
        
        $wordsSizes = array();
        
        foreach($expectedWords as $i => $word)
        {
            $chars = $word ? str_split($word) : array();
            
            array_walk($chars, function(&$value){
                $value = ord($value);
            });
            
            $size = rand(1, 20);
            $wordsSizes[] = $size;
            $fontMock->expects($this->at($i))
                     ->method('getCharsWidth')
                     ->with($chars, $fontSize)
                     ->will($this->returnValue($size));
        }
        
        $textMock->expects($this->once())
                 ->method('setWordsSizes')
                 ->with($expectedWords, $wordsSizes);   
                 
        $this->formatter->format($textMock, $this->document);
    }
    
    public function textProvider()
    {
        return array(
            array('some text with some words', array('some ', 'text ', 'with ', 'some ', 'words'), 12),
            array('some text with some words ', array('some ', 'text ', 'with ', 'some ', 'words ', ''), 12),
        );
    }
}