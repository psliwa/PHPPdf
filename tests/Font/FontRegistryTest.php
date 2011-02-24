<?php

use PHPPdf\Font\Registry,
    PHPPdf\Font\Font;

require_once 'Zend/Pdf/Font.php';

class FontRegistryTest extends PHPUnit_Framework_TestCase
{
    private $registry;

    public function setUp()
    {
        $this->registry = new Registry();
    }

    /**
     * @test
     */
    public function addingDefinition()
    {
        $fontPath = dirname(__FILE__).'/../resources';

        $this->registry->register('verdana', array(
            Font::STYLE_NORMAL => \Zend_Pdf_Font::fontWithPath($fontPath.'/verdana.ttf'),
            Font::STYLE_BOLD => \Zend_Pdf_Font::fontWithPath($fontPath.'/verdanab.ttf'),
            Font::STYLE_ITALIC => \Zend_Pdf_Font::fontWithPath($fontPath.'/verdanai.ttf'),
            Font::STYLE_BOLD_ITALIC => \Zend_Pdf_Font::fontWithPath($fontPath.'/verdanaz.ttf'),
        ));

        $font = $this->registry->get('verdana');

        $this->assertTrue($font instanceof Font);
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