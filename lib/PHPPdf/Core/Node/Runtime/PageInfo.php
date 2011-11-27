<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Runtime;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageInfo extends PageText
{    
    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('dummy-text', 'no. / no.');

        static::addAttribute('dummy-number', 'no.');
        static::addAttribute('format', '%s / %s');
        static::addAttribute('text-align', null);
    }

    protected function refreshDummyText()
    {
        $dummy = $this->getAttribute('dummy-number');
        $this->setAttribute('dummy-text', sprintf($this->getAttribute('format'), $dummy, $dummy));
    }

    protected function getTextAfterEvaluating()
    {
        $page = $this->getPage();
        $context = $page->getContext();

        $numberOfPage = $context->getNumberOfPages() + $this->getAttribute('offset');
        $pageNumber = $context->getPageNumber() + $this->getAttribute('offset');

        return sprintf($this->getAttribute('format'), $pageNumber, $numberOfPage);
    }

    public function unserialize($serialized)
    {
        parent::unserialize($serialized);

        $this->refreshDummyText();
    }
}