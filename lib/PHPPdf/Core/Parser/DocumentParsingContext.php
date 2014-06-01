<?php


namespace PHPPdf\Core\Parser;


class DocumentParsingContext
{
    private $inPlaceholder = false;
    private $inBehaviour = false;

    public function isInPlaceholder()
    {
        return $this->inPlaceholder;
    }

    public function isInBehaviour()
    {
        return $this->inBehaviour;
    }

    public function enterPlaceholder()
    {
        $this->inPlaceholder = true;
    }

    public function exitPlaceholder()
    {
        $this->inPlaceholder = false;
    }

    public function enterBehaviour()
    {
        $this->inBehaviour = true;
    }

    public function exitBehaviour()
    {
        $this->inBehaviour = false;
    }
} 