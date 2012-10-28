<?php

/*
 * Copyright 2011 Piotr Śliwa <peter.pl7@gmail.com>
 *
 * License information is in LICENSE file
 */

namespace PHPPdf\Core\Node\Chart;

use PHPPdf\Core\Engine\GraphicsContext;

use PHPPdf\Exception\InvalidArgumentException;
use PHPPdf\Core\Node\Circle;
use PHPPdf\Core\Document;
use PHPPdf\Core\DrawingTaskHeap;
use PHPPdf\Core\DrawingTask;

class PieChart extends Circle
{
    protected static function setDefaultAttributes()
    {
        parent::setDefaultAttributes();
        
        static::addAttribute('chart-values');
        static::addAttribute('chart-colors');
    }
    
    protected static function initializeType()
    {
        parent::initializeType();
        
        static::setAttributeSetters(array('chart-values' => 'setChartValues'));
        static::setAttributeSetters(array('chart-colors' => 'setChartColors'));
    }
    
    public function setChartValues($values)
    {
        if(is_string($values))
        {
            $values = explode('|', $values);
            $values = array_map('floatval', $values);
        }
        elseif(!is_array($values))
        {
            throw new InvalidArgumentException('chart-values attribute should be an array or string');
        }        
        
        $this->setAttributeDirectly('chart-values', $values);
    }
    
    public function setChartColors($colors)
    {
        if(is_string($colors))
        {
            $colors = explode('|', $colors);
        }
        elseif(!is_array($colors))
        {
            throw new InvalidArgumentException('chart-colors attribute should be an array or string');
        }   
        
        $this->setAttributeDirectly('chart-colors', $colors);
    }
    
    protected function doDraw(Document $document, DrawingTaskHeap $tasks)
    {
        parent::doDraw($document, $tasks);
        
        $callback = function(PieChart $node, Document $document){
            $gc = $node->getGraphicsContext();
            $point = $node->getMiddlePoint();
            
            $values = $node->getAttribute('chart-values');
            $colors = $node->getAttribute('chart-colors');
            
            $totalValues = array_sum($values);
            
            if($totalValues > 0)
            {             
                $start = 0;  
                foreach($values as $i => $value)
                {
                    if(!isset($colors[$i]))
                    {
                        throw new InvalidArgumentException(sprintf('Color number %d for pie chart value (%d) is missing.', $i, $value));
                    }

                    $color = $colors[$i];
                    $relativeValue = $value/$totalValues;
                    $end = $start + 360*$relativeValue;
                    
                    $gc->saveGS();
                    $gc->setFillColor($color);
                    $gc->drawArc($point->getX(), $point->getY(), $node->getWidth(), $node->getHeight(), $start, $end, GraphicsContext::SHAPE_DRAW_FILL);
                    $gc->restoreGS();

                    $start = $end;
                }
            }
        };
        
        $tasks->insert(new DrawingTask($callback, array($this, $document), /* between background and border */45));
    }
}