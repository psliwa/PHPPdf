<?php

namespace PHPPdf\Test;

use PHPPdf\PHPUnit\Framework\TestCase;
use PHPPdf\Util;

class UtilTest extends TestCase
{
    /**
     * @test
     * @dataProvider dependantSizesProvider
     */
    public function testCalculationDependantSizes($width, $height, $ratio, $result)
    {
        $this->assertEquals($result, Util::calculateDependantSizes($width, $height, $ratio));
    }

    public function dependantSizesProvider()
    {
        return array(
            array(100, 100, 2, array(100, 100)),
            array(100, null, 2, array(100, 50)),
            array(null, 100, 2, array(200, 100)),
            array(100, null, 0, array(100, 0)),
            array(null, 100, 0, array(0, 100)),
        );
    }
}