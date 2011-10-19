<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Runtime
{
    public function evaluate();
    public function setPage(Page $page);
    public function copyAsRuntime();
}