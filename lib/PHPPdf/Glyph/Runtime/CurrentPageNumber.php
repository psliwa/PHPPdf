<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Runtime;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class CurrentPageNumber extends PageText
{
    protected function getTextAfterEvaluating()
    {
        $page = $this->getPage();
        $context = $page->getContext();

        return $context->getPageNumber();
    }
}