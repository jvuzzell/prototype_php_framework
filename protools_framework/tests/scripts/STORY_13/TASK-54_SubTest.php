<?php
 
namespace Tests\Scripts\Story_13;

use PHPUnit\Framework\TestCase;

class SubTest extends TestCase {    
    public function testTestIsDeprecatedAndContainsAnError() {
        $this->assertType('int', 3);
        echo $some_undefined_variable;
    }

    function test2() {
        $fred = array(2, 4, 6);
        echo '$fred=';
        print_r($fred);
        $this->assertEquals($fred, array(2, 4, 6));
    }
    
}