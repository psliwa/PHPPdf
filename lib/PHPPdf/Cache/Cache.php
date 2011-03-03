<?php

namespace PHPPdf\Cache;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Cache
{
    public function save($id, $data);
    public function load($id);
    public function test($id);
    public function clean($mode);
    public function remove($id);
}