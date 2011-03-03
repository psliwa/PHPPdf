<?php

use PHPPdf\Cache\NullCache;

class NullCacheTest extends TestCase
{
    private $cache;

    public function setUp()
    {
        $this->cache = new NullCache();
    }

    /**
     * @test
     * @dataProvider provideMethods
     */
    public function everyMethodIsDummy($method, array $args, $returnValue)
    {
        $value = call_user_func_array(array($this->cache, $method), $args);
        $this->assertEquals($returnValue, $value);
    }

    public function provideMethods()
    {
        return array(
            array('load', array('id'), false),
            array('test', array('id'), false),
            array('save', array('id', 'value'), true),
            array('clean', array('all'), true),
            array('remove', array('id'), true),
        );
    }
}