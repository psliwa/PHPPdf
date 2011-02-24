<?php

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\Formatter,
    PHPPdf\Glyph as Glyphs,
    PHPPdf\Formatter\Chain;

/**
 * Sets chain to children glyphs
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ContainerFormatter extends BaseFormatter
{
    public function preFormat(Glyphs\Glyph $glyph)
    {
        if($glyph instanceof Glyphs\Container)
        {
            foreach($glyph->getChildren() as $child)
            {
                $child->preFormat($this->getDocument());
                $child->format($this->getDocument()->getFormatters());
            }
        }
    }
}