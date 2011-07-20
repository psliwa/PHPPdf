<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

use PHPPdf\Document;

/**
 * Able to draw element
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Drawable
{
    /**
     * @return array Array of DrawingTask objects
     */
    public function getDrawingTasks(Document $document);
}