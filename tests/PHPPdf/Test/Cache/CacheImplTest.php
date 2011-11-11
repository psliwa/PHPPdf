<?php

namespace PHPPdf\Test\Cache;

use PHPPdf\Cache\CacheImpl;

class CacheImplTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $engineMock;

    public function setUp()
    {
        $this->engineMock = $this->getCacheEngineMock();
    }

    /**
     * @test
     * @expectedException \PHPPdf\Exception\Exception
     */
    public function throwExceptionIfPassedCacheEngineIsUnavailable()
    {
        new CacheImpl('Unexisted-cache-engine');
    }

    /**
     * @test
     * @dataProvider provideCacheOperations
     */
    public function delegateOperationsToCacheEngine($method, array $args, $returnValue, $expectedArgs = null, $cacheOptions = array())
    {
        $expectedArgs = $expectedArgs ? $expectedArgs : $args;

        $matcher = $this->engineMock->expects($this->once())
                                    ->method($method)
                                    ->will($this->returnValue($returnValue));
        call_user_func_array(array($matcher, 'with'), $expectedArgs);

        $cache = new CacheImpl(CacheImpl::ENGINE_BLACK_HOLE, $cacheOptions);
        $this->invokeMethod($cache, 'setBackend', array($this->engineMock));

        $this->assertEquals($returnValue, call_user_func_array(array($cache, $method), $args));
    }

    public function provideCacheOperations()
    {
        return array(
            array('load', array('id'), 'value', null, array('automatic_serialization' => false)),
            array('test', array('id'), true),
            array('save', array('value', 'id'), true, array(serialize('value'), 'id')),
            array('remove', array('id'), true),
            array('clean', array('all'), true),
        );
    }

    private function getCacheEngineMock()
    {
        $mock = $this->getMock('Zend\Cache\Backend', array('clean', 'load', 'setDirectives', 'remove', 'save', 'test'));

        return $mock;
    }

    /**
     * @test
     * @expectedException \PHPPdf\Exception\Exception
     * @dataProvider provideCacheOperations
     */
    public function wrapCacheEngineExceptions($operation, array $args)
    {
        $e = new \Zend\Cache\Exception();
        
        $this->engineMock->expects($this->once())
                         ->method($operation)
                         ->will($this->throwException($e));

        $cache = new CacheImpl();
        $this->invokeMethod($cache, 'setBackend', array($this->engineMock));

        $cache->load('id');
        call_user_func_array(array($cache, $operation), $args);
    }
}