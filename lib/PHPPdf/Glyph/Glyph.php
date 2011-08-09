<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph;

use PHPPdf\Glyph\Behaviour\Behaviour;

use PHPPdf\Glyph\Behaviour\GoToUrl;

use PHPPdf\Exception\UnregisteredGlyphException;

use PHPPdf\Exception\InvalidAttributeException;

use PHPPdf\Util\Point;

use PHPPdf\Document,
    PHPPdf\Util,
    PHPPdf\Glyph\Container,
    PHPPdf\Util\Boundary,
    PHPPdf\Util\DrawingTask,
    PHPPdf\Enhancement\EnhancementBag,
    PHPPdf\Formatter\Formatter,
    PHPPdf\Util\GlyphIterator;

/**
 * Base glyph class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class Glyph implements Drawable, GlyphAware, \ArrayAccess, \Serializable
{
    const DISPLAY_BLOCK = 'block';
    const DISPLAY_INLINE = 'inline';
    const DISPLAY_NONE = 'none';
    const MARGIN_AUTO = 'auto';
    const FLOAT_NONE = 'none';
    const FLOAT_LEFT = 'left';
    const FLOAT_RIGHT = 'right';
    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';
    const ALIGN_CENTER = 'center';
    const VERTICAL_ALIGN_TOP = 'top';
    const VERTICAL_ALIGN_MIDDLE = 'middle';
    const VERTICAL_ALIGN_BOTTOM = 'bottom';
    
    private static $attributeSetters = array();
    private static $attributeGetters = array();
    private static $initialized = array();

    private $attributes = array();
    private $attributesSnapshot = null;
    private $priority = 0;

    private $parent = null;
    private $hadAutoMargins = false;
    private $relativeWidth = null;

    private $boundary = null;

    private $enhancements = array();
    private $enhancementBag = null;
    private $drawingTasks = array();
    private $formattersNames = array();
    
    private $behaviours = array();

    public function __construct(array $attributes = array())
    {
        $this->boundary = new Boundary();

        static::initializeTypeIfNecessary();

        $this->initialize();
        $this->setAttributes($attributes);
    }
    
    protected final static function initializeTypeIfNecessary()
    {
        $class = get_called_class();
        if(!isset(self::$initialized[$class]))
        {
            static::initializeType();
            self::$initialized[$class] = true;
        }
    }

    protected static function initializeType()
    {
        //TODO refactoring
        $attributeWithGetters = array('width', 'height', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom', 'padding-left', 'padding-right', 'padding-top', 'padding-bottom', 'display', 'font-type', 'font-size', 'float', 'splittable');
        $attributeWithSetters = array('width', 'height', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom', 'display', 'font-type', 'float', 'static-size', 'font-size', 'margin', 'padding', 'page-break', 'splittable');

        $predicateGetters = array('splittable');
        
        $attributeWithGetters = array_flip($attributeWithGetters);
        array_walk($attributeWithGetters, function(&$value, $key) use($predicateGetters){
            $method = in_array($key, $predicateGetters) ? 'is' : 'get';
            $value = $method.str_replace('-', '', $key);
        });
        
        $attributeWithSetters = array_flip($attributeWithSetters);
        array_walk($attributeWithSetters, function(&$value, $key){
            $value = 'set'.str_replace('-', '', $key);
        });
        
        static::setAttributeGetters($attributeWithGetters);
        static::setAttributeSetters($attributeWithSetters);
    }
    
    /**
     * @todo refactoring
     */
    protected final static function setAttributeGetters(array $getters)
    {
        $class = get_called_class();
        if(!isset(self::$attributeGetters[$class]))
        {
            self::$attributeGetters[$class] = array();
        }

        self::$attributeGetters[$class] = $getters + self::$attributeGetters[$class];
    }
    
    protected final static function setAttributeSetters(array $setters)
    {
        $class = get_called_class();
        if(!isset(self::$attributeSetters[$class]))
        {
            self::$attributeSetters[$class] = array();
        }
        
        self::$attributeSetters[$class] = $setters + self::$attributeSetters[$class];
    }

    protected function addDrawingTasks(array $tasks)
    {
        foreach($tasks as $task)
        {
            $this->addDrawingTask($task);
        }
    }
    
    protected function addDrawingTask(DrawingTask $task)
    {
        $this->drawingTasks[] = $task;
    }

    public function mergeEnhancementAttributes($name, array $attributes = array())
    {
        $this->enhancementBag->add($name, $attributes);
    }

    public function getEnhancementsAttributes($name = null)
    {
        if($name === null)
        {
            return $this->enhancementBag->getAll();
        }

        return $this->enhancementBag->get($name);
    }

    public function getEnhancements()
    {
        return $this->enhancements;
    }

    /**
     * @return PHPPdf\Util\Boundary
     */
    public function getBoundary()
    {
        return $this->boundary;
    }
    
    /**
     * @return PHPPdf\Util\Boundary Boundary with no translated points by margins, paddings etc.
     */
    public function getRealBoundary()
    {
        return $this->getBoundary();
    }

    protected function setBoundary(Boundary $boundary)
    {
        $this->boundary = $boundary;
    }

    /**
     * Gets point of left upper corner of this glyph or null if boundaries have not been
     * calculated yet.
     *
     * @return PHPPdf\Util\Point
     */
    public function getFirstPoint()
    {
        return $this->getBoundary()->getFirstPoint();
    }
    
    /**
     * Gets point of left upper corner of this glyph, this method works on boundary from {@see getRealBoundary()}
     * on contrast to {@see getFirstPoint()} method.
     * 
     * @return PHPPdf\Util\Point
     */
    public function getRealFirstPoint()
    {
        return $this->getRealBoundary()->getFirstPoint();
    }

    /**
     * Get point of right bottom corner of this glyph or null if boundaries have not been
     * calculated yet.
     *
     * @return PHPPdf\Util\Point
     */
    public function getDiagonalPoint()
    {
        return $this->getBoundary()->getDiagonalPoint();
    }
    
    /**
     * Gets point of right bottom corner of this glyph, this method works on boundary from {@see getRealBoundary()}
     * on contrast to {@see getDiagonalPoint()} method.
     * 
     * @return PHPPdf\Util\Point
     */
    public function getRealDiagonalPoint()
    {
        return $this->getRealBoundary()->getDiagonalPoint();
    }
    
    /**
     * @return PHPPdf\Util\Point Point that divides line between first and diagonal points on half
     */
    public function getMiddlePoint()
    {
        $x = $this->getFirstPoint()->getX() + ($this->getDiagonalPoint()->getX() - $this->getFirstPoint()->getX())/2;
        $y = $this->getDiagonalPoint()->getY() + ($this->getFirstPoint()->getY() - $this->getDiagonalPoint()->getY())/2;
        
        return Point::getInstance($x, $y);
    }

    public function setParent(Container $glyph)
    {
        $oldParent = $this->getParent();
        if($oldParent)
        {
            $oldParent->remove($this);
        }

        $this->parent = $glyph;

        return $this;
    }

    /**
     * @return Glyph
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param string $type Full class name with namespace
     * @return PHPPdf\Glyph\Glyph Nearest ancestor in $type
     */
    public function getAncestorByType($type)
    {
        $current = $this;
        do
        {
            $parent = $current->getParent();
            $current = $parent;
        }
        while($parent && !$parent instanceof $type);

        return $parent;
    }

    /**
     * @return array Siblings with current object
     */
    public function getSiblings()
    {
        $parent = $this->getParent();

        if(!$parent)
        {
            return array();
        }

        return $parent->getChildren();
    }

    public function initialize()
    {
        $this->addAttribute('width', null);
        $this->addAttribute('height', null);

        $this->addAttribute('min-width', 0);

        $this->addAttribute('margin-top');
        $this->addAttribute('margin-left');
        $this->addAttribute('margin-right');
        $this->addAttribute('margin-bottom');

        $this->addAttribute('margin');
        $this->addAttribute('padding');

        $this->addAttribute('font-type');
        $this->addAttribute('font-size');

        $this->addAttribute('color');

        $this->addAttribute('display', self::DISPLAY_BLOCK);

        $this->addAttribute('padding-top', 0);
        $this->addAttribute('padding-right', 0);
        $this->addAttribute('padding-bottom', 0);
        $this->addAttribute('padding-left', 0);
        $this->addAttribute('splittable', true);

        $this->addAttribute('line-height');
        $this->addAttribute('text-align', null);

        $this->addAttribute('float', self::FLOAT_NONE);
        $this->addAttribute('font-style', null);
        $this->addAttribute('static-size', false);
        $this->addAttribute('page-break', false);
        
        $this->addAttribute('vertical-align', null);

        $this->setEnhancementBag(new EnhancementBag());
    }
    
    protected function setEnhancementBag(EnhancementBag $bag)
    {
        $this->enhancementBag = $bag;
    }
    
    public function addBehaviour(Behaviour $behaviour)
    {
        $this->behaviours[] = $behaviour;
    }

    public function reset()
    {
    }

    /**
     * @return PHPPdf\Glyph\Page
     */
    public function getPage()
    {
        $page = $this->getAncestorByType('\PHPPdf\Glyph\Page');

        if(!$page)
        {
            throw new \LogicException(sprintf('Glyph "%s" is not attach to any page.', get_class($this)));
        }

        return $page;
    }

    public function getFont()
    {
        $font = $this->getRecurseAttribute('font-type');

        if($font)
        {
            $fontStyle = $this->getRecurseAttribute('font-style');
            if($fontStyle)
            {
                $font->setStyle($fontStyle);
            }

            return $font;
        }

        return null;
    }
    
    public function setFloat($float)
    {
        $this->setAttributeDirectly('float', $float);
    }
       
    public function getFloat()
    {
        return $this->getAttributeDirectly('float');
    }
    
    public function setFontType($fontType)
    {
        $this->setAttributeDirectly('font-type', $fontType);
    }

    public function getFontType($recurse = false)
    {
        if(!$recurse)
        {
            return $this->getAttributeDirectly('font-type');
        }
        else
        {
            return $this->getRecurseAttribute('font-type');
        }
    }
    
    public function getFontSize()
    {
        return $this->getAttributeDirectly('font-size');
    }
    
    public function getFontSizeRecursively()
    {
        return $this->getRecurseAttribute('font-size');
    }
    
    public function getDisplay()
    {
        return $this->getAttributeDirectly('display');
    }
    
    public function setDisplay($display)
    {
        $this->setAttributeDirectly('display', $display);
    }

    /**
     * Set target width
     *
     * @param int|null $width
     */
    public function setWidth($width)
    {
        $this->setAttributeDirectly('width', $width);

        if(\strpos($width, '%') !== false)
        {
            $this->setRelativeWidth($width);
        }

        return $this;
    }

    public function setRelativeWidth($width)
    {
        $this->relativeWidth = $width;
    }

    public function getRelativeWidth()
    {
        return $this->relativeWidth;
    }

    private function convertToInteger($value, $nullValue = null)
    {
        return ($value === null ? $nullValue : (int) $value);
    }

    public function getWidth()
    {
        return $this->getWidthOrHeight('width');
    }
    
    /**
     * @return int Real width not modified by margins, paddings etc.
     */
    public function getRealWidth()
    {
        return $this->getWidth();
    }
    
    public function getMinWidth()
    {
        return 0;
    }
    
    /**
     * @return int Real height not modified by margins, paddings etc.
     */
    public function getRealHeight()
    {
        return $this->getHeight();
    }

    private function getWidthOrHeight($sizeType)
    {
        $display = $this->getAttribute('display');
        if($display == self::DISPLAY_BLOCK)
        {
            return $this->getAttributeDirectly($sizeType);
        }
        elseif($display == self::DISPLAY_NONE)
        {
            return 0;
        }

        return (double) $this->getAttributeDirectly($sizeType);
    }

    public function getWidthWithMargins()
    {
        $width = $this->getWidth();

        $margins = $this->getMarginLeft() + $this->getMarginRight();

        return ($width + $margins);
    }

    public function getWidthWithoutPaddings()
    {
        $width = $this->getWidth();

        $paddings = $this->getPaddingLeft() + $this->getPaddingRight();

        return ($width - $paddings);
    }

    public function getHeightWithMargins()
    {
        $height = $this->getHeight();

        $margins = $this->getMarginTop() + $this->getMarginBottom();

        return ($height + $margins);
    }

    public function getHeightWithoutPaddings()
    {
        $height = $this->getHeight();

        $paddings = $this->getPaddingTop() + $this->getPaddingBottom();

        return ($height - $paddings);
    }

    /**
     * Set target height
     *
     * @param int|null $height
     */
    public function setHeight($height)
    {
        $this->setAttributeDirectly('height', $height);

        return $this;
    }

    public function getHeight()
    {
        return $this->getWidthOrHeight('height');
    }

    public function setMarginTop($margin)
    {
        return $this->setMarginAttribute('margin-top', $margin);
    }

    protected function setMarginAttribute($name, $value)
    {
        $this->setAttributeDirectly($name, $value === self::MARGIN_AUTO ? $value : $this->convertToInteger($value));

        return $this;
    }

    public function setMarginLeft($margin)
    {
        return $this->setMarginAttribute('margin-left', $margin);
    }

    public function setMarginRight($margin)
    {
        return $this->setMarginAttribute('margin-right', $margin);
    }

    public function setMarginBottom($margin)
    {
        return $this->setMarginAttribute('margin-bottom', $margin);
    }

    public function getMarginTop()
    {
        return $this->getAttributeDirectly('margin-top');
    }

    public function getMarginLeft()
    {
        return $this->getAttributeDirectly('margin-left');
    }

    public function getMarginRight()
    {
        return $this->getAttributeDirectly('margin-right');
    }

    public function getMarginBottom()
    {
        return $this->getAttributeDirectly('margin-bottom');
    }

    /**
     * @return bool|null Null if $flag !== null, true if margins was 'auto' value, otherwise false
     */
    public function hadAutoMargins($flag = null)
    {
        if($flag === null)
        {
            return $this->hadAutoMargins;
        }

        $this->hadAutoMargins = (bool) $flag;
    }

    /**
     * Setting "css style" margins
     */
    public function setMargin()
    {
        $margins = \func_get_args();

        if(count($margins) === 1 && is_string(current($margins)))
        {
            $margins = explode(' ', current($margins));
        }

        $marginLabels = array('margin-top', 'margin-right', 'margin-bottom', 'margin-left');
        $this->setComposeAttribute($marginLabels, $margins);

        return $this;
    }

    private function setComposeAttribute($attributeNames, $attributes)
    {
        $count = count($attributes);

        if($count === 0)
        {
            throw new \InvalidArgumentException('Attribute values doesn\'t pass.');
        }

        $repeat = \ceil(4 / $count);

        for($i=1; $i<$repeat; $i++)
        {
            $attributes = array_merge($attributes, $attributes);
        }

        foreach($attributeNames as $key => $label)
        {
            $this->setAttribute($label, $attributes[$key]);
        }
    }

    public function setPadding()
    {
        $paddings = \func_get_args();

        if(count($paddings) === 1 && is_string(current($paddings)))
        {
            $paddings = explode(' ', current($paddings));
        }


        $paddingLabels = array('padding-top', 'padding-right', 'padding-bottom', 'padding-left');
        $this->setComposeAttribute($paddingLabels, $paddings);

        return $this;
    }
    
    public function getPaddingTop()
    {
        return $this->getAttributeDirectly('padding-top');
    }
    
    public function getPaddingBottom()
    {
        return $this->getAttributeDirectly('padding-bottom');
    }
    
    public function getPaddingLeft()
    {
        return $this->getAttributeDirectly('padding-left');
    }
    
    public function getPaddingRight()
    {
        return $this->getAttributeDirectly('padding-right');
    }
    
    public function getEncoding()
    {
        return $this->getPage()->getAttribute('encoding');
    }
    
    public function setFontSize($size)
    {
        $this->setAttributeDirectly('font-size', (int)$size);
        return $this;
    }

    public function setAttributes(array $attributes)
    {
        foreach($attributes as $name => $value)
        {
            $this->setAttribute($name, $value);
        }
    }

    public function setAttribute($name, $value)
    {
        $this->throwExceptionIfAttributeDosntExist($name);
        
        $class = get_class($this);
        if(isset(self::$attributeSetters[$class][$name]))
        {
            $methodName = self::$attributeSetters[$class][$name];
            $this->$methodName($value);
        }
        else
        {
            $this->setAttributeDirectly($name, $value);
        }

        return $this;
    }

    protected function setAttributeDirectly($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    protected function getAttributeDirectly($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    private function throwExceptionIfAttributeDosntExist($name)
    {
        if(!$this->hasAttribute($name))
        {
            throw new InvalidAttributeException($name);
//            throw new \InvalidArgumentException(sprintf('Class "%s" dosn\'t have "%s" attribute.', get_class($this), $name));
        }
    }

    private function getAttributeMethodName($prefix, $name)
    {
        $parts = \explode('-', $name);

        return sprintf('%s%s', $prefix, \implode('', $parts));
    }

    protected function addAttribute($name, $default = null)
    {
        $this->setAttributeDirectly($name, $default);
    }
    
    public function setSplittable($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('splittable', $flag);
    }
    
    public function isSplittable()
    {
        try
        {
            $page = $this->getPage();
            
            if($page->getHeight() < $this->getHeight())
            {
                return true;
            }            
        }
        catch (\LogicException $e)
        {
            //ignore, original attribute value will be returned
        }
        
        return $this->getAttributeDirectly('splittable');
    }
    
    public function setStaticSize($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('static-size', $flag);
    }
    
    public function setPageBreak($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('page-break', $flag);
    }
    
    final protected function filterBooleanValue($value)
    {
        return Util::convertBooleanValue($value);
    }

    /**
     * @return bool True if attribute exeists, even if have null value, otherwise false
     */
    public function hasAttribute($name)
    {
        return (in_array($name, array_keys($this->attributes)));
    }

    public function getAttribute($name)
    {
        $this->throwExceptionIfAttributeDosntExist($name);

        $class = get_class($this);
        $getters = self::$attributeGetters;
        if(isset(self::$attributeGetters[$class][$name]))
        {
            $methodName = self::$attributeGetters[$class][$name];
            return $this->$methodName();
        }
        else
        {
            return $this->getAttributeDirectly($name);
        }        
    }

    /**
     * Getting attribute from this glyph or parents. If value of attribute is null,
     * this method is recurse invoking on parent.
     */
    public function getRecurseAttribute($name)
    {
        $value = $this->getAttribute($name);
        $parent = $this->getParent();
        if($value === null && $parent)
        {
            $value = $parent->getRecurseAttribute($name);
            $this->setAttribute($name, $value);
            return $value;
        }

        return $value;
    }
    
    /**
     * Make snapshot of attribute's map
     */
    public function makeAttributesSnapshot(array $attributeNames = null)
    {
        if($attributeNames === null)
        {
            $attributeNames = array_keys($this->attributes);
        }
               
        $this->attributesSnapshot = array_intersect_key($this->attributes, array_flip($attributeNames));
    }

    /**
     * @return array|null Last made attribute's snapshot, null if snapshot haven't made.
     */
    public function getAttributesSnapshot()
    {
        return $this->attributesSnapshot;
    }

    /**
     * Returns array of PHPPdf\Util\DrawingTask objects. Those objects encapsulate drawing function.
     *
     * @return array Array of PHPPdf\Util\DrawingTask objects
     */
    public function getDrawingTasks(Document $document)
    {
        if($this->getAttribute('display') == self::DISPLAY_NONE)
        {
            return array();
        }

        try
        {
            $this->preDraw($document);
            $this->doDraw($document);
            $this->postDraw($document);

            return $this->drawingTasks;
        }
        catch(\Exception $e)
        {
            throw new \PHPPdf\Exception\DrawingException(sprintf('Error while drawing glyph "%s"', get_class($this)), 0, $e);
        }
    }

    protected function preDraw(Document $document)
    {
        $tasks = $this->getDrawingTasksFromEnhancements($document);
        $this->addDrawingTasks($tasks);
    }
    
    protected function getDrawingTasksFromEnhancements(Document $document)
    {
        $tasks = array();
        
        $enhancements = $document->getEnhancements($this->enhancementBag);
        foreach($enhancements as $enhancement)
        {
            $callback = array($enhancement, 'enhance');
            $args = array($this, $document);
            $priority = $enhancement->getPriority() + $this->getPriority();
            $tasks[] = new DrawingTask($callback, $args, $priority);
        }
        
        foreach($this->behaviours as $behaviour)
        {
            $callback = function($behaviour, $glyph){
                $behaviour->attach($glyph->getGraphicsContext(), $glyph);
            };
            $args = array($behaviour, $this);
            $tasks[] = new DrawingTask($callback, $args);
        }
        
        return $tasks;
    }

    public function getPriority()
    {
        return $this->priority;
    }
    
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    protected function setPriorityFromParent()
    {
        $parentPriority = $this->getParent() ? $this->getParent()->getPriority() : 0;
        $this->priority = $parentPriority - 1;

        foreach($this->getChildren() as $child)
        {
            $child->setPriorityFromParent();
        }
    }

    protected function doDraw(Document $document)
    {
    }

    protected function postDraw(Document $document)
    {
    }

    public function preFormat(Document $document)
    {
    }

    public function offsetExists($offset)
    {
        return $this->hasAttribute($offset);
    }

    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->setAttribute($offset, null);
    }

    public function getStartDrawingPoint()
    {
        list($x, $y) = $this->getFirstPoint()->toArray();

        return array($x + $this->getPaddingLeft(), $y - $this->getPaddingTop());
    }

    public function getPreviousSibling()
    {
        $siblings = $this->getSiblings();
        for($i=0, $count = count($siblings); $i<$count && $siblings[$i] !== $this; $i++)
        {
        }

        return isset($siblings[$i-1]) ? $siblings[$i-1] : null;
    }
    
    public function getEndDrawingPoint()
    {
        list($x, $y) = $this->getDiagonalPoint()->toArray();

        return array($x - $this->getPaddingRight(), $y + $this->getPaddingBottom());
    }

    public function getRealMarginLeft()
    {
        return $this->getMarginLeft();
    }

    public function removeParent()
    {
        $this->parent = null;
    }

    public function copy()
    {
        $copy = clone $this;
        $copy->reset();
        $copy->removeParent();
        $copy->boundary = new Boundary();
        $copy->enhancementBag = clone $this->enhancementBag;
        $copy->drawingTasks = array();

        return $copy;
    }

    final protected function __clone()
    {
    }

    public function translate($x, $y)
    {
        $this->getBoundary()->translate($x, $y);
    }

    public function resize($x, $y)
    {
        $diagonalXCoord = $this->getDiagonalPoint()->getX() - $this->getPaddingRight();

        $this->getBoundary()->pointTranslate(1, $x, 0);
        $this->getBoundary()->pointTranslate(2, $x, $y);
        $this->getBoundary()->pointTranslate(3, 0, $y);

        foreach($this->getChildren() as $child)
        {
            $childDiagonalXCoord = $child->getDiagonalPoint()->getX() + $child->getMarginRight();

            $relativeWidth = $child->getRelativeWidth();

            if($relativeWidth !== null)
            {
                $relativeWidth = ((int) $relativeWidth)/100;
                $childResize = ($diagonalXCoord + $x) * $relativeWidth - $childDiagonalXCoord;
            }
            else
            {
                $childResize = $x + ($diagonalXCoord - $childDiagonalXCoord);
                $childResize = $childResize < 0 ? $childResize : 0;
            }

            if($childResize != 0)
            {
                $child->resize($childResize, 0);
            }
        }
    }

    /**
     * Split glyph on passed $height.
     *
     * @param integer $height
     * @return \PHPPdf\Glyph\Glyph|null Second glyph created afted splitting
     */
    public function split($height)
    {
        if(!$this->getAttribute('splittable') || $height <= 0 || $height >= $this->getHeight())
        {
            return null;
        }

        return $this->doSplit($height);
    }

    protected function doSplit($height)
    {
        $boundary = $this->getBoundary();
        $clonedBoundary = clone $boundary;

        $trueHeight = $boundary->getFirstPoint()->getY() - $boundary->getDiagonalPoint()->getY();
        
        $heightComplement = $trueHeight - $height;

        $boundary->reset();
        $clone = $this->copy();

        $boundary->setNext($clonedBoundary[0])
                 ->setNext($clonedBoundary[1])
                 ->setNext($clonedBoundary[2]->translate(0, - $heightComplement))
                 ->setNext($clonedBoundary[3]->translate(0, - $heightComplement))
                 ->close();

        $boundaryOfClone = $clone->getBoundary();
        $boundaryOfClone->reset();

        $boundaryOfClone->setNext($clonedBoundary[0]->translate(0, $height))
                        ->setNext($clonedBoundary[1]->translate(0, $height))
                        ->setNext($clonedBoundary[2])
                        ->setNext($clonedBoundary[3])
                        ->close();

        $clone->setHeight($this->getHeight() - $height);
        $this->setHeight($height);

        return $clone;
    }

    public function add(Glyph $glyph)
    {
    }

    public function remove(Glyph $glyph)
    {
        return false;
    }

    public function getChildren()
    {
        return array();
    }
    
    /**
     * @return PHPPdf\Glyph\Glyph
     */
    public function getChild($index)
    {
        $children = $this->getChildren();
        
        if(!isset($children[$index]))
        {
            throw new \OutOfBoundsException(sprintf('Child "%s" dosn\'t exist.', $index));
        }

        return $children[$index];
    }

    public function getNumberOfChildren()
    {
        return count($this->getChildren());
    }

    public function removeAll()
    {
    }
    
    public function convertScalarAttribute($name, $parentValue = null)
    {
        if($parentValue === null && ($parent = $this->getParent()))
        {
            $parentValue = $this->getParent()->getAttribute($name);
        }
        
        $potentiallyRelativeValue = $this->getAttribute($name);
        
        $absoluteValue = \PHPPdf\Util::convertFromPercentageValue($potentiallyRelativeValue, $parentValue);
        if($absoluteValue !== $potentiallyRelativeValue)
        {
            $this->setAttribute($name, $absoluteValue);
        }
    }

    /**
     * Format glyph by given formatters.
     */
    public function format(Document $document)
    {
        foreach($this->formattersNames as $formatterName)
        {
            $formatter = $document->getFormatter($formatterName);
            $formatter->format($this, $document);
        }
    }

    public function setFormattersNames(array $formattersNames)
    {
        $this->formattersNames = $formattersNames;
    }

    public function addFormatterName($formatterName)
    {
        $this->formattersNames[] = $formatterName;
    }

    public function getFormattersNames()
    {
        return $this->formattersNames;
    }
    
    public function getGraphicsContext()
    {
        return $this->getPage()->getGraphicsContext();
    }

    public function getPlaceholder($name)
    {
        return null;
    }

    public function hasPlaceholder($name)
    {
        return false;
    }

    public function setPlaceholder($name, Glyph $placeholder)
    {
        throw new \InvalidArgumentException(sprintf('Placeholder "%s" is not supported by class "%s".', $name, get_class($this)));
    }

    protected function getDataForSerialize()
    {
        $data = array(
            'boundary' => $this->getBoundary(),
            'attributes' => $this->attributes,
            'enhancementBag' => $this->enhancementBag->getAll(),
            'formattersNames' => $this->formattersNames,
            'priority' => $this->priority,
        );

        return $data;
    }
    
    public function serialize()
    {
        $data = $this->getDataForSerialize();

        return serialize($data);
    }

    public function unserialize($serialized)
    {
        static::initializeTypeIfNecessary();

        $data = unserialize($serialized);

        $this->setDataFromUnserialize($data);
    }
    
    protected function setDataFromUnserialize(array $data)
    {       
        $this->setBoundary($data['boundary']);
        $this->attributes = $data['attributes'];
        $this->enhancementBag = new EnhancementBag($data['enhancementBag']);
        $this->setFormattersNames($data['formattersNames']);
        $this->priority = $data['priority'];
    }
    
    public function getGlyph()
    {
        return $this;
    }

    public function __toString()
    {
        return get_class($this).\spl_object_hash($this);
    }
}