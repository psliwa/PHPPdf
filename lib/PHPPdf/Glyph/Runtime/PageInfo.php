<?php

namespace PHPPdf\Glyph\Runtime;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageInfo extends PageText
{
    public function initialize()
    {
        parent::initialize();

        $this->setAttribute('dummy-text', 'no. / no.');

        $this->addAttribute('dummy-number', 'no.');
        $this->addAttribute('format', '%s / %s');
        $this->addAttribute('text-align', null);
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

        $numberOfPage = $context->getNumberOfPages();
        $pageNumber = $context->getPageNumber();

        return sprintf($this->getAttribute('format'), $pageNumber, $numberOfPage);
    }

    public function unserialize($serialized)
    {
        parent::unserialize($serialized);

        $this->refreshDummyText();
    }
}