<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Stream;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
interface Stream
{
    const SEEK_CUR = 1;
    const SEEK_SET = 2;
    const SEEK_END = 3;
    
    public function read($length);
    public function close();
    public function seek($index, $seekType = self::SEEK_CUR);
    public function tell();
    public function size();
}