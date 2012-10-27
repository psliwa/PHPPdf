<?php

namespace PHPPdf\Test\Core\Node\Chart;

use PHPPdf\Core\Node\Chart\PieChart;
use PHPPdf\PHPUnit\Framework\TestCase;

class PieChartTest extends TestCase
{
    private $chart;
    
    public function setUp()
    {
        $this->chart = new PieChart();
    }
    
    /**
     * @test
     * @dataProvider chartDataProvider
     */
    public function setChartData($chartValues, $chartColors, $expectedValues, $expectedColors)
    {
        $this->chart->setAttribute('chart-values', $chartValues);
        $this->chart->setAttribute('chart-colors', $chartColors);
        
        $this->assertEquals($expectedValues, $this->chart->getAttribute('chart-values'));
        $this->assertEquals($expectedColors, $this->chart->getAttribute('chart-colors'));
    }
    
    public function chartDataProvider()
    {
        return array(
            array(
                '10|20|70',
                '#ffffff|#000000|gray',
                array(10, 20, 70),
                array('#ffffff', '#000000', 'gray'),
            ),
        );
    }
}