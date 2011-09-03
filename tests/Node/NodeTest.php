<?php

use PHPPdf\Node\Page;
use PHPPdf\Enhancement\Background,
    PHPPdf\Enhancement\Border,
    PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Node\Node,
    PHPPdf\Node\Container;

class StubNode extends Node
{
    public function initialize()
    {
        parent::initialize();
        $this->addAttribute('name-two');
        $this->addAttribute('name', 'value');
    }

    public function setNameTwo($value)
    {
        $this->setAttributeDirectly('name-two', $value.' from setter');
    }
}

class StubComposeNode extends Container
{
}

class NodeTest extends TestCase
{
    private $node;
    private $objectMother;

    protected function init()
    {
        $this->objectMother = new GenericNodeObjectMother($this);
    }
    
    public function setUp()
    {
        $this->node = new StubNode();
    }

    /**
     * @test
     * @expectedException PHPPdf\Exception\InvalidAttributeException
     */
    public function failureSettingAttribute()
    {
        $this->node->setAttribute('someName', 'someValue');
    }

    /**
     * @test
     */
    public function gettingAttribute()
    {
        $this->assertEquals('value', $this->node->getAttribute('name'));
    }

    /**
     * @test
     */
    public function successSettingAttribute()
    {
        $this->node->setAttribute('name', 'value2');
        $this->assertEquals('value2', $this->node->getAttribute('name'));
    }

    /**
     * @test
     */
    public function setter()
    {
        $this->node->setNameTwo('value');
        $this->assertEquals('value from setter', $this->node->getAttribute('name-two'));
    }

    /**
     * @test
     */
    public function gettingAncestor()
    {
        $this->assertNull($this->node->getAncestorByType('Page'));
        $this->assertNull($this->node->getParent());

        $composeNode = new StubComposeNode();
        $secondComposeNode = new StubComposeNode();
        $composeNode->add($secondComposeNode);
        $secondComposeNode->add($this->node);

        $this->assertEquals($secondComposeNode, $this->node->getParent());
        $this->assertEquals($secondComposeNode, $this->node->getAncestorByType('StubComposeNode'));
        $this->assertEquals($composeNode, $secondComposeNode->getAncestorByType('StubComposeNode'));

        $composeNode->add($this->node);
        $this->assertNotEquals($secondComposeNode, $this->node->getAncestorByType('StubComposeNode'));
        $this->assertEquals($composeNode, $this->node->getAncestorByType('StubComposeNode'));

        $this->assertNull($this->node->getWidth());
        $this->node->setWidth(100);
        $this->assertEquals(100, $this->node->getWidth());

        $this->assertNull($this->node->getAttribute('margin-top'));
        $this->node->setAttribute('margin-top', 10);
        $this->assertEquals(10, $this->node->getAttribute('margin-top'));
    }

    /**
     * @test
     */
    public function settingMargin()
    {
        $margins = array('margin-top', 'margin-right', 'margin-bottom', 'margin-left');
        $this->node->setMargin('auto');
        foreach($margins as $margin)
        {
            $this->assertEquals('auto', $this->node->getAttribute($margin));
        }

        $this->node->setMargin('auto', 10);

        $this->assertEquals('auto', $this->node->getMarginTop());
        $this->assertEquals('auto', $this->node->getMarginBottom());
        $this->assertEquals(10, $this->node->getMarginLeft());
        $this->assertEquals(10, $this->node->getMarginRight());

        $this->node->setMargin(5, 10, 15, 20);
        $this->assertEquals(5, $this->node->getMarginTop());
        $this->assertEquals(15, $this->node->getMarginBottom());
        $this->assertEquals(20, $this->node->getMarginLeft());
        $this->assertEquals(10, $this->node->getMarginRight());

        $this->node->setMargin(5, 10, 15);
        $this->assertEquals(5, $this->node->getMarginTop());
        $this->assertEquals(15, $this->node->getMarginBottom());
        $this->assertEquals(5, $this->node->getMarginLeft());
        $this->assertEquals(10, $this->node->getMarginRight());
    }

    /**
     * @test
     */
    public function callMethod()
    {
        try
        {
            $color = '#aaaaaa';
            $result = $this->node->setAttribute('color', $color);
            $this->assertEquals($this->node, $result);
            $this->assertEquals($color, $this->node->getAttribute('color'));
        }
        catch(BadMethodCallException $e)
        {
            $this->fail('exception should not be thrown');
        }
    }

    /**
     * @test
     */
    public function translation()
    {
        $this->node->getBoundary()->setNext(0, 500)
                                   ->setNext(200, 500)
                                   ->setNext(200, 400);

        $this->node->translate(20, 100);

        $this->assertEquals(array(20, 400), $this->node->getStartDrawingPoint());
        $this->assertEquals(array(220, 300), $this->node->getEndDrawingPoint());
    }

    /**
     * @test
     */
    public function paddings()
    {
        $this->node->setWidth(100);
        $this->node->setHeight(80);
        $this->node->setPadding(10);

        $this->assertEquals(80, $this->node->getWidthWithoutPaddings());
        $this->assertEquals(60, $this->node->getHeightWithoutPaddings());
    }

    /**
     * @test
     */
    public function split()
    {
        $this->node->setWidth(100);
        $this->node->setHeight(80);
        $boundary = $this->node->getBoundary();
        $boundary->setNext(20, 50)
                 ->setNext(70, 50)
                 ->setNext(70, -30)
                 ->setNext(20, -30)
                 ->close();

        $result = $this->node->split(50);

        $this->assertEquals(100, $this->node->getWidth());
        $this->assertEquals(50, $this->node->getHeight());
        $this->assertEquals(array(70, 0), $this->node->getEndDrawingPoint());

        $this->assertEquals(100, $result->getWidth());
        $this->assertEquals(30, $result->getHeight());
        $this->assertEquals(array(70, -30), $result->getEndDrawingPoint());
    }
    
    /**
     * @test
     */
    public function splitIfNodeIsHigherThanPageEvenIfSplittableAttributeIsFalse()
    {
        $x = 0;
        $y = 500;
        $height = 600;
        $width = 300;
        
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        
        $this->invokeMethod($this->node, 'setBoundary', array($boundary));
        $this->node->setWidth($width);
        $this->node->setHeight($height);
        $this->node->setAttribute('splittable', false);
        
        $page = new Page(array('page-size' => $width.':'.($height/2)));
        $page->add($this->node);
        
        $newNode = $this->node->split(500);
        
        $this->assertNotNull($newNode);
    }

    /**
     * @test
     */
    public function splitWhenSplitableAttributeIsOff()
    {
        $this->node->setAttribute('splittable', false);
        $this->node->setWidth(100)->setHeight(200);
        $boundary = $this->node->getBoundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, -100)
                 ->setNext(0, -100)
                 ->close();

        $result = $this->node->split(50);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function attributeSnapshot()
    {
        $this->node->makeAttributesSnapshot();
        $snapshot = $this->node->getAttributesSnapshot();

        foreach($snapshot as $name => $value)
        {
            $this->assertEquals($value, $this->node->getAttribute($name));
        }

        $this->node->setAttribute('display', 'none');

        $this->assertNotEquals($snapshot['display'], $this->node->getAttribute('display'));
    }

    /**
     * @test
     */
    public function mergeEnhancements()
    {
        $this->node->mergeEnhancementAttributes('border', array('color' => 'red'));
        $this->assertEquals(array('border' => array('color' => 'red')), $this->node->getEnhancementsAttributes());

        $this->node->mergeEnhancementAttributes('border', array('style' => 'dotted'));
        $this->assertEquals(array('border' => array('color' => 'red', 'style' => 'dotted')), $this->node->getEnhancementsAttributes());
    }

    /**
     * @test
     * @dataProvider pseudoAttributes
     */
    public function marginAndPaddingAsPseudoAttribute($attribute, $value, $expectedTop, $expectedRight, $expectedBottom, $expectedLeft)
    {
        $this->node->setAttribute($attribute, $value);
        
        $this->assertEquals($expectedTop, $this->node->getAttribute($attribute.'-top'));
        $this->assertEquals($expectedRight, $this->node->getAttribute($attribute.'-right'));
        $this->assertEquals($expectedBottom, $this->node->getAttribute($attribute.'-bottom'));
        $this->assertEquals($expectedLeft, $this->node->getAttribute($attribute.'-left'));
    }

    public function pseudoAttributes()
    {
        return array(
            array('margin', '10 20', 10, 20, 10, 20),
            array('padding', '10 20', 10, 20, 10, 20),
            array('margin', '10', 10, 10, 10, 10),
            array('margin', '10 20 30 40', 10, 20, 30, 40),
//            array('margin', '10 20 30', 10, 20, 30, 20),
        );
    }

    /**
     * @test
     */
    public function usePlaceholders()
    {
        $this->assertFalse($this->node->hasPlaceholder('name'));
        $this->assertNull($this->node->getPlaceholder('name'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwExceptionIfNotExistedPlaceholderIsSet()
    {
        $this->node->setPlaceholder('name', $this->getMock('PHPPdf\Node\Container'));
    }

    /**
     * @test
     */
    public function gettingBoundaryPoints()
    {
        $boundary = $this->getMock('PHPPdf\Util\Boundary', array('getFirstPoint', 'getDiagonalPoint'));
        $boundary->expects($this->once())
                 ->id('first-point')
                 ->method('getFirstPoint')
                 ->will($this->returnValue(Point::getInstance(10, 10)));

        $boundary->expects($this->once())
                 ->after('first-point')
                 ->method('getDiagonalPoint')
                 ->will($this->returnValue(Point::getInstance(20, 20)));

        $this->invokeMethod($this->node, 'setBoundary', array($boundary));

        $this->assertTrue($this->node->getFirstPoint() === Point::getInstance(10, 10));
        $this->assertTrue($this->node->getDiagonalPoint() === Point::getInstance(20, 20));
    }

    /**
     * @test
     */
    public function serializeWithoutParentAndBoundary()
    {
        $parent = new StubComposeNode();
        $this->node->setParent($parent);

        $this->node->getBoundary()->setNext(10, 10);
        $node = unserialize(serialize($this->node));

        $this->assertNull($node->getParent());
        $this->assertEquals(1, $node->getBoundary()->count());
    }

    /**
     * @test
     */
    public function serializeWithAttributesAndEnhancementBagAndFormattersNames()
    {
        $this->node->mergeEnhancementAttributes('some-enhancement', array('attribute' => 'value'));
        $this->node->setAttribute('display', 'inline');
        $this->node->getBoundary()->setNext(0, 0);
        $this->node->addFormatterName('SomeName');

        $node = unserialize(serialize($this->node));

        $this->assertEquals($this->node->getEnhancementsAttributes(), $node->getEnhancementsAttributes());
        $this->assertEquals($this->node->getAttribute('display'), $node->getAttribute('display'));
        $this->assertEquals($this->node->getBoundary(), $node->getBoundary());
        $this->assertEquals($this->node->getFormattersNames(), $node->getFormattersNames());
    }

    /**
     * @test
     */
    public function callFormattersWhenFormatMethodHasInvoked()
    {
        $formatterName = 'someFormatter';

        $documentMock = $this->getMock('PHPPdf\Document', array('getFormatter'));

        $formatterMock = $this->getMock('PHPPdf\Formatter\Formatter', array('format'));
        $formatterMock->expects($this->once())
                      ->method('format')
                      ->with($this->node, $documentMock);


        $documentMock->expects($this->once())
                     ->method('getFormatter')
                     ->with($formatterName)
                     ->will($this->returnValue($formatterMock));

        $this->node->setFormattersNames(array($formatterName));
        $this->node->format($documentMock);
    }

    /**
     * @test
     */
    public function setRelativeWidthAttributeWhenPercentageWidthIsSet()
    {
        $this->node->setWidth(100);

        $this->assertNull($this->node->getRelativeWidth());

        $this->node->setWidth('100%');

        $this->assertEquals('100%', $this->node->getRelativeWidth());
    }
    
    /**
     * @test
     */
    public function convertBooleanAttributes()
    {
        $testData = array(
            array('break', 'true', true),
            array('break', '1', true),
            array('break', 'yes', true),
            array('break', 'false', false),
            array('break', '0', false),
            array('break', 'no', false),
            array('static-size', 'true', true),
            array('static-size', '1', true),
            array('static-size', 'yes', true),
            array('static-size', 'false', false),
            array('static-size', '0', false),
            array('static-size', 'no', false),
            array('splittable', 'true', true),
            array('splittable', '1', true),
            array('splittable', 'yes', true),
            array('splittable', 'false', false),
            array('splittable', '0', false),
            array('splittable', 'no', false),
        );
        
        foreach($testData as $data)
        {
            list($attributeName, $attributeValue, $expectedValue) = $data;
            
            $this->node->setAttribute($attributeName, $attributeValue);
        
            $this->assertTrue($expectedValue === $this->node->getAttribute($attributeName));
        }    
    }
    
    /**
     * @test
     */
    public function getEncodingIsDelegatedToPage()
    {
        $encoding = 'some-encoding';
        $page = $this->getMock('PHPPdf\Node\Page', array('getAttribute'));
        $page->expects($this->once())
             ->method('getAttribute')
             ->will($this->returnValue($encoding));
        
        $this->node->setParent($page);

        $this->assertEquals($encoding, $this->node->getEncoding());
    }
    
    /**
     * @test
     * @dataProvider splittableProvider
     */
    public function nodeIsSplittableIfSplittableAttributeIsSetOrNodeIsHigherThanPage($splittable, $pageHeight, $nodeHeight, $expectedSplittable)
    {
        $page = $this->getMockBuilder('PHPPdf\Node\Page')
                     ->setMethods(array('getHeight'))
                     ->getMock();

        $page->expects($this->atLeastOnce())
             ->method('getHeight')
             ->will($this->returnValue($pageHeight));
             
        $this->node->setParent($page);
        $this->node->setSplittable($splittable);
        $this->node->setHeight($nodeHeight);
        
        $this->assertEquals($expectedSplittable, $this->node->isSplittable());
    }
    
    public function splittableProvider()
    {
        return array(
            array(
                false, 100, 50, false,
                true, 100, 50, true,
                true, 100, 100, true,
                false, 100, 100, false,
                false, 100, 101, true,
                true, 100, 101, true,
            ),
        );
    }
    
    /**
     * @test
     * @dataProvider enhancementStubsProvider
     */
    public function forEachEnhancementAddOneDrawingTask(array $enhancementStubs)
    {
        $page = new Page();
        $page->add($this->node);
        
        $document = $this->getMockBuilder('PHPPdf\Document')
                         ->setMethods(array('getEnhancements'))
                         ->getMock();
        $bag = $this->getMockBuilder('PHPPdf\Enhancement\EnhancementBag')
                    ->getMock();
                         
                         
        $this->invokeMethod($this->node, 'setEnhancementBag', array($bag));
        
        $document->expects($this->once())
                 ->method('getEnhancements')
                 ->with($bag)
                 ->will($this->returnValue($enhancementStubs));

        $drawingTasks = $this->node->getDrawingTasks($document);
        
        $this->assertEquals(count($enhancementStubs), count($drawingTasks));
    }
    
    public function enhancementStubsProvider()
    {
        return array(
            array(
                array(new Border(), new Border(), new Background()),
            ),
            array(
                array(),
            ),
        );
    }
    
    /**
     * @test
     */
    public function eachBehavioursShouldBeAttachedByDrawingTasks()
    {
        $gc = $this->getMockBuilder('PHPPdf\Engine\GraphicsContext')
                   ->getMock();
                   
        $page = new Page();
        $this->invokeMethod($page, 'setGraphicsContext', array($gc));
        $page->add($this->node);
        
        for($i=0; $i<2; $i++)
        {
            $behaviour = $this->getMockBuilder('PHPPdf\Node\Behaviour\Behaviour')
                              ->setMethods(array('doAttach', 'attach'))
                              ->getMock();
            $behaviour->expects($this->once())
                      ->method('attach')
                      ->with($gc, $this->node)
                      ;
            $this->node->addBehaviour($behaviour);
        }
        
        $tasks = $this->node->getDrawingTasks(new Document());
        
        foreach($tasks as $task)
        {
            $task->invoke();
        }
    }
    
    /**
     * @test
     * @dataProvider rotationProvider
     */
    public function getAncestorWithRotation($nodeRotation, $parentRotation, $expected)
    {
        $this->node->setAttribute('rotate', $nodeRotation);
        $parent = new Container();
        $parent->setAttribute('rotate', $parentRotation);
        $parent->add($this->node);
        
        switch($expected)
        {
            case 'node':
                $expectedNode = $this->node;
                break;
            case 'parent':
                $expectedNode = $parent;
                break;
            default:
                $expectedNode = false;
        }
        
        $actualNode = $this->node->getAncestorWithRotation();
        
        $this->assertTrue($expectedNode === $actualNode);
    }
    
    public function rotationProvider()
    {
        return array(
            array(null, null, null),
            array(0.4, null, 'node'),
            array(0.4, 0.5, 'node'),
            array(null, 0.5, 'parent'),
        );
    }
    
    /**
     * @test
     * @dataProvider calculateDiagonallyRotationProvider
     */
    public function calculateDiagonallyRotation($rotate, $width, $height, $expectedRadians)
    {
        $page = new Page(array('page-size' => $width.':'.$height));
        $page->add($this->node);
        
        $this->node->setAttribute('rotate', $rotate);
        
        $this->assertEquals($expectedRadians, $this->node->getRotate(), 'diagonally rotate dosn\'t match', 0.001);
    }
    
    public function calculateDiagonallyRotationProvider()
    {
        return array(
            array(Node::ROTATE_DIAGONALLY, 100, 100, pi()/4),
            array(Node::ROTATE_DIAGONALLY, 3, 4, acos(3/5)),
            array(Node::ROTATE_OPPOSITE_DIAGONALLY, 100, 100, -pi()/4),
        );
    }
}