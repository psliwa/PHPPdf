<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

/**
 * Context of page
 * 
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

    /**
     * @return integer Number of current page
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * @return integer Total number of pages
     */
    public function getNumberOfPages()
    {
        return $this->dynamicPage->getNumberOfPages();
    }
}