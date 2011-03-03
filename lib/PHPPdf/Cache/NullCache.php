<?php

namespace PHPPdf\Cache;

class NullCache implements Cache
{
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
