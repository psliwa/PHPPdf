<?php

use PHPPdf\Document;
use PHPPdf\Glyph\Glyph;
use PHPPdf\Glyph\Container;
use PHPPdf\Glyph\Page;
use PHPPdf\Formatter\TextDimensionFormatter;
use PHPPdf\Font\Font;
use PHPPdf\Font\ResourceWrapper;

class TextDimensionFormatterTest extends PHPUnit_Framework_TestCase
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
    public function calculateSizeOfEachWord($text, $fontSize)
    {
        $textMock = $this->getMockBuilder('PHPPdf\Glyph\Text')
                         ->setMethods(array('setWordsSizes', 'getText', 'getFont', 'getRecurseAttribute'))
                         ->getMock();
                     
        $fontMock = $this->getMockBuilder('PHPPdf\Font\Font')
                         ->disableOriginalConstructor()
                         ->setMethods(array('getCharsWidth'))
                         ->getMock();
                     
        $textMock->expects($this->atLeastOnce())
                 ->method('getText')
                 ->will($this->returnValue($text));
        $textMock->expects($this->atLeastOnce())
                 ->method('getFont')
                 ->will($this->returnValue($fontMock));
        $textMock->expects($this->atLeastOnce())
                 ->method('getRecurseAttribute')
                 ->with('font-size')
                 ->will($this->returnValue($fontSize));
                 
        $words = preg_split('/\s+/', $text);
        
        array_walk($words, function(&$value){
            $value .= ' ';
        });
        $lastIndex = count($words) - 1;
        $words[$lastIndex] = rtrim($words[$lastIndex]);
        
        $wordsSizes = array();
        
        foreach($words as $i => $word)
        {
            $chars = str_split($word);
            
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
                 ->with($words, $wordsSizes);   
                 
        $this->formatter->format($textMock, $this->document);
    }
    
    public function textProvider()
    {
        return array(
            array('some text with some words', 12),
        );
    }
//
//    /**
//     * @test
//     */
//    public function glyphFormatter()
//    {
//        $page = $this->getPageStub();
//        $text = new PHPPdf\Glyph\Text('a', array('width' => 1, 'display' => 'block'));
//        $page->add($text);
//
//        $text->getBoundary()->setNext(0, $page->getHeight());
//        $this->formatter->format($text, $this->document);
//
//        $height = $text->getHeight();
//
//        $this->assertNotEquals(1, $text->getWidth());
//
//        $text->reset();
//
//        $text->getBoundary()->setNext(0, $page->getHeight());
//        
//        $text->setText('a a a');
//
//        $this->formatter->format($text, $this->document);
//
//        $this->assertEquals(3*$height, $text->getHeight());
//    }
//
//    private function getPageStub()
//    {
//        $page = new Page();
//        $page['font-type'] = new Font(array(
//            Font::STYLE_NORMAL => ResourceWrapper::fromName(\Zend_Pdf_Font::FONT_COURIER)
//        ));
//        $page['font-size'] = 12;
//
//        return $page;
//    }
//
//    /**
//     * @test
//     */
//    public function calculateWidthWithNoAsciChars()
//    {
//        $page = $this->getPageStub();
//
//        $text = new PHPPdf\Glyph\Text('ąę', array('display' => 'inline'));
//        $text2 = $text->copy();
//        $page->add($text);
//
//        $text->getBoundary()->setNext(0, $page->getHeight());
//
//        $this->formatter->format($text, $this->document);
//
//        $text2->setText('ae');
//        $page->add($text2);
//
//        $text2->getBoundary()->setNext(0, $page->getHeight()/2);
//
//        $this->formatter->format($text2, $this->document);
//
//        $this->assertEquals($text->getWidth(), $text2->getWidth());
//    }
}