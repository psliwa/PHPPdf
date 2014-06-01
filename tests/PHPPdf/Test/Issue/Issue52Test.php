<?php


namespace PHPPdf\Test\Issue;


use PHPPdf\Core\Document;
use PHPPdf\Core\Engine\ZF\Engine;
use PHPPdf\Core\Node\DynamicPage;
use PHPPdf\PHPUnit\Framework\TestCase;
use ZendPdf\PdfDocument;

class Issue52Test extends TestCase
{
    /**
     * @test
     */
    public function dynamicPageAndDocumentTemplate_setPrototypeSizeFromDocumentTemplate()
    {
        //given

        $page = new DynamicPage();
        $page->setAttribute('document-template', $this->get200x200DocumentTemplate());

        //when

        $page->format($this->createDocument());

        //then

        $this->assertEquals(200, $page->getPrototypePage()->getWidth());
        $this->assertEquals(200, $page->getPrototypePage()->getHeight());
    }

    /**
     * @test
     * @dataProvider booleanProvider
     */
    public function dynamicPageAndDocumentTemplate_placeholdersMissing_useDocumentTemplateNonetheless($placeholdersExists)
    {
        //given

        $placeholders = $placeholdersExists ? '<placeholders><header><div height="10">placeholders</div></header></placeholders>' : '';

        $xml = <<<XML
<pdf>
    <dynamic-page document-template="{$this->get200x200DocumentTemplate()}">
        {$placeholders}
        some text
    </dynamic-page>
</pdf>
XML;

        //when

        $pdfContent = $this->createFacade()->render($xml);

        //then

        $document = new PdfDocument($pdfContent, null, false);

        $this->assertEquals(200, $document->pages[0]->getWidth());
        $this->assertEquals(200, $document->pages[0]->getHeight());

        if($placeholders)
        {
            $this->assertContains('placeholders', $pdfContent);
        }
    }

    public function booleanProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    /**
     * @return Document
     */
    private function createDocument()
    {
        return new Document(new Engine());
    }

    /**
     * @return string
     */
    private function get200x200DocumentTemplate()
    {
        return __DIR__ . '/../../Resources/200x200.pdf';
    }

    /**
     * @return \PHPPdf\Core\Facade
     */
    private function createFacade()
    {
        $facade = \PHPPdf\Core\FacadeBuilder::create()
            ->build();
        return $facade;
    }
} 