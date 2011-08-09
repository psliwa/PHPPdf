<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Behaviour;

use PHPPdf\Glyph\Manager;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Factory
{
    private $glyphManager;
    
    public function setGlyphManager(Manager $manager)
    {
        $this->glyphManager = $manager;
    }
    
    /**
     * @return Behaviour
     */
    public function create($name, $arg)
    {
        switch($name)
        {
            case 'href':
                return new GoToUrl($arg);
            case 'ref':
                return new GoToInternal($this->glyphManager->get($arg));
            default:
                return null;
        }
    }

    public function getSupportedBehaviourNames()
    {
        return array('href', 'ref');
    }
}