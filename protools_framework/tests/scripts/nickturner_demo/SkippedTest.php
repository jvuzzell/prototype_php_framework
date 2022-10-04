<?php
 
namespace Tests\Scripts\Nickturner_Demo;

use PHPUnit\Framework\TestCase;

class SkippedTest extends TestCase  {
    private function testSetUp() {
        $this->markTestSkipped('All tests are marked as skipped.');
    }

    public function testPassed() {
        $this->assertEquals(1, 1, 'This test should not fail!');
    }

    public function testFailed() {
        $this->assertEquals(1, 0, 'This test is meant to fail!');
    }
}

?>
