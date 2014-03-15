<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Formatter\StandardDimensionFormatter;
use PHPPdf\Test\Helper\NodeBuilder;
use PHPPdf\Test\Helper\NodeAssert;

class StandardDimensionFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new StandardDimensionFormatter();
        $this->document = $this->createDocumentStub();
    }

    /**
     * @test
     */
    public function givenExplictDimension_useGivenDimension()
    {
        $node = NodeBuilder::create()
            ->attr('width', 120)
            ->attr('height', 140)
            ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
            ->width(120)
            ->height(140);
    }

    /**
     * @test
     */
    public function givenNullWidthAndFloat_setZeroAsWidth()
    {
        $node = NodeBuilder::create()
            ->attr('width', null)
            ->attr('float', 'left')
            ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
            ->width(0);
    }
    
    /**
     * @test
     * @dataProvider dimensionProvider
     */
    public function givenRealDimensionAndPaddings_useRealDimensionAndPaddingsToCalsulateDimension($realWidth, $realHeight, array $paddings)
    {
        $node = NodeBuilder::create()
            ->attr('width', rand(1, 200))
            ->attr('height', rand(1, 200))
            ->attr('real-width', $realWidth)
            ->attr('real-height', $realHeight)
            ->attrs($paddings)
            ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
            ->width($realWidth + $paddings['padding-left'] + $paddings['padding-right'])
            ->height($realHeight + $paddings['padding-top'] + $paddings['padding-bottom']);
    }
    
    public function dimensionProvider()
    {
        return array(
            array(200, 300, array(
                'padding-left' => 10,
                'padding-top' => 11,
                'padding-right' => 12,
                'padding-bottom' => 13,
            )),
        );
    }
    
    /**
     * @test
     */
    public function givenNodeAndParentWithDimensions_nodeWidthCantExceedParentWidth()
    {
        $node = NodeBuilder::create()
            ->attr('width', 90)
            ->attr('height', 90)
            ->attr('padding', 10)
            ->parent()
                ->attr('width', 100)
                ->attr('height', 100)
                ->attr('padding', 2)
            ->end()
            ->getNode();

        $this->formatter->format($node, $this->document);

        NodeAssert::create($node)
            ->height(90 + 2*10)
            ->widthAsTheSameAsParentsWithoutPaddings();
    }
}