<?php

namespace PHPPdf\Font;

/**
 * Encapsulates font in different styles
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Font
{
    const STYLE_NORMAL = 0;
    const STYLE_BOLD = 1;
    const STYLE_ITALIC = 2;
    const STYLE_BOLD_ITALIC = 3;

    private $fontPaths = array();
    private $currentFont = null;

    public function __construct(array $fonts)
    {
        $this->throwsExceptionIfFontsAreInvalid($fonts);

        $this->fontPaths = $fonts;
        $this->setStyle(self::STYLE_NORMAL);
    }

    /**
     * Create and return font composed only by normal style font
     * 
     * @param string $fontPath Path to font file
     * @return Font
     */
    public static function createSingleStyle($fontPath)
    {
        return new self(array(
            self::STYLE_NORMAL => $fontPath,
        ));
    }

    private function throwsExceptionIfFontsAreInvalid(array $fonts)
    {
        $types = array(
            self::STYLE_NORMAL,
            self::STYLE_BOLD,
            self::STYLE_ITALIC,
            self::STYLE_BOLD_ITALIC,
        );

        if(count($fonts) === 0)
        {
            throw new \InvalidArgumentException('Passed empty map of fonts.');
        }
        elseif(count(\array_diff(array_keys($fonts), $types)) > 0)
        {
            throw new \InvalidArgumentException('Invalid font types in map of fonts.');
        }
        elseif(!isset($fonts[self::STYLE_NORMAL]))
        {
            throw new \InvalidArgumentException('Path for normal font must by passed.');
        }

        foreach($fonts as $type => $font)
        {
            if(!$font instanceof \Zend_Pdf_Resource_Font)
            {
                throw new \InvalidArgumentException(sprintf('Font with type "%s" must be instance of "\Zend_Pdf_Resource_Font" class.', $type));
            }
        }
    }

    public function setStyle($style)
    {
        $style = $this->convertStyleType($style);

        $this->currentFont = $this->createFont($style);
    }

    private function convertStyleType($style)
    {
        if(is_string($style))
        {
            $style = str_replace('-', '_', strtoupper($style));
            $const = sprintf('PHPPdf\Font\Font::STYLE_%s', $style);

            if(defined($const))
            {
                $style = constant($const);
            }
            else
            {
                $style = self::STYLE_NORMAL;
            }
        }

        return $style;
    }

    public function hasStyle($style)
    {
        $style = $this->convertStyleType($style);
        return isset($this->fontPaths[$style]);
    }

    private function createFont($style)
    {
        $font = !$this->hasStyle($style) ? $this->fontPaths[self::STYLE_NORMAL] : $this->fontPaths[$style];

        if(!$font instanceof \Zend_Pdf_Resource_Font)
        {
            $font = \Zend_Pdf_Font::fontWithPath($font);
        }

        return $font;
    }

    public function getFont()
    {
        return $this->currentFont;
    }

    public function getCharsWidth(array $chars, $fontSize)
    {
        $glyphs = $this->getFont()->glyphNumbersForCharacters($chars);
        $widths = $this->getFont()->widthsForGlyphs($glyphs);
        $textWidth = (array_sum($widths) / $this->getFont()->getUnitsPerEm()) * $fontSize;

        return $textWidth;
    }
}