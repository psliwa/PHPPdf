<?php

namespace PHPPdf\Glyph;

use PHPPdf\Document,
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
abstract class AbstractGlyph implements Glyph, \ArrayAccess, \Serializable
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

    private $attributes = array();
    private $attributesSnapshot = null;
    private $attributeGetters = array();
    private $attributeSetters = array();
    private $priority = 0;

    private $parent = null;
    private $hadAutoMargins = false;
    private $relativeWidth = null;

    private $boundary = null;

    private $enhancements = array();
    private $enhancementBag = null;
    private $drawingTasks = array();
    private $formattersNames = array();

    public function __construct(array $attributes = array())
    {
        $this->boundary = new Boundary();

        $this->initialize();
        $this->setAttributes($attributes);
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

    protected function setBoundary(Boundary $boundary)
    {
        $this->boundary = $boundary;
    }

    /**
     * Get point of left upper corner of this glyph or null if boundaries have not been
     * calculated yet.
     *
     * @return PHPPdf\Util\Point
     */
    public function getFirstPoint()
    {
        return $this->getBoundary()->getFirstPoint();
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
        $this->addAttribute('width', null, 'getWidth', 'setWidth');
        $this->addAttribute('height', null, 'getHeight', 'setHeight');

        $this->addAttribute('min-width', 0);

        $this->addAttribute('margin-top', null, 'getMarginTop', 'setMarginTop');
        $this->addAttribute('margin-left', null, 'getMarginLeft', 'setMarginLeft');
        $this->addAttribute('margin-right', null, 'getMarginRight', 'setMarginRight');
        $this->addAttribute('margin-bottom', null, 'getMarginBottom', 'setMarginBottom');

        $this->addAttribute('margin', null, null, 'setMargin');
        $this->addAttribute('padding', null, null, 'setPadding');

        $this->addAttribute('font-type', null, 'getFontType', 'setFontType');
        $this->addAttribute('font-size', null, null, 'setFontSize');

        $this->addAttribute('color');

        $this->addAttribute('display', self::DISPLAY_BLOCK, 'getDisplay', 'setDisplay');

        $this->addAttribute('padding-top', 0, 'getPaddingTop');
        $this->addAttribute('padding-right', 0, 'getPaddingRight');
        $this->addAttribute('padding-bottom', 0, 'getPaddingBottom');
        $this->addAttribute('padding-left', 0, 'getPaddingLeft');
        $this->addAttribute('splittable', true);

        $this->addAttribute('line-height');
        $this->addAttribute('text-align', self::ALIGN_LEFT);

        $this->addAttribute('float', self::FLOAT_NONE, 'getFloat', 'setFloat');
        $this->addAttribute('font-style', null);
        $this->addAttribute('static-size', false, null, 'setStaticSize');
        $this->addAttribute('page-break', false);

        $this->enhancementBag = new EnhancementBag();
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

    /**
     * Get font resource object
     * @return \Zend_Pdf_Resource_Font
     */
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

    public function getFontType()
    {
        return $this->getAttributeDirectly('font-type');
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

        if(isset($this->attributeSetters[$name]))
        {
            $methodName = $this->attributeSetters[$name];
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
            throw new \InvalidArgumentException(sprintf('Class "%s" dosn\'t have "%s" attribute.', get_class($this), $name));
        }
    }

    private function getAttributeMethodName($prefix, $name)
    {
        $parts = \explode('-', $name);

        return sprintf('%s%s', $prefix, \implode('', $parts));
    }

    protected function addAttribute($name, $default = null, $getter = null, $setter = null)
    {
        $this->setAttributeDirectly($name, $default);
        
        if($getter !== null)
        {
            $this->attributeGetters[$name] = $getter;
        }
        
        if($setter !== null)
        {
            $this->attributeSetters[$name] = $setter;
        }
    }
    
    public function setStaticSize($size)
    {
        $this->setAttributeDirectly('static-size', $size);
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

        if(isset($this->attributeGetters[$name]))
        {
            $methodName = $this->attributeGetters[$name];
            return $this->$methodName();
        }
        else
        {
            return $this->getAttributeDirectly($name);
        }        
    }

    /**
     * Getting attribute from this glyph or parents. If value of attribute is null,
     * this method is recurse invking on parent.
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
        $enhancements = $document->getEnhancements($this->enhancementBag);
        foreach($enhancements as $enhancement)
        {
            $callback = array($enhancement, 'enhance');
            $args = array($this->getPage(), $this);
            $priority = $enhancement->getPriority() + $this->getPriority();
            $this->addDrawingTask(new DrawingTask($callback, $args, $priority));
        }
    }

    public function getPriority()
    {
        return $this->priority;
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
        if(!$this->getSplittable() || $height <= 0 || $height >= $this->getHeight())
        {
            return null;
        }

        return $this->doSplit($height);
    }

    protected function doSplit($height)
    {
        $boundary = $this->getBoundary();
        $clonedBoundary = clone $boundary;

        $heightComplement = $this->getHeight() - $height;

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

    public function getNumberOfChildren()
    {
        return count($this->getChildren());
    }

    public function removeAll()
    {
    }

    /**
     * @param string $method Method name
     * @param array $arguments Method arguments
     * @return mixed
     * @throws \BadMethodCallException Attribute and method doesn\' exist
     */
    public function __call($method, array $arguments)
    {
        $causedException = null;
        try
        {
            $prefix = substr($method, 0, 3);
            if(in_array($prefix, array('get', 'set')))
            {
                $attributeNameParts = $this->uncamelize(substr($method, 3));
                $attributeName = implode('-', $attributeNameParts);

                if($prefix === 'get')
                {
                    return $this->getAttribute($attributeName);
                }
                else
                {
                    return $this->setAttribute($attributeName, current($arguments));
                }
            }
        }
        catch(\InvalidArgumentException $e)
        {
            $causedException = $e;
        }

        throw new \BadMethodCallException(sprintf('Method %s::%s dosn\'t exist.', get_class($this), $method), 0, $causedException);
    }

    private function uncamelize($string)
    {
        $pattern = '/[A-Z][a-z0-9]+/';

        $matches = array();
        if(preg_match_all($pattern, $string, $matches))
        {
            array_walk($matches[0], function(&$value){
                $value = strtolower($value);
            });
        }

        return (isset($matches[0]) ? $matches[0] : array());
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
            'getters' => $this->attributeGetters,
            'setters' => $this->attributeSetters,
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
        $this->attributeGetters = $data['getters'];
        $this->attributeSetters = $data['setters'];
    }

    public function __toString()
    {
        return get_class($this).\spl_object_hash($this);
    }
}