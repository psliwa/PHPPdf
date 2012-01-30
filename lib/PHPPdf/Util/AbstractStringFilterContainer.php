<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Util;

/**
 * Abstract string filter container
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class AbstractStringFilterContainer implements StringFilterContainer
{
    protected $stringFilters = array();
    
    public function setStringFilters(array $filters)
    {
        $this->stringFilters = array();
        
        foreach($filters as $filter)
        {
            $this->addStringFilter($filter);
        }
    }
    
    protected function addStringFilter(StringFilter $filter)
    {
        $this->stringFilters[] = $filter;
    }
}