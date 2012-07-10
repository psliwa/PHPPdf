<?php

namespace PHPPdf\Test\Cache;

use PHPPdf\Cache\CacheImpl;

class CacheImplTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $engineMock;

    public function setUp()
    {
        if(!class_exists('Zend\Cache\StorageFactory', true))
        {
            $this->fail('Zend Framework 2 library is missing. You have to download dependencies, for example by using "vendors.php" file.');
        }

        $this->engineMock = $this->getCacheEngineMock();
    }

    /**
     * @test
     * @expectedException \PHPPdf\Exception\RuntimeException
     */
    public function throwExceptionIfPassedCacheEngineIsUnavailable()
    {
        new CacheImpl('Unexisted-cache-engine');
    }

    /**
     * @test
     * @dataProvider provideCacheOperations
     */
    public function delegateOperationsToCacheEngine($method, $adapterMethod, array $args, $returnValue, $expectedArgs = null, $expectedReturnValue = null, $cacheOptions = array())
    {
        $expectedArgs = $expectedArgs ? $expectedArgs : $args;
        $expectedReturnValue = $expectedReturnValue === null ? $returnValue : $expectedReturnValue;

        $matcher = $this->engineMock->expects($this->once())
                                    ->method($adapterMethod)
                                    ->will($this->returnValue($expectedReturnValue));
        call_user_func_array(array($matcher, 'with'), $expectedArgs);

        $cache = new CacheImpl($this->engineMock, $cacheOptions);
        
        $this->assertEquals($returnValue, call_user_func_array(array($cache, $method), $args));
    }

    public function provideCacheOperations()
    {
        return array(
            array('load', 'getItem', array('id'), 'value', null, null, array('automatic_serialization' => false)),
            array('load', 'getItem', array('id'), 'value', null, serialize('value'), array('automatic_serialization' => true)),
            array('load', 'getItem', array('id'), 'value', null, serialize('value')),
            array('test', 'hasItem', array('id'), true),
            array('save', 'setItem', array('value', 'id'), true, array('id', 'value'), null, array('automatic_serialization' => false)),
            array('save', 'setItem', array('value', 'id'), true, array('id', serialize('value')), null, array('automatic_serialization' => true)),
            array('save', 'setItem', array('value', 'id'), true, array('id', serialize('value')), null),
            array('remove', 'removeItem', array('id'), true),
        );
    }

    private function getCacheEngineMock()
    {
        $mock = $this->getMock('Zend\Cache\Storage\StorageInterface');

        return $mock;
    }

    /**
     * @test
     * @expectedException \PHPPdf\Exception\RuntimeException
     * @dataProvider provideCacheOperations
     */
    public function wrapCacheEngineExceptions($operation, $adapterMethod, array $args)
    {
        $e = new \Zend\Cache\Exception\InvalidArgumentException();
        
        $this->engineMock->expects($this->once())
                         ->method($adapterMethod)
                         ->will($this->throwException($e));

        $cache = new CacheImpl($this->engineMock);

        call_user_func_array(array($cache, $operation), $args);
    }
}