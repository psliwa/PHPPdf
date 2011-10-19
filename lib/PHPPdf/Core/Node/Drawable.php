<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\Document;

/**
 * Element being able to draw
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Drawable
{
    /**
     * Insert to $tasks collection DrawingTask objects. Order of tasks is important
     */
    public function collectOrderedDrawingTasks(Document $document, DrawingTaskHeap $tasks);

    /**
     * Insert to $tasks collection DrawingTask objects. Order of tasks is not important
     */
    public function collectUnorderedDrawingTasks(Document $document, DrawingTaskHeap $tasks);
    
    /**
     * Insert to $tasks collection DrawingTask objects. This tasks must be evaluated on the end of all tasks
     */
    public function collectPostDrawingTasks(Document $document, DrawingTaskHeap $tasks);
}