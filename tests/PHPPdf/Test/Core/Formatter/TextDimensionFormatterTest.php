<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Formatter\TextDimensionFormatter;

class TextDimensionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextDimensionFormatter();
        $this->document = $this->createDocumentStub();
    }
    
    /**
     * @test
     * @dataProvider textProvider
     */
    public function calculateSizeOfEachWord($text, $expectedWords, $fontSize)
    {
        $textMock = $this->getMockBuilder('PHPPdf\Core\Node\Text')
                         ->setMethods(array('setWordsSizes', 'getText', 'getFont', 'getRecurseAttribute', 'getFontSizeRecursively'))
                         ->getMock();
                     
        $fontMock = $this->getMockBuilder('PHPPdf\Core\Engine\Font')
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
            $size = rand(1, 20);
            $wordsSizes[] = $size;
            $fontMock->expects($this->at($i))
                     ->method('getWidthOfText')
                     ->with($word, $fontSize)
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