<?php

namespace PHPPdf\Glyph;

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

    public function __construct(Container $containerPrototype, array $attributes = array())
    {
        $this->containerPrototype = $containerPrototype;

        parent::__construct($attributes);
    }

    public function initialize()
    {
        parent::initialize();

        $this->addAttribute('number-of-columns', 2);
        $this->addAttribute('margin-between-columns', 10);
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
        $this->containers[] = $this->currentContainer;

        $boundary = $this->getBoundary();
        $firstPoint = $this->getFirstPoint();
        $secondPoint = $boundary[1];
        
        $this->currentContainer->getBoundary()->setNext($firstPoint->translate($translateX, 0))
                                              ->setNext($secondPoint->translate($translateX, 0));
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
}