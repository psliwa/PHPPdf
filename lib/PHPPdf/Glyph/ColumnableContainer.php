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
        $this->currentContainer = $this->containerPrototype->copy();
        $this->containers[] = $this->currentContainer;
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