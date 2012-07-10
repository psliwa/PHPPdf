<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Cache;

use Zend\Cache\StorageFactory;
use Zend\Cache\Storage\StorageInterface;
use PHPPdf\Exception\RuntimeException;

/**
 * Standard implementation of Cache
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class CacheImpl implements Cache
{
    const ENGINE_FILE = 'File';
    const ENGINE_APC = 'Apc';
    const ENGINE_MEMCACHED = 'Memcached';
    const ENGINE_FILESYSTEM = 'Filesystem';
    
    /**
     * @var Zend\Cache\Storage\Adapter
     */
    private $adapter;
    
    private $automaticSerialization = true;

    public function __construct($engine = self::ENGINE_FILE, array $options = array())
    {
        if(!$engine instanceof StorageInterface)
        {
            $engine = $this->createAdapter($engine);
        }
        
        $this->adapter = $engine;
        
        if(isset($options['automatic_serialization']))
        {
            $this->automaticSerialization = $options['automatic_serialization'];
            unset($options['automatic_serialization']);
        }
        
        $this->adapter->setOptions($options);
    }
    
    private function createAdapter($name)
    {
        $name = ucfirst(strtolower($name));

        if($name === self::ENGINE_FILE)
        {
            $name = self::ENGINE_FILESYSTEM;
        }
        
        $const = 'PHPPdf\Cache\CacheImpl::ENGINE_'.strtoupper($name);
        if(!defined($const))
        {
            throw $this->cacheEngineDosntExistException($name);
        }
        
        $name = constant($const);
        
        return StorageFactory::adapterFactory($name);
    }

    private function cacheEngineDosntExistException($engine, \Exception $e = null)
    {
        return new RuntimeException(sprintf('Cache engine "%s" dosn\'t exist.', $engine), 1, $e);
    }

    public function load($id)
    {
        try
        {
            $data = $this->adapter->getItem($id);
            
            if($this->automaticSerialization)
            {
                $data = @unserialize($data);
                
                if($data === false)
                {
                    $this->remove($id);
                    throw new RuntimeException(sprintf('Invalid data under "%s" key. Cache has been remove.', $id));
                }
            }
            
            return $data;
        }
        catch(\Zend\Cache\Exception\ExceptionInterface $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }

    private function wrapLowLevelException(\Exception $e, $methodName)
    {
        throw new RuntimeException(sprintf('Error while invoking "%s".', $methodName), 0, $e);
    }

    public function test($id)
    {
        try
        {
            return $this->adapter->hasItem($id);
        }
        catch(\Zend\Cache\Exception\ExceptionInterface $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }

    /**
     * @TODO change params order
     * 
     * @param mixed $data Data to save in cache. Attention: false value is not supported!
     * @param string $id Identifier of cache
     */
    public function save($data, $id)
    {
        try
        {
            if($this->automaticSerialization)
            {
                $data = serialize($data);
            }
            
            return $this->adapter->setItem($id, $data);
        }
        catch(\Zend\Cache\Exception\ExceptionInterface $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }

    public function remove($id)
    {
        try
        {
            return $this->adapter->removeItem($id);
        }
        catch(\Zend\Cache\Exception\ExceptionInterface $e)
        {
            $this->wrapLowLevelException($e, __METHOD__);
        }
    }
}