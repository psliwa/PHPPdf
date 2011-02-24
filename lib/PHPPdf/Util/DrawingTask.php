<?php

namespace PHPPdf\Util;

use PHPPdf\Document;

/**
 * Encapsulate drawing task (callback + arguments + priority)
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
     * @throws PHPPdf\Exception\DrawingException If error occurs while drawing
     */
    public function __invoke()
    {
        return call_user_func_array($this->callback, $this->arguments);
    }

    /**
     * @see PHPPdf\Util\DrawingTask::__invoke()
     */
    public function invoke()
    {
        return $this->__invoke();
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
}