<?php

namespace PHPPdf\Cache;

/**
 * Dummy cache class. Used when cache is no used.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class NullCache implements Cache
{
    public static function getInstance()
    {
        return new self();
    }

    public function load($id)
    {
        return false;
    }

    public function test($id)
    {
        return false;
    }

    public function save($id, $value)
    {
        return true;
    }

    public function remove($id)
    {
        return true;
    }

    public function clean($mode)
    {
        return true;
    }
}
