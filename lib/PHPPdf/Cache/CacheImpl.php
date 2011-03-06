<?php

namespace PHPPdf\Cache;

use PHPPdf\Exception\Exception;

/**
 * Standard implementation of Cache
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class CacheImpl implements Cache
{
    const ENGINE_FILE = 'File';
    const ENGINE_APC = 'Apc';
    const ENGINE_BLACK_HOLE = 'BlackHole';
    const ENGINE_MEMCACHED = 'Memcached';
    const ENGINE_SQLITE = 'Sqlite';
    const ENGINE_XCACHE = 'Xcache';
    const ENGINE_ZEND_PLATFORM = 'ZendPlatform';
    const ENGINE_ZEND_SERVER = 'ZendServer';

    /**
     * @var Zend_Cache_Core
     */
    private $core = null;

    public function __construct($engine = self::ENGINE_FILE, array $options = array())
    {
        $defaultOptions = array('write_control' => false);
        $options = array_merge($defaultOptions, $options);

        $this->core = new \Zend_Cache_Core($options);

        $backend = $this->createCacheBackend($engine, $options);

        $this->setBackend($backend);
    }

    private function createCacheBackend($engine, array $options)
    {
        try
        {
            $className = sprintf('\Zend_Cache_Backend_%s', $engine);
            $class = new \ReflectionClass($className);
            $backend = $class->newInstance($options);

            if(!$backend instanceof \Zend_Cache_Backend_Interface)
            {
                $this->cacheEngineDosntExistException($engine);
            }

            return $backend;
        }
        catch(\ReflectionException $e)
        {
            $this->cacheEngineDosntExistException($engine, $e);
        }
    }

    private function cacheEngineDosntExistException($engine, \Exception $e = null)
    {
        throw new Exception(sprintf('Cache engine "%s" dosn\'t exist.', $engine), 1, $e);
    }

    private function setBackend(\Zend_Cache_Backend $backend)
    {
        $this->core->setBackend($backend);
    }

    public function load($id)
    {
        try
        {
            return $this->core->load($id);
        }
        catch(\Exception $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }

    private function wrapLowLevelException(\Exception $e, $methodName)
    {
        throw new Exception(sprintf('Error while invoking "%s".', $methodName), 0, $e);
    }

    public function test($id)
    {
        try
        {
            return $this->core->test($id);
        }
        catch(\Exception $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }

    public function save($id, $data)
    {
        try
        {
            return $this->core->save($id, $data);
        }
        catch(\Exception $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }

    public function remove($id)
    {
        try
        {
            return $this->core->remove($id);
        }
        catch(\Exception $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }

    public function clean($mode = \Zend_Cache::CLEANING_MODE_ALL)
    {
        try
        {
            return $this->core->clean($mode);
        }
        catch(\Exception $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }
}