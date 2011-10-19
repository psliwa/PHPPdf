<?php

namespace PHPPdf\Test\Core\Node\BasicList;

require_once __DIR__.'/EnumerationStrategyTest.php';

use PHPPdf\Core\Node\BasicList;
use PHPPdf\Core\Node\BasicList\UnorderedEnumerationStrategy;
use PHPPdf\Core\Node\BasicList\EnumerationStrategy;

class UnorderedEnumerationStrategyTest extends EnumerationStrategyTest
{
    protected function createStrategy()
    {
        return new UnorderedEnumerationStrategy();
    }
    
    protected function getExpectedText($elementIndex, $elementPattern)
    {
        return $elementPattern;
    }
    
    protected function getElementPattern($index)
    {
        $patterns = array(BasicList::TYPE_CIRCLE, BasicList::TYPE_SQUARE);//, '%d)');
        
        return $patterns[$index % 2];
    }
    
    protected function setElementPattern($list, $pattern)
    {
        $list->expects($this->atLeastOnce())
                 ->method('getType')
                 ->will($this->returnValue($pattern));
    }
    
    protected function getListMockedMethod()
    {
        return array('getType');
    }
}