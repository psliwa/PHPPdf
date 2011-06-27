<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Enhancement;

use PHPPdf\Util\Bag;

/**
 * Bag of enhancements creation data. Values are arrays of parameters.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class EnhancementBag extends Bag
{
    public function add($name, $value)
    {
        $value = (array) $value;

        if($this->has($name))
        {
            $value = array_merge($this->get($name), $value);
        }

        return parent::add($name, $value);
    }
}