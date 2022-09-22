<?php 
/**
 * @TODO Exit if accessed directly 
 * 
 * @TODO 
 * 
 * 1. Consume given test_result
 * 2. Implement passing condition 
 * 3. Implement callback(s) to validate test_result
 * 4. Return whether passing condition has been met 
 */

/**
 * Types of Passing Conditions
 * 
 * Value 
 * 1. Exact Match (Input must be identical; Matches data type or typeof AND exact value)
 * 2. Match Type (Matches data type or typeof)
 * 3. Match within Range (Matches data type or typeof within a range)
 * 4. Match Contents 
 * 5. Count (Above and Below)
 * 
 * State
 * 4. Validate File/Directory Contents (Exists, Readable, Writable,)
 * 5. Validate Email 
 * 6. Validate Database
 * 7. Validate API Response (Integrated) 
 * 8. 
 * 
 * Assertions 
 * 1. AssertTrue 
 * 2. AssertFalse 
 * 3. AssertClassHasAttribute
 * 4. AssertContains
 * 5. AssertStringContains
 */

class Assertions { 

    // Recursion can occur when evaluating objects and arrays for sameness
    Private $first_recursive_iteration  = true; 
    Private $recursive_condition_type = ''; 
    Private static $instance = null; 
    
    /**
     * Get_instance
     * 
     * Applying a singleton pattern to this class because of the recursion in the assert_identical_values method
     */

    Public function get_instance() {
        
        if( self::$instance == null ) {
            self::$instance = new Assertions();
        }

        return self::$instance;

    }
    
    /**
     * Assert Identical Values 
     * 
     * @param mixed  $passing_condition  A constant that is compared to the test result to determine the success or failure of a test
     * @param mixed  $test_results       
     * @param string $condition_type     Describes the method that will be used to compare the passing_condition to the test_results, 
     *                                   Possible values: "expected-type" (loose match), "expected-structure" (compares the structure 
     *                                   of two objects or arrays, but not the value of their attributes), "expected-match" (strict
     *                                   comparison).
     * 
     * @return boolean                   Describes whether asserted test results are the same as the passing condition (pass) 
     *                                   or different (fail)
     */

    Private function assert_identical_values( $passing_condition, $test_results, $condition_type = 'expected-type' ): bool {
        
        // Prevent the type of test from changing during recursive checks of arrays and objects
        if( $this->first_recursive_iteration ) {
            $this->recursive_condition_type = $condition_type; 
            $this->first_recursive_iteration = false; 
        }

        // Note: condition_type - type of passing condition
        //       possible values: 'exact-match'        - everything matches the $passing-condition; 
        //                                               data type, structure, values within structure
        //                        'expected-structure' - data type and structure of objects and array match
        //                                               $passing_condition 
        //                        'expected-type'      - check that top level data type matches, nothing else

        /**
         * First Evaluation, check types and if we only cared that the types matched then exit this assertion
         */
        $type1 = gettype( $passing_condition );    
        $type2 = gettype( $test_results );
        
        if( $type1 !== $type2 ){
            return false;
        } else {
            if( $this->condition_type == 'expected-type' ) {
                return true; 
            }
        }


        /**
         * Second Evaluation, if the structure and/or the values matter to us
         */

        // For those unfamiliar with this technique, this switch will run the first condition that evaluates to true
        switch( true ){

            case ( $type1 === 'boolean' || $type1 === 'integer' || $type1 === 'double' || $type1 === 'string' ):
                // Do strict comparison of values.
                if($test_results !== $passing_condition){
                    return false;
                }
                break;

            case ( $type1 === 'array' ):
                $bool = $this->assert_identical_arrays( $passing_condition, $test_results, $this->condition_type );

                if( $bool === false ){
                    return false;
                }
                break;

            case 'object':
                $bool = $this->assert_identical_objects( $passing_condition, $test_results, $condition_type );
                
                if( $bool ===false ){
                    return false;
                }
                break;

            case 'NULL':
                // Since both types were of type NULL, consider their "values" equal.
                break;

            case 'resource':
                // How to compare if at all?
                break;

            case 'unknown type':
                // How to compare if at all?
                break;
                
        } // end switch

        // All tests passed.
        return true;
        
    }

    /**
     * Assert Identical Objects
     * 
     * @param object $o1  
     * @param object $o2 
     * @param string $condition_type     Describes the method that will be used to compare the passing_condition to the test_results, 
     *                                   Possible values: "expected-type" (loose match), "expected-structure" (compares the structure 
     *                                   of two objects or arrays, but not the value of their attributes), "expected-match" (strict
     *                                   comparison).
     * 
     * @return boolean                   Describes whether asserted test results are the same as the passing condition (pass) 
     *                                   or different (fail)
     */

    Private function assert_identical_objects( $o1, $o2, $condition_type ): bool {

        // See if loose comparison passes.
        if( $o1 != $o2 ){
            return false;
        }

        // Now do strict(er) comparison.
        $objReflection1 = new ReflectionObject( $o1 );
        $objReflection2 = new ReflectionObject( $o2 );

        $arrProperties1 = $objReflection1->getProperties( ReflectionProperty::IS_Public );
        $arrProperties2 = $objReflection2->getProperties( ReflectionProperty::IS_Public );

        $bool = $this->assert_identical_arrays( $arrProperties1, $arrProperties2 );

        if( $bool === false ){
            return false;
        }

        foreach( $arrProperties1 as $key=>$propName ){

            $bool = $this->assert_identical_values( $o1->$propName, $o2->$propName );
            if( $bool === false ){
                return false;
            }

        }
        
        // Clean up task; reset recursive flag for next test
        $this->clean_up();

        // All tests passed.
        return true;

    }

    /**
     * Assert Identical Arrays
     * 
     * @param array $array1
     * @param array $array2
     * @param string $condition_type     Describes the method that will be used to compare the passing_condition to the test_results, 
     *                                   Possible values: "expected-type" (loose match), "expected-structure" (compares the structure 
     *                                   of two objects or arrays, but not the value of their attributes), "expected-match" (strict
     *                                   comparison).
     * 
     * @return boolean                   Describes whether asserted test results are the same as the passing condition (pass) 
     *                                   or different (fail)
     */

    Private function assert_identical_arrays( array $arr1, array $arr2, $condition_type ): bool {
        
        //Note: $arr1 should be the $passing_condition, while $arr2 is should be the $test_result

        $count = count($arr1);

        // Require that they have the same size.
        if( count( $arr2 ) !== $count ){
            return false;
        }

        // Require that they have the same keys.
        $arrKeysInCommon = array_intersect_key( $arr1, $arr2 );

        if( count( $arrKeysInCommon )!== $count ) {
            return false;
        }

        // Require that their keys be in the same order.
        $arrKeys1 = array_keys( $arr1 );
        $arrKeys2 = array_keys( $arr2 );


        foreach( $arrKeys1 as $key => $val ){

            if( $arrKeys1[ $key ] !== $arrKeys2[ $key ] ) {
                return false;
            }

        }

        // They do have same keys and in same order.
        // Check for 'exact-value' match on array 
        foreach( $arr1 as $key => $val ) {

            $arrValue1 = gettype( $arr1[ $key ] ); 
            $valueEqualArray = ( $arrValue1 == 'array' ) ? true : false;

            if( 
                ( $this->condition_type == 'expected-structure' && $valueEqualArray  ) ||
                ( $this->condition_type == 'exact-match' )
            ) {

                $bool = $this->assert_identical_values( $arr1[ $key ], $arr2[ $key ] );
                if( $bool===false ){
                    return false;
                }

            }

        }

         // Clean up task; reset recursive flag for next test
         $this->clean_up();

        // All tests passed.
        return true;

    }

    /**
     * Expected Matching Type
     * 
     * @param mixed $value1
     * @param mixed $value2
     * 
     * @return boolean Describes whether asserted test results are the same as the passing condition (pass) 
     *                 or different (fail)
     */

    Public function expected_matching_type( $value1, $value2 ) {

        $assertion = $this->assert_identical_values( $value1, $value2, 'expected-type' ); 
        $this->clean_up();

        return $assertion;

    }

    /**
     * Expected Matching Array Structure
     * 
     * @param array $array1
     * @param array $array2
     * 
     * @return boolean Describes whether asserted test results are the same as the passing condition (pass) 
     *                 or different (fail)
     */

    Public function expected_array_structure( $array1, $array2 ) {

        $assertion = $this->assert_identical_values( $array1, $array2, 'expected-structure' ); 
        $this->clean_up();

        return $assertion;

    }

    /**
     * Expected Matching Object Structure
     * 
     * @param object $object1
     * @param object $object2
     * 
     * @return boolean Describes whether asserted test results are the same as the passing condition (pass) 
     *                 or different (fail)
     */

    Public function expected_object_structure( $object1, $object2 ) {

        $assertion = $this->assert_identical_values( $object1, $object2, 'expected-structure' );
        $this->clean_up();

        return $assertion;

    }

    /**
     * Expected Identical Values
     * 
     * @param mixed $value1
     * @param mixed $value2
     * 
     * @return boolean Describes whether asserted test results are the same as the passing condition (pass) 
     *                 or different (fail) 
     */

    Public function expected_identical_values( $value1, $value2 ) {

        $assertion = $this->assert_identical_values( $value1, $value2, 'exact-match' );
        $this->clean_up();

        return $assertion;

    }

    /**
     * Expected Identical Array
     * 
     * @param array $array1
     * @param array $array2
     * 
     * @return boolean Describes whether asserted test results are the same as the passing condition (pass) 
     *                 or different (fail)
     */

    Public function expected_identical_arrays( $array1, $array2 ) {

        $assertion = $this->assert_identical_values( $array1, $array2, 'exact-match' );
        $this->clean_up();

        return $assertion;

    }

    /**
     * Expected Identical Objects
     * 
     * @param object $object1
     * @param object $object2
     * 
     * @return boolean Describes whether asserted test results are the same as the passing condition (pass) 
     *                 or different (fail)
     */

    Public function expected_identical_objects( $object1, $object2 ) {

        $assertion = $this->assert_identical_values( $object1, $object2, 'exact-match' ); 
        $this->clean_up();

        return $assertion;
    }

    /**
     * Clean up 
     */

    Private function clean_up() {

        // Clean up task; reset recursive flag for next test
        $first_recursive_iteration = true;

    }

}

