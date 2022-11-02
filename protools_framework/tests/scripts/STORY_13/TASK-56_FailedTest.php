<?php

namespace Tests\Scripts\Story_13;

use PHPUnit\Framework\TestCase;

class FailedTest extends TestCase  { 
    
    /**
     * @ticket STORY-999
     */
    public function testTestWithAFailedAssertion() {
        print_r('some random debug message');
        $this->assertEquals(1, 2, 'This is doomed to failure!');
    }

    public function testTestWithAnError() {
        somestr;
        $this->assertEquals(1, 2);
    }

    public function testTestWithADeepError() {
	    sample_function();
        $this->assertEquals(1, 2);
    }
}

function sample_function() { sample_function1(); }
function sample_function1() { sample_function2(); }
function sample_function2() { sample_function3(); }
function sample_function3() { sample_function_containing_error(); }
function sample_function_containing_error() { echo $some_undefined_variable; }