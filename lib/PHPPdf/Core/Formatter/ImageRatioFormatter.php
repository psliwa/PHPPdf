<?php

/*
 * Copyright 2014 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;


use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Node;

class ImageRatioFormatter extends BaseFormatter
{
    public function format(Node $node, Document $document)
    {
        $originalRatio = $node->getOriginalRatio();
        $currentRatio = $node->getCurrentRatio();

        if(!$this->floatEquals($originalRatio, $currentRatio))
        {
            $width = $node->getWidth();
            $height = $node->getHeight();

            if($originalRatio > $currentRatio)
            {
                $height = $originalRatio ? $width/$originalRatio : 0;
                $node->setHeight($height);
            }
            else
            {
                $width = $height * $originalRatio;
                $node->setWidth($width);
            }
        }
    }

    private function floatEquals($f1, $f2)
    {
        $f1 = round($f1*1000);
        $f2 = round($f2*1000);

        return $f1 == $f2;
    }
}