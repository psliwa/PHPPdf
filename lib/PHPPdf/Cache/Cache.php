<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Cache;

/**
 * Interface of cache
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Cache
{
    /**
     * Saves data under passed id and returns boolean value about operation status
     *
     * @param mixed $data Cached data
     * @param string $id Cache id
     * @return boolean True if cache has been succesfully saved, otherwise false
     */
    public function save($data, $id);
    
    /**
     * Loads and returns data from cache. Returns false if cache is empty
     *
     * @param string $id Cache id
     * @return mixed Cached data
     */
    public function load($id);
    
    /**
     * Tests if cache have value under passed id
     *
     * @param string $id Cache id
     * @return bollean Cache is fresh?
     */
    public function test($id);
    
    /**
     * Remove cache stored under passed id
     *
     * @param string $id Cache id
     * @return boolean True on success, otherwise false
     */
    public function remove($id);
}