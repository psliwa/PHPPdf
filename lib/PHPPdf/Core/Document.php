<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core;

use PHPPdf\Util\AbstractStringFilterContainer;
use PHPPdf\Util\StringFilter;
use PHPPdf\Exception\RuntimeException;
use PHPPdf\Exception\LogicException;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\Node\Node;
use PHPPdf\Core\Engine\ZF\Engine as ZfEngine;
use PHPPdf\Core\Formatter as Formatters;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Node\PageCollection;
use PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory;
use PHPPdf\Core\Exception\DrawingException;
use PHPPdf\Core\Engine\Engine;
use PHPPdf\Core\Engine\GraphicsContext;

/**
 * Document to generate
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Document extends AbstractStringFilterContainer implements Engine
{   
    const DRAWING_PRIORITY_BACKGROUND1 = 60;
    const DRAWING_PRIORITY_BACKGROUND2 = 50;
    const DRAWING_PRIORITY_BACKGROUND3 = 40;

    const DRAWING_PRIORITY_FOREGROUND1 = 10;
    const DRAWING_PRIORITY_FOREGROUND2 = 20;
    const DRAWING_PRIORITY_FOREGROUND3 = 30;

    private $processed = false;

    /**
     * @var PHPPdf\Core\Engine\Engine
     */
    private $engine;

    private $complexAttributeFactory = null;

    private $fontRegistry = null;

    private $formatters = array();
    
    private $unitConverter = null;
    
    private $colorPalette = null;

    public function __construct(Engine $engine)
    {
        $this->setUnitConverter($engine);
        $this->engine = $engine;
        $this->colorPalette = new ColorPalette();
        $this->initialize();
    }

    /**
     * Create complexAttributes objects depends on bag content
     * 
     * @return array Array of ComplexAttribute objects
     */
    public function getComplexAttributes(AttributeBag $bag)
    {
        $complexAttributes = array();

        if($this->complexAttributeFactory !== null)
        {
            foreach($bag->getAll() as $id => $parameters)
            {
                if(!isset($parameters['name']))
                {
                    throw new InvalidArgumentException('"name" attribute is required.');
                }

                $name = $parameters['name'];
                unset($parameters['name']);
                
                $complexAttribute = $this->complexAttributeFactory->create($name, $parameters);
                
                if(!$complexAttribute->isEmpty())
                {
                    $complexAttributes[] = $complexAttribute;
                }
            }
        }

        return $complexAttributes;
    }

    public function setComplexAttributeFactory(ComplexAttributeFactory $complexAttributeFactory)
    {
        $this->complexAttributeFactory = $complexAttributeFactory;
    }
    
    public function setUnitConverter(UnitConverter $converter)
    {
        $this->unitConverter = $converter;
    }

    /**
     * Reset Document state
     */
    public function initialize()
    {
        $this->processed = false;
        $this->engine->reset();
    }
    
    //TODO: this is only test and temporary method
    public function setEngine($engine)
    {
        $this->engine = $engine;
    }
    
    public function getEngine()
    {
        return $this->engine;
    }
    
    /**
     * @return PHPPdf\Font\Registry
     */
    public function getFontRegistry()
    {
        if($this->fontRegistry === null)
        {
            $this->fontRegistry = new FontRegistry($this);
        }

        return $this->fontRegistry;
    }

    public function setFontRegistry(FontRegistry $registry)
    {
        $this->fontRegistry = $registry;
    }

    /**
     * Invokes drawing procedure.
     *
     * Formats each of node, retreives drawing tasks and executes them.
     *
     * @param array $pages Array of pages to draw
     */
    public function draw($pages)
    {
        if($this->isProcessed())
        {
            throw new LogicException(sprintf('Pdf has alredy been drawed.'));
        }

        $this->processed = true;


        if(is_array($pages))
        {
            $pageCollection = new PageCollection();

            foreach($pages as $page)
            {
                if(!$page instanceof Page)
                {
                    throw new DrawingException(sprintf('Not all elements of passed array are PHPPdf\Core\Node\Page type. One of them is "%s".', get_class($page)));
                }

                $pageCollection->add($page);
            }
        }
        elseif($pages instanceof PageCollection)
        {
            $pageCollection = $pages;
        }
        else
        {
            throw new InvalidArgumentException(sprintf('Argument of draw method must be an array of pages or PageCollection object, "%s" given.', get_class($pages)));
        }

        $pageCollection->format($this);

        $tasks = $pageCollection->getAllDrawingTasks($this);
        $this->invokeTasks($tasks);
    }

    public function invokeTasks($tasks)
    {
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }

    public function addDrawingTask(Callable $callable, $priority = self::DRAWING_PRIORITY_FOREGROUND3)
    {
        $this->drawingTasksQueue->insert($callable, $priority);
    }

    public function isProcessed()
    {
        return $this->processed;
    }

    /**
     * @param string $className Formatter class name
     * @return PHPPdf\Core\Formatter\Formatter
     */
    public function getFormatter($className)
    {
        if(!isset($this->formatters[$className]))
        {
            $this->formatters[$className] = $this->createFormatter($className);
        }

        return $this->formatters[$className];
    }

    private function createFormatter($className)
    {
        try
        {
            $class = new \ReflectionClass($className);
            $formatter = $class->newInstance();

            if(!$formatter instanceof Formatters\Formatter)
            {
                throw new RuntimeException(sprintf('Class "%s" dosn\'t implement PHPPdf\Formatrer\Formatter interface.', $className));
            }

            return $formatter;
        }
        catch(\ReflectionException $e)
        {
            throw new RuntimeException(sprintf('Class "%s" dosn\'t exist or haven\'t default constructor.', $className), 0, $e);
        }
    }
    
    public function createGraphicsContext($size, $encoding)
    {
        return $this->engine->createGraphicsContext($size, $encoding);
    }
    
    public function attachGraphicsContext(GraphicsContext $gc)
    {
        $this->engine->attachGraphicsContext($gc);
    }
    
    public function getAttachedGraphicsContexts()
    {
        return $this->engine->getAttachedGraphicsContexts();
    }

    public function createImage($path)
    {
        return $this->engine->createImage($path);
    }
    
    public function createFont($data)
    {
        foreach($data as $name => $value)
        {
            foreach($this->stringFilters as $filter)
            {
                $value = $filter->filter($value);
            }

			$data[$name] = $value;
        }
        
        return $this->engine->createFont($data);
    }
    
    public function loadEngine($file, $encoding)
    {
        return $this->engine->loadEngine($file, $encoding);
    }
    
    public function setMetadataValue($name, $value)
    {
        $this->engine->setMetadataValue($name, $value);
    }
    
    public function getFont($name)
    {
        return $this->getFontRegistry()->get($name);
    }
    
    public function setFontDefinition($name, array $definition)
    {
        $this->getFontRegistry()->register($name, $definition);
    }
    
    public function addFontDefinitions(array $definitions)
    {
        foreach($definitions as $name => $definition)
        {
            $this->setFontDefinition($name, $definition);
        }
    }
    
    public function convertUnit($value, $unit = null)
    {
        if($this->unitConverter)
        {
            return $this->unitConverter->convertUnit($value);
        }

        return $value;
    }
    
    public function convertPercentageValue($value, $width)
    {
        if($this->unitConverter)
        {
            return $this->unitConverter->convertPercentageValue($value, $width);
        }
        
        return $value;
    }

    /**
     * @return string Content of document
     */
    public function render()
    {
        return $this->engine->render();
    }
    
    public function reset()
    {
        $this->initialize();
    }

    public function setColorPalette(ColorPalette $colorPalette)
    {
        $this->colorPalette = $colorPalette;
    }
    
	public function addColorsToPalette(array $colors)
	{
		$this->colorPalette->merge($colors);
	}
	
	public function getColorFromPalette($color)
	{
	    if($this->colorPalette)
	    {
    	    return $this->colorPalette->get($color);
	    }
	    
	    return $color;
	}
}