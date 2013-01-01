<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node;

use PHPPdf\Core\ComplexAttribute\ComplexAttribute;
use PHPPdf\Exception\OutOfBoundsException;
use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Exception\LogicException;
use PHPPdf\Core\AttributeBag;
use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\UnitConverter;
use PHPPdf\Core\Document;
use PHPPdf\Util;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Boundary;
use PHPPdf\Core\DrawingTask;
use PHPPdf\Core\Formatter\Formatter;
use PHPPdf\Core\Node\Behaviour\Behaviour;
use PHPPdf\Core\Exception\InvalidAttributeException;
use PHPPdf\Core\Point;

/**
 * Base node class
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class Node implements Drawable, NodeAware, \ArrayAccess, \Serializable
{
    const MARGIN_AUTO = 'auto';
    const FLOAT_NONE = 'none';
    const FLOAT_LEFT = 'left';
    const FLOAT_RIGHT = 'right';
    const ALIGN_LEFT = 'left';
    const ALIGN_RIGHT = 'right';
    const ALIGN_CENTER = 'center';
    const ALIGN_JUSTIFY = 'justify';
    const VERTICAL_ALIGN_TOP = 'top';
    const VERTICAL_ALIGN_MIDDLE = 'middle';
    const VERTICAL_ALIGN_BOTTOM = 'bottom';
    const TEXT_DECORATION_NONE = 'none';
    const TEXT_DECORATION_UNDERLINE = 'underline';
    const TEXT_DECORATION_LINE_THROUGH = 'line-through';
    const TEXT_DECORATION_OVERLINE = 'overline';
    const ROTATE_DIAGONALLY = 'diagonally';
    const ROTATE_OPPOSITE_DIAGONALLY = '-diagonally';
    const POSITION_STATIC = 'static';
    const POSITION_RELATIVE = 'relative';
    const POSITION_ABSOLUTE = 'absolute';
    
    const SHAPE_RECTANGLE = 'rectangle';
    const SHAPE_ELLIPSE = 'ellipse';

    private static $attributeSetters = array();
    private static $attributeGetters = array();
    private static $defaultAttributes = array();
    private static $initialized = array();

    private $attributes = array();
    private $attributesSnapshot = null;
    private $priority = 0;

    private $parent = null;
    private $hadAutoMargins = false;
    private $relativeWidth = null;

    private $boundary = null;

    protected $complexAttributeBag = null;
    private $formattersNames = array();
    
    private $behaviours = array();
    
    private $ancestorWithRotation = null;
    private $ancestorWithFontSize = null;
    
    private $unitConverter = null;
    
    private $closestAncestorWithPosition = null;
    private $positionTranslation = null;

    public function __construct(array $attributes = array(), UnitConverter $converter = null)
    {
        static::initializeTypeIfNecessary();

        $this->initialize();
        if($converter)
        {
            $this->setUnitConverter($converter);
        }
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
        $attributeWithGetters = array('width', 'height', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom', 'padding-left', 'padding-right', 'padding-top', 'padding-bottom', 'font-type', 'font-size', 'float', 'breakable');
        $attributeWithSetters = array('width', 'height', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom', 'font-type', 'float', 'static-size', 'font-size', 'margin', 'padding', 'break', 'breakable', 'dump', 'padding-left', 'padding-right', 'padding-top', 'padding-bottom', 'min-width', 'line-height', 'line-break', 'left', 'top', 'position');

        $predicateGetters = array('breakable');
        
        $attributeWithGetters = array_flip($attributeWithGetters);
        array_walk($attributeWithGetters, function(&$value, $key, $predicateGetters){
            $method = in_array($key, $predicateGetters) ? 'is' : 'get';
            $value = $method.str_replace('-', '', $key);
        }, $predicateGetters);
        
        $attributeWithSetters = array_flip($attributeWithSetters);
        array_walk($attributeWithSetters, function(&$value, $key){
            $value = 'set'.str_replace('-', '', $key);
        });
        
        static::setAttributeGetters($attributeWithGetters);
        static::setAttributeSetters($attributeWithSetters);
               
        static::setDefaultAttributes();
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
    
    protected static function addAttribute($name, $default = null)
    {
        $class = get_called_class();
        if(!isset(self::$defaultAttributes[$class]))
        {
            self::$defaultAttributes[$class] = array();
        }
        
        self::$defaultAttributes[$class][$name] = $default;
    }
    
    
    protected static function setDefaultAttributes()
    {
        static::addAttribute('width', null);
        static::addAttribute('height', null);

        static::addAttribute('min-width', 0);

        static::addAttribute('margin-top');
        static::addAttribute('margin-left');
        static::addAttribute('margin-right');
        static::addAttribute('margin-bottom');

        static::addAttribute('margin');
        static::addAttribute('padding');

        static::addAttribute('font-type');
        static::addAttribute('font-size');

        static::addAttribute('color');

        static::addAttribute('padding-top', 0);
        static::addAttribute('padding-right', 0);
        static::addAttribute('padding-bottom', 0);
        static::addAttribute('padding-left', 0);
        static::addAttribute('breakable', true);

        static::addAttribute('line-height');
        static::addAttribute('text-align', null);

        static::addAttribute('float', self::FLOAT_NONE);
        static::addAttribute('font-style', null);
        static::addAttribute('static-size', false);
        static::addAttribute('break', false);
        
        static::addAttribute('vertical-align', null);
        
        static::addAttribute('text-decoration', null);
        
        static::addAttribute('dump', false);
        
        static::addAttribute('alpha', null);
        static::addAttribute('rotate', null);
        
        static::addAttribute('line-break', false);
        
        static::addAttribute('position', self::POSITION_STATIC);
        static::addAttribute('left', null);
        static::addAttribute('top', null);
        static::addAttribute('right', null);
        static::addAttribute('bottom', null);
    }

    public function setUnitConverter(UnitConverter $unitConverter)
    {
        $this->unitConverter = $unitConverter;
    }
    
    public function getUnitConverter()
    {
        return $this->unitConverter;
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

    /**
     * Add complexAttribute attributes, if complexAttribute with passed name is exists, it will be
     * merged.
     * 
     * @param string $name Name of complexAttribute
     * @param array $attributes Attributes of complexAttribute
     */
    public function mergeComplexAttributes($name, array $attributes = array())
    {
        $this->complexAttributeBag->add($name, $attributes);
    }

    /**
     * Get all complexAttribute data or data of complexAttribute with passed name
     * 
     * @param string $name Name of complexAttribute to get
     * @return array If $name is null, data of all complexAttributes will be returned, otherwise data of complexAttribute with passed name will be returned.
     */
    public function getComplexAttributes($name = null)
    {
        if($name === null)
        {
            return $this->complexAttributeBag->getAll();
        }

        return $this->complexAttributeBag->get($name);
    }

    /**
     * @return PHPPdf\Core\Boundary
     */
    public function getBoundary()
    {
        if($this->boundary === null)
        {
            $this->setBoundary(new Boundary());
        }

        return $this->boundary;
    }
    
    /**
     * @return PHPPdf\Core\Boundary Boundary with no translated points by margins, paddings etc.
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
     * Gets point of left upper corner of this node or null if boundaries have not been
     * calculated yet.
     *
     * @return PHPPdf\Core\Point
     */
    public function getFirstPoint()
    {
        return $this->getBoundary()->getFirstPoint();
    }
    
    /**
     * Gets point of left upper corner of this node, this method works on boundary from {@see getRealBoundary()}
     * on contrast to {@see getFirstPoint()} method.
     * 
     * @return PHPPdf\Core\Point
     */
    public function getRealFirstPoint()
    {
        return $this->getRealBoundary()->getFirstPoint();
    }

    /**
     * Get point of right bottom corner of this node or null if boundaries have not been
     * calculated yet.
     *
     * @return PHPPdf\Core\Point
     */
    public function getDiagonalPoint()
    {
        return $this->getBoundary()->getDiagonalPoint();
    }
    
    /**
     * Gets point of right bottom corner of this node, this method works on boundary from {@see getRealBoundary()}
     * on contrast to {@see getDiagonalPoint()} method.
     * 
     * @return PHPPdf\Core\Point
     */
    public function getRealDiagonalPoint()
    {
        return $this->getRealBoundary()->getDiagonalPoint();
    }
    
    /**
     * @return PHPPdf\Core\Point Point that divides line between first and diagonal points on half
     */
    public function getMiddlePoint()
    {
        return $this->getBoundary()->getMiddlePoint();
    }

    public function setParent(Container $node)
    {
        $oldParent = $this->parent;
        if($oldParent)
        {
            $oldParent->remove($this);
        }

        $this->parent = $node;

        return $this;
    }

    /**
     * @return Node
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Gets ancestor with passed type. If ancestor has not been found, null will be returned.
     * 
     * @param string $type Full class name with namespace
     * @return PHPPdf\Core\Node\Node Nearest ancestor in $type
     */
    public function getAncestorByType($type)
    {
        $current = $this;
        do
        {
            $parent = $current->parent;
            $current = $parent;
        }
        while($parent && !$parent instanceof $type);

        return $parent;
    }

    /**
     * @return array Siblings with current object includes current object
     */
    public function getSiblings()
    {
        $parent = $this->parent;

        if(!$parent)
        {
            return array();
        }

        return $parent->getChildren();
    }

    public function initialize()
    {
        $this->setComplexAttributeBag(new AttributeBag());
    }
    
    protected function setComplexAttributeBag(AttributeBag $bag)
    {
        $this->complexAttributeBag = $bag;
    }
    
    public function addBehaviour(Behaviour $behaviour)
    {
        $this->behaviours[] = $behaviour;
    }
    
    /**
     * @return array Array of Behhaviour objects
     */
    public function getBehaviours()
    {
        return $this->behaviours;
    }

    /**
     * Reset state of object
     */
    public function reset()
    {
    }

    /**
     * @return Page Page of current objects
     * @throws LogicException If object has not been attached to any page
     */
    public function getPage()
    {
        $page = $this->getAncestorByType('\PHPPdf\Core\Node\Page');

        if(!$page)
        {
            throw new LogicException(sprintf('Node "%s" is not attach to any page.', get_class($this)));
        }

        return $page;
    }

    /**
     * Gets font object associated with current object
     * 
     * @return Font
     */
    public function getFont(Document $document)
    {
        $fontType = $this->getRecurseAttribute('font-type');

        if($fontType)
        {
            $font = $document->getFont($fontType);
            
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
        $ancestor = $this->getAncestorWithFontSize();
        
        return $ancestor === false ? $this->getFontSize() : $ancestor->getFontSize();
    }
    
    public function getLineHeightRecursively()
    {
        $ancestor = $this->getAncestorWithFontSize();
        
        return $ancestor === false ? $this->getAttribute('line-height') : $ancestor->getAttribute('line-height');
    }
    
    public function getTextDecorationRecursively()
    {
        return $this->getRecurseAttribute('text-decoration');
    }

    /**
     * Set target width
     *
     * @param int|null $width
     */
    public function setWidth($width)
    {
        $width = $this->convertUnit($width);
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
        return $this->getAttributeDirectly('width');
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
        $height = $this->convertUnit($height);
        $this->setAttributeDirectly('height', $height);

        return $this;
    }
    
    public function setLineHeight($value)
    {
        $this->setAttributeDirectly('line-height', $this->convertUnit($value));
    }

    public function setMinWidth($value)
    {
        $this->setAttributeDirectly('min-width', $this->convertUnit($value));
    }

    public function getHeight()
    {
        return $this->getAttributeDirectly('height');
    }

    public function setMarginTop($margin)
    {
        return $this->setMarginAttribute('margin-top', $margin);
    }

    protected function setMarginAttribute($name, $value)
    {
        $this->setAttributeDirectly($name, $value === self::MARGIN_AUTO ? $value : $this->convertUnit($value));

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
            throw new InvalidArgumentException('Attribute values doesn\'t pass.');
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

    /**
     * Set "css style" paddings
     */
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
    
    public function setPaddingTop($value)
    {
        $this->setAttributeDirectly('padding-top', $this->convertUnit($value));
    }

    public function setPaddingBottom($value)
    {
        $this->setAttributeDirectly('padding-bottom', $this->convertUnit($value));
    }

    public function setPaddingLeft($value)
    {
        $this->setAttributeDirectly('padding-left', $this->convertUnit($value));
    }

    public function setPaddingRight($value)
    {
        $this->setAttributeDirectly('padding-right', $this->convertUnit($value));
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
    
    protected function setLeft($left)
    {
        $left = $this->convertUnit($left);
        $this->setAttributeDirectly('left', $left);
    }
    
    protected function setTop($top)
    {
        $top = $this->convertUnit($top);
        $this->setAttributeDirectly('top', $top);
    }
    
    protected function setPosition($position)
    {
        $positions = array(self::POSITION_ABSOLUTE, self::POSITION_RELATIVE, self::POSITION_STATIC);
        
        if(!in_array($position, $positions))
        {
            throw new InvalidArgumentException('Unsupported position value: "%s", expected: %s', $position, implode(', ', $positions));
        }
        
        $this->setAttributeDirectly('position', $position);
    }
    
    public function getEncoding()
    {
        return $this->getPage()->getAttributeDirectly('encoding');
    }
    
    public function getAlpha()
    {
        return $this->getRecurseAttribute('alpha');
    }
    
    /**
     * @return float Angle of rotate in radians
     */
    public function getRotate()
    {
        $rotate = $this->getAttribute('rotate');
        if(in_array($rotate, array(self::ROTATE_DIAGONALLY, self::ROTATE_OPPOSITE_DIAGONALLY)) && ($page = $this->getPage()))
        {
            $width = $page->getWidth();
            $height = $page->getHeight();
            $d = sqrt($width*$width + $height*$height);

            $angle = $d == 0 ? 0 : acos($width/$d);
            
            if($rotate === self::ROTATE_OPPOSITE_DIAGONALLY)
            {
                $angle = -$angle;
            }
            
            $rotate = $angle;
        }

        return $rotate === null ? null : (float) $rotate;
    }
    
    public function setFontSize($size)
    {
        $size = $this->convertUnit($size);

        $this->setAttributeDirectly('font-size', $size);
        $this->setAttribute('line-height', (int) ($size + $size*0.2));

        return $this;
    }
    
    protected function convertUnit($value)
    {
        if($this->unitConverter)
        {
            return $this->unitConverter->convertUnit($value);
        }
        
        return $value;
    }

    /**
     * Sets attributes values
     * 
     * @param array $attributes Array of attributes
     * 
     * @throws InvalidAttributeException If at least one of attributes isn't supported by this node
     */
    public function setAttributes(array $attributes)
    {
        foreach($attributes as $name => $value)
        {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Sets attribute value
     * 
     * @param string $name Name of attribute
     * @param mixed $value Value of attribute
     * 
     * @throws InvalidAttributeException If attribute isn't supported by this node
     * @return Node Self reference
     */
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
        if(self::$defaultAttributes[get_class($this)][$name] === $value)
        {
            unset($this->attributes[$name]);
        }
        else
        {
            $this->attributes[$name] = $value;
        }
    }

    protected function getAttributeDirectly($name)
    {
        if(!isset($this->attributes[$name]))
        {
            $class = get_class($this);
            return self::$defaultAttributes[$class][$name];
        }
        return $this->attributes[$name];
    }

    private function throwExceptionIfAttributeDosntExist($name)
    {
        if(!$this->hasAttribute($name))
        {
            throw new InvalidAttributeException($name);
        }
    }

    private function getAttributeMethodName($prefix, $name)
    {
        $parts = \explode('-', $name);

        return sprintf('%s%s', $prefix, \implode('', $parts));
    }

    public function setBreakable($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('breakable', $flag);
    }
    
    public function setDump($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('dump', $flag);
    }
    
    public function isBreakable()
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
        
        return $this->getAttributeDirectly('breakable');
    }
    
    public function setStaticSize($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('static-size', $flag);
    }
    
    public function setBreak($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('break', $flag);
    }
    
    public function setLineBreak($flag)
    {
        $flag = $this->filterBooleanValue($flag);
        $this->setAttributeDirectly('line-break', $flag);
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
        $class = get_class($this);
        return array_key_exists($name, self::$defaultAttributes[$class]);
    }

    /**
     * Returns attribute value
     * 
     * @param string $name Name of attribute
     * 
     * @throws InvalidAttributeException If attribute isn't supported by this node
     * @return mixed Value of attribute
     */
    public function getAttribute($name)
    {
        $this->throwExceptionIfAttributeDosntExist($name);

        $class = get_class($this);
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
     * Getting attribute from this node or parents. If value of attribute is null,
     * this method is recurse invoking on parent.
     */
    public function getRecurseAttribute($name)
    {
        $value = $this->getAttribute($name);
        $parent = $this->parent;
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
        $class = get_class($this);
        $this->attributesSnapshot = array_intersect_key($this->attributes + self::$defaultAttributes[$class], array_flip($attributeNames));
    }

    /**
     * @return array|null Last made attribute's snapshot, null if snapshot haven't made.
     */
    public function getAttributesSnapshot()
    {
        return $this->attributesSnapshot;
    }

    /**
     * Returns array of PHPPdf\Core\DrawingTask objects. Those objects encapsulate drawing function.
     *
     * @return array Array of PHPPdf\Core\DrawingTask objects
     */
    public function collectOrderedDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
        try
        {
            $this->preDraw($document, $tasks);
            $this->doDraw($document, $tasks);
            $this->postDraw($document, $tasks);
        }
        catch(\Exception $e)
        {
            throw new \PHPPdf\Core\Exception\DrawingException(sprintf('Error while drawing node "%s"', get_class($this)), 0, $e);
        }
    }
    
    public function collectPostDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
    }
    
    public function collectUnorderedDrawingTasks(Document $document, DrawingTaskHeap $tasks)
    {
        foreach($this->getChildren() as $node)
        {
            $node->collectUnorderedDrawingTasks($document, $tasks);
        }
        
        foreach($this->behaviours as $behaviour)
        {
            $callback = function($behaviour, $node){
                $behaviour->attach($node->getGraphicsContext(), $node);
            };
            $args = array($behaviour, $this);
            $tasks->insert(new DrawingTask($callback, $args));
        }
    }

    protected function preDraw(Document $document, DrawingTaskHeap $tasks)
    {
        $this->getDrawingTasksFromComplexAttributes($document, $tasks);
    }
    
    protected function getDrawingTasksFromComplexAttributes(Document $document, DrawingTaskHeap $tasks)
    {
        $complexAttributes = $document->getComplexAttributes($this->complexAttributeBag);
        foreach($complexAttributes as $complexAttribute)
        {
            $this->insertComplexAttributeTask($complexAttribute, $tasks, $document);
        }
    }
    
    protected function insertComplexAttributeTask(ComplexAttribute $complexAttribute, DrawingTaskHeap $tasks, Document $document)
    {
        $callback = array($complexAttribute, 'enhance');
        $args = array($this, $document);
        $priority = $complexAttribute->getPriority() + $this->getPriority();
        $tasks->insert(new DrawingTask($callback, $args, $priority));
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
        $parentPriority = $this->parent ? $this->parent->getPriority() : 0;
        $this->priority = $parentPriority - 1;

        foreach($this->getChildren() as $child)
        {
            $child->setPriorityFromParent();
        }
    }

    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
    }

    protected function postDraw(Document $document, DrawingTaskHeap $tasks)
    {
        if($this->getAttribute('dump'))
        {
            $tasks->insert($this->createDumpTask());
        }
    }
    
    protected function createDumpTask()
    {
        $task = new DrawingTask(function($node){
            $gc = $node->getGraphicsContext();
            $firstPoint = $node->getFirstPoint();
            $diagonalPoint = $node->getDiagonalPoint();
            
            $boundary = $node->getBoundary();
            $coordinations = array();
            foreach($boundary->getPoints() as $point)
            {
                $coordinations[] = $point->toArray();
            }
            
            $attributes = $node->getAttributes() + $node->getComplexAttributes();
            
            $dumpText = var_export(array(
                'attributes' => $attributes,
                'coordinations' => $coordinations,
            ), true);

            $gc->attachStickyNote($firstPoint->getX(), $firstPoint->getY(), $diagonalPoint->getX(), $diagonalPoint->getY(), $dumpText);
        }, array($this));

        return $task;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    protected function beforeFormat(Document $document)
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

    /**
     * @return Node Previous sibling node, null if previous sibling dosn't exist
     */
    public function getPreviousSibling()
    {
        $siblings = $this->getSiblings();
        $previous = null;
        
        foreach($siblings as $sibling)
        {
            if($sibling === $this)
            {
                return $previous;
            }
            $previous = $sibling;
        }
        
        return null;
    }
    
    /**
     * @deprecated To remove, getDiagonalPoint() is replacement
     */
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

    /**
     * @return Node Copy of this node
     */
    public function copy()
    {
        $copy = clone $this;
        $copy->reset();
        $copy->parent = null;
        $copy->boundary = null;
        $copy->complexAttributeBag = clone $this->complexAttributeBag;
        $copy->drawingTasks = array();
        $copy->positionTranslation = null;
        $copy->ancestorWithFontSize = null;
        $copy->ancestorWithRotation = null;
        $copy->closestAncestorWithPosition = null;

        return $copy;
    }

    final protected function __clone()
    {
    }

    /**
     * Translates position of this node
     * 
     * @param float $x X coord of translation vector
     * @param float $y Y coord of translation vector
     */
    public function translate($x, $y)
    {
        if(!$x && !$y)
        {
            return;
        }
        
        $this->getBoundary()->translate($x, $y);
    }

    /**
     * Resizes node by passed sizes
     * 
     * @param float $x Value of width's resize
     * @param float $y Value of height's resize
     */
    public function resize($x, $y)
    {
        if(!$x && !$y)
        {
            return;
        }

        $diagonalXCoord = $this->getDiagonalPoint()->getX() - $this->getPaddingRight();
        $firstXCoord = $this->getFirstPoint()->getX() + $this->getPaddingLeft();

        $this->getBoundary()->pointTranslate(1, $x, 0);
        $this->getBoundary()->pointTranslate(2, $x, $y);
        $this->getBoundary()->pointTranslate(3, 0, $y);
        
        $this->setHeight($this->getHeight() + $y);
        $this->setWidth($this->getWidth() + $x);

        foreach($this->getChildren() as $child)
        {
            if($child->getFloat() === Node::FLOAT_RIGHT)
            {
                $child->translate($x - $this->getPaddingRight(), 0);
            }
            else
            {
                $childDiagonalXCoord = $child->getDiagonalPoint()->getX() + $child->getMarginRight();
                $childFirstXCoord = $child->getFirstPoint()->getX();
    
                $relativeWidth = $child->getRelativeWidth();
    
                if($relativeWidth !== null)
                {
                    $relativeWidth = ($x + $diagonalXCoord - $firstXCoord)*((int) $relativeWidth)/100;
                    $childResize = (($childFirstXCoord + $relativeWidth) + $child->getMarginRight()) - $childDiagonalXCoord;
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
    }

    /**
     * Break node at passed $height.
     *
     * @param integer $height
     * @return \PHPPdf\Core\Node\Node|null Second node created afted breaking
     */
    public function breakAt($height)
    {
        if(!$this->shouldBeBroken($height))
        {
            return null;
        }

        return $this->doBreakAt($height);
    }
    
    public function shouldBeBroken($height)
    {
        if($height <= 0 || $height >= $this->getHeight())
        {
            return false;
        }
        
        try
        {
            $page = $this->getPage();
            if($page && $page->getHeight() < $this->getHeight())
            {
                return true;
            }
        }
        catch(\LogicException $e)
        {
            //if node has no parent, breakable attribute will decide
        }
        
        return $this->getAttribute('breakable');
    }

    protected function doBreakAt($height)
    {
        $boundary = $this->getBoundary();
        $clonedBoundary = clone $boundary;

        $trueHeight = $boundary->getFirstPoint()->getY() - $boundary->getDiagonalPoint()->getY();
        
        $heightComplement = $trueHeight - $height;

        $boundary->reset();
        $clone = $this->copy();

        $boundary->setNext($clonedBoundary->getPoint(0))
                 ->setNext($clonedBoundary->getPoint(1))
                 ->setNext($clonedBoundary->getPoint(2)->translate(0, - $heightComplement))
                 ->setNext($clonedBoundary->getPoint(3)->translate(0, - $heightComplement))
                 ->close();

        $boundaryOfClone = $clone->getBoundary();
        $boundaryOfClone->reset();

        $boundaryOfClone->setNext($clonedBoundary->getPoint(0)->translate(0, $height))
                        ->setNext($clonedBoundary->getPoint(1)->translate(0, $height))
                        ->setNext($clonedBoundary->getPoint(2))
                        ->setNext($clonedBoundary->getPoint(3))
                        ->close();

        $clone->setHeight($this->getHeight() - $height);
        $this->setHeight($height);

        return $clone;
    }

    /**
     * Adds node as child
     */
    public function add(Node $node)
    {
    }

    /**
     * Removes node from children
     * 
     * @return boolean True if node has been found and succesfully removed, otherwise false
     */
    public function remove(Node $node)
    {
        return false;
    }

    /**
     * @return array Array of Node objects
     */
    public function getChildren()
    {
        return array();
    }
    
    /**
     * @return boolean Node is able to have children?
     */
    public function isLeaf()
    {
        return false;
    }
    
    /**
     * @return boolean True if element is inline, false if block
     */
    public function isInline()
    {
        return false;
    }
    
    /**
     * Check if this node has leaf descendants.
     * 
     * If $bottomYCoord is passed, only descendants above passed coord are checked
     * 
     * @return boolean
     */
    public function hasLeafDescendants($bottomYCoord = null)
    {
        return false;
    }
    
    protected function isAbleToExistsAboveCoord($yCoord)
    {
        foreach($this->getChildren() as $child)
        {
            if($child->isAbleToExistsAboveCoord($yCoord))
            {
                return true;
            }
        }

        return false;
    }
    
    /**
     * Gets child under passed index
     * 
     * @param integer Index of child
     * 
     * @return Node
     * @throws OutOfBoundsException Child dosn't exist
     */
    public function getChild($index)
    {
        $children = $this->getChildren();
        
        if(!isset($children[$index]))
        {
            throw new OutOfBoundsException(sprintf('Child "%s" dosn\'t exist.', $index));
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
        if($parentValue === null && ($parent = $this->parent))
        {
            $parentValue = $this->parent->getAttribute($name);
        }
        
        $potentiallyRelativeValue = $this->getAttribute($name);

        $absoluteValue = $this->unitConverter ? $this->unitConverter->convertPercentageValue($potentiallyRelativeValue, $parentValue) : $potentiallyRelativeValue;

        if($absoluteValue !== $potentiallyRelativeValue)
        {
            $this->setAttribute($name, $absoluteValue);
        }
    }

    /**
     * Format node by given formatters.
     */
    public function format(Document $document)
    {        
        $this->preFormat($document);
        foreach($this->getChildren() as $child)
        {
            $child->format($document);
        }
        $this->postFormat($document);
    }
    
    public function preFormat(Document $document)
    {
        $this->beforeFormat($document);
        
        $this->doFormat('pre', $document);
    }
    
    public function doFormat($type, Document $document)
    {
        $formattersNames = $this->getFormattersNames($type);
        
        $this->invokeFormatter($document, $formattersNames);
    }
    
    private function invokeFormatter(Document $document, array $formattersNames)
    {
        foreach($formattersNames as $formatterName)
        {
            $formatter = $document->getFormatter($formatterName);
            $formatter->format($this, $document);
        }
    }
    
    public function postFormat(Document $document)
    {
        $this->doFormat('post', $document);
    }

    public function setFormattersNames($type, array $formattersNames)
    {
        $this->formattersNames[$type] = $formattersNames;
    }

    public function addFormatterName($type, $formatterName)
    {
        $this->formattersNames[$type][] = $formatterName;
    }

    public function getFormattersNames($type)
    {
        return isset($this->formattersNames[$type]) ? $this->formattersNames[$type] : array();
    }
    
    /**
     * @return \PHPPdf\Core\Engine\GraphicsContext
     */
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

    /**
     * Set placeholder
     * 
     * @param string $name Name of placeholder
     * @param Node $placeholder Object of Node
     * 
     * @throws InvalidArgumentException Placeholder isn't supported by node
     */
    public function setPlaceholder($name, Node $placeholder)
    {
        throw new InvalidArgumentException(sprintf('Placeholder "%s" is not supported by class "%s".', $name, get_class($this)));
    }

    protected function getDataForSerialize()
    {
        $data = array(
            'boundary' => $this->getBoundary(),
            'attributes' => $this->attributes,
            'complexAttributeBag' => $this->complexAttributeBag->getAll(),
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
        if(isset($data['boundary']))
        {
            $this->setBoundary($data['boundary']);
        }
        $this->attributes = $data['attributes'];
        $this->complexAttributeBag = new AttributeBag($data['complexAttributeBag']);

        foreach((array) $data['formattersNames'] as $type => $names)
        {
            $this->setFormattersNames($type, $names);
        }

        $this->priority = $data['priority'];
    }
    
    /**
     * Method from NodeAware interface
     * 
     * @return Node
     */
    public function getNode()
    {
        return $this;
    }

    public function __toString()
    {
        return get_class($this).\spl_object_hash($this);
    }
    
    public function getAncestorWithRotation()
    {
        if($this->ancestorWithRotation === null)
        {
            $parent = $this->parent;
            $this->ancestorWithRotation = $this->getRotate() === null ? ($parent ? $parent->getAncestorWithRotation() : false) : $this;
        }

        return $this->ancestorWithRotation;
    }
    
    protected function getAncestorWithFontSize()
    {
        if($this->ancestorWithFontSize === null)
        {
            $parent = $this->parent;
            $this->ancestorWithFontSize = $this->getFontSize() === null ? ($parent ? $parent->getAncestorWithFontSize() : false) : $this;
        }
        
        return $this->ancestorWithFontSize;
    }
    
    public function getClosestAncestorWithPosition()
    {
        if($this->closestAncestorWithPosition === null)
        {
            if($this->parent)
            {
                $position = $this->parent->getAttribute('position');
                $position = $position ? : self::POSITION_STATIC;
                $this->closestAncestorWithPosition = $position === self::POSITION_STATIC ? $this->parent->getClosestAncestorWithPosition() : $this->parent;
            }
            else 
            {
                $this->closestAncestorWithPosition = false;
            }
        }
        
        return $this->closestAncestorWithPosition;
    }
    
    /**
     * @return Point
     */
    public function getPositionTranslation()
    {
        if($this->positionTranslation === null)
        {
            $this->positionTranslation = $this->calculatePositionTranslation();
        }
        
        return $this->positionTranslation;
    }
    
    private function calculatePositionTranslation()
    {
        $ancestor = $this->getClosestAncestorWithPosition();
        $position = $this->getAttributeDirectly('position');
        
        $position = $position ? : self::POSITION_STATIC;

        if($position === self::POSITION_STATIC && !$ancestor)
        {
            return Point::getInstance(0, 0);
        }
        
        if($position === self::POSITION_RELATIVE && $ancestor)
        {
            if($ancestor)
            {
                $ancestorTranslation = $ancestor->getPositionTranslation();
                return $this->translatePointByPosition($ancestorTranslation);
            }
            else
            {
                return Point::getInstance($this->getAttributeDirectly('left'), $this->getAttributeDirectly('top'));
            }
        }
        elseif($position === self::POSITION_STATIC)
        {
            return $ancestor->getPositionTranslation();
        }
        else
        {
            if($ancestor)
            {
                $ancestorTranslation = $ancestor->getPositionTranslation();

                $top = $this->boundary->getFirstPoint()->getY() - $ancestor->getFirstPoint()->getY() + $this->getAttributeDirectly('top') + $ancestorTranslation->getY();
                $left = $ancestor->getFirstPoint()->getX() - $this->boundary->getFirstPoint()->getX() + $this->getAttributeDirectly('left') + $ancestorTranslation->getX();

                return Point::getInstance($left, $top);
            }
            else
            {
                $firstPoint = $this->getFirstPoint();
                $page = $this->getPage();
                
                $originalLeft = $this->getAttributeDirectly('left');
                $originalTop = $this->getAttributeDirectly('top');

                $left = $originalLeft === null ? 0 : -$firstPoint->getX() + $originalLeft;
                $top = $originalTop === null ? 0 : $firstPoint->getY() - $page->getRealHeight() + $originalTop;

                return Point::getInstance($left, $top);
            }
        }
    }
    
    private function translatePointByPosition(Point $point)
    {
        return $point->translate($this->getAttributeDirectly('left'), $this->getAttributeDirectly('top'));
    }
    
    public function getTranslationAwareBoundary()
    {
        $translation = $this->getPositionTranslation();
        
        $boundary = $this->boundary;
        
        if($translation->getX() != 0 || $translation->getY() != 0)
        {
            $boundary = clone $boundary;
            $boundary->translate($translation->getX(), $translation->getY());
        }
        
        return $boundary;
    }
    
    /**
     * Free references to other object, after this method invocation
     * Node is in invalid state!
     */
    public function flush()
    {
        $this->ancestorWithFontSize = null;
        $this->ancestorWithRotation = null;
        $this->closestAncestorWithPosition = null;

        foreach($this->getChildren() as $child)
        {
            $child->flush();
        }

        $this->removeAll();
    }
    
    public function getShape()
    {
        return self::SHAPE_RECTANGLE;
    }
}