<?php

namespace Tests\Scripts\Story_13;

use PHPUnit\Framework\TestCase;

class PassedTest extends TestCase  {    
    public function testThisTestPasses() {
        print_r(array('some', 'random', 'array'));
        $this->assertEquals(1, 1);
        $this->assertEquals(1, 1);
    }

    public function testThisTestPassesToo() {
        print_r(file_get_contents(__FILE__));
        $this->assertEquals(1, 1);
    }
}