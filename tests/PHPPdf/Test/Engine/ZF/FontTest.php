<?php

namespace PHPPdf\Test\Engine\ZF;

use PHPPdf\Engine\ZF\Font;

class FontTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $font;
    private $fontPath;

    public function setUp()
    {
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
    public function switchingDecorationStyle()
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
     * @expectedException \InvalidArgumentException
     */
    public function creationWithEmptyArray()
    {
        new Font(array());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function creationWithInvalidFontTypes()
    {
        new Font(array(
            Font::STYLE_BOLD => $this->fontPath.'/font-judson/bold.ttf',
            Font::STYLE_NORMAL => $this->fontPath.'/font-judson/normal.ttf',
            8 => $this->fontPath.'/font-judson/normal.ttf',
        ));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function creationWithoutNormalFont()
    {
        new Font(array(
            Font::STYLE_BOLD => $this->fontPath.'/font-judson/normal.ttf',
        ));
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
}