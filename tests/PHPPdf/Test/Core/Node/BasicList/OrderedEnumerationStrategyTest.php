<?php

namespace PHPPdf\Test\Core\Node\BasicList;

require_once __DIR__.'/EnumerationStrategyTest.php';

use PHPPdf\Core\Node\BasicList\EnumerationStrategy;
use PHPPdf\Core\Node\BasicList;
use PHPPdf\Core\Point;
use PHPPdf\Core\Node\Container;
use PHPPdf\Core\Node\BasicList\OrderedEnumerationStrategy;

class OrderedEnumerationStrategyTest extends EnumerationStrategyTest
{   
    protected function createStrategy()
    {
        return new OrderedEnumerationStrategy();
    }
    
    protected function getExpectedText($elementIndex, $elementPattern)
    {
        return sprintf($elementPattern, $elementIndex+1);
    }
    
    protected function getElementPattern($index)
    {
        $patterns = array('%d.');//, '%d)');
        
        return $patterns[$index % 1];
    }
    
    protected function setElementPattern($list, $pattern)
    {
    }
}