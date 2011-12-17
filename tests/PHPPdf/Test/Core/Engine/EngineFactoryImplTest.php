<?php

namespace PHPPdf\Test\Core\Engine;

use PHPPdf\Core\Engine\EngineFactoryImpl;
use PHPPdf\PHPUnit\Framework\TestCase;

class EngineFactoryImplTest extends TestCase
{
    private $factory;
    
    public function setUp()
    {
        $this->factory = new EngineFactoryImpl();
    }
    
    /**
     * @test
     * @dataProvider validTypeProvider
     */
    public function engineCreationSuccess($type, $expectedClass)
    {
        try
        {
            $engine = $this->factory->createEngine($type);
            
            $this->assertInstanceOf($expectedClass, $engine);
        }
        catch(\Imagine\Exception\RuntimeException $e)
        {
            $this->markTestSkipped('Exception from Imagine library, propably some graphics library is not installed');
        }
    }
    
    public function validTypeProvider()
    {
        return array(
            array(EngineFactoryImpl::TYPE_IMAGE, 'PHPPdf\Core\Engine\Imagine\Engine'),
            array(EngineFactoryImpl::TYPE_PDF, 'PHPPdf\Core\Engine\ZF\Engine'),
        );
    }
    
    /**
     * @test
     * @dataProvider invalidTypeProvider
     * @expectedException PHPPdf\Exception\DomainException
     */
    public function engineCreationFailure($type)
    {
        $this->factory->createEngine($type);
    }

    public function invalidTypeProvider()
    {
        return array(
            array('some type'),
        );
    }
    
    /**
     * @test
     * @dataProvider validImageTypeProvider
     */
    public function imageEngineCreationSuccess($type)
    {
        try
        {
            $engine = $this->factory->createEngine(EngineFactoryImpl::TYPE_IMAGE, array(
                EngineFactoryImpl::OPTION_ENGINE => $type,
            ));
            
            $this->assertInstanceOf('PHPPdf\Core\Engine\Imagine\Engine', $engine);
        }
        catch(\Imagine\Exception\RuntimeException $e)
        {
            $this->markTestSkipped('Exception from Imagine library, propably some graphics library is not installed');
        }
    }
    
    public function validImageTypeProvider()
    {
        return array(
            array(EngineFactoryImpl::ENGINE_GD),
            array(EngineFactoryImpl::ENGINE_IMAGICK),
            array(EngineFactoryImpl::ENGINE_GMAGICK),
        );
    }
    
    /**
     * @test
     * @dataProvider invvalidImageTypeProvider
     * @expectedException PHPPdf\Exception\DomainException
     */
    public function imageEngineCreationFailure($type)
    {
        $engine = $this->factory->createEngine(EngineFactoryImpl::TYPE_IMAGE, array(
            EngineFactoryImpl::OPTION_ENGINE => $type,
        ));
    }
    
    public function invvalidImageTypeProvider()
    {
        return array(
            array('some'),
        );
    }
}