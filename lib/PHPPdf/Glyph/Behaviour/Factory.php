<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\Behaviour;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Factory
{
    /**
     * @return Behaviour
     */
    public function create($name, $arg)
    {
        switch($name)
        {
            case 'href':
                return new GoToUrl($arg);
            default:
                return null;
        }
    }

    public function getSupportedBehaviourNames()
    {
        return array('href');
    }
}