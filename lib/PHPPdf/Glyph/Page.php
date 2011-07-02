<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

use PHPPdf\Document,
    PHPPdf\Util\DrawingTask,
    PHPPdf\Util\Point,
    PHPPdf\Formatter\Formatter;

/**
 * Single pdf page
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Page extends Container
{
    const ATTR_SIZE = 'page-size';
    const SIZE_A4 = \Zend_Pdf_Page::SIZE_A4;

    private $graphicsContext;

    /**
     * @var PHPPdf\Glyph\Glyph
     */
    private $footer;

    /**
     * @var PHPPdf\Glyph\Glyph
     */
    private $header;

    /**
     * @var PHPPdf\Glyph\PageContext;
     */
    private $context;

    private $runtimeGlyphs = array();

    private $preparedTemplate = false;

    public function  __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        $this->initializeBoundary();
        $this->initializePlaceholders();
    }

    public function initialize()
    {
        parent::initialize();

        $this->addAttribute(self::ATTR_SIZE);       
        $this->addAttribute('page-size', null, null, 'setPageSize');
        $this->setAttribute('page-size', self::SIZE_A4);
        $this->addAttribute('encoding', 'utf-8');
        $this->addAttribute('static-size', true);
    }

    protected static function initializeType()
    {
        parent::initializeType();
        static::setAttributeSetters(array('page-size' => 'setPageSize'));
    }
    
    private function initializeBoundary()
    {
        $width = $this->getRealWidth();
        $height = $this->getRealHeight();
        
        $boundary = $this->getBoundary();
        if($boundary->isClosed())
        {
            $boundary->reset();
        }
        
        $boundary->setNext(0, $height)
                 ->setNext($width, $height)
                 ->setNext($width, 0)
                 ->setNext(0, 0)
                 ->close();
                 
        foreach(array('margin-top', 'margin-bottom', 'margin-left', 'margin-right') as $name)
        {
            $value = $this->getAttribute($name);
            $this->translateMargin($name, $value);
        }
    }

    private function initializePlaceholders()
    {
        $this->setFooter(new Container(array('height' => 0)));
        $this->setHeader(new Container(array('height' => 0)));
    }

    public function setPageSize($pageSize)
    {
        $sizes = explode(':', $pageSize);

        if(count($sizes) < 2)
        {
            throw new \InvalidArgumentException(sprintf('page-size attribute should be in "width:height" format, "%s" given.', $pageSize));
        }

        list($width, $height) = $sizes;

        $this->setWidth($width);
        $this->setHeight($height);

        $this->setAttributeDirectly('page-size', $pageSize);
        
        $this->initializeBoundary();

        return $this;
    }

    protected function doDraw(Document $document)
    {
        $graphicsContext = $this->getGraphicsContext();

        if($graphicsContext->getStyle() === null)
        {
            $this->setGraphicsContextDefaultStyle();
        }

        $document->getDocumentEngine()->pages[] = $graphicsContext->getPage();

        if(!$this->preparedTemplate)
        {
            $this->prepareTemplate($document);
        }
        
        parent::doDraw($document);

        foreach($this->runtimeGlyphs as $glyph)
        {
            $glyph->evaluate();
            $tasks = $glyph->getDrawingTasks($document);

            foreach($tasks as $task)
            {
                $this->addDrawingTask($task);
            }
        }
    }

    /**
     * @return GraphicsContext
     */
    public function getGraphicsContext()
    {
        if($this->graphicsContext === null)
        {
            $page = new \Zend_Pdf_Page($this->getAttribute(self::ATTR_SIZE));

            $this->createGraphicsContext($page);
        }

        return $this->graphicsContext;
    }

    private function createGraphicsContext(\Zend_Pdf_Page $page)
    {
        $this->graphicsContext = new GraphicsContext($page);
    }

    private function setGraphicsContextDefaultStyle()
    {
        $font = $this->getFont();
        if($font && $this->getAttribute('font-size'))
        {
            $this->graphicsContext->setFont($font, $this->getAttribute('font-size'));
        }

        $defaultStyle = new \Zend_Pdf_Style();
        $color = new \Zend_Pdf_Color_Rgb(0, 0, 0);
        $defaultStyle->setFillColor($color);
        $defaultStyle->setLineColor($color);

        $this->graphicsContext->setStyle($defaultStyle);
    }

    public function getPage()
    {
        return $this;
    }

    public function getParent()
    {
        return null;
    }

    public function split($height)
    {
        throw new \LogicException('Page can\'t be splitted.');
    }

    public function copy()
    {
        $boundary = clone $this->getBoundary();
        $copy = parent::copy();
        
        if($this->graphicsContext)
        {
            $graphicsContext = $this->getGraphicsContext();
            $clonedGraphicsContext = clone $graphicsContext;
            $copy->graphicsContext = $clonedGraphicsContext;
        }

        $copy->setBoundary($boundary);

        foreach($this->runtimeGlyphs as $index => $glyph)
        {
            $clonedGlyph = $glyph->copy();
            $clonedGlyph->setPage($copy);
            $copy->runtimeGlyphs[$index] = $clonedGlyph;
        }

        return $copy;
    }

    /**
     * @return int Height without vertical margins
     */
    public function getHeight()
    {
        $verticalMargins = $this->getMarginTop() + $this->getMarginBottom();

        return (parent::getHeight() - $verticalMargins);
    }

    /**
     * @return int Width without horizontal margins
     */
    public function getWidth()
    {
        $horizontalMargins = $this->getMarginLeft() + $this->getMarginRight();

        return (parent::getWidth() - $horizontalMargins);
    }

    /**
     * @return int Height with vertical margins
     */
    public function getRealHeight()
    {
        return parent::getHeight();
    }

    /**
     * @return int Width with horizontal margins
     */
    public function getRealWidth()
    {
        return parent::getWidth();
    }
    
    public function getRealBoundary()
    {
        $boundary = clone $this->getBoundary();
        $boundary->pointTranslate(0, -$this->getMarginLeft(), -$this->getMarginTop());
        $boundary->pointTranslate(1, $this->getMarginRight(), -$this->getMarginTop());
        $boundary->pointTranslate(2, $this->getMarginRight(), $this->getMarginBottom());
        $boundary->pointTranslate(3, -$this->getMarginLeft(), $this->getMarginBottom());
        $boundary->pointTranslate(4, -$this->getMarginLeft(), -$this->getMarginTop());
        
        return $boundary;
    }

    protected function setMarginAttribute($name, $value)
    {
        $value = (int) $value;

        $diff = $value - $this->getAttribute($name);

        $this->translateMargin($name, $diff);

        return parent::setMarginAttribute($name, $value);
    }

    private function translateMargin($name, $value)
    {
        $boundary = $this->getBoundary();
        $x = $y = 0;
        if($name == 'margin-left')
        {
            $indexes = array(0, 3, 4);
            $x = $value;
        }
        elseif($name == 'margin-right')
        {
            $indexes = array(1, 2);
            $x = -$value;
        }
        elseif($name == 'margin-top')
        {
            $indexes = array(0, 1, 4);
            $y = $value;
        }
        else
        {
            $indexes = array(2, 3);
            $y = -$value;
        }

        foreach($indexes as $index)
        {
            if(isset($boundary[$index]))
            {
                $boundary->pointTranslate($index, $x, $y);
            }
        }
    }

    public function setFooter(Container $footer)
    {
        $this->throwExceptionIfHeightIsntSet($footer);
        $footer->setAttribute('static-size', true);
        $footer->setParent($this);

        $boundary = $this->getBoundary();
        $height = $footer->getHeight();

        $this->setMarginBottom($this->getMarginBottom() + $height);
        $footer->setWidth($this->getWidth());

        $footer->getBoundary()->setNext($boundary[3])
                              ->setNext($boundary[2])
                              ->setNext($boundary[2]->translate(0, $height))
                              ->setNext($boundary[3]->translate(0, $height))
                              ->close();

        $this->footer = $footer;
    }

    private function throwExceptionIfHeightIsntSet(Container $contaienr)
    {
        $height = $contaienr->getHeight();

        if($height === null || !is_numeric($height))
        {
            throw new \InvalidArgumentException('Height of header and footer must be set.');
        }
    }

    public function setHeader(Container $header)
    {
        $this->throwExceptionIfHeightIsntSet($header);
        $header->setAttribute('static-size', true);
        
        $header->setParent($this);

        $boundary = $this->getBoundary();
        $height = $header->getHeight();

        $this->setMarginTop($this->getMarginTop() + $height);
        $header->setWidth($this->getWidth());

        $header->getBoundary()->setNext($boundary[0]->translate(0, -$height))
                              ->setNext($boundary[1]->translate(0, -$height))
                              ->setNext($boundary[1])
                              ->setNext($boundary[0])
                              ->close();

        $this->header = $header;
    }

    protected function getHeader()
    {
        return $this->header;
    }

    protected function getFooter()
    {
        return $this->footer;
    }

    public function prepareTemplate(Document $document)
    {
        $this->formatConvertAttributes($document);
        
        $this->getHeader()->format($document);
        $this->getFooter()->format($document);

        $tasks = array();

        $tasks = array_merge($tasks, $this->footer->getDrawingTasks($document));
        $tasks = array_merge($tasks, $this->header->getDrawingTasks($document));

        $this->footer->removeAll();
        $this->header->removeAll();

        $document->invokeTasks($tasks);

        $this->preparedTemplate = true;
    }

    private function formatConvertAttributes(Document $document)
    {
        $formatterName = 'PHPPdf\Formatter\ConvertAttributesFormatter';

        $formatter = $document->getFormatter($formatterName);
        $formatter->format($this, $document);
    }

    public function getContext()
    {
        if($this->context === null)
        {
            throw new \LogicException('PageContext has not been set.');
        }

        return $this->context;
    }

    public function setContext(PageContext $context)
    {
        $this->context = $context;
    }

    public function markAsRuntimeGlyph(Runtime $glyph)
    {
        $this->runtimeGlyphs[] = $glyph;
    }

    public function getPlaceholder($name)
    {
        if($name === 'footer')
        {
            return $this->getFooter();
        }
        elseif($name === 'header')
        {
            return $this->getHeader();
        }

        return null;
    }

    public function setPlaceholder($name, Glyph $glyph)
    {
        if($name === 'footer')
        {
            return $this->setFooter($glyph);
        }
        elseif($name === 'header')
        {
            return $this->setHeader($glyph);
        }

        parent::setPlaceholder($name, $glyph);
    }

    public function hasPlaceholder($name)
    {
        return in_array($name, array('footer', 'header'));
    }

    public function unserialize($serialized)
    {
        parent::unserialize($serialized);

        $this->initializePlaceholders();
    }
}