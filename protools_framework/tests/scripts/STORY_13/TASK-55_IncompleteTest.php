<?php
 
namespace Tests\Scripts\Story_13;

use PHPUnit\Framework\TestCase;

class IncompleteTest extends TestCase  {
    public function testThisIsAnIncompleteTest() {
      
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
    }

    public function testSomething() {
        // Stop here and mark this test as incomplete.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
          );
        $this->assertTrue(FALSE);
        // Optional: Test anything here, if you want.
        $this->assertTrue(true, 'This should already work.');
    }

    // public function testTestAPassedAndAFailedAssertion() {
    //     print_r('This test has a bad assertion followed by a good assertion');
    //     $this->assertEquals(1, 0);
    //     $this->assertEquals(1, 1);
    // }

    // public function testThisTestHasNoAssertions() {
    //     print_r('This test does nothing');
    // }
}