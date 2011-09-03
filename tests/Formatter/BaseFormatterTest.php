<?php

use PHPPdf\Formatter\BaseFormatter;

class StubFormatter extends BaseFormatter
{
    public function format(PHPPdf\Node\Node $node, PHPPdf\Document $document)
    {
    }
}

abstract class BaseFormatterTest extends TestCase
{
    private $formatter;

    public function setUp()
    {
        $this->formatter = new StubFormatter();
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function throwExceptionIfTryToGetUnsettedDocument()
    {
        $this->formatter->getDocument();
    }

    /**
     * @test
     */
    public function dontThrowExceptionIfDocumentIsSet()
    {
        $document = new PHPPdf\Document();
        $this->formatter->setDocument($document);

        $this->assertTrue($document === $this->formatter->getDocument());
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function unserializedFormatterHaveDocumentDetached()
    {
        $document = new PHPPdf\Document();
        $this->formatter->setDocument($document);

        $unserializedFormatter = unserialize(serialize($this->formatter));

        $unserializedFormatter->getDocument();
    }
}