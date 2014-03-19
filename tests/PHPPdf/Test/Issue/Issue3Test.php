<?php


namespace PHPPdf\Test\Issue;

use PHPPdf\Core\Node\Node;

use PHPPdf\ObjectMother\NodeObjectMother;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Node\Page;
use PHPPdf\Core\Document;
use PHPPdf\Core\Formatter\FloatFormatter;
use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Test\Core\Formatter\Issue\PHPPdf;

class Issue3Test extends TestCase
{
    /**
     * @var PHPPdf\Core\Formatter\FloatFormatter
     */
    private $formatter = null;
    private $document;
    private $objectMother;

    public function setUp()
    {
        $this->formatter = new FloatFormatter();
        $this->document = $this->createDocumentStub();
        $this->objectMother = new NodeObjectMother($this);
    }
    
    /**
     * @test
     */
    public function dontIgnorePaddingBottom()
    {
        $page = new Page();
        
        $paddingBottom = 10;
        $leftFloatedContainerHeight = 20;
        $rightFloatedContainerHeight = 40;
        
        $containerHeight = $paddingBottom + $leftFloatedContainerHeight + $rightFloatedContainerHeight;
        
        $container = new Container();
        $container->makeAttributesSnapshot();

        $container->setPaddingBottom($paddingBottom);
        $container->setHeight($containerHeight);
        $container->setWidth($page->getWidth());
        $this->invokeMethod($container, 'setBoundary', array($this->objectMother->getBoundaryStub(0, $page->getFirstPoint()->getY(), $page->getWidth(), $containerHeight)));
        
        $leftFloatedContainer = new Container();
        $leftFloatedContainer->setFloat(Node::FLOAT_LEFT);
        $leftFloatedContainer->setHeight($leftFloatedContainerHeight);
        $leftFloatedContainer->setWidth(10);
        $this->invokeMethod($leftFloatedContainer, 'setBoundary', array($this->objectMother->getBoundaryStub(0, $page->getFirstPoint()->getY(), 10, $leftFloatedContainerHeight)));
        
        $rightFloatedContainer = new Container();
        $rightFloatedContainer->setFloat(Node::FLOAT_RIGHT);
        $rightFloatedContainer->setHeight($rightFloatedContainerHeight);
        $rightFloatedContainer->setWidth(10);
        $this->invokeMethod($rightFloatedContainer, 'setBoundary', array($this->objectMother->getBoundaryStub(0, $page->getFirstPoint()->getY() + $leftFloatedContainerHeight, 10, $rightFloatedContainerHeight)));
        
        $page->add($container->add($leftFloatedContainer)
                             ->add($rightFloatedContainer));
                          
        $this->formatter->format($container, $this->document);
        
        $expectedHeight = $paddingBottom + $rightFloatedContainerHeight;

        $this->assertEquals($expectedHeight, $container->getHeight());
        $this->assertEquals($expectedHeight, $container->getFirstPoint()->getY() - $container->getDiagonalPoint()->getY());
    }
}