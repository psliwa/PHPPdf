<?php

require_once __DIR__.'/EnumerationStrategyTest.php';

use PHPPdf\Glyph\BasicList;
use PHPPdf\Glyph\BasicList\UnorderedEnumerationStrategy;
use PHPPdf\Glyph\BasicList\EnumerationStrategy;

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
        $list->expects($this->once())
                 ->method('getType')
                 ->will($this->returnValue($pattern));
    }
    
    protected function getListMockedMethod()
    {
        return array('getType');
    }
}