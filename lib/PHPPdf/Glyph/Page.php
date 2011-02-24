<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document,
    PHPPdf\Util\DrawingTask,
    PHPPdf\Formatter\Formatter;

require_once 'Zend/Pdf.php';

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

        $this->getBoundary()->setNext(0, $this->getHeight())
                            ->setNext($this->getWidth(), $this->getHeight())
                            ->setNext($this->getWidth(), 0)
                            ->setNext(0, 0)
                            ->close();

        $this->setFooter(new Container(array('height' => 0)));
        $this->setHeader(new Container(array('height' => 0)));
    }

    public function initialize()
    {
        parent::initialize();

        $this->addAttribute(self::ATTR_SIZE);       
        $this->setPageSize(self::SIZE_A4);
        $this->addAttribute('encoding', 'utf-8');
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

    protected function preDraw(Document $document)
    {
        foreach($this->getEnhancements() as $enhancement)
        {
            $callback = array($enhancement, 'enhance');
            $args = array($this->getPage(), $this);
            $this->addDrawingTask(new DrawingTask($callback, $args, $enhancement->getPriority()));
        }
    }

    public function split($height)
    {
        throw new \LogicException('Page can\'t be splitted.');
    }

    public function copy()
    {
        $boundary = clone $this->getBoundary();
        $copy = parent::copy();
        
        $graphicsContext = $this->getGraphicsContext();
        $clonedGraphicsContext = clone $graphicsContext;
        $copy->graphicsContext = $clonedGraphicsContext;

        $copy->setBoundary($boundary);

        foreach($this->runtimeGlyphs as $index => $glyph)
        {
            $clonedGlyph = $glyph->copy();
            $clonedGlyph->setPage($copy);
            $copy->runtimeGlyphs[$index] = $clonedGlyph;
        }

        return $copy;
    }

    public function getHeight()
    {
        $verticalMargins = $this->getMarginTop() + $this->getMarginBottom();

        return (parent::getHeight() - $verticalMargins);
    }

    public function getWidth()
    {
        $horizontalMargins = $this->getMarginLeft() + $this->getMarginRight();

        return (parent::getWidth() - $horizontalMargins);
    }

    public function getRealHeight()
    {
        return parent::getHeight();
    }

    public function getRealWidth()
    {
        return parent::getWidth();
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
            $boundary->pointTranslate($index, $x, $y);
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

    public function format(array $formatters)
    {
        $acceptFormatters = array();
        foreach($formatters as $formatter)
        {
            if($formatter instanceof \PHPPdf\Formatter\ContainerFormatter || $formatter instanceof \PHPPdf\Formatter\ConvertDimensionFormatter || $formatter instanceof \PHPPdf\Formatter\FloatFormatter)
            {
                $acceptFormatters[] = $formatter;
            }
        }

        parent::format($acceptFormatters);
    }

    public function prepareTemplate(Document $document)
    {
        $formatters = $document->getFormatters();

        foreach($formatters as $formatter)
        {
            if($formatter instanceof \PHPPdf\Formatter\ConvertDimensionFormatter)
            {
                parent::format(array($formatter));
            }
        }
        
        $this->getHeader()->format($formatters);
        $this->getFooter()->format($formatters);

        $tasks = array();

        $tasks = array_merge($tasks, $this->footer->getDrawingTasks($document));
        $tasks = array_merge($tasks, $this->header->getDrawingTasks($document));

        $this->footer->removeAll();
        $this->header->removeAll();

        $document->invokeTasks($tasks);

        $this->preparedTemplate = true;
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
}