<?php

use PHPPdf\Cache\CacheImpl;

class CacheImplTest extends TestCase
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
    public function delegateOperationsToCacheEngine($method, array $args, $returnValue)
    {
        $id = 'someId';
        $value = 'value';

        $matcher = $this->engineMock->expects($this->once())
                                    ->method($method)
                                    ->will($this->returnValue($returnValue));
        call_user_func_array(array($matcher, 'with'), $args);

        $cache = new CacheImpl();
        $this->invokeMethod($cache, 'setBackend', array($this->engineMock));

        $this->assertEquals($returnValue, call_user_func_array(array($cache, $method), $args));
    }

    public function provideCacheOperations()
    {
        return array(
            array('load', array('id'), 'value'),
            array('test', array('id'), true),
            array('save', array('id', 'value'), true),
            array('remove', array('id'), true),
            array('clean', array('all'), true),
        );
    }

    private function getCacheEngineMock()
    {
        $mock = $this->getMock('Zend_Cache_Backend', array('clean', 'load', 'setDirectives', 'remove', 'save', 'test'));

        return $mock;
    }

    /**
     * @test
     * @expectedException \PHPPdf\Exception\Exception
     * @dataProvider provideCacheOperations
     */
    public function wrapCacheEngineExceptions($operation, array $args)
    {
        $e = new \Exception();
        
        $this->engineMock->expects($this->once())
                         ->method($operation)
                         ->will($this->throwException($e));

        $cache = new CacheImpl();
        $this->invokeMethod($cache, 'setBackend', array($this->engineMock));

        $cache->load('id');
        call_user_func_array(array($cache, $operation), $args);
    }
}