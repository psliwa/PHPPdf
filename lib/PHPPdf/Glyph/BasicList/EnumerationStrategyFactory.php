<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Glyph\BasicList;

use PHPPdf\Glyph\BasicList;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class EnumerationStrategyFactory
{
    /**
     * @return EnumerationStrategy
     */
    public function create($type)
    {
        $strategy = null;
        
        switch($type)
        {
            case BasicList::TYPE_DECIMAL:
                $strategy = new OrderedEnumerationStrategy();
                break;
            case BasicList::TYPE_DECIMAL_LEADING_ZERO:
                $strategy = new OrderedEnumerationStrategy();
                $strategy->setPattern('%02d.');
                break;
            case BasicList::TYPE_IMAGE:
                $strategy = new ImageEnumerationStrategy();
                break;
            case BasicList::TYPE_LOWER_ALPHA:
                $strategy = new OrderedEnumerationStrategy();
                $strategy->setVisualIndex('a');
                break;
            case BasicList::TYPE_UPPER_ALPHA:
                $strategy = new OrderedEnumerationStrategy();
                $strategy->setVisualIndex('A');
                break;
            default:
                $strategy = new UnorderedEnumerationStrategy();                
                break;
        }
        
        return $strategy;
    }
}