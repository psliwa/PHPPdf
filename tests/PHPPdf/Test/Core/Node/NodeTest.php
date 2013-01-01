<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\DrawingTaskHeap;

use PHPPdf\Core\Node\Page,
    PHPPdf\Core\ComplexAttribute\Background,
    PHPPdf\Core\ComplexAttribute\Border,
    PHPPdf\Core\Document,
    PHPPdf\Core\Point,
    PHPPdf\Core\Node\Node,
    PHPPdf\Stub\Node\StubNode,
    PHPPdf\Stub\Node\StubComposeNode,
    PHPPdf\ObjectMother\NodeObjectMother,
    PHPPdf\Core\Node\Container;


class NodeTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    const PAGE_HEIGHT = 100;
    
    private $node;
    private $objectMother;

    protected function init()
    {
        $this->objectMother = new NodeObjectMother($this);
    }
    
    public function setUp()
    {
        $this->node = new StubNode();
    }

    /**
     * @test
     * @expectedException PHPPdf\Core\Exception\InvalidAttributeException
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
        $this->assertEquals($secondComposeNode, $this->node->getAncestorByType('PHPPdf\Stub\Node\StubComposeNode'));
        $this->assertEquals($composeNode, $secondComposeNode->getAncestorByType('PHPPdf\Stub\Node\StubComposeNode'));

        $composeNode->add($this->node);
        $this->assertNotEquals($secondComposeNode, $this->node->getAncestorByType('PHPPdf\Stub\Node\StubComposeNode'));
        $this->assertEquals($composeNode, $this->node->getAncestorByType('PHPPdf\Stub\Node\StubComposeNode'));

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
        catch(\BadMethodCallException $e)
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
    public function breakAt()
    {
        $this->node->setWidth(100);
        $this->node->setHeight(80);
        $boundary = $this->node->getBoundary();
        $boundary->setNext(20, 50)
                 ->setNext(70, 50)
                 ->setNext(70, -30)
                 ->setNext(20, -30)
                 ->close();

        $result = $this->node->breakAt(50);

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
    public function breakIfNodeIsHigherThanPageEvenIfBreakableAttributeIsFalse()
    {
        $x = 0;
        $y = 500;
        $height = 600;
        $width = 300;
        
        $boundary = $this->objectMother->getBoundaryStub($x, $y, $width, $height);
        
        $this->invokeMethod($this->node, 'setBoundary', array($boundary));
        $this->node->setWidth($width);
        $this->node->setHeight($height);
        $this->node->setAttribute('breakable', false);
        
        $page = new Page(array('page-size' => $width.':'.($height/2)));
        $page->add($this->node);
        
        $newNode = $this->node->breakAt(500);
        
        $this->assertNotNull($newNode);
    }

    /**
     * @test
     */
    public function breakWhenBreakableAttributeIsOff()
    {
        $this->node->setAttribute('breakable', false);
        $this->node->setWidth(100)->setHeight(200);
        $boundary = $this->node->getBoundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, -100)
                 ->setNext(0, -100)
                 ->close();

        $result = $this->node->breakAt(50);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function attributeSnapshot()
    {
        $this->node->setAttribute('font-size', 12);
        $this->node->makeAttributesSnapshot();
        $snapshot = $this->node->getAttributesSnapshot();

        foreach($snapshot as $name => $value)
        {
            $this->assertEquals($value, $this->node->getAttribute($name));
        }

        $this->node->setAttribute('font-size', 123);

        $this->assertNotEquals($snapshot['font-size'], $this->node->getAttribute('font-size'));
    }

    /**
     * @test
     */
    public function mergeComplexAttributes()
    {
        $this->node->mergeComplexAttributes('border', array('color' => 'red'));
        $this->assertEquals(array('border' => array('color' => 'red')), $this->node->getComplexAttributes());

        $this->node->mergeComplexAttributes('border', array('style' => 'dotted'));
        $this->assertEquals(array('border' => array('color' => 'red', 'style' => 'dotted')), $this->node->getComplexAttributes());
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
        $this->node->setPlaceholder('name', $this->getMock('PHPPdf\Core\Node\Container'));
    }

    /**
     * @test
     */
    public function gettingBoundaryPoints()
    {
        $boundary = $this->getMock('PHPPdf\Core\Boundary', array('getFirstPoint', 'getDiagonalPoint'));
        $boundary->expects($this->once())
                 ->id('first-point')
                 ->method('getFirstPoint')
                 ->will($this->returnValue(Point::getInstance(10, 10)));

        $boundary->expects($this->once())
                 ->after('first-point')
                 ->method('getDiagonalPoint')
                 ->will($this->returnValue(Point::getInstance(20, 20)));

        $this->invokeMethod($this->node, 'setBoundary', array($boundary));

        $this->assertTrue($this->node->getFirstPoint() == Point::getInstance(10, 10));
        $this->assertTrue($this->node->getDiagonalPoint() == Point::getInstance(20, 20));
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
    public function serializeWithAttributesAndComplexAttributeBagAndFormattersNames()
    {
        $this->node->mergeComplexAttributes('some-complexAttribute', array('attribute' => 'value'));
        $this->node->setAttribute('font-size', 123);
        $this->node->getBoundary()->setNext(0, 0);
        $this->node->addFormatterName('pre', 'SomeName');

        $node = unserialize(serialize($this->node));

        $this->assertEquals($this->node->getComplexAttributes(), $node->getComplexAttributes());
        $this->assertEquals($this->node->getAttribute('font-size'), $node->getAttribute('font-size'));
        $this->assertEquals($this->node->getBoundary(), $node->getBoundary());
        $this->assertEquals($this->node->getFormattersNames('pre'), $node->getFormattersNames('pre'));
    }

    /**
     * @test
     */
    public function callFormattersWhenFormatMethodHasInvoked()
    {
        $formatterName = 'someFormatter';

        $documentMock = $this->getMockBuilder('PHPPdf\Core\Document')
                             ->setMethods(array('getFormatter'))
                             ->disableOriginalConstructor()
                             ->getMock();

        $formatterMock = $this->getMock('PHPPdf\Core\Formatter\Formatter', array('format'));
        $formatterMock->expects($this->once())
                      ->method('format')
                      ->with($this->node, $documentMock);


        $documentMock->expects($this->once())
                     ->method('getFormatter')
                     ->with($formatterName)
                     ->will($this->returnValue($formatterMock));

        $this->node->setFormattersNames('pre', array($formatterName));
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
            array('breakable', 'true', true),
            array('breakable', '1', true),
            array('breakable', 'yes', true),
            array('breakable', 'false', false),
            array('breakable', '0', false),
            array('breakable', 'no', false),
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
        $page = $this->getMock('PHPPdf\Core\Node\Page', array('getAttributeDirectly'));
        $page->expects($this->once())
             ->method('getAttributeDirectly')
             ->will($this->returnValue($encoding));
        
        $this->node->setParent($page);

        $this->assertEquals($encoding, $this->node->getEncoding());
    }
    
    /**
     * @test
     * @dataProvider breakableProvider
     */
    public function nodeIsBreakableIfBreakableAttributeIsSetOrNodeIsHigherThanPage($breakable, $pageHeight, $nodeHeight, $expectedBreakable)
    {
        $page = $this->getMockBuilder('PHPPdf\Core\Node\Page')
                     ->setMethods(array('getHeight'))
                     ->getMock();

        $page->expects($this->atLeastOnce())
             ->method('getHeight')
             ->will($this->returnValue($pageHeight));
             
        $this->node->setParent($page);
        $this->node->setBreakable($breakable);
        $this->node->setHeight($nodeHeight);
        
        $this->assertEquals($expectedBreakable, $this->node->isBreakable());
    }
    
    public function breakableProvider()
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
     * @dataProvider complexAttributeStubsProvider
     */
    public function forEachComplexAttributeAddOneDrawingTask(array $complexAttributeStubs)
    {
        $page = new Page();
        $page->add($this->node);
        
        $document = $this->getMockBuilder('PHPPdf\Core\Document')
                         ->setMethods(array('getComplexAttributes'))
                         ->disableOriginalConstructor()
                         ->getMock();
        $bag = $this->getMockBuilder('PHPPdf\Core\AttributeBag')
                    ->getMock();
                         
                         
        $this->invokeMethod($this->node, 'setComplexAttributeBag', array($bag));
        
        $document->expects($this->once())
                 ->method('getComplexAttributes')
                 ->with($bag)
                 ->will($this->returnValue($complexAttributeStubs));

        $drawingTasks = new DrawingTaskHeap();
        $this->node->collectOrderedDrawingTasks($document, $drawingTasks);
        
        $this->assertEquals(count($complexAttributeStubs), count($drawingTasks));
    }
    
    public function complexAttributeStubsProvider()
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
        $gc = $this->getMockBuilder('PHPPdf\Core\Engine\GraphicsContext')
                   ->getMock();
                   
        $page = new Page();
        $this->invokeMethod($page, 'setGraphicsContext', array($gc));
        $page->add($this->node);
        
        for($i=0; $i<2; $i++)
        {
            $behaviour = $this->getMockBuilder('PHPPdf\Core\Node\Behaviour\Behaviour')
                              ->setMethods(array('doAttach', 'attach'))
                              ->getMock();
            $behaviour->expects($this->once())
                      ->method('attach')
                      ->with($gc, $this->node)
                      ;
            $this->node->addBehaviour($behaviour);
        }
        
        $tasks = new DrawingTaskHeap();
        $this->node->collectUnorderedDrawingTasks($this->createDocumentStub(), $tasks);
        
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
    
    /**
     * @test
     */
    public function useUnitConverterToSetAttributes()
    {
        $converter = $this->getMockBuilder('PHPPdf\Core\UnitConverter')
                         ->getMock();
        $actual = '12px';
        $expected = 123;

        $converter->expects($this->at(0))
                  ->method('convertUnit')
                  ->with($actual)
                  ->will($this->returnValue($expected));
        $converter->expects($this->at(1))
                  ->method('convertUnit')
                  ->with($actual)
                  ->will($this->returnValue($expected));
                         
        $this->node->setUnitConverter($converter);
        
        $this->node->setAttribute('line-height', $actual);
        $this->node->setAttribute('padding-left', $actual);
        
        $this->assertEquals($expected, $this->node->getAttribute('line-height'));
        $this->assertEquals($expected, $this->node->getAttribute('padding-left'));
    }
    
    /**
     * @test
     * @dataProvider positionProvider
     */
    public function getsClosestAncestorWithPosition($position, $expectedFalse)
    {
        $grandparent = new Container();
        $parent = new Container();
        $grandparent->add($parent);
        
        $parent->add($this->node);
        
        $grandparent->setAttribute('position', $position);
        
        $actualAncestor = $this->node->getClosestAncestorWithPosition();
        
        if($expectedFalse)
        {
            $this->assertFalse($actualAncestor);
        }
        else
        {
            $this->assertEquals($grandparent, $actualAncestor);
        }
    }
    
    public function positionProvider()
    {
        return array(
            array(
                Node::POSITION_STATIC, true,
            ),
            array(
                Node::POSITION_ABSOLUTE, false,
            ),
            array(
                Node::POSITION_RELATIVE, false,
            ),
        );
    }
    
    /**
     * @test
     * @dataProvider getsPositionTranslationProvider
     */
    public function getsPositionTranslation($position, $positionPoint, $firstPoint, $parentPosition, $parentPositionTranslation, $parentFirstPoint, $expectedPositionTranslation)
    {
        $this->node->setAttribute('position', $position);
        $this->node->setAttribute('left', $positionPoint[0]);
        $this->node->setAttribute('top', $positionPoint[1]);
        
        $boundary = $this->objectMother->getBoundaryStub($firstPoint[0], $firstPoint[1], 100, 100);
        $this->writeAttribute($this->node, 'boundary', $boundary);
        
        $parent = $this->getMockBuilder('PHPPdf\Core\Node\Container')
                       ->setMethods(array('getPositionTranslation'))
                       ->getMock();
        $parent->setAttribute('position', $parentPosition);
        $parent->expects($this->any())
               ->method('getPositionTranslation')
               ->will($this->returnValue(Point::getInstance($parentPositionTranslation[0], $parentPositionTranslation[1])));
               
        $parentBoundary = $this->objectMother->getBoundaryStub($parentFirstPoint[0], $parentFirstPoint[1], 100, 100);
        $this->writeAttribute($parent, 'boundary', $parentBoundary);

        $this->node->setParent($parent);
        
        $page = new Page();
        $page->setHeight(self::PAGE_HEIGHT);
        $parent->setParent($page);
        
        $expectedPositionTranslation = Point::getInstance($expectedPositionTranslation[0], $expectedPositionTranslation[1]);
        
        $this->assertEquals($expectedPositionTranslation, $this->node->getPositionTranslation());
    }
    
    public function getsPositionTranslationProvider()
    {
        return array(
            array(Node::POSITION_STATIC, array(0, 0), array(0, 0), Node::POSITION_STATIC, array(0, 0), array(0, 0), array(0, 0)),
            array(Node::POSITION_STATIC, array(0, 0), array(0, 0), Node::POSITION_ABSOLUTE, array(10, 20), array(0, 0), array(10, 20)),
            array(Node::POSITION_STATIC, array(0, 0), array(0, 0), Node::POSITION_RELATIVE, array(10, 20), array(0, 0), array(10, 20)),
            array(Node::POSITION_ABSOLUTE, array(11, 12), array(50, 60), Node::POSITION_STATIC, array(0, 0), array(0, 0), array(-39, -28)),
            array(Node::POSITION_ABSOLUTE, array(11, 12), array(50, 60), Node::POSITION_ABSOLUTE, array(10, 20), array(50, 60), array(21, 32)),
            array(Node::POSITION_ABSOLUTE, array(11, 12), array(50, 60), Node::POSITION_RELATIVE, array(null, null), array(40, 70), array(1, 2)),
            array(Node::POSITION_ABSOLUTE, array(11, 12), array(50, 60), Node::POSITION_STATIC, array(0, 0), array(0, self::PAGE_HEIGHT), array(-39, -28)),
        );
    }
}