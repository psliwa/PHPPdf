<?php

use PHPPdf\Node\BasicList\EnumerationStrategy;
require_once __DIR__.'/EnumerationStrategyTest.php';

use PHPPdf\Node\BasicList;
use PHPPdf\Util\Point;
use PHPPdf\Node\Container;
use PHPPdf\Node\BasicList\OrderedEnumerationStrategy;

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