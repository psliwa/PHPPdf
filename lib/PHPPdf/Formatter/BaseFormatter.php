<?php

namespace PHPPdf\Formatter;

use PHPPdf\Formatter\Formatter,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Document,
    PHPPdf\Formatter\Chain;

/**
 * Base formatter class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class BaseFormatter implements Formatter
{
    private $document;

    public function setDocument(Document $document)
    {
        $this->document = $document;
    }

    public function getDocument()
    {
        if($this->document === null)
        {
            throw new \LogicException(sprintf('PHPPdf\Document object haven\'t set in object "%s".', __CLASS__));
        }

        return $this->document;
    }

    public function preFormat(Glyph $glyph)
    {
    }

    public function postFormat(Glyph $glyph)
    {
    }
}