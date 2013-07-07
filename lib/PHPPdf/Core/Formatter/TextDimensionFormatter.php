<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Formatter;

use PHPPdf\Core\Formatter\BaseFormatter,
    PHPPdf\Core\Node as Nodes,
    PHPPdf\Core\Document,
    PHPPdf\Core\Formatter\Chain;

/**
 * Calculates text's real dimension
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class TextDimensionFormatter extends BaseFormatter
{
    public function format(Nodes\Node $node, Document $document)
    {
        $words = preg_split('/[ \t]+/', $node->getText());
        $node->setText('');

        for($i=0, $lastIndex = count($words) - 1; $i < $lastIndex; $i++)
        {
            $words[$i] .= ' ';
        }

        $wordsSizes = array();
        
        $font = $node->getFont($document);
        $fontSize = $node->getFontSizeRecursively();
        
        foreach($words as $word)
        {
            $wordsSizes[] = $font->getWidthOfText($word, $fontSize);
        }
        
        $node->setWordsSizes($words, $wordsSizes);
    }
}