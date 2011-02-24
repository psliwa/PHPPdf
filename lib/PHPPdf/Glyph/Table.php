<?php

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Glyph\Table\Row;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Table extends Container
{
    public function initialize()
    {
        parent::initialize();

        $this->addAttribute('row-height');
    }

    public function add(Glyph $glyph)
    {
        if(!$glyph instanceof Row)
        {
            throw new \InvalidArgumentException(sprintf('Invalid child glyph type, expected PHPPdf\Glyph\Table\Row, %s given.', get_class($glyph)));
        }

        return parent::add($glyph);
    }

    protected function doSplit($height)
    {
        $splited = parent::doSplit($height);

        if($splited)
        {
            $height = 0;
            foreach($this->getChildren() as $row)
            {
                $height += $row->getHeight() + $row->getMarginTop() + $row->getMarginBottom();
            }

            $oldHeight = $this->getHeight();
            $this->setHeight($height);
            $diff = $oldHeight - $height;

            $boundary = $this->getBoundary();
            $boundary->pointTranslate(2, 0, -$diff);
            $boundary->pointTranslate(3, 0, -$diff);
        }

        return $splited;
    }
}