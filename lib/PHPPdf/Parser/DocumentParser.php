<?php

namespace PHPPdf\Parser;

use PHPPdf\Glyph\Factory as GlyphFactory,
    PHPPdf\Glyph\PageCollection,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Parser\BagContainer,
    PHPPdf\Parser\Exception as Exceptions,
    PHPPdf\Enhancement\Factory as EnhancementFactory,
    PHPPdf\Parser\StylesheetConstraint;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class DocumentParser extends XmlParser
{
    const ROOT_TAG = 'pdf';
    const ATTRIBUTE_ID = 'id';
    const ATTRIBUTE_EXTENDS = 'extends';
    const ATTRIBUTE_CLASS = 'class';
    const STYLESHEET_TAG = 'stylesheet';
    const PLACEHOLDERS_TAG = 'placeholders';

    private $factory = null;
    private $enhancementFactory = null;
    private $stylesheetConstraint = null;
    private $prototypes = array();
    private $stylesheetParser = null;
    private $ignoredTags = array('attribute', 'enhancement');
    private $tagStack = array();
    private $innerParser = null;
    private $inPlaceholder = false;
    private $endTag = self::ROOT_TAG;

    public function __construct()
    {
        $factory = new GlyphFactory();        
        $stylesheetParser = new StylesheetParser();
        $enhancementFactory = new EnhancementFactory();

        $this->setGlyphFactory($factory);
        $this->setStylesheetParser($stylesheetParser);
        $this->setEnhancementFactory($enhancementFactory);

        $this->initialize();
    }

    private function initialize()
    {
        $stylesheetConstraint = new StylesheetConstraint();
        $this->setStylesheetConstraint($stylesheetConstraint);
    }

    private function setEndTag($tag)
    {
        $this->endTag = $tag;
    }

    /**
     * @return DocumentParser
     */
    private function getInnerParser()
    {
        if($this->innerParser === null)
        {
            $innerParser = new self();
            $innerParser->setEnhancementFactory($this->getEnhancementFactory());
            $innerParser->setGlyphFactory($this->getGlyphFactory());

            $this->innerParser = $innerParser;
        }

        return $this->innerParser;
    }

    public function parse($content, StylesheetConstraint $stylesheetConstraint = null)
    {
        if($stylesheetConstraint !== null)
        {
            $this->setStylesheetConstraint($stylesheetConstraint);
        }

        $pageCollection = parent::parse($content);

        $this->initialize();

        return $pageCollection;
    }

    /**
     * @return PageCollection
     */
    protected function createRoot()
    {
        return new PageCollection();
    }

    public function setGlyphFactory(GlyphFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return GlyphFactory
     */
    public function getGlyphFactory()
    {
        return $this->factory;
    }

    /**
     * @return EnhancementFactory
     */
    public function getEnhancementFactory()
    {
        return $this->enhancementFactory;
    }

    public function setEnhancementFactory(EnhancementFactory $enhancementFactory)
    {
        $this->enhancementFactory = $enhancementFactory;
    }

    /**
     * @return StylesheetConstraint
     */
    protected function getStylesheetConstraint()
    {
        return $this->stylesheetConstraint;
    }

    protected function setStylesheetConstraint(StylesheetConstraint $stylesheetConstraint)
    {
        $this->stylesheetConstraint = $stylesheetConstraint;
    }

    /**
     * @return StylesheetParser
     */
    public function getStylesheetParser()
    {
        return $this->stylesheetParser;
    }

    public function setStylesheetParser(StylesheetParser $stylesheetParser)
    {
        $this->stylesheetParser = $stylesheetParser;
    }

    protected function parseElement(\XMLReader $reader)
    {
        $tag = $reader->name;
        $parentGlyph = $this->getLastElementFromStack();

        if($this->inPlaceholder)
        {
            $this->parsePlaceholder($reader, $parentGlyph);
        }
        elseif($tag === self::PLACEHOLDERS_TAG)
        {
            $this->inPlaceholder = true;
        }
        elseif($tag === self::STYLESHEET_TAG)
        {
            $this->parseStylesheet($reader, $parentGlyph);
        }
        elseif($this->isntIgnoredTag($tag))
        {
            $this->parseGlyph($reader, $parentGlyph);
        }
    }

    private function parseStylesheet(\XMLReader $reader, Glyph $glyph)
    {
        $this->seekReaderToNextTag($reader);
        $constraint = $this->getStylesheetParser()->parse($reader);

        $this->setGlyphStylesheet($glyph, $constraint);
    }

    private function parsePlaceholder(\XMLReader $reader, Glyph $parentGlyph)
    {
        $placeholderName = $reader->name;
        $innerParser = $this->getInnerParser();

        $this->seekReaderToNextTag($reader);

        if($parentGlyph->hasPlaceholder($placeholderName))
        {
            $innerParser->setEndTag($placeholderName);
            $collection = $innerParser->parse($reader, $this->getStylesheetConstraint());
            $placeholder = current($collection->getChildren());

            if($placeholder)
            {
                $parentGlyph->setPlaceholder($placeholderName, $placeholder);
            }
        }
        else
        {
            //TODO exception
        }
    }

    private function isntIgnoredTag($tag)
    {
        return !in_array($tag, $this->ignoredTags);
    }
    
    private function setGlyphStylesheet(Glyph $glyph, BagContainer $bagContainer)
    {
        $attributeBag = $bagContainer->getAttributeBag();
        $enhancementBag = $bagContainer->getEnhancementBag();

        foreach($attributeBag->getAll() as $name => $value)
        {
            $glyph->setAttribute($name, $value);
        }

        foreach($enhancementBag->getAll() as $name => $parameters)
        {
            $glyph->mergeEnhancementAttributes($name, $parameters);
        }
    }

    private function parseGlyph(\XMLReader $reader, Glyph $parentGlyph)
    {
        try
        {
            $tag = $reader->name;
            $glyph = $this->createGlyph($reader);

            $parentGlyph->add($glyph);

            $class = $reader->getAttribute('class');
            $this->pushOnTagStack($tag, $class);

            $bagContainer = $this->getStylesheetConstraint()->find($this->tagStack);
            $this->setGlyphStylesheet($glyph, $bagContainer);

            $id = $reader->getAttribute('id');

            if($id)
            {
                if(isset($this->prototypes[$id]))
                {
                    throw new Exceptions\DuplicatedIdException(sprintf('Duplicate of id "%s".', $id));
                }

                $this->prototypes[$id] = $glyph;
            }
            $this->setGlyphAttributesFromReader($reader, $glyph);

            $this->pushOnStack($glyph);

            if($reader->isEmptyElement)
            {
                $this->parseEndElement($reader);
            }
        }
        catch(\InvalidArgumentException $e)
        {
            throw new Exceptions\ParseException(sprintf('Tag "%s" in undefined.', $tag), 1, $e);
        }
    }

    private function createGlyph(\XMLReader $reader)
    {
        $extends = $reader->getAttribute('extends');
        $tag = $reader->name;

        if($extends)
        {
            if(!isset($this->prototypes[$extends]))
            {
                throw new Exceptions\IdNotFoundException(sprintf('Element with id="%s" dosn\'t exist.', $extends));
            }

            $glyph = $this->prototypes[$extends]->copy();
            $glyph->removeAll();
        }
        else
        {
            $glyph = $this->getGlyphFactory()->create($tag);
        }

        return $glyph;
    }

    private function pushOnTagStack($tag, $class)
    {
        $class = (string) $class;
        $classes = $class ? explode(' ', $class) : array();

        array_push($this->tagStack, array('tag' => $tag, 'classes' => $classes));
    }

    private function setGlyphAttributesFromReader(\XMLReader $reader, Glyph $glyph)
    {
        while($reader->moveToNextAttribute())
        {
            $name = $reader->name;

            if(!in_array($name, array(self::ATTRIBUTE_ID, self::ATTRIBUTE_EXTENDS, self::ATTRIBUTE_CLASS)))
            {

                $attributes[$reader->name] = $reader->value;
                $glyph->setAttribute($reader->name, $reader->value);
            }
        }
    }

    protected function parseEndElement(\XMLReader $reader)
    {
        if($reader->name === self::PLACEHOLDERS_TAG)
        {
            $this->inPlaceholder = false;
        }
        else
        {
            $this->popFromStack();
            $this->popFromTagStack();
        }
    }

    private function popFromTagStack()
    {
        array_pop($this->tagStack);
    }

    protected function parseText(\XMLReader $reader)
    {
        $text = $reader->value;
        $text = str_replace(array("\n", "\t", "\r"), '', $text);
        $text = trim($text);

        if($text)
        {
            $parentGlyph = $this->getLastElementFromStack();

            $textGlyph = $this->getGlyphFactory()->create('text');
            $textGlyph->setText($text);

            $parentGlyph->add($textGlyph);
        }
    }

    protected function isEndOfParsedDocument(\XMLReader $reader)
    {
        return $reader->name == $this->endTag;
    }
}