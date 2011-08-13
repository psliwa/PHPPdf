<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Behaviour;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Engine\GraphicsContext;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class Behaviour
{
    private $passive = false;
    
    public function attach(GraphicsContext $gc, Glyph $glyph)
    {
        if(!$this->isPassive())
        {
            $this->doAttach($gc, $glyph);
        }
    }
    
    abstract protected function doAttach(GraphicsContext $gc, Glyph $glyph);

    public function isPassive()
    {
        return $this->passive;
    }

    public function setPassive($flag)
    {
        $this->passive = (boolean) $flag;
    }
}