<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Engine;

/**
 * Empty image.
 * 
 * This class can be used in situation when original image can't be
 * created (file does not exist etc.) and we decide to ignore this error.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class EmptyImage implements Image
{
    /**
     * @return EmptyImage
     */
    public static function getInstance()
    {
        static $instance;
        
        if(!$instance)
        {
            $instance = new self();
        }
        
        return $instance;
    }
    
	public function getOriginalHeight()
	{
		return 0;
	}

	public function getOriginalWidth()
	{
		return 0;
	}
}