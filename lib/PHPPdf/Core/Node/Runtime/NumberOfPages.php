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
class NumberOfPages extends PageText
{
    protected function getTextAfterEvaluating()
    {
        $page = $this->getPage();
        $context = $page->getContext();

        return sprintf($this->getAttribute('format'), $context->getNumberOfPages() + $this->getAttribute('offset'));
    }
}