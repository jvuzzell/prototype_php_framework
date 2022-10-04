<?php

namespace Tests\Scripts\Nickturner_Demo;

use PHPUnit\Framework\TestCase;

class DeprecatedTest extends TestCase {    
    public function testDeprecated() {
        $this->assertType('int', 3);
    }
}

?>
