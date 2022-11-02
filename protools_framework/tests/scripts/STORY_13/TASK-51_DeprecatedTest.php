<?php

namespace Tests\Scripts\Story_13;

use PHPUnit\Framework\TestCase;

class TestPHPUnitDeprecatedAssertionsTest extends TestCase {    
    public function testThisTestContainsADeprecatedAssertion() {
        $this->assertType('int', 3);
    }
}