<?php

namespace PHPPdf\Test\Core\Formatter;

use PHPPdf\Core\Node\BasicList;
use PHPPdf\Core\Document;
use PHPPdf\Core\Formatter\ListFormatter;

class ListFormatterTest extends \PHPPdf\PHPUnit\Framework\TestCase
{
    private $formatter;
    
    public function setUp()
    {
        $this->formatter = new ListFormatter();
    }
    
    /**
     * @test
     */
    public function ifListsPositionIsOutsidePositionOfChildrenWontBeTranslated()
    {
        $list = $this->getMock('PHPPdf\Core\Node\BasicList', array('getChildren', 'getAttribute', 'assignEnumerationStrategyFromFactory'));
        
        $list->expects($this->once())
             ->method('getAttribute')
             ->with('list-position')
             ->will($this->returnValue(BasicList::LIST_POSITION_OUTSIDE));

        $list->expects($this->never())
             ->method('getChildren');
             
        $list->expects($this->once())
             ->method('assignEnumerationStrategyFromFactory');
             
        $this->formatter->format($list, $this->createDocumentStub());
    }
    
    /**
     * @test
     */
    public function ifListsPositionIsInsidePositionOfChildrenWillBeTranslated()
    {
        $widthOfEnumerationChar = 7;
        
        $documentStub = new Document($this->getMock('PHPPdf\Core\Engine\Engine'));
        
        $list = $this->getMock('PHPPdf\Core\Node\BasicList', array('getChildren', 'getEnumerationStrategy', 'getAttribute', 'assignEnumerationStrategyFromFactory'));
        
        $enumerationStrategy = $this->getMockBuilder('PHPPdf\Core\Node\BasicList\EnumerationStrategy')
                                    ->getMock();
        
        $list->expects($this->once())
             ->after('assign')
             ->method('getEnumerationStrategy')
             ->will($this->returnValue($enumerationStrategy));
            
        $list->expects($this->at(0))
             ->method('getAttribute')
             ->with('list-position')
             ->will($this->returnValue(BasicList::LIST_POSITION_INSIDE));
             
        $list->expects($this->once())
             ->id('assign')
             ->method('assignEnumerationStrategyFromFactory');
             
        $enumerationStrategy->expects($this->once())
                            ->method('getWidthOfTheBiggestPosibleEnumerationElement') 
                            ->with($documentStub, $list)
                            ->will($this->returnValue($widthOfEnumerationChar));

        $children = array();
        $leftMargin = 10;
        for($i=0; $i<2; $i++)
        {
            $child = $this->getMock('PHPPdf\Core\Node\Container', array('setAttribute', 'getMarginLeft'));
            $child->expects($this->once())
                  ->method('getMarginLeft')
                  ->will($this->returnValue($leftMargin));
            $child->expects($this->once())
                  ->method('setAttribute')
                  ->with('margin-left', $widthOfEnumerationChar + $leftMargin);
            $children[] = $child;
        }
        
        $list->expects($this->atLeastOnce())
             ->method('getChildren')
             ->will($this->returnValue($children));
             
        $this->formatter->format($list, $documentStub);
    }
}