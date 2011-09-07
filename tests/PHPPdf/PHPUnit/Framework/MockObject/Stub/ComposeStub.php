<?php

namespace PHPPdf\PHPUnit\Framework\MockObject\Stub;

class ComposeStub implements \PHPUnit_Framework_MockObject_Stub
{
    private $stubs;
    
    public function __construct(array $stubs)
    {
        foreach($stubs as $stub)
        {
            if(!$stub instanceof \PHPUnit_Framework_MockObject_Stub)
            {
                throw new \InvalidArgumentException('Stubs have to implements PHPUnit_Framework_MockObject_Stub interface.');
            }
        }
        
        $this->stubs = $stubs;
    }

    public function invoke(\PHPUnit_Framework_MockObject_Invocation $invocation)
    {
        $returnValue = null;
        foreach($this->stubs as $stub)
        {
            $value = $stub->invoke($invocation);
            
            if($value !== null)
            {
                $returnValue = $value;
            }
        }
        
        return $returnValue;        
    }
    
    public function toString()
    {
        $text = '';
        
        foreach($this->stubs as $stub)
        {
            $text .= $stub->toString();
        }
        
        return $text;
    }    
} 