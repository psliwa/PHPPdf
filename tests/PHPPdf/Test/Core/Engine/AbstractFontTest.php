<?php

namespace PHPPdf\Test\Core\Engine;

use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Stub\Engine\Font as StubFont;
use PHPPdf\Core\Engine\Font;

class AbstractFontTest extends TestCase
{
    private $font;
    
    public function setUp()
    {
        $this->font = new StubFont(array(
            Font::STYLE_NORMAL => TEST_RESOURCES_DIR.'/font-judson/normal.ttf',
            Font::STYLE_BOLD => TEST_RESOURCES_DIR.'/font-judson/bold.ttf',
            Font::STYLE_ITALIC => TEST_RESOURCES_DIR.'/font-judson/italic.ttf',
            Font::STYLE_BOLD_ITALIC => TEST_RESOURCES_DIR.'/font-judson/bold+italic.ttf',
        ));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function creationWithEmptyArray()
    {
        new StubFont(array());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function creationWithInvalidFontTypes()
    {
        new StubFont(array(
            Font::STYLE_BOLD => TEST_RESOURCES_DIR.'/font-judson/bold.ttf',
            Font::STYLE_NORMAL => TEST_RESOURCES_DIR.'/font-judson/normal.ttf',
            8 => TEST_RESOURCES_DIR.'/font-judson/normal.ttf',
        ));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function creationWithoutNormalFont()
    {
        new StubFont(array(
            Font::STYLE_BOLD => TEST_RESOURCES_DIR.'/font-judson/normal.ttf',
        ));
    }
}