<?php

namespace PHPPdf\Glyph;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class PageContext
{
    private $dynamicPage;
    private $pageNumber;

    public function __construct($pageNumber, DynamicPage $dynamicPage)
    {
        $this->dynamicPage = $dynamicPage;
        $this->pageNumber = (int) $pageNumber;
    }

    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    public function getNumberOfPages()
    {
        return count($this->dynamicPage->getPages());
    }
}