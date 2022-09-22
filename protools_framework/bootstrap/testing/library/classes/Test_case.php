<?php 
/**
 * @TODO Exit if accessed directly  
 * 
 * @TODO  
 * 
 * 1. Read manifest of Test Steps (Include prepare, execution, validation, and cleanup steps)
 * 2. Execute Test Steps 
 * 3. Report pass or fail flags with messages and data related to discrepancies  
 * 4. Handle multiple tests
 * 
 */

class Test_case {

    Public function __construct( Assertions $assertions ) {

        $this->assertions = $assertions;

    }

    PRIVATE function execute_callback( $callback, $parameters ) {

        $test_results = call_user_func( $callback, $parameters );  

        return $test_results; 

    }

    /**
     * Evaluate Test Results 
     * 
     * @param mixed $exoected_value  
     * @param array $test_results 
     * @param string $condition_type  
     * 
     * @return boolean Return True or False based on whether the results match passing condition
     */

    Private function evaluate_test_results( $expected_value, $test_results, $condition_type ) {

        return $this->assertions->assert_identical_values( $expected_value, $test_results, $condition_type );

    } 

    /**
     * Expect
     * 
     * Takes a callback, inputs for that callback, and expected output from that callback when it is run.
     * 
     * @param array $args  Test configurations and meta describing the implementation of the test
     * 
     * @return array       Array describing test results
     * 
     */

    Public function expect( $args = array() ) { 
        
        $test_results = array(
            'answer'       => null, // Mixed
            'outcome_flag' => '', // This is meta and is defined by the developer as success, failure, skip, etc. to give greater context
            'pass'         => false, // Question of whether the test passed or failed
        );

        // Note: If these values are not updated; these arguments will result in a failed test. The goal is make sure that developers 
        //       are accurately using the test framework. 
        //       
        //       Details - The system expects an exact match based on the arguments below. So it will fail because the callback  
        //       returns false, and the value of the passing condition is true.

        $default_args = array(
            'name'        => 'Unnamed Test', 
            'description' => 'Missing test description - All tests should be named and given meaningful descriptions of what they are testing',
            'callback'    => function( $args ) { return false; }, 
            'callback_arguments' => array(), 
            'passing_conditions' => array(
                array(
                    'execution_type' => 'exact-match', 
                    'outcome_flag'   => 'fail', 
                    'value'          => true  
                )
            ), 
            'vital'   => true, 
            'skip'    => false
        );

        $args = array_merge( $default_args, $args );

        // Compile test description 
        

        // Begin Testing
        if( $args[ 'skip' ] ) {

            $test_results = array(
                'answer'       => null, 
                'outcome_flag' => 'skipped', 
                'pass'         => false
            );

            return $test_results;
        }

        $passing_conditions = $args[ 'passing_conditions']; 

        // Execute test
        $test_results[ 'answer' ] = $this->execute_callback( $args[ 'callback' ], $args[ 'callback_arguments' ] ); 

        // Evaluate passing conditions with preference
        foreach( $passing_conditions as $condition ) {
            
            $evaluation = $this->evaluate_test_results( $condition[ 'value' ], $test_results[ 'answer' ], $condition[ 'type' ] ); 
            $condition_outcome = $condition[ 'outcome_flag' ]; 
            if( $evaluation ) {
                
                // If result of test matches the passing condition then exit loop, and return test results to be rendered on screen
                // If not continue testing other passing conditions
                $test_results[ 'pass' ] = $evaluation; 
                $test_results[ 'outcome_flag' ] = $condition_outcome; 
                
                return $test_results; 

            }

        } 

        $test_results[ 'pass' ] = $evaluation;
        $test_results[ 'outcome_flag' ] = $condition_outcome;

        return $test_results; 

    }

}