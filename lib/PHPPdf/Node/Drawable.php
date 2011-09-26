<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Node;

use PHPPdf\Util\DrawingTaskHeap;
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
    public function collectOrderedDrawingTasks(Document $document, DrawingTaskHeap $tasks);

    /**
     * @return array Unordered array of DrawingTask objects
     */
    public function collectUnorderedDrawingTasks(Document $document, DrawingTaskHeap $tasks);
    
    public function collectPostDrawingTasks(Document $document, DrawingTaskHeap $tasks);
}