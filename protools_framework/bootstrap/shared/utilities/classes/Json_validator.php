<?php 

namespace Bootstrap\Shared\Utilities\Classes;

/** 
 * @package JSON Validator
 * @version 1.0
 * 
 * @author Joshua Uzzell 
 * 
 * Purpose: 
 * Provide a common way to determine whether a JSON is formatted correctly.
 * 
 * - If the JSON is valid then the JSON is returned to the caller as  
 *   a decoded array 
 * - If the JSON is invalid then this package provides feedback in the form 
 *   of an with HTTP status code. 
 * 
 * Public Methods
 *      @method validate_file
 */

class Json_validator {

    Public function validate( $file ) {

        /**
         * 1. Is file blank
         */

        if( $file == "" || $file == null ) {

            return array( 
                'error'   => TRUE, 
                'status'  => 100, // Continue processing 
                'system'  => array(
                    'issue_id' => 'json_validator_001', 
                    'log'      => FALSE, 
                    'private'  => FALSE, 
                    'continue' => TRUE
                ),
                'source'  => get_class( $this ),
                'message' => 'Blank file received',
                'data'    => NULL
            );

        } else {

            /**
             * 2. Is JSON format valid 
             * 
             * If valid, then the Json received will be decoded
             */

            $url_decoded_file = urldecode( $file ); 
            
            if( is_string( $url_decoded_file ) ) { 

                $response_data = json_decode( $url_decoded_file, true );
   
                if( json_last_error() === JSON_ERROR_NONE ) {

                    return array(
                        'error'   => FALSE, 
                        'status'  => 200, // ok
                        'source'  => get_class( $this ),
                        'message' => 'Valid json received',
                        'data'    => $response_data // return decoded JSON
                    ); 

                } else {

                    // Determine JSON error

                    switch (json_last_error()) {

                        case JSON_ERROR_NONE:
                            $error_message = 'No errors';
                        break;
                        case JSON_ERROR_DEPTH:
                            $error_message = 'Maximum stack depth exceeded';
                        break;
                        case JSON_ERROR_STATE_MISMATCH:
                            $error_message = 'Underflow or the modes mismatch';
                        break;
                        case JSON_ERROR_CTRL_CHAR:
                            $error_message = 'Unexpected control character found';
                        break;
                        case JSON_ERROR_SYNTAX:
                            $error_message = 'Syntax error, malformed JSON';
                        break;
                        case JSON_ERROR_UTF8:
                            $error_message = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                        break;
                        default:
                            $error_message = 'Unknown error';
                        break;
                    }

                    return array( 
                        'error'   => TRUE, 
                        'status'  => 406, // Not acceptable
                        'system'  => array(
                            'issue_id' => 'json_validator_002', 
                            'log'      => FALSE, 
                            'private'  => FALSE, 
                            'continue' => FALSE
                        ),
                        'source'  => get_class( $this ),
                        'message' => 'Error - ' . $error_message,
                        'data'    => $response_data
                    );     

                }

            } else { 

                /** 
                 * 3. Do not process files that were not sent as valid JSON strings 
                 */

                return array( 
                    'error'   => TRUE, 
                    'status'  => 415, // Unsupported media     
                    'system'  => array(
                        'issue_id' => 'json_validator_003', 
                        'log'      => FALSE, 
                        'private'  => FALSE, 
                        'continue' => FALSE
                    ),
                    'source'  => get_class( $this ),          
                    'message' => 'Unsupported media detected'
                );  

            }          

        }

    } // end validate_file

}