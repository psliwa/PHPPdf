<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Parser;

use PHPPdf\Document;

use PHPPdf\Glyph\Manager;

use PHPPdf\Glyph\GlyphWrapper;

use PHPPdf\Glyph\Text,
    PHPPdf\Parser\Exception\ParseException,
    PHPPdf\Glyph\Factory as GlyphFactory,
    PHPPdf\Glyph\PageCollection,
    PHPPdf\Glyph\Glyph,
    PHPPdf\Parser\BagContainer,
    PHPPdf\Parser\Exception as Exceptions,
    PHPPdf\Enhancement\Factory as EnhancementFactory,
    PHPPdf\Parser\StylesheetConstraint,
    PHPPdf\Glyph\Behaviour\Factory as BehaviourFactory;

/**
 * Parse document to graph of Glyphs
 * 
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
    const BEHAVIOURS_TAG = 'behaviours';
    
    private $factory = null;
    private $enhancementFactory = null;
    private $stylesheetConstraint = null;
    private $prototypes = array();
    private $stylesheetParser = null;
    private $ignoredTags = array('attribute', 'enhancement');
    private $tagStack = array();
    private $innerParser = null;
    private $inPlaceholder = false;
    private $inBehaviour = false;
    private $endTag = self::ROOT_TAG;
    private $behaviourFactory = null;
    private $glyphManager = null;
    
    private $isPreviousText = false;
    
    private $currentParagraph = null;
    
    private $wrappers = array();
    private $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
        $factory = new GlyphFactory();        
        $stylesheetParser = new StylesheetParser(null, true);
        $enhancementFactory = new EnhancementFactory();

        $this->setGlyphFactory($factory);
        $this->setStylesheetParser($stylesheetParser);
        $this->setEnhancementFactory($enhancementFactory);
        $this->glyphManager = new Manager();
        $this->setBehaviourFactory(new BehaviourFactory());

        $this->initialize();
    }

    private function initialize()
    {
        $stylesheetConstraint = new StylesheetConstraint();
        $this->setStylesheetConstraint($stylesheetConstraint);
        $this->isPreviousText = false;
        $this->currentParagraph = null;
    }
    
    protected function createReader($content)
    {
        $reader = new \XMLReader();

        $reader->XML($content, null, LIBXML_DTDLOAD);
        $reader->setParserProperty(\XMLReader::SUBST_ENTITIES, true);
        
        return $reader;
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
            $innerParser = new self($this->document);
            $innerParser->setEnhancementFactory($this->getEnhancementFactory());
            $innerParser->setGlyphFactory($this->getGlyphFactory());

            $this->innerParser = $innerParser;
        }

        return $this->innerParser;
    }

    /**
     * Parses document and build graph of Glyph
     * 
     * @return PageCollection Root of glyph's graph
     */
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
    
    public function setBehaviourFactory(BehaviourFactory $factory)
    {
        $this->behaviourFactory = $factory;
        $factory->setGlyphManager($this->glyphManager);
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
        elseif($this->inBehaviour)
        {
            $this->parseBehaviour($reader, $parentGlyph);
        }
        elseif($tag === self::PLACEHOLDERS_TAG)
        {
            $this->inPlaceholder = true;
        }
        elseif($tag === self::BEHAVIOURS_TAG)
        {
            $this->inBehaviour = true;
        }
        elseif($tag === self::STYLESHEET_TAG)
        {
            $this->parseStylesheet($reader, $parentGlyph);
        }
        else
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
            $element = end($this->tagStack);

            throw new ParseException(sprintf('Placeholder "%s" is not supported by "%s" tag.', $placeholderName, $element['tag']));
        }
    }
    
    private function parseBehaviour(\XMLReader $reader, Glyph $parentGlyph)
    {
        $behaviourName = $reader->name;
        
        $this->seekReaderToNextTag($reader);
        
        $value = trim((string) $reader->value);

        $parentGlyph->addBehaviour($this->behaviourFactory->create($behaviourName, $value));
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
        $tag = $reader->name;
        $isEmptyElement = $reader->isEmptyElement;

        $glyph = $this->createGlyph($reader);
        
        if($this->isntTextGlyph($glyph))
        {
            $this->currentParagraph = null;
            $this->isPreviousText = false;
        }
        else
        {
            $this->isPreviousText = true;
        }

        $class = $reader->getAttribute('class');
        $this->pushOnTagStack($tag, $class);
    
        $bagContainer = $this->getStylesheetConstraint()->find($this->tagStack);
        $this->setGlyphStylesheet($glyph, $bagContainer);
    
        $id = $reader->getAttribute(self::ATTRIBUTE_ID);
    
        if($id)
        {
            $this->glyphManager->register($id, $glyph);
        }
        $this->setBehavioursFromReader($reader, $glyph);
        $this->setGlyphAttributesFromReader($reader, $glyph);
    
        if($this->isTextGlyph($glyph) && $this->isntTextGlyph($parentGlyph))
        {
            $parentGlyph = $this->getCurrentParagraph();
        }

        $parentGlyph->add($glyph);
        $this->pushOnStack($glyph);

        if($isEmptyElement)
        {
            $this->parseEndElement($reader);
        }
    }

    private function createGlyph(\XMLReader $reader)
    {
        $extends = $reader->getAttribute('extends');
        $tag = $reader->name;

        if($extends)
        {
            $parent = $this->glyphManager->get($extends);
            
            if($parent->getGlyph() == null)
            {
                throw new Exceptions\IdNotFoundException(sprintf('Element with id="%s" dosn\'t exist.', $extends));
            }

            $glyph = $parent->getGlyph()->copy();
            $glyph->removeAll();
        }
        else
        {
            $glyph = $this->createGlyphByTag($tag);            
        }

        return $glyph;
    }
    
    private function createGlyphByTag($tag)
    {
        try
        {
            return $this->getGlyphFactory()->create($tag);
        }
        catch(\PHPPdf\Exception\UnregisteredGlyphException $e)
        {
            throw new ParseException(sprintf('Unknown tag "%s".', $tag), 0, $e);
        }
    }
    
    private function isTextGlyph(Glyph $glyph)
    {
        return $glyph instanceof Text;
    }
    
    private function isntTextGlyph(Glyph $glyph)
    {
        return !$this->isTextGlyph($glyph);
    }

    private function pushOnTagStack($tag, $class)
    {
        $class = (string) $class;
        $classes = $class ? explode(' ', $class) : array();

        array_push($this->tagStack, array('tag' => $tag, 'classes' => $classes));
    }

    private function setGlyphAttributesFromReader(\XMLReader $reader, Glyph $glyph)
    {
        $bagContainer = new BagContainer();
        
        $stylesheetParser = $this->getStylesheetParser();
        
        $ignoredTags = array_merge($this->behaviourFactory->getSupportedBehaviourNames(), array(self::ATTRIBUTE_ID, self::ATTRIBUTE_EXTENDS, self::ATTRIBUTE_CLASS));
        
        $stylesheetParser->addConstraintsFromAttributes($bagContainer, $reader, $ignoredTags);

        $this->setGlyphStylesheet($glyph, $bagContainer);
    }
    
    private function setBehavioursFromReader(\XMLReader $reader, Glyph $glyph)
    {
        foreach($this->behaviourFactory->getSupportedBehaviourNames() as $name)
        {
            $value = $reader->getAttribute($name);
            if($value)
            {                
                $glyph->addBehaviour($this->behaviourFactory->create($name, $value));
            }
        }
    }

    protected function parseEndElement(\XMLReader $reader)
    {
        if($reader->name === self::PLACEHOLDERS_TAG)
        {
            $this->inPlaceholder = false;
        }
        elseif($this->inBehaviour && $reader->name === self::BEHAVIOURS_TAG)
        {
            $this->inBehaviour = false;
        }
        elseif(!$this->inBehaviour)
        {
            $glyph = $this->popFromStack();
            
            if($this->isntTextGlyph($glyph))
            {
                $this->isPreviousText = false;
                $this->currentParagraph = null;
            }
            
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
        
        $text = str_replace(array("\n", "\r", "\t"), '', $text);
        if(!$this->isPreviousText)
        {
            $text = ltrim($text);
        }

        if($text)
        {
            $this->isPreviousText = true;
            $parentGlyph = $this->getLastElementFromStack();

            if($this->isntTextGlyph($parentGlyph))
            {
                $parentGlyph = $this->getCurrentParagraph();
            }

            $textGlyph = $this->getGlyphFactory()->create('text');
            $textGlyph->setText($text);
            
            $parentGlyph->add($textGlyph);
        }
    }
    
    private function getCurrentParagraph()
    {
        if($this->currentParagraph === null)
        {
            $this->currentParagraph = $this->getGlyphFactory()->create('paragraph');
            $parentGlyph = $this->getLastElementFromStack();
            
            $parentGlyph->add($this->currentParagraph);
        }
        
        return $this->currentParagraph;
    }

    protected function isEndOfParsedDocument(\XMLReader $reader)
    {
        return $reader->name == $this->endTag;
    }
    
    protected function parseRootAttributes(\XMLReader $reader)
    {
        while($reader->moveToNextAttribute())
        {
            $name = $reader->name;
            $value = $reader->value;
            
            $this->document->setMetadataValue($name, $value);
        }
    }
}