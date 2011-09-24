<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node\Runtime;

use PHPPdf\Node\Text,
    PHPPdf\Node\Runtime,
    PHPPdf\Node\Page,
    PHPPdf\Util\UnitConverter,
    PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class PageText extends Text implements Runtime
{
    private $evaluated = false;
    private $page = null;

    public function  __construct(array $attributes = array(), UnitConverter $converter = null)
    {
        parent::__construct('', $attributes, $converter);
    }

    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        static::addAttribute('dummy-text', 'no.');
        static::addAttribute('text-align', self::ALIGN_LEFT);
        static::addAttribute('format', '%s.');
        static::addAttribute('dummy-number', 'no.');
    }
    
    public function initialize()
    {
        parent::initialize();

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
            return parent::preDraw($document);
        }
        
        return array();
    }

    protected function doDraw(Document $document)
    {
        if($this->evaluated)
        {
            return parent::doDraw($document);
        }
        else
        {
            $page = $this->getPage();
            $page->markAsRuntimeNode($this);
            return array();
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

    public function copyAsRuntime()
    {
        $boundary = $this->getBoundary();
        $parent = $this->getParent();
        $lineParts = $this->lineParts;
        
        $copy = $this->copy();

        foreach($lineParts as $part)
        {
            $copyPart = clone $part;
            $copyPart->setText($copy);
            $copy->lineParts[] = $copyPart;
        }
        
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