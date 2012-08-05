<?php

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\ZF\Font;

class FontTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $font;
    private $fontPath;

    public function setUp()
    {
        if(!class_exists('ZendPdf\PdfDocument', true))
        {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }
        
        $this->fontPath = TEST_RESOURCES_DIR;

        $this->font = new Font(array(
            Font::STYLE_NORMAL => TEST_RESOURCES_DIR.'/font-judson/normal.ttf',
            Font::STYLE_BOLD => TEST_RESOURCES_DIR.'/font-judson/bold.ttf',
            Font::STYLE_ITALIC => TEST_RESOURCES_DIR.'/font-judson/italic.ttf',
            Font::STYLE_BOLD_ITALIC => TEST_RESOURCES_DIR.'/font-judson/bold+italic.ttf',
        ));
    }

    /**
     * @test
     */
    public function styleSwitching()
    {
        $font = $this->font->getCurrentWrappedFont();

        $this->assertFalse($font->isBold() || $font->isItalic());

        $this->font->setStyle(Font::STYLE_BOLD);
        $font = $this->font->getCurrentWrappedFont();
        
        $this->assertTrue($font->isBold());
        $this->assertFalse($font->isItalic());

        $this->font->setStyle(Font::STYLE_ITALIC);
        $font = $this->font->getCurrentWrappedFont();

        $this->assertFalse($font->isBold());
        $this->assertTrue($font->isItalic());

        $this->font->setStyle(Font::STYLE_ITALIC | Font::STYLE_BOLD);
        $font = $this->font->getCurrentWrappedFont();

        $this->assertTrue($font->isBold() && $font->isItalic());

        $this->font->setStyle(Font::STYLE_BOLD_ITALIC);
        $font = $this->font->getCurrentWrappedFont();

        $this->assertTrue($font->isBold() && $font->isItalic());

        $this->font->setStyle(Font::STYLE_NORMAL);
        $font = $this->font->getCurrentWrappedFont();

        $this->assertFalse($font->isBold() || $font->isItalic());
    }

    /**
     * @test
     */
    public function switchingDecorationStyleByString()
    {
        $this->font->setStyle('bold');
        $font = $this->font->getCurrentWrappedFont();
        
        $this->assertTrue($font->isBold());
        $this->assertFalse($font->isItalic());

        $this->font->setStyle('italic');
        $font = $this->font->getCurrentWrappedFont();

        $this->assertFalse($font->isBold());
        $this->assertTrue($font->isItalic());

        $this->font->setStyle('normal');
        $font = $this->font->getCurrentWrappedFont();
        $this->assertFalse($font->isBold() || $font->isItalic());
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidResourceException
     */
    public function throwExceptionIfInvalidFontDataPassed()
    {
        $font = new Font(array(
            Font::STYLE_NORMAL => 'some/unexisted/path.ttf',
        ));
        
        $wrappedFont = $font->getCurrentWrappedFont();
    }

    /**
     * @test
     */
    public function getWidthOfText()
    {        
        $text = 'some text';
        
        $width12 = $this->font->getWidthOfText($text, 12);
        $width14 = $this->font->getWidthOfText($text, 14);
        
        $this->assertTrue($width12 > 0);
        $this->assertTrue($width14 > $width12);
    }
}