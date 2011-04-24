<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document;

/**
 * TODO: przeciążyć metodę doDraw(?) tak aby były rysowane obiekty zwracane przez getContainers()
 *
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
        $this->currentContainer->setParent($this);
        $this->containers[] = $this->currentContainer;

        $boundary = $this->getBoundary();
        $firstPoint = $this->getFirstPoint();
        $secondPoint = $boundary[1];

        //TODO: ustalanie wysokości i 2 ostanich punktów dla kolumn (w nowym formaterze)
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
}