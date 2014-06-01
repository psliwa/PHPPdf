<?php


namespace PHPPdf\Test\Issue;


use PHPPdf\Core\Document;
use PHPPdf\Core\Engine\ZF\Engine;
use PHPPdf\Core\Node\DynamicPage;
use PHPPdf\PHPUnit\Framework\TestCase;

class Issue52Test extends TestCase
{
    /**
     * @test
     */
    public function dynamicPageAndDocumentTemplate_setPrototypeSizeFromDocumentTemplate()
    {
        //given

        $page = new DynamicPage();
        $page->setAttribute('document-template', __DIR__.'/../../Resources/200x200.pdf');

        //when

        $page->format($this->createDocument());

        //then

        $this->assertEquals(200, $page->getPrototypePage()->getWidth());
        $this->assertEquals(200, $page->getPrototypePage()->getHeight());
    }

    /**
     * @return Document
     */
    private function createDocument()
    {
        return new Document(new Engine());
    }
} 