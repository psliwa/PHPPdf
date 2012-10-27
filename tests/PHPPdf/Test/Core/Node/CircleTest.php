<?php

namespace PHPPdf\Test\Core\Node;

use PHPPdf\Core\Node\Circle;

class CircleTest extends  \PHPPdf\PHPUnit\Framework\TestCase
{
	private $circle;
	
	public function setUp()
	{
		$this->circle = new Circle();
	}

	/**
	 * @test
	 */
	public function changeSizeOnRadiusChange()
	{
		$radius = 30;
		$expectedSize = $radius*2;
		
		$this->circle->setAttribute('radius', $radius);
		
		$this->assertEquals($expectedSize, $this->circle->getAttribute('width'));
		$this->assertEquals($expectedSize, $this->circle->getAttribute('height'));
	}
}