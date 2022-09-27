<?php 

use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;

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
        Api_response::print_json( 404, $exception_response, 'dev' );
    }
    
    if( IS_CLI ) {
        Api_response::print_json( 404, $exception_response, ENV_NAME );
    } else {
        Api_response::route_to_custom_page( 404, $exception_response, ERROR_PAGE, ENV_NAME ); 
    } 

}

/**
 * Set Universal Error Handler
 */

function universal_error_handler( $error = 0, $error_message = '', $error_file = '', $error_line = 0, $error_context = [] ) {

    $response_code = 500;
    $exception_response = array(
        'error'   => TRUE, 
        'status'  => $response_code,
        'system'  => array(
            'issue_id' => 'universal_exception_handler_002', 
            'log'      => TRUE, 
            'private'  => TRUE, 
            'continue' => FALSE
        ),
        'source'  => 'universal_error_handler',
        'message' => $error_message,
        'data'  => array(
            'exception' => array( 
                'message' => $error_message, 
                'code' => $error, 
                'file' => $error_file, 
                'line' => $error_line
            )
        )
    );

    if ( !defined( 'IS_CLI' ) || ( !defined( 'ENV' ) ) ) {
        Api_response::print_json( 404, $exception_response, 'dev' ); // Assume we're not in a safe space, so be quiet
    }

    if( IS_CLI ) {
        Api_response::print_json( 404, $exception_response, ENV_NAME );
    } else {
        Api_response::route_to_custom_page( 404, $exception_response, ERROR_PAGE, ENV_NAME ); 
    } 
    
}

set_error_handler( 'universal_error_handler', E_ALL );
set_exception_handler( 'universal_exception_handler' );