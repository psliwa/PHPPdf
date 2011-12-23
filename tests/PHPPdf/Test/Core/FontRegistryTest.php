<?php

namespace PHPPdf\Test\Font;

use PHPPdf\Core\FontRegistry,
    PHPPdf\Core\Engine\Font;

class FontRegistryTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $registry;
    private $document;

    public function setUp()
    {
        $this->document = $this->getMockBuilder('PHPPdf\Core\Document')
                               ->setMethods(array('createFont'))
                               ->disableOriginalConstructor()
                               ->getMock();
        $this->registry = new FontRegistry($this->document);
    }

    /**
     * @test
     */
    public function addingDefinition()
    {
        $fontPath = TEST_RESOURCES_DIR.'/resources';
        
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
     * @expectedException PHPPdf\Exception\InvalidArgumentException
     */
    public function throwExceptionIfFontDosntExist()
    {
        $this->registry->get('font');
    }
}