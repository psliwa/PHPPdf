<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Parser;

use PHPPdf\Parser\XmlParser;
use PHPPdf\Core\Document;
use PHPPdf\Core\Node\Manager;
use PHPPdf\Core\Node\NodeWrapper;
use PHPPdf\Core\Node\Text,
    PHPPdf\Parser\Exception\ParseException,
    PHPPdf\Core\Node\NodeFactory,
    PHPPdf\Core\Node\PageCollection,
    PHPPdf\Core\Node\Node,
    PHPPdf\Core\Parser\BagContainer,
    PHPPdf\Core\Parser\Exception as Exceptions,
    PHPPdf\Core\ComplexAttribute\ComplexAttributeFactory,
    PHPPdf\Core\Parser\StylesheetConstraint,
    PHPPdf\Core\Node\Behaviour\Factory as BehaviourFactory;

/**
 * Parse document to graph of Nodes
 * 
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class XmlDocumentParser extends XmlParser implements DocumentParser
{
    const ROOT_TAG = 'pdf';
    const ATTRIBUTE_ID = 'id';
    const ATTRIBUTE_NAME = 'name';
    const ATTRIBUTE_EXTENDS = 'extends';
    const ATTRIBUTE_CLASS = 'class';
    const STYLESHEET_TAG = 'stylesheet';
    const PLACEHOLDERS_TAG = 'placeholders';
    const BEHAVIOURS_TAG = 'behaviours';
    
    private $factory = null;
    private $complexAttributeFactory = null;
    private $stylesheetConstraint = null;
    private $stylesheetParser = null;
    private $ignoredTags = array('attribute', 'enhancement', 'complex-attribute');
    private $tagStack = array();
    private $innerParser = null;
    /**
     * @var DocumentParsingContext
     */
    private $context;
    private $endTag = self::ROOT_TAG;
    private $behaviourFactory = null;
    private $nodeManager = null;
    
    private $isPreviousText = false;
    
    private $currentParagraph = null;
    
    private $document;
    
    private $listeners = array();

    public function __construct(ComplexAttributeFactory $complexAttributeFactory, Document $document = null)
    {
        $this->document = $document;
        $factory = new NodeFactory();        
        $stylesheetParser = new StylesheetParser(null, true);
        $stylesheetParser->setComplexAttributeFactory($complexAttributeFactory);

        $this->setNodeFactory($factory);
        $this->setStylesheetParser($stylesheetParser);
        $this->setComplexAttributeFactory($complexAttributeFactory);
        $this->nodeManager = new Manager();
        $this->setBehaviourFactory(new BehaviourFactory());

        $this->initialize();
    }
    
    public function setDocument(Document $document)
    {
        $this->document = $document;
    }
    
    public function getNodeManager()
    {
        return $this->nodeManager;
    }

    private function initialize()
    {
        $stylesheetConstraint = new StylesheetConstraint();
        $this->setStylesheetConstraint($stylesheetConstraint);
        $this->isPreviousText = false;
        $this->currentParagraph = null;
        $this->context = new DocumentParsingContext();
        $this->tagStack = array();
        $this->prototypes = array();
        $this->clearStack();
        $this->nodeManager->clear();
    }
    
    public function addListener(DocumentParserListener $listener)
    {
        $this->listeners[] = $listener;
    }
    
    public function clearListeners()
    {
        $this->listeners = array();
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
     * @return XmlDocumentParser
     */
    private function getInnerParser()
    {
        if($this->innerParser === null)
        {
            $innerParser = new self($this->getComplexAttributeFactory(), $this->document);
            $innerParser->setNodeFactory($this->getNodeFactory());

            $this->innerParser = $innerParser;
        }

        return $this->innerParser;
    }

    /**
     * Parses document and build graph of Node
     * 
     * @return PageCollection Root of node's graph
     */
    public function parse($content, StylesheetConstraint $stylesheetConstraint = null)
    {
        if($stylesheetConstraint !== null)
        {
            $this->setStylesheetConstraint($stylesheetConstraint);
        }

        $pageCollection = parent::parse($content);
        
        $this->fireOnEndParsing($pageCollection);

        $this->initialize();

        return $pageCollection;
    }
    
    private function fireOnEndParsing(PageCollection $root)
    {
        foreach($this->listeners as $listener)
        {
            $listener->onEndParsing($this->document, $root);
        }
    }

    /**
     * @return PageCollection
     */
    protected function createRoot()
    {
        return new PageCollection();
    }

    public function setNodeFactory(NodeFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return NodeFactory
     */
    public function getNodeFactory()
    {
        return $this->factory;
    }
    
    public function setBehaviourFactory(BehaviourFactory $factory)
    {
        $this->behaviourFactory = $factory;
        $factory->setNodeManager($this->nodeManager);
    }

    /**
     * @return ComplexAttributeFactory
     */
    public function getComplexAttributeFactory()
    {
        return $this->complexAttributeFactory;
    }

    public function setComplexAttributeFactory(ComplexAttributeFactory $complexAttributeFactory)
    {
        $this->complexAttributeFactory = $complexAttributeFactory;
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
        $parentNode = $this->getLastElementFromStack();

        if($this->context->isInPlaceholder())
        {
            $this->parsePlaceholder($reader, $parentNode);
        }
        elseif($this->context->isInBehaviour())
        {
            $this->parseBehaviour($reader, $parentNode);
        }
        elseif($tag === self::PLACEHOLDERS_TAG)
        {
            $this->context->enterPlaceholder();
        }
        elseif($tag === self::BEHAVIOURS_TAG)
        {
            $this->context->enterBehaviour();
        }
        elseif($tag === self::STYLESHEET_TAG)
        {
            $this->parseStylesheet($reader, $parentNode);
        }
        else
        {
            $this->parseNode($reader, $parentNode);
        }
    }

    private function parseStylesheet(\XMLReader $reader, Node $node)
    {
        $this->seekReaderToNextTag($reader);
        $constraint = $this->getStylesheetParser()->parse($reader);

        $this->setNodeStylesheet($node, $constraint);
    }

    private function parsePlaceholder(\XMLReader $reader, Node $parentNode)
    {
        $placeholderName = $reader->name;
        $innerParser = $this->getInnerParser();

        $this->seekReaderToNextTag($reader);

        if($parentNode->hasPlaceholder($placeholderName))
        {
            $innerParser->setEndTag($placeholderName);
            $collection = $innerParser->parse($reader, $this->getStylesheetConstraint());
            $placeholder = current($collection->getChildren());

            if($placeholder)
            {
                $parentNode->setPlaceholder($placeholderName, $placeholder);
            }
        }
        else
        {
            $element = end($this->tagStack);

            throw new ParseException(sprintf('Placeholder "%s" is not supported by "%s" tag.', $placeholderName, $element['tag']));
        }
    }
    
    private function parseBehaviour(\XMLReader $reader, Node $parentNode)
    {
        $behaviourName = $reader->name;
        
        $options = array();
        
        while($reader->moveToNextAttribute())
        {
            $options[$reader->name] = $reader->value;
        }
        
        $this->seekReaderToNextTag($reader);
        
        $value = trim((string) $reader->value);

        $parentNode->addBehaviour($this->behaviourFactory->create($behaviourName, $value, $options));
    }

    private function isntIgnoredTag($tag)
    {
        return !in_array($tag, $this->ignoredTags);
    }
    
    private function setNodeStylesheet(Node $node, BagContainer $bagContainer)
    {
        $bagContainer->apply($node);
    }

    private function parseNode(\XMLReader $reader, Node $parentNode)
    {
        $tag = $reader->name;
        $isEmptyElement = $reader->isEmptyElement;

        $node = $this->createNode($reader);
        
        if($this->isntTextNode($node))
        {
            if($this->currentParagraph !== null)
            {
                $this->fireOnEndParseNode($this->currentParagraph);
            }
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
        $this->setNodeStylesheet($node, $bagContainer);
    
        $id = $this->getIdAttribute($reader);
    
        if($id)
        {
            $this->nodeManager->register($id, $node);
        }
        $this->setBehavioursFromReader($reader, $node);
        $this->setNodeAttributesFromReader($reader, $node);
    
        if($this->isTextNode($node) && $this->isntTextNode($parentNode))
        {
            $parentNode = $this->getCurrentParagraph();
        }

        $parentNode->add($node);
        $this->pushOnStack($node);
        
        $this->fireOnStartParseNode($node);

        if($isEmptyElement)
        {
            $this->parseEndElement($reader);
        }
    }
    
    private function getIdAttribute(\XMLReader $reader)
    {
        foreach(array(self::ATTRIBUTE_ID, self::ATTRIBUTE_NAME) as $attributeName)
        {
            $id = $reader->getAttribute($attributeName);
            
            if($id)
            {
                return $id;
            }
        }
        
        return null;
    }
    
    private function fireOnStartParseNode(Node $node)
    {
        foreach($this->listeners as $listener)
        {
            $listener->onStartParseNode($this->document, $this->getFirstElementFromStack(), $node, $this->context);
        }
    }

    private function createNode(\XMLReader $reader)
    {
        $extends = $reader->getAttribute('extends');
        $tag = $reader->name;

        if($extends)
        {
            $parent = $this->nodeManager->get($extends);
            
            if($parent->getNode() == null)
            {
                throw new Exceptions\IdNotFoundException(sprintf('Element with id="%s" dosn\'t exist.', $extends));
            }

            $node = $parent->getNode()->copy();
            $node->removeAll();
        }
        else
        {
            $node = $this->createNodeByTag($tag);            
        }

        $node->setUnitConverter($this->document);

        return $node;
    }
    
    private function createNodeByTag($tag)
    {
        try
        {
            return $this->getNodeFactory()->create($tag);
        }
        catch(\PHPPdf\Core\Exception\UnregisteredNodeException $e)
        {
            throw new ParseException(sprintf('Unknown tag "%s".', $tag), 0, $e);
        }
    }
    
    private function isTextNode(Node $node)
    {
        return $node instanceof Text;
    }
    
    private function isntTextNode(Node $node)
    {
        return !$this->isTextNode($node);
    }

    private function pushOnTagStack($tag, $class)
    {
        $class = (string) $class;
        $classes = $class ? explode(' ', $class) : array();

        array_push($this->tagStack, array('tag' => $tag, 'classes' => $classes));
    }

    private function setNodeAttributesFromReader(\XMLReader $reader, Node $node)
    {
        $bagContainer = new BagContainer();
        
        $stylesheetParser = $this->getStylesheetParser();
        
        $ignoredTags = array_merge($this->behaviourFactory->getSupportedBehaviourNames(), array(self::ATTRIBUTE_ID, self::ATTRIBUTE_NAME, self::ATTRIBUTE_EXTENDS, self::ATTRIBUTE_CLASS));
        
        $stylesheetParser->addConstraintsFromAttributes($bagContainer, $reader, $ignoredTags);

        $this->setNodeStylesheet($node, $bagContainer);
    }
    
    private function setBehavioursFromReader(\XMLReader $reader, Node $node)
    {
        foreach($this->behaviourFactory->getSupportedBehaviourNames() as $name)
        {
            $value = $reader->getAttribute($name);
            if($value)
            {                
                $node->addBehaviour($this->behaviourFactory->create($name, $value));
            }
        }
    }

    protected function parseEndElement(\XMLReader $reader)
    {
        if($reader->name === self::PLACEHOLDERS_TAG)
        {
            $this->context->exitPlaceholder();
            $node = $this->getLastElementFromStack();
            $this->fireOnEndParsePlaceholders($node);
        }
        elseif($this->context->isInBehaviour() && $reader->name === self::BEHAVIOURS_TAG)
        {
            $this->context->exitBehaviour();
        }
        elseif(!$this->context->isInBehaviour())
        {
            $node = $this->getLastElementFromStack();

            if($this->isntTextNode($node))
            {
                $this->isPreviousText = false;
                
                if($this->currentParagraph !== null)
                {
                    $this->fireOnEndParseNode($this->currentParagraph);
                }
                $this->currentParagraph = null;
            }
            
            if($reader->name !== self::ROOT_TAG)
            {
                $this->fireOnEndParseNode($node);
            }
            
            $this->popFromStack();
            $this->popFromTagStack();
        }
    }
    
    private function fireOnEndParsePlaceholders(Node $node)
    {
        foreach($this->listeners as $listener)
        {
            $listener->onEndParsePlaceholders($this->document, $this->getFirstElementFromStack(), $node, $this->context);
        }
    }
    
    private function fireOnEndParseNode(Node $node)
    {
        foreach($this->listeners as $listener)
        {
            $listener->onEndParseNode($this->document, $this->getFirstElementFromStack(), $node, $this->context);
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

        if($text !== '')
        {
            $this->isPreviousText = true;
            $parentNode = $this->getLastElementFromStack();

            if($this->isntTextNode($parentNode))
            {
                $parentNode = $this->getCurrentParagraph();
            }

            $textNode = $this->getNodeFactory()->create('text');
            $textNode->setText($text);
            
            $parentNode->add($textNode);
            
            if($this->isntTextNode($parentNode))
            {
                $this->fireOnStartParseNode($textNode);
                $this->fireOnEndParseNode($textNode);
            }
        }
    }
    
    private function getCurrentParagraph()
    {
        if($this->currentParagraph === null)
        {
            $this->currentParagraph = $this->getNodeFactory()->create('paragraph');
            $parentNode = $this->getLastElementFromStack();
            
            $parentNode->add($this->currentParagraph);
            
            $this->fireOnStartParseNode($this->currentParagraph);
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