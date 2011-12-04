<?php

namespace PHPPdf\PHPUnit\Framework\Constraint;

class ValidateByCallback extends \PHPUnit_Framework_Constraint
{
    private $closure;
    private $testCase;
    private $failureException;
    private $valid = null;
    
    public function __construct(\Closure $closure, \PHPUnit_Framework_TestCase $testCase)
    {
        $this->closure = $closure;
        $this->testCase = $testCase;
    }
    
	public function evaluate($other, $description = '', $returnResult = FALSE)
	{
	    if($this->valid !== null)
	    {
	        return $this->valid;
	    }
	    
	    try
	    {
	        $closure = $this->closure;
	        $closure($other, $this->testCase);
	    }
	    catch(\PHPUnit_Framework_AssertionFailedError $e)
	    {
	        $this->failureException = $e;
	        $this->valid = false;
	        return false;
	    }
	    
	    $this->valid = true;
	    
	    return true;
	}

	public function toString()
	{
		return $this->failureException->toString();
	}
}