<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Runtime;

use PHPPdf\Glyph\Text,
    PHPPdf\Glyph\Runtime,
    PHPPdf\Glyph\Page,
    PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class PageText extends Text implements Runtime
{
    private $evaluated = false;
    private $page = null;

    public function  __construct(array $attributes = array())
    {
        parent::__construct('', $attributes);
    }

    public function initialize()
    {
        parent::initialize();

        $this->addAttribute('dummy-text', 'no.');
        $this->setAttribute('text-align', self::ALIGN_LEFT);
        $this->addAttribute('format', '%s.');
        $this->addAttribute('dummy-number', 'no.');

        $this->setText($this->getAttribute('dummy-text'));
    }

    protected function refreshDummyText()
    {
        $dummy = $this->getDummyNumber();
        $this->setAttribute('dummy-text', sprintf($this->getAttribute('format'), $dummy));
    }

    public function setFormat($format)
    {
        $format = (string) $format;

        $this->setAttributeDirectly('format', $format);

        $this->refreshDummyText();
    }

    public function setDummyNumber($dummy)
    {
        $dummy = (string) $dummy;
        $this->setAttributeDirectly('dummy-number', $dummy);

        $this->refreshDummyText();
    }

    public function setDummyText($text)
    {
        $text = (string) $text;

        $this->setAttributeDirectly('dummy-text', $text);
        $this->setText($text);
    }

    protected function preDraw(Document $document)
    {
        if($this->evaluated)
        {
            parent::preDraw($document);
        }
    }

    protected function doDraw(Document $document)
    {
        if($this->evaluated)
        {
            parent::doDraw($document);
        }
        else
        {
            $page = $this->getPage();
            $page->markAsRuntimeGlyph($this);
        }
    }

    public function evaluate()
    {
        $text = $this->getTextAfterEvaluating();

        $this->setText($text);
        
        foreach($this->lineParts as $part)
        {
            $part->setWords($text);
        }

        $this->evaluated = true;
    }

    abstract protected function getTextAfterEvaluating();

    public function copy()
    {
        $boundary = $this->getBoundary();
        $parent = $this->getParent();
        $copy = parent::copy();

        $copy->setBoundary(clone $boundary);
        if($parent)
        {
            $copy->setParent($parent);
        }

        return $copy;
    }

    public function mergeEnhancementAttributes($name, array $parameters = array())
    {
    }

    public function getPage()
    {
        if($this->page !== null)
        {
            return $this->page;
        }

        return parent::getPage();
    }

    public function setPage(Page $page)
    {
        $this->page = $page;
    }
}