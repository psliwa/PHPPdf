<?php

namespace PHPPdf\Test\Core\Engine\ZF;

use PHPPdf\Core\Engine\EmptyImage;

use PHPPdf\Core\Engine\ZF\Engine;

use PHPPdf\Core\Engine\ZF\GraphicsContext;

class GraphicsContextTest extends \PHPPdf\PHPUnit\Framework\TestCase
{   
    const ENCODING = 'utf-8';
    
    protected function setUp()
    {
        if(!class_exists('ZendPdf\PdfDocument', true))
        {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }
    }
    
    /**
     * @test
     */
    public function clipRectangleWrapper()
    {
        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('clipRectangle'))
                             ->disableOriginalConstructor()
                             ->disableOriginalClone()
                             ->getMock();

        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock->expects($this->once())
                     ->method('clipRectangle')
                     ->with($x1, $y1, $x2, $y2);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->clipRectangle($x1, $y1, $x2, $y2);
        $gc->commit();
    }
    
    private function createGc($engine, $page)
    {
        return new GraphicsContext($engine, $page, self::ENCODING);
    }
    
    private function getEngineMock(array $methods = array())
    {
        $engine = $this->getMockBuilder('PHPPdf\Core\Engine\ZF\Engine')
                       ->setMethods($methods)
                       ->getMock();
        return $engine;
    }

    /**
     * @test
     */
    public function saveAndRestoreGSWrapper()
    {
        $zendPageMock = $this->getMock('\ZendPdf\Page', array('saveGS', 'restoreGS'), array(), '', false);

        $zendPageMock->expects($this->at(0))
                     ->method('saveGS');
        $zendPageMock->expects($this->at(1))
                     ->method('restoreGS');

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->saveGS();
        $gc->restoreGS();
        $gc->commit();
    }

    /**
     * @test
     */
    public function drawImageWrapper()
    {
        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('drawImage'))
                             ->disableOriginalConstructor()
                             ->getMock();
        $zendImage = $this->getMockBuilder('ZendPdf\Resource\Image\AbstractImage')
                          ->disableOriginalClone()
                          ->getMock();

        $image = $this->getMockBuilder('PHPPdf\Core\Engine\ZF\Image')
                      ->setMethods(array('getWrappedImage'))
                      ->disableOriginalConstructor()
                      ->getMock();
        $image->expects($this->once())
              ->method('getWrappedImage')
              ->will($this->returnvalue($zendImage));

        $zendPageMock->expects($this->once())
                     ->method('drawImage')
                     ->with($zendImage, $x1, $y1, $x2, $y2);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawImage($image, $x1, $y1, $x2, $y2);
        $gc->commit();
    }

    /**
     * @test
     */
    public function drawLineWrapper()
    {
        $x1 = 0;
        $x2 = 100;
        $y1 = 0;
        $y2 = 100;

        $zendPageMock = $this->getMock('\ZendPdf\Page', array('drawLine'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('drawLine')
                     ->with($x1, $y1, $x2, $y2);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawLine($x1, $y1, $x2, $y2);
        $gc->commit();
    }

    /**
     * @test
     */
    public function setFontWrapper()
    {
        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('setFont'))
                             ->disableOriginalConstructor()
                             ->getMock();
        $zendFontMock = $this->getMockBuilder('\ZendPdf\Resource\Font\AbstractFont')
                             ->disableOriginalClone()
                             ->getMock();

        $fontMock = $this->getMockBuilder('PHPPdf\Core\Engine\ZF\Font')
                         ->setMethods(array('getCurrentWrappedFont'))
                         ->disableOriginalConstructor()
                         ->getMock();

        $fontMock->expects($this->once())
                 ->method('getCurrentWrappedFont')
                 ->will($this->returnValue($zendFontMock));
        $size = 12;

        $zendPageMock->expects($this->once())
                     ->method('setFont')
                     ->with($zendFontMock, $size);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->setFont($fontMock, $size);
        $gc->commit();
    }

    /**
     * @test
     * @dataProvider colorSetters
     */
    public function setColorsWrapper($method)
    {
        $zendPageMock = $this->getMockBuilder('ZendPdf\Page')
                             ->setMethods(array($method))
                             ->disableOriginalConstructor()
                             ->getMock();

        $color = '#123456';
        $zendColor = \ZendPdf\Color\Html::color($color);

        $zendPageMock->expects($this->once())
                     ->method($method)
                     ->with($zendColor);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->$method($color);

        //don't delegate if not necessary
        $gc->$method($color);
        $gc->commit();
    }

    public function colorSetters()
    {
        return array(
            array('setFillColor'),
            array('setLineColor'),
        );
    }

    /**
     * @test
     */
    public function drawPolygonWrapper()
    {
        $x = array(0, 100, 50);
        $y = array(0, 100, 50);
        $drawType = 1;

        $zendPageMock = $this->getMock('\ZendPdf\Page', array('drawPolygon'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('drawPolygon')
                     ->with($x, $y, $drawType);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawPolygon($x, $y, $drawType);
        $gc->commit();
    }

    /**
     * @test
     */
    public function drawTextWrapper()
    {
        $x = 10;
        $y = 200;
        $text = 'some text';
        $encoding = 'utf-8';

        $zendPageMock = $this->getMock('\ZendPdf\Page', array('drawText'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('drawText')
                     ->with($text, $x, $y, $encoding);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawText($text, $x, $y, $encoding);
        $gc->commit();
    }

    /**
     * @test
     */
    public function drawRoundedRectangleWrapper()
    {
        $x1 = 10;
        $y1 = 100;
        $x2 = 100;
        $y2 = 50;
        $radius = 0.5;
        $fillType = 1;

        $zendPageMock = $this->getMock('\ZendPdf\Page', array('drawRoundedRectangle'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('drawRoundedRectangle')
                     ->with($x1, $y1, $x2, $y2, $radius, $fillType);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $fillType);
        $gc->commit();
    }

    /**
     * @test
     */
    public function setLineWidthWrapper()
    {
        $width = 2.1;

        $zendPageMock = $this->getMock('\ZendPdf\Page', array('setLineWidth'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('setLineWidth')
                     ->with($width);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->setLineWidth($width);

        //don't delegate if not necessary
        $gc->setLineWidth($width);
        $gc->commit();
    }

    /**
     * @test
     * @dataProvider lineDashingPatternProvider
     */
    public function setLineDashingPatternWrapper($pattern, $expected)
    {
        $zendPageMock = $this->getMock('\ZendPdf\Page', array('setLineDashingPattern'), array(), '', false);

        $zendPageMock->expects($this->once())
                     ->method('setLineDashingPattern')
                     ->with($expected);

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->setLineDashingPattern($pattern);

        //don't delegate if not necessary
        $gc->setLineDashingPattern($pattern);
        $gc->commit();
    }

    public function lineDashingPatternProvider()
    {
        return array(
            array(array(0), array(0)),
            array(GraphicsContext::DASHING_PATTERN_SOLID, 0),
            array(GraphicsContext::DASHING_PATTERN_DOTTED, array(1, 2))
        );
    }

    /**
     * @test
     */
    public function cachingGraphicsState()
    {
        $color1 = '#123456';
        $color2 = '#654321';

        $zendPageMock = $this->getMock('\ZendPdf\Page', array('setLineDashingPattern', 'setLineWidth', 'setFillColor', 'setLineColor', 'saveGS', 'restoreGS'), array(), '', false);

        $zendPageMock->expects($this->at(0))
                     ->method('saveGS');
        $zendPageMock->expects($this->at(1))
                     ->method('setLineDashingPattern');        
        $zendPageMock->expects($this->at(2))
                     ->method('setLineWidth');
        $zendPageMock->expects($this->at(3))
                     ->method('setFillColor');        
        $zendPageMock->expects($this->at(4))
                     ->method('setLineColor');
        $zendPageMock->expects($this->at(5))
                     ->method('restoreGS');
        $zendPageMock->expects($this->at(6))
                     ->method('setLineDashingPattern');        
        $zendPageMock->expects($this->at(7))
                     ->method('setLineWidth');
        $zendPageMock->expects($this->at(8))
                     ->method('setFillColor');        
        $zendPageMock->expects($this->at(9))
                     ->method('setLineColor');
        $zendPageMock->expects($this->at(10))
                     ->method('setLineDashingPattern');
        $zendPageMock->expects($this->at(11))
                     ->method('setLineWidth');
        $zendPageMock->expects($this->at(12))
                     ->method('setFillColor');
        $zendPageMock->expects($this->at(13))
                     ->method('setLineColor');


        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);

        $gc->saveGS();
        //second loop pass do not change internal gc state
        for($i=0; $i<2; $i++)
        {
            $gc->setLineDashingPattern(array(1, 1));
            $gc->setLineWidth(1);
            $gc->setFillColor($color1);
            $gc->setLineColor($color1);
        }

        $gc->restoreGS();

        //second loop pass do not change internal gc state
        for($i=0; $i<2; $i++)
        {
            $gc->setLineDashingPattern(array(1, 1));
            $gc->setLineWidth(1);
            $gc->setFillColor($color1);
            $gc->setLineColor($color1);
        }

        //overriding by new values
        $gc->setLineDashingPattern(array(1, 2));
        $gc->setLineWidth(2);
        $gc->setFillColor($color2);
        $gc->setLineColor($color2);
        
        $gc->commit();
    }
    
    private function createColorMock($zendColor, array $components = null)
    {
        $color = $this->getMockBuilder('PHPPdf\Core\Engine\ZF\Color')
                      ->setMethods(array('getWrappedColor', 'getComponents'))
                      ->disableOriginalConstructor()
                      ->getMock();
                      
        $color->expects($this->any())
              ->method('getWrappedColor')
              ->will($this->returnValue($zendColor));
              
        if($components !== null)
        {
            $color->expects($this->any())
                  ->method('getComponents')
                  ->will($this->returnValue($components));
        }
              
        return $color;
    }
    
    /**
     * @test
     */
    public function attachUriAction()
    {
        $uri = 'http://google.com';
        $coords = array(0, 100, 200, 50);

        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('attachAnnotation'))
                             ->disableOriginalConstructor()
                             ->getMock();

        $zendPageMock->expects($this->once())
                     ->method('attachAnnotation')
                     ->with($this->validateByCallback(function($actual, \PHPUnit_Framework_TestCase $testCase) use($uri, $coords){
                         $testCase->assertAnnotationLinkWithRectangle($coords, $actual);
                         
                         $action = $actual->getDestination();
                         $testCase->assertInstanceOf('\ZendPdf\Action\Uri', $action);
                         $testCase->assertEquals($uri, $action->getUri());
                     }, $this));
                             
        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);
        
        $gc->uriAction($coords[0], $coords[1], $coords[2], $coords[3], $uri);
        $gc->commit();
    }
    
    public function assertAnnotationLinkWithRectangle(array $coords, $actual)
    {
        $this->assertInstanceOf('\ZendPdf\Annotation\Link', $actual);
        
        $boundary = $actual->getResource()->Rect;
        
        foreach($coords as $i => $coord)
        {
            $this->assertEquals((string) $coord, $boundary->items[$i]->toString());
        }
    }
    
    /**
     * @test
     */
    public function attachGoToAction()
    {
        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('attachAnnotation'))
                             ->disableOriginalConstructor()
                             ->getMock();
                             
        $coords = array(0, 100, 200, 50);
        $top = 100;
        
        $pageStub = new \ZendPdf\Page('a4');
        $gcStub = $this->createGc($this->getEngineMock(), $pageStub);
        
        $zendPageMock->expects($this->once())
                     ->method('attachAnnotation')
                     ->with($this->validateByCallback(function($actual, \PHPUnit_Framework_TestCase $testCase) use($top, $coords, $pageStub){
                         $testCase->assertAnnotationLinkWithRectangle($coords, $actual);
                         
                         $destination = $actual->getDestination();
                         $testCase->assertZendPageDestination($top, $pageStub, $destination);

                     }, $this));
                     
        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);
        
        $gc->goToAction($gcStub, $coords[0], $coords[1], $coords[2], $coords[3], $top);
        $gc->commit();
    }
    
    public function assertZendPageDestination($expectedTop, $expectedPage, $actualDestination)
    {
        $this->assertInstanceOf('\ZendPdf\Destination\FitHorizontally', $actualDestination);
        
        $this->assertEquals($expectedTop, $actualDestination->getTopEdge());
        $this->assertTrue($actualDestination->getResource()->items[0] === $expectedPage->getPageDictionary());
    }
    
    /**
     * @test
     * @expectedException PHPPdf\Exception\RuntimeException
     * @dataProvider wrapZendExceptionsFromActionsProvider
     */
    public function wrapZendExceptionsFromActions($method, array $args)
    {
        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('attachAnnotation'))
                             ->disableOriginalConstructor()
                             ->getMock();
                             
        $zendPageMock->expects($this->any())
                     ->method('attachAnnotation')
                     ->will($this->throwException($this->getMock('\ZendPdf\Exception\RuntimeException')));

        $gc = $this->createGc($this->getEngineMock(), $zendPageMock);
        
        call_user_func_array(array($gc, $method), $args);
        $gc->commit();
    }
    
    public function wrapZendExceptionsFromActionsProvider()
    {
        return array(
            array(
                'goToAction', array($this->createGc($this->getEngineMock(), new \ZendPdf\Page('a4')), 0, 0, 0, 0, 10),
            ),
            array(
                'uriAction', array(0, 0, 0, 0, 'invalid-uri'),
            ),
        );
    }
    
    /**
     * @test
     */
    public function attachSingleBookmark()
    {
        $pageStub = new \ZendPdf\Page('a4');
        $identifier = 'some id';
                             
        $top = 100;
        $bookmarkName = 'some name';
                            
        $engine = new Engine();
        $gc = $this->createGc($engine, $pageStub);
        
        $gc->addBookmark($identifier, $bookmarkName, $top);
        $gc->commit();
        
        $zendPdf = $engine->getZendPdf();
        
        $this->assertEquals(1, count($zendPdf->outlines));
        
        $outline = $zendPdf->outlines[0];
        
        $this->assertOutline($bookmarkName, $pageStub, $top, $outline);
    }
    
    private function assertOutline($expectedName, $expectedPage, $expectedTop, $actualOutline)
    {
        $this->assertEquals(iconv(self::ENCODING, 'UTF-16', $expectedName), $actualOutline->getTitle());
        
        $target = $actualOutline->getTarget();
        
        $this->assertInstanceOf('\ZendPdf\Action\GoToAction', $target);
        $destination = $target->getDestination();
        
        $this->assertZendPageDestination($expectedTop, $expectedPage, $destination);
    }
    
    /**
     * @test
     */
    public function attachNestedBookmarks()
    {
        $pageStub = new \ZendPdf\Page('a4');
        
        $engine = new Engine();
        $gc = $this->createGc($engine, $pageStub);
        
        //child bookmark can be added before parent
        $gc->addBookmark(2, '2', 10, 1);
        $gc->addBookmark(1, '1', 0, null);
        $gc->addBookmark(3, '3', 0, null);
        $gc->commit();
        
        $zendPdf = $engine->getZendPdf();
        
        $this->assertEquals(2, count($zendPdf->outlines));
        
        $firstOutline = $zendPdf->outlines[0];
        $secondOutline = $zendPdf->outlines[1];
        
        $this->assertEquals(1, count($firstOutline->childOutlines));
        $this->assertEquals(0, count($secondOutline->childOutlines));
        
        $childOutline = $firstOutline->childOutlines[0];
        $this->assertOutline('2', $pageStub, 10, $childOutline);
    }
    
    /**
     * @test
     */
    public function attachStickyNote()
    {
        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('attachAnnotation'))
                             ->disableOriginalConstructor()
                             ->getMock();
        $gc = $this->createGc(new Engine(), $zendPageMock);
        
        $coords = array(1, 2, 3, 4);
        $text = 'text';
        
        $zendPageMock->expects($this->once())
                     ->method('attachAnnotation')
                     ->with($this->validateByCallback(function($actual, \PHPUnit_Framework_TestCase $testCase) use($text, $coords){
                         $testCase->assertInstanceOf('ZendPdf\Annotation\Text', $actual);
                         $rect = $actual->getResource()->Rect;

                         foreach($coords as $i => $coord)
                         {
                             $testCase->assertEquals($coord, $rect->items[$i]->toPhp());
                         }
                         $actualText = $actual->getResource()->Contents->toString();
                         $testCase->assertEquals($text, $actual->getResource()->Contents->toPhp());
                     }, $this));

        $gc->attachStickyNote($coords[0], $coords[1], $coords[2], $coords[3], $text);
        $gc->commit();
    }
    
    /**
     * @test
     * @dataProvider alphaProvider
     */
    public function setAlpha($alpha, $expectCall)
    {
        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('setAlpha'))
                             ->disableOriginalConstructor()
                             ->getMock();
                             
        $gc = $this->createGc(new Engine(), $zendPageMock);
        
        if($expectCall)
        {
            $zendPageMock->expects($this->at(0))
                         ->method('setAlpha')
                         ->with($alpha);
        }
        else
        {
            $zendPageMock->expects($this->never())
                         ->method('setAlpha');
        }

        $gc->setAlpha($alpha);
        $gc->setAlpha($alpha);
        $gc->commit();
    }
    
    public function alphaProvider()
    {
        return array(
            array(0.5, true),
            array(1, false),
        );
    }
    
    /**
     * @expectedException PHPPdf\Exception\RuntimeException
     */
    public function throwExceptionIdParentOfBookmarkDosntExist()
    {
        $gc = $this->createGc(new Engine(), new \ZendPdf\Page('a4'));
        
        $gc->addBookmark('someId', 'some name', 100, 'unexistedParentId');
    }
    
    /**
     * @test
     */
    public function ignoreEmptyImage()
    {
        $zendPageMock = $this->getMockBuilder('\ZendPdf\Page')
                             ->setMethods(array('drawImage'))
                             ->disableOriginalConstructor()
                             ->disableOriginalClone()
                             ->getMock();
        
        $image = EmptyImage::getInstance();
        
        $zendPageMock->expects($this->never())
                      ->method('drawImage');
        
        $gc = $this->createGc(new Engine(), $zendPageMock);
                      
        $gc->drawImage($image, 50, 50, 100, 10);
        $gc->commit();
    }
}