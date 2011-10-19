<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Core\Document;

/**
 * Encapsulate drawing task (callback + arguments + priority + order)
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class DrawingTask
{
    private $callback;
    private $arguments;
    private $priority;
    private $order;

    public function __construct($callback, array $arguments = array(), $priority = Document::DRAWING_PRIORITY_FOREGROUND2)
    {
        $this->callback = $callback;
        $this->arguments = $arguments;
        $this->priority = $priority;
    }
    
    /**
     * @throws PHPPdf\Core\Exception\DrawingException If error occurs while drawing
     */
    public function invoke()
    {
        return call_user_func_array($this->callback, $this->arguments);
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = (int) $order;
    }
    
    public function compareTo(DrawingTask $task)
    {
        $diff = ($this->priority - $task->priority);

        if($diff === 0)
        {
            return ($task->order - $this->order);
        }
        else
        {
            return $diff;
        }
    }
}