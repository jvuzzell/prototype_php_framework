<?php
 
namespace Tests\Scripts\Story_13;

use PHPUnit\Framework\TestCase;

class SkippedTest extends TestCase  {
    protected function setUp() :void {
        $this->markTestSkipped('All tests are marked as skipped.');
    }

    public function testThisTestPassedButShouldBeSkipped() {
        $this->assertEquals(1, 1, 'This test should not fail!');
    }

    public function testThisTestFailedButShouldBeSkipped() {
        $this->assertEquals(1, 0, 'This test is meant to fail!');
    }
}