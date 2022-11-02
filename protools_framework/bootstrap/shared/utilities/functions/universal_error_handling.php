<?php 

use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;

/**
 * Set Universal Exception Handler
 */

const debug_env = 'prod';

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

    switch(true) {
        case ( ! defined( 'IS_CLI' ) && ! isset( $_SERVER ) ) : 
            Api_response::print_stderr( $exception_response, debug_env );
            break; 
        case ( ! defined( 'IS_CLI' ) && isset( $_SERVER ) ) : 
            Api_response::print_json_to_screen( 500, $exception_response, debug_env ); 
            break; 
        case IS_CLI : 
            Api_response::print_stderr( $exception_response, ENV_NAME );
        case ( ! defined( 'ERROR_PAGE' ) || ! defined( 'ENV_NAME' ) ) :
            Api_response::print_json_to_screen( 500, $exception_response, debug_env );
            break; 
        default : 
            Api_response::route_to_custom_page( 500, $exception_response, ERROR_PAGE, ENV_NAME ); 
            break;
    }

    exit; 
    
}

/**
 * Set Universal Error Handler
 */

function universal_error_handler( $severity, $message, $file, $line ) {

    universal_exception_handler( new ErrorException($message, 0, $severity, $file, $line) );

}

set_error_handler( 'universal_error_handler' );
set_exception_handler( 'universal_exception_handler' );

if( debug_env !== 'prod' ) {
    ini_set( "display_errors", "on" );
    error_reporting( E_ALL );
}