<?php

namespace PHPPdf\Parser;

use PHPPdf\Parser\DocumentParser,
    PHPPdf\Document,
    PHPPdf\Parser\StylesheetParser,
    PHPPdf\Parser\EnhancementFactoryParser,
    PHPPdf\Parser\FontRegistryParser,
    PHPPdf\Parser\GlyphFactoryParser;

/**
 * Simple facade whom encapsulate logical complexity of this library
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Facade
{
    private $documentParser;
    private $stylesheetParser;
    private $document;

    public function __construct(FacadeParameters $facadeParameters = null)
    {
        if($facadeParameters === null)
        {
            $facadeParameters = FacadeParameters::newInstance();
        }

        $this->setDocumentParser(new DocumentParser());
        $this->setStylesheetParser(new StylesheetParser());
        $this->setDocument(new Document());

        $this->load($facadeParameters->getGlyphsConfigFile(), $facadeParameters->getEnhancementsConfigFile(), $facadeParameters->getFontsConfigFile(), $facadeParameters->getFormattersConfigFile());
    }

    private function load($glyphFactoryConfigFile, $enhancementFactoryConfigFile, $fontRegistryConfigFile, $formattersConfigFile)
    {
        $this->loadGlyphs($glyphFactoryConfigFile);
        $this->loadEnhancements($enhancementFactoryConfigFile);
        $this->loadFonts($fontRegistryConfigFile);
        $this->loadFormatters($formattersConfigFile);
    }

    private function loadGlyphs($file)
    {
        $content = $this->loadFile($file);
        $glyphFactoryParser = new GlyphFactoryParser();

        $glyphFactory = $glyphFactoryParser->parse($content);
        $glyphFactory->addPrototype('dynamic-page', new \PHPPdf\Glyph\DynamicPage($glyphFactory->create('page')));
        
        $this->getDocumentParser()->setGlyphFactory($glyphFactory);
    }

    private function loadFile($file)
    {
        return \file_get_contents($file);
    }

    private function loadEnhancements($file)
    {
        $content = $this->loadFile($file);
        $enhancementFactoryParser = new EnhancementFactoryParser();
        $enhancementFactory = $enhancementFactoryParser->parse($content);

        $this->getDocument()->setEnhancementFactory($enhancementFactory);
        $this->getDocumentParser()->setEnhancementFactory($enhancementFactory);
    }

    private function loadFonts($file)
    {
        $content = $this->loadFile($file);

        $fontRegistryParser = new FontRegistryParser();

        $fonts = $fontRegistryParser->parse($content);

        foreach($fonts as $name => $font)
        {
            $this->getDocument()->getFontRegistry()->register($name, $font);
        }
    }

    private function loadFormatters($file)
    {
        $content = $this->loadFile($file);
        $parser = new FormatterParser($this->getDocument());
        $formatters = $parser->parse($content);

        foreach($formatters as $formatter)
        {
            $this->getDocument()->addFormatter($formatter);
        }
    }

    /**
     *
     * @return DocumentParser
     */
    public function getDocumentParser()
    {
        return $this->documentParser;
    }

    public function getStylesheetParser()
    {
        return $this->stylesheetParser;
    }

    public function setDocumentParser(DocumentParser $documentParser)
    {
        $this->documentParser = $documentParser;
    }

    public function setStylesheetParser(StylesheetParser $stylesheetParser)
    {
        $this->stylesheetParser = $stylesheetParser;
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function setDocument(Document $document)
    {
        $this->document = $document;
    }

    public function render($documentXml, $stylesheetXml = null)
    {
        $stylesheetConstraint = null;

        if($stylesheetXml)
        {
            $stylesheetConstraint = $this->getStylesheetParser()->parse($stylesheetXml);
        }

        $pageCollection = $this->getDocumentParser()->parse($documentXml, $stylesheetConstraint);
        $this->getDocument()->draw($pageCollection);

        $content = $this->getDocument()->render();
        $this->getDocument()->initialize();

        return $content;
    }
}