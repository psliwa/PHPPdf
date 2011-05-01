<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class ColumnableContainer extends Container
{
    /**
     * @var Container
     */
    private $containerPrototype;

    private $containers = array();

    /**
     * @var Container
     */
    private $currentContainer = null;

    public function __construct(Container $containerPrototype = null, array $attributes = array())
    {
        if($containerPrototype === null)
        {
            $containerPrototype = new Container();
        }

        $this->containerPrototype = $containerPrototype;

        parent::__construct($attributes);
    }

    public function initialize()
    {
        parent::initialize();

        $this->addAttribute('number-of-columns', 2);
        $this->addAttribute('margin-between-columns', 10);
    }

    public function setNumberOfColumns($count)
    {
        $count = (int) $count;

        if($count < 2)
        {
            throw new \InvalidArgumentException(sprintf('Number of columns should be integer greater than 1, %d given.', $count));
        }

        $this->setAttributeDirectly('number-of-columns', $count);

        return $this;
    }

    /**
     * @return array Array of Container objects
     */
    public function getContainers()
    {
        return $this->containers;
    }

    public function createNextContainer()
    {
        $numberOfContainers = count($this->containers);
        $translateX = ($this->getWidth() + $this->getAttribute('margin-between-columns')) * ($numberOfContainers % $this->getAttribute('number-of-columns'));

        $this->currentContainer = $this->containerPrototype->copy();
        $this->currentContainer->setAttribute('splittable', false);
        $this->currentContainer->setParent($this);
        $this->containers[] = $this->currentContainer;

        $boundary = $this->getBoundary();
        $firstPoint = $this->getFirstPoint();
    }

    /**
     * @return Container
     */
    public function getCurrentContainer()
    {
        if($this->currentContainer === null)
        {
            $this->createNextContainer();
        }

        return $this->currentContainer;
    }

    protected function doDraw(Document $document)
    {
        foreach($this->getContainers() as $container)
        {
            $tasks = $container->getDrawingTasks($document);

            foreach($tasks as $task)
            {
                $this->addDrawingTask($task);
            }
        }
    }

    public function setParent(Container $glyph)
    {
        parent::setParent($glyph);

        $width = ($glyph->getWidth() - ($this->getAttribute('number-of-columns')-1)*$this->getAttribute('margin-between-columns')) /$this->getAttribute('number-of-columns');
        $this->setWidth($width);

        return $this;
    }
}