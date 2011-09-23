<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Document;

/**
 * Able to draw element
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Drawable
{
    /**
     * @return array Ordered array of DrawingTask objects
     */
    public function getOrderedDrawingTasks(Document $document);

    /**
     * @return array Unordered array of DrawingTask objects
     */
    public function getUnorderedDrawingTasks(Document $document);
    
    public function getPostDrawingTasks(Document $document);
}