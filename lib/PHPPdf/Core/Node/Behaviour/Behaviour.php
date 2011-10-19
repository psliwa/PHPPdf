<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Behaviour;

use PHPPdf\Core\Node\Node,
    PHPPdf\Core\Engine\GraphicsContext;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class Behaviour
{
    private $passive = false;
    
    public function attach(GraphicsContext $gc, Node $node)
    {
        if(!$this->isPassive())
        {
            $this->doAttach($gc, $node);
        }
    }
    
    abstract protected function doAttach(GraphicsContext $gc, Node $node);

    public function isPassive()
    {
        return $this->passive;
    }

    public function setPassive($flag)
    {
        $this->passive = (boolean) $flag;
    }
    
    public function getUniqueId()
    {
        return spl_object_hash($this);
    }
}