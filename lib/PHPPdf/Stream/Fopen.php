<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Stream;

use PHPPdf\Exception\Exception;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Fopen implements Stream
{
    private $fp;
    
    public function __construct($filepath, $mode)
    {
        $this->fp = @\fopen($filepath, $mode);
        
        if($this->fp === false)
        {
            throw new Exception(sprintf('File "%s" can\'t be opened in mode "%s".', $filepath, $mode));
        }
    }

    public function seek($index, $mode = self::SEEK_CUR)
    {
        $realMode = null;
        
        switch($mode)
        {
            case self::SEEK_CUR:
                $realMode = SEEK_CUR;
                break;
            case self::SEEK_SET:
                $realMode = SEEK_SET;
                break;
            case self::SEEK_END:
                $realMode = SEEK_END;
                break;
        }

        return fseek($this->fp, $index, $realMode);
    }
    
    public function read($length)
    {
        return fread($this->fp, $length);
    }
    
    public function close()
    {
        fclose($this->fp);
    }
    
    public function tell()
    {
        return ftell($this->fp);
    }
    
    public function size()
    {
        $fileStats = fstat($this->fp);
        return $fileStats['size'];
    }
}