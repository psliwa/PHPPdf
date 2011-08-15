<?php

use PHPPdf\Font\Registry,
    PHPPdf\Engine\Font;

class FontRegistryTest extends PHPUnit_Framework_TestCase
{
    private $registry;
    private $document;

    public function setUp()
    {
        $this->document = $this->getMockBuilder('PHPPdf\Document')
                               ->setMethods(array('createFont'))
                               ->getMock();
        $this->registry = new Registry($this->document);
    }

    /**
     * @test
     */
    public function addingDefinition()
    {
        $fontPath = dirname(__FILE__).'/../resources';
        
        $definition = array(
            Font::STYLE_NORMAL => 'source1',
            Font::STYLE_BOLD => 'source2',
            Font::STYLE_ITALIC => 'source3',
            Font::STYLE_BOLD_ITALIC => 'source4',
        );
        
        $fontStub = 'font stub';
        
        $this->document->expects($this->once())
                       ->method('createFont')
                       ->with($definition)
                       ->will($this->returnValue($fontStub));
        
        $this->registry->register('font', $definition);

        $font = $this->registry->get('font');

        $this->assertEquals($fontStub, $font);
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\Exception
     */
    public function throwExceptionIfFontDosntExist()
    {
        $this->registry->get('font');
    }
}