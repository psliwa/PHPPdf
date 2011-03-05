<?php

use PHPPdf\Font\Font,
    PHPPdf\Font\ResourceWrapper;

class FontTest extends PHPUnit_Framework_TestCase
{
    private $font;
    private $fontPath;

    public function setUp()
    {
        $fontPath = __DIR__.'/../resources';
        $this->fontPath = $fontPath;
        $this->font = new Font(array(
            Font::STYLE_NORMAL => ResourceWrapper::fromFile($fontPath.'/verdana.ttf'),
            Font::STYLE_BOLD => ResourceWrapper::fromFile($fontPath.'/verdanab.ttf'),
            Font::STYLE_ITALIC => ResourceWrapper::fromFile($fontPath.'/verdanai.ttf'),
            Font::STYLE_BOLD_ITALIC => ResourceWrapper::fromFile($fontPath.'/verdanaz.ttf'),
        ));
    }

    /**
     * @test
     */
    public function switchingDecorationStyle()
    {
        $font = $this->font->getFont();

        $this->assertFalse($font->isBold() || $font->isItalic());

        $this->font->setStyle(Font::STYLE_BOLD);
        $font = $this->font->getFont();
        
        $this->assertTrue($font->isBold());
        $this->assertFalse($font->isItalic());

        $this->font->setStyle(Font::STYLE_ITALIC);
        $font = $this->font->getFont();

        $this->assertFalse($font->isBold());
        $this->assertTrue($font->isItalic());

        $this->font->setStyle(Font::STYLE_ITALIC | Font::STYLE_BOLD);
        $font = $this->font->getFont();

        $this->assertTrue($font->isBold() && $font->isItalic());

        $this->font->setStyle(Font::STYLE_BOLD_ITALIC);
        $font = $this->font->getFont();

        $this->assertTrue($font->isBold() && $font->isItalic());

        $this->font->setStyle(Font::STYLE_NORMAL);
        $font = $this->font->getFont();

        $this->assertFalse($font->isBold() || $font->isItalic());
    }

    /**
     * @test
     */
    public function switchingDecorationStyleByString()
    {
        $this->font->setStyle('bold');
        $font = $this->font->getFont();
        
        $this->assertTrue($font->isBold());
        $this->assertFalse($font->isItalic());

        $this->font->setStyle('italic');
        $font = $this->font->getFont();

        $this->assertFalse($font->isBold());
        $this->assertTrue($font->isItalic());

        $this->font->setStyle('normal');
        $font = $this->font->getFont();
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
            Font::STYLE_BOLD => ResourceWrapper::fromFile($this->fontPath.'/verdana.ttf'),
            Font::STYLE_NORMAL => ResourceWrapper::fromFile($this->fontPath.'/verdana.ttf'),
            8 => ResourceWrapper::fromFile($this->fontPath.'/verdana.ttf'),
        ));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function creationWithoutNormalFont()
    {
        new Font(array(
            Font::STYLE_BOLD => ResourceWrapper::fromFile($this->fontPath.'/verdana.ttf'),
        ));
    }

    /**
     * @test
     */
    public function unserializedFontIsCopyOfSerializedFont()
    {
        $unserializedFont = unserialize(serialize($this->font));
        $styles = array(Font::STYLE_NORMAL, Font::STYLE_BOLD, Font::STYLE_ITALIC, Font::STYLE_BOLD_ITALIC);

        foreach($styles as $style)
        {
            $this->assertEquals($this->font->hasStyle($style), $unserializedFont->hasStyle($style));
        }
    }
}