<?php

class ClassWithTwoArguments
{
    private $arg1;
    private $arg2;
    
	public function __construct($arg1, $arg2)
    {
        $this->arg1 = $arg1;
        $this->arg2 = $arg2;
    }
    
    public function getArg1()
    {
        return $this->arg1;
    }

	public function getArg2()
    {
        return $this->arg2;
    }
}