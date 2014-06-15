<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Exception\LogicException;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\Document;
use PHPPdf\Core\DrawingTask;
use PHPPdf\Core\UnitConverter;
use PHPPdf\Core\Engine\GraphicsContext;
use PHPPdf\Core\Point;
use PHPPdf\Core\Formatter\Formatter;

/**
 * Single pdf page
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Page extends Container
{
    const ATTR_SIZE = 'page-size';

    const SIZE_A10 = '74:105';
    const SIZE_A10_LANDSCAPE = '105:74';
    const SIZE_A9 = '105:147';
    const SIZE_A9_LANDSCAPE = '147:105';
    const SIZE_A8 = '147:210';
    const SIZE_A8_LANDSCAPE = '210:147';
    const SIZE_A7 = '210:298';
    const SIZE_A7_LANDSCAPE = '298:210';
    const SIZE_A6 = '298:410';
    const SIZE_A6_LANDSCAPE = '410:298';
    const SIZE_A5 = '410:595';
    const SIZE_A5_LANDSCAPE = '595:410';
    const SIZE_A4 = '595:842';
    const SIZE_A4_LANDSCAPE = '842:595';
    const SIZE_A3 = '842:1191';
    const SIZE_A3_LANDSCAPE = '1191:842';
    const SIZE_A2 = '1191:1684';
    const SIZE_A2_LANDSCAPE = '1684:1191';
    const SIZE_A1 = '1684:2384';
    const SIZE_A1_LANDSCAPE = '2384:1684';
    const SIZE_A0 = '2384:3370';
    const SIZE_A0_LANDSCAPE = '3370:2384';
    const SIZE_2A0 = '3370:4768';
    const SIZE_2A0_LANDSCAPE = '4768:3370';
    const SIZE_4A0 = '4768:6741';
    const SIZE_4A0_LANDSCAPE = '6741:4768';

    const SIZE_B10 = '88:125';
    const SIZE_B10_LANDSCAPE = '125:88';
    const SIZE_B9 = '125:176';
    const SIZE_B9_LANDSCAPE = '176:125';
    const SIZE_B8 = '176:249';
    const SIZE_B8_LANDSCAPE = '249:176';
    const SIZE_B7 = '249:354';
    const SIZE_B7_LANDSCAPE = '354:249';
    const SIZE_B6 = '354:499';
    const SIZE_B6_LANDSCAPE = '499:354';
    const SIZE_B5 = '499:709';
    const SIZE_B5_LANDSCAPE = '709:499';
    const SIZE_B4 = '709:1001';
    const SIZE_B4_LANDSCAPE = '1001:709';
    const SIZE_B3 = '1001:1417';
    const SIZE_B3_LANDSCAPE = '1417:1001';
    const SIZE_B2 = '1417:2004';
    const SIZE_B2_LANDSCAPE = '2004:1417';
    const SIZE_B1 = '2004:2835';
    const SIZE_B1_LANDSCAPE = '2835:2004';
    const SIZE_B0 = '2835:4008';
    const SIZE_B0_LANDSCAPE = '4008:2835';

    const SIZE_C10 = '79:113';
    const SIZE_C10_LANDSCAPE = '113:79';
    const SIZE_C9 = '113:161';
    const SIZE_C9_LANDSCAPE = '161:113';
    const SIZE_C8 = '161:230';
    const SIZE_C8_LANDSCAPE = '230:161';
    const SIZE_C7 = '230:323';
    const SIZE_C7_LANDSCAPE = '323:230';
    const SIZE_C6 = '323:459';
    const SIZE_C6_LANDSCAPE = '459:323';
    const SIZE_C5 = '459:649';
    const SIZE_C5_LANDSCAPE = '649:459';
    const SIZE_C4 = '649:918';
    const SIZE_C4_LANDSCAPE = '918:649';
    const SIZE_C3 = '918:1298';
    const SIZE_C3_LANDSCAPE = '1298:918';
    const SIZE_C2 = '1298:1837';
    const SIZE_C2_LANDSCAPE = '1837:1298';
    const SIZE_C1 = '1837:2599';
    const SIZE_C1_LANDSCAPE = '2599:1837';
    const SIZE_C0 = '2599:3677';
    const SIZE_C0_LANDSCAPE = '3677:2599';

    const SIZE_LETTER = '612:792';
    const SIZE_LETTER_LANDSCAPE = '792:612';
    const SIZE_LEGAL = '612:1008';
    const SIZE_LEGAL_LANDSCAPE = '1008:612';

    private $graphicsContext;

    /**
     * @var Node
     */
    private $footer;

    /**
     * @var Node
     */
    private $header;

    /**
     * @var Node
     */
    private $watermark;

    /**
     * @var PageContext;
     */
    private $context;

    private $runtimeNodes = array();

    private $preparedTemplate = false;

    public function __construct(array $attributes = array(), UnitConverter $converter = null)
    {
        parent::__construct($attributes, $converter);

        $this->initializeBoundary();
        $this->initializePlaceholders();
    }

    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();

        static::addAttribute(self::ATTR_SIZE);
        static::addAttribute('page-size', self::SIZE_A4);
        static::addAttribute('encoding', 'utf-8');
        static::addAttribute('static-size', true);
        static::addAttribute('text-align', self::ALIGN_LEFT);
        static::addAttribute('text-decoration', self::TEXT_DECORATION_NONE);
        static::addAttribute('alpha', 1);
        static::addAttribute('document-template');
    }

    public function initialize()
    {
        parent::initialize();

        $this->setAttribute('page-size', self::SIZE_A4);
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
        $this->setWatermark(new Container(array('height' => 0)));
    }

    public function setPageSize($param1, $param2 = null)
    {
        if($param2 === null)
        {
            list($width, $height) = $this->getPageDimensions($param1);
        }
        else
        {
            $width = $param1;
            $height = $param2;
        }

        $width = $this->convertUnit($width);
        $height = $this->convertUnit($height);
        $this->setAttributeDirectly('width', $width);
        $this->setAttributeDirectly('height', $height);

        $this->setAttributeDirectly('page-size', $width.':'.$height);

        $this->initializeBoundary();

        return $this;
    }

    private function getPageDimensions($pageSize)
    {
        $const = 'PHPPdf\Core\Node\Page::SIZE_'.strtoupper(str_replace(array('-', ' '), '_', $pageSize));

        if(defined($const))
        {
            $pageSize = constant($const);
        }

        $sizes = explode(':', $pageSize);

        if(count($sizes) < 2)
        {
            throw new InvalidArgumentException(sprintf('page-size attribute should be in "width:height" format, "%s" given.', $pageSize));
        }

        return $sizes;
    }

    public function setWidth($width)
    {
        parent::setWidth($width);

        $height = $this->getAttributeDirectly('height');
        $width = $this->getAttributeDirectly('width');
        $this->setPageSize($width, $height);
    }

    public function setHeight($height)
    {
        parent::setHeight($height);

        $width = $this->getAttributeDirectly('width');
        $height = $this->getAttributeDirectly('height');

        $this->setPageSize($width, $height);

        $this->initializeBoundary();
    }

    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        $this->prepareGraphicsContext($document);

        $document->attachGraphicsContext($this->getGraphicsContext());

        if(!$this->preparedTemplate)
        {
            foreach($this->getTemplateDrawingTasksAndFormatPlaceholders($document) as $task)
            {
                $tasks->insert($task);
            }
        }

        parent::doDraw($document, $tasks);
    }

    public function collectPostDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
        foreach($this->runtimeNodes as $node)
        {
            $node->evaluate();
            $node->collectOrderedDrawingTasks($document, $tasks);
        }
    }

    private function prepareGraphicsContext(Document $document)
    {
        $this->assignGraphicsContextIfIsNull($document);
    }

    private function assignGraphicsContextIfIsNull(Document $document)
    {
        if($this->graphicsContext === null)
        {
            $this->setGraphicsContext($document->createGraphicsContext($this->getRealWidth().':'.$this->getRealHeight(), $this->getEncoding()));
            $this->setGraphicsContextDefaultStyle($document);
        }
    }

    protected function setGraphicsContext(GraphicsContext $gc)
    {
        $this->graphicsContext = $gc;
    }

    /**
     * @return GraphicsContext
     */
    public function getGraphicsContext()
    {
        return $this->graphicsContext;
    }

    private function setGraphicsContextDefaultStyle(Document $document)
    {
        $font = $this->getFont($document);
        if($font && $this->getAttribute('font-size'))
        {
            $this->graphicsContext->setFont($font, $this->getAttribute('font-size'));
        }

        $blackColor = '#000000';
        $this->graphicsContext->setFillColor($blackColor);
        $this->graphicsContext->setLineColor($blackColor);
    }

    public function getPage()
    {
        return $this;
    }

    public function getParent()
    {
        return null;
    }

    public function breakAt($height)
    {
        throw new LogicException('Page can\'t be broken.');
    }

    public function copy()
    {
        $boundary = clone $this->getBoundary();
        $copy = parent::copy();

        if($this->graphicsContext)
        {
            $graphicsContext = $this->getGraphicsContext();
            $clonedGraphicsContext = $graphicsContext->copy();
            $copy->graphicsContext = $clonedGraphicsContext;
        }

        $copy->setBoundary($boundary);

        foreach($this->runtimeNodes as $index => $node)
        {
            $clonedNode = $node->copyAsRuntime();
            $clonedNode->setPage($copy);
            $copy->runtimeNodes[$index] = $clonedNode;
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
        $value = $this->convertUnit($value);

        $diff = $value - $this->getAttribute($name);

        $this->translateMargin($name, $diff);

        $this->setAttributeDirectly($name, $value);

        return $this;
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

        $footer->getBoundary()->setNext($boundary->getPoint(3))
                              ->setNext($boundary->getPoint(2))
                              ->setNext($boundary->getPoint(2)->translate(0, $height))
                              ->setNext($boundary->getPoint(3)->translate(0, $height))
                              ->close();

        $this->footer = $footer;
    }

    private function throwExceptionIfHeightIsntSet(Container $contaienr)
    {
        $height = $contaienr->getHeight();

        if($height === null || !is_numeric($height))
        {
            throw new InvalidArgumentException('Height of header and footer must be set.');
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

        $header->getBoundary()->setNext($boundary->getPoint(0)->translate(0, -$height))
                              ->setNext($boundary->getPoint(1)->translate(0, -$height))
                              ->setNext($boundary->getPoint(1))
                              ->setNext($boundary->getPoint(0))
                              ->close();

        $this->header = $header;
    }

    public function setWatermark(Container $watermark)
    {
        $watermark->setParent($this);
        $watermark->setAttribute('vertical-align', self::VERTICAL_ALIGN_MIDDLE);
        $watermark->setHeight($this->getHeight());
        $watermark->setWidth($this->getWidth());

        $watermark->setBoundary(clone $this->getBoundary());

        $this->watermark = $watermark;
    }

    protected function getHeader()
    {
        return $this->header;
    }

    protected function getFooter()
    {
        return $this->footer;
    }

    protected function getWatermark()
    {
        return $this->watermark;
    }

    public function prepareTemplate(Document $document)
    {
        $this->prepareGraphicsContext($document);

        $tasks = $this->getTemplateDrawingTasksAndFormatPlaceholders($document);

        $document->invokeTasks($tasks);

        $this->preparedTemplate = true;
    }

    private function getTemplateDrawingTasksAndFormatPlaceholders(Document $document)
    {
        $this->formatConvertAttributes($document);

        $this->getHeader()->format($document);
        $this->getFooter()->format($document);
        $this->getWatermark()->format($document);

        $tasks = new DrawingTaskHeap();

        $this->getDrawingTasksFromComplexAttributes($document, $tasks);
        $this->footer->collectOrderedDrawingTasks($document, $tasks);
        $this->header->collectOrderedDrawingTasks($document, $tasks);
        $this->watermark->collectOrderedDrawingTasks($document, $tasks);

        $this->footer->removeAll();
        $this->header->removeAll();
        $this->watermark->removeAll();

        return $tasks;
    }

    protected function preDraw(Document $document, DrawingTaskHeap $tasks)
    {
    }

    private function formatConvertAttributes(Document $document)
    {
        $formatterName = 'PHPPdf\Core\Formatter\ConvertAttributesFormatter';

        $formatter = $document->getFormatter($formatterName);
        $formatter->format($this, $document);
    }

    public function getContext()
    {
        if($this->context === null)
        {
            throw new LogicException('PageContext has not been set.');
        }

        return $this->context;
    }

    public function setContext(PageContext $context)
    {
        $this->context = $context;
    }

    public function markAsRuntimeNode(Runtime $node)
    {
        $this->runtimeNodes[] = $node;
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

    public function setPlaceholder($name, Node $node)
    {
        switch($name)
        {
            case 'footer':
                return $this->setFooter($node);
            case 'header':
                return $this->setHeader($node);
            case 'watermark':
                return $this->setWatermark($node);
            default:
                parent::setPlaceholder($name, $node);
        }
    }

    public function hasPlaceholder($name)
    {
        return in_array($name, array('footer', 'header', 'watermark'));
    }

    public function unserialize($serialized)
    {
        parent::unserialize($serialized);
        $this->initializePlaceholders();
    }

    public function setGraphicsContextFromSourceDocumentIfNecessary(Document $document)
    {
        $gc = $this->getGraphicsContextFromSourceDocument($document);

        if($gc !== null)
        {
            $gc = $gc->copy();

            $this->setGraphicsContext($gc);
            $this->setPageSize($gc->getWidth().':'.$gc->getHeight());
            $this->setGraphicsContextDefaultStyle($document);
        }
    }

    protected function beforeFormat(Document $document)
    {
        $this->setGraphicsContextFromSourceDocumentIfNecessary($document);
    }

    protected function getGraphicsContextFromSourceDocument(Document $document)
    {
        $fileOfSourcePage = $this->getAttribute('document-template');

        if($fileOfSourcePage)
        {
            $engine = $document->loadEngine($fileOfSourcePage, $this->getEncoding());

            $graphicsContexts = $engine->getAttachedGraphicsContexts();

            $count = count($graphicsContexts);

            if($count == 0)
            {
                return null;
            }

            $pageContext = $this->context;
            $index = ($pageContext ? ($pageContext->getPageNumber()-1) : 0) % $count;

            return $graphicsContexts[$index];
        }

        return null;
    }

    public function flush()
    {
        $placeholders = array('footer', 'header', 'watermark');
        foreach($placeholders as $placeholder)
        {
            if($this->$placeholder)
            {
                $this->$placeholder->flush();
                $this->$placeholder = null;
            }
        }

        parent::flush();
    }

    public function removeGraphicsContext()
    {
        $this->graphicsContext = null;
    }
}