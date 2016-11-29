<?php

/*
 * Copyright 2011 Piotr Sliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Runtime;

/**
 * @author Piotr Sliwa <peter.pl7@gmail.com>
 */
class PageTotal extends PageText
{    
    protected function getTextAfterEvaluating()
    {
        $page = $this->getPage();
        $context = $page->getContext();
        
        $currentPageNumber = $context->getNumberOfPages() + $this->getAttribute('offset');

        return sprintf($this->getAttribute('format'), $currentPageNumber);
    }
}
