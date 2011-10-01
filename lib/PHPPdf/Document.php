<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf;

use PHPPdf\Util\UnitConverter,
    PHPPdf\Node\Node,
    PHPPdf\Engine\ZF\Engine as ZfEngine,
    PHPPdf\Formatter as Formatters,
    PHPPdf\Node\Page,
    PHPPdf\Node\PageCollection,
    PHPPdf\Enhancement\EnhancementBag,
    PHPPdf\Font\Registry as FontRegistry,
    PHPPdf\Enhancement\Factory as EnhancementFactory,
    PHPPdf\Exception\DrawingException,
    PHPPdf\Engine\Engine,
    PHPPdf\Engine\GraphicsContext,
    PHPPdf\Util\DrawingTaskHeap;

/**
 * Document to generate
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 * @todo Inject Engine object form outside
 */
class Document implements UnitConverter, Engine
{   
    const DRAWING_PRIORITY_BACKGROUND1 = 60;
    const DRAWING_PRIORITY_BACKGROUND2 = 50;
    const DRAWING_PRIORITY_BACKGROUND3 = 40;

    const DRAWING_PRIORITY_FOREGROUND1 = 10;
    const DRAWING_PRIORITY_FOREGROUND2 = 20;
    const DRAWING_PRIORITY_FOREGROUND3 = 30;

    private $processed = false;

    /**
     * @var PHPPdf\Engine\Engine
     */
    private $engine;

    private $enhancementFactory = null;

    private $fontRegistry = null;

    private $formatters = array();
    
    private $unitConverter = null;

    public function __construct(UnitConverter $converter = null)
    {
        if($converter)
        {
            $this->setUnitConverter($converter);
        }
        $this->initialize();
    }

    /**
     * Create enhancements objects depends on bag content
     * 
     * @return array Array of Enhancement objects
     */
    public function getEnhancements(EnhancementBag $bag)
    {
        $enhancements = array();

        if($this->enhancementFactory !== null)
        {
            foreach($bag->getAll() as $id => $parameters)
            {
                if(!isset($parameters['name']))
                {
                    throw new \InvalidArgumentException('"name" attribute is required.');
                }

                $name = $parameters['name'];
                unset($parameters['name']);
                $enhancements[] = $this->enhancementFactory->create($name, $parameters);
            }
        }

        return $enhancements;
    }

    public function setEnhancementFactory(EnhancementFactory $enhancementFactory)
    {
        $this->enhancementFactory = $enhancementFactory;
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
        $this->engine = new ZfEngine(null, $this->unitConverter);
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
            throw new \LogicException(sprintf('Pdf has alredy been drawed.'));
        }

        $this->processed = true;


        if(is_array($pages))
        {
            $pageCollection = new PageCollection();

            foreach($pages as $page)
            {
                if(!$page instanceof Page)
                {
                    throw new DrawingException(sprintf('Not all elements of passed array are PHPPdf\Node\Page type. One of them is "%s".', get_class($page)));
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
            throw new \InvalidArgumentException(sprintf('Argument of draw method must be an array of pages or PageCollection object, "%s" given.', get_class($pages)));
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
     * @return PHPPdf\Formatter\Formatter
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
                throw new \PHPPdf\Exception\Exception(sprintf('Class "%s" dosn\'t implement PHPPdf\Formatrer\Formatter interface.', $className));
            }

            return $formatter;
        }
        catch(\ReflectionException $e)
        {
            throw new \PHPPdf\Exception\Exception(sprintf('Class "%s" dosn\'t exist or haven\'t default constructor.', $className), 0, $e);
        }
    }
    
    public function createGraphicsContext($size)
    {
        return $this->engine->createGraphicsContext($size);
    }
    
    public function attachGraphicsContext(GraphicsContext $gc)
    {
        $this->engine->attachGraphicsContext($gc);
    }
    
    public function getAttachedGraphicsContexts()
    {
        return $this->engine->getAttachedGraphicsContexts();
    }
    
    public function createColor($color)
    {
        return $this->engine->createColor($color);
    }
    
    public function createImage($path)
    {
        return $this->engine->createImage($path);
    }
    
    public function createFont($data)
    {
        foreach($data as $name => $value)
        {
            if(strpos($value, '/') !== false)
            {
                $value = str_replace('%resources%', __DIR__.'/Resources', $value);
                $data[$name] = $value;
            }
        }
        
        return $this->engine->createFont($data);
    }
    
    public function loadEngine($file)
    {
        return $this->engine->loadEngine($file);
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
}