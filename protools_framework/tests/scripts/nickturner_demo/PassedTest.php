<?php

namespace Tests\Scripts\Nickturner_Demo;

use PHPUnit\Framework\TestCase;

class PassedTest extends TestCase  {    
    public function testThisPasses() {
        print_r(array('some', 'random', 'array'));
        $this->assertEquals(1, 1);
        $this->assertEquals(1, 1);
    }

    public function testThisPassesToo() {
        print_r(file_get_contents(__FILE__));
        $this->assertEquals(1, 1);
    }
}

?>
