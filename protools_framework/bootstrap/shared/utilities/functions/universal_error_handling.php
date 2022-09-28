<?php 

use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;

/**
 * Set Universal Exception Handler
 */

function universal_exception_handler( $exception ) {

    $response_code = 500;
    $exception_response = array(
        'error'   => TRUE, 
        'status'  => $response_code,
        'system'  => array(
            'issue_id' => 'universal_exception_handler_001', 
            'log'      => TRUE, 
            'private'  => TRUE,
            'continue' => FALSE
        ),
        'source'  => 'universal_exception_handler',
        'message' => $exception->getMessage(), 
        'data'  => array(
            'exception' => array( 
                'message' => $exception->getMessage(), 
                'code' => $exception->getCode(), 
                'file' => $exception->getFile(), 
                'line' => $exception->getLine()
            )
        )
    );

    if ( ! defined( 'IS_CLI' ) ) {
        Api_response::print_stderr( 404, $exception_response, 'dev' );
    }
    
    if( IS_CLI ) {
        Api_response::print_stderr( 404, $exception_response, ENV_NAME );
    } else {
        Api_response::route_to_custom_page( 404, $exception_response, ERROR_PAGE, ENV_NAME ); 
    } 

}

/**
 * Set Universal Error Handler
 */

function universal_error_handler( $severity, $message, $file, $line ) {

    universal_exception_handler( new ErrorException($message, 0, $severity, $file, $line) );

}

/**
* Checks for a fatal error, work around for set_error_handler not working on fatal errors.
*/
function check_for_fatal() {

    $error = error_get_last();

    if ( isset( $error["type"] ) && $error["type"] == E_ERROR ) {
        log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
    }

}

register_shutdown_function( "check_for_fatal" );
set_error_handler( 'universal_error_handler' );
set_exception_handler( 'universal_exception_handler' );
ini_set( "display_errors", "off" );
error_reporting( E_ALL );