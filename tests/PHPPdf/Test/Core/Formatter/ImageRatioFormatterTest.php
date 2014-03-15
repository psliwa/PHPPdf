<?php


namespace PHPPdf\Test\Core\Formatter;


use PHPPdf\Core\Formatter\ImageRatioFormatter;
use PHPPdf\Test\Helper\NodeAssert;
use PHPPdf\Test\Helper\NodeBuilder;

class ImageRatioFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    private $document;

    public function setUp()
    {
        $this->formatter = new ImageRatioFormatter();
        $this->document = $this->createDocumentStub();
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function givenRatioDoesntMatchOriginalRatio_fixDimensionToFitOriginalRatio($originalWidth, $originalHeight, $currentWidth, $currentHeight, $expectedWidth, $expectedHeight)
    {
        //given

        $image = NodeBuilder::create()
            ->nodeClass('PHPPdf\Test\Helper\Image')
            ->attr('original-width', $originalWidth)
            ->attr('original-height', $originalHeight)
            ->attr('width', $currentWidth)
            ->attr('height', $currentHeight)
            ->getNode();

        //when

        $this->formatter->format($image, $this->document);

        //then

        NodeAssert::create($image)
            ->width($expectedWidth)
            ->height($expectedHeight);
    }

    public function dataProvider()
    {
        return array(
            array(50, 100, 50, 50, 25, 50),
            array(100, 50, 50, 50, 50, 25),
        );
    }
}
 