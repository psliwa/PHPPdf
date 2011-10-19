<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

/**
 * Heap of drawing tasks.
 *
 * Role of this heap is sort tasks by priority.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class DrawingTaskHeap extends \SplHeap
{
    private $elements = 0;

    public function insert($value)
    {
        $value->setOrder($this->elements++);
        parent::insert($value);
    }

    public function compare($value1, $value2)
    {
        return $value1->compareTo($value2);
    }
}