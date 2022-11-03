<?php

namespace Bootstrap\Shared\Utilities\Classes\Static;

use \Dump_var;

/** 
 * @package Standard_api_response
 * @author Joshua Uzzell 
 * @since 2019
 * 
 * @param $log_file
 * 
 * @method print_json_to_screen
 * @method route_to_custom_page
 * @method print_stderr 
 * @method get_response
 * 
 */

class Api_response {

    Private static $log_manifest = array();

    /**
     * Set HTTP Response Code 
     *  
     * @param int $code  HTTP response code 
     */

    Private static function set_http_response_code( int $code ) {
        
        if ( $code !== NULL ) {

            switch ( $code ) {
                case 100: $text = 'Continue'; break;
                case 101: $text = 'Switching Protocols'; break;

                case 200: $text = 'OK'; break;
                case 201: $text = 'Created'; break;
                case 202: $text = 'Accepted'; break;
                case 203: $text = 'Non-Authoritative Information'; break;
                case 204: $text = 'No Content'; break;
                case 205: $text = 'Reset Content'; break;
                case 206: $text = 'Partial Content'; break;

                case 300: $text = 'Multiple Choices'; break;
                case 301: $text = 'Moved Permanently'; break;
                case 302: $text = 'Moved Temporarily'; break;
                case 303: $text = 'See Other'; break;
                case 304: $text = 'Not Modified'; break;
                case 305: $text = 'Use Proxy'; break;

                case 400: $text = 'Bad Request'; break;
                case 401: $text = 'Unauthorized'; break;
                case 402: $text = 'Payment Required'; break;
                case 403: $text = 'Forbidden'; break;
                case 404: $text = 'Not Found'; break;
                case 405: $text = 'Method Not Allowed'; break;
                case 406: $text = 'Not Acceptable'; break;
                case 407: $text = 'Proxy Authentication Required'; break;
                case 408: $text = 'Request Time-out'; break;
                case 409: $text = 'Conflict'; break;
                case 410: $text = 'Gone'; break;
                case 411: $text = 'Length Required'; break;
                case 412: $text = 'Precondition Failed'; break;
                case 413: $text = 'Request Entity Too Large'; break;
                case 414: $text = 'Request-URI Too Large'; break;
                case 415: $text = 'Unsupported Media Type'; break;

                case 500: $text = 'Internal Server Error'; break;
                case 501: $text = 'Not Implemented'; break;
                case 502: $text = 'Bad Gateway'; break;
                case 503: $text = 'Service Unavailable'; break;
                case 504: $text = 'Gateway Time-out'; break; 
                case 505: $text = 'HTTP Version not supported'; break;
                default:
                    // The program you ran is trying to output an Unknown HTTP status code; this is common when using ResponseHelper
                    exit( 'standard_api_response_001; Unknown http status code "' . htmlentities( $code ) . '"' );
                break;
            }

        } else {
            $code = 200; 
            $text = 'OK';
        }

        // Set header
        http_response_code( $code );
        
    }

    /**
     * Set Standardized API Response
     * 
     * @param array $args
     * @param string $environment
     */

    Private static function set_response( $args = array(), string $environment = 'prod' ) {
      
        $default_args = array(
            'error'  => true,
            'status' => 500, 
            'logged_at' => date( 'Y-m-d H:i:s' ),
            'system' => array(
                'issue_id' => 'standard_api_response_003',
                'log'      => false,
                'private'  => true,
                'continue' => true
            ),
            'source'  => get_class(),
            'message' => '',
            'data'    => array(),
        ); 

        $args = self::array_merge_recursive_distinct( $default_args, $args );

        // @todo In the future if an API response is private, we need to make sure 
        //       that it is logged with all attributes
        
        if( $args[ 'system' ][ 'log' ] ) {
            self::log_response( $args );
        }

        if( 
            $args[ 'system' ][ 'private' ] === false ||
            $environment === 'dev'
        ) {

            $api_response = $args;

        } else {

            $api_response = array( 
                'status'  => $args[ 'status' ],
                'error'   => $args[ 'error' ],
                'message' => $args[ 'message' ], 
                'source'  => $args[ 'source' ]
            );

        }

        return $api_response; 

    }

    Private static function array_merge_recursive_distinct ( array &$array1, array &$array2 )
    {
      $merged = $array1;
    
      foreach ( $array2 as $key => &$value )
      {
        if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
        {
          $merged [$key] = self::array_merge_recursive_distinct ( $merged [$key], $value );
        }
        else
        {
          $merged [$key] = $value;
        }
      }
    
      return $merged;
    }

    /**
     * Route to Custom Error Handling Page 
     * 
     * @param int $response_code
     * @param array $response_data 
     * @param string $internal_path
     */

    Public static function route_to_custom_page( int $response_code, array $response_data, string $internal_path, string $environment = 'prod' ) {
 
        // Add defaults for Standardized API Response
        $response_data = self::set_response( $response_data, $environment );
        $response_data[ 'source' ] = ( isset( $response_data[ 'source' ] ) ) ? $response_data[ 'source' ] : 'api_response_005';
 
        // Move response data to the end of the array
        if( isset( $response_data[ 'data' ] ) ) {
            $data = $response_data[ 'data' ];
            unset( $response_data[ 'data' ] );
            $response_data[ 'data' ] = $data;
        }

        include( $internal_path );
        
        die(); 

    }

    /**
     * JSON Encode Standard API Response 
     * 
     * @param array $response_data 
     * @param string $caller_ref    If an error occurs, a new message regarding the error is sent back to the caller.
     *                              Caller references limited to encode_api_response() and print_json_to_screen().
     * 
     * @return string               JSON string of standard API response 
     */

    Public static function encode_response( array $response_data, string $caller_ref = 'encode_response' ) : string {

        // (Style guide) Move response data to the end of the array 
        if( isset( $response_data[ 'data' ] ) ) {
            $data = $response_data[ 'data' ];
            unset( $response_data[ 'data' ] );
            $response_data[ 'data' ] = $data;
        }

        $response_data_json = json_encode( $response_data, JSON_PRETTY_PRINT ); 

        // Error handling
        if( $response_data_json === false ) {

            // Compile error
            $response_code = 500;
            $response_data = self::response_helper( 
                array(
                    'status'   => $response_code, 
                    'issue_id' => 'standard_api_response_002', 
                    'message'  => json_last_error_msg() 
                )
            );

            // Re-submit data to be JSON encoded and returned to the client
            // This ensures that they always receive a JSON as expected
            if( $caller_ref == 'encode_response' ) {
                self::encode_response( $response_data );
            } else {
                self::print_json_to_screen( $response_code, $response_data );
            }

        } else {

            // Success 
            return $response_data_json;

        }

    }

    /**
     * Print JSON to Screen
     * 
     * Set HTTP response header, content type, encodes response data as json, 
     * and prints the response 
     * 
     * @param int $response_code
     * @param string $response_data
     * @param string $environment  
     */

    Public static function print_json_to_screen( int $response_code, array $response_data, string $environment = 'prod' ) {

        // Set headers
        self::set_http_response_code( $response_code ); 
        header( 'content-type: application/json' ); 

        // Add response headers to response
        $response_data[ 'system' ][ 'response_header' ] = headers_list();

        // Add defaults for Standardized API Response
        $response_data = self::set_response( $response_data, $environment );

        // Print JSON to screen
        echo self::encode_response( $response_data, __FUNCTION__ );

        // No further processing
        die();

    }

    /**
     * Print Stderr
     */

    Public static function print_stderr( array $response_data, string $environment = 'prod' ) {

        // Add defaults for Standardized API Response
        $response_data = self::set_response( $response_data, $environment );

        // Print JSON to screen
        fwrite( STDERR, self::encode_response( $response_data, __FUNCTION__ ) );

        exit;
    }

    /**
     * Alias for print_json_to_screen
     */

    Public static function print_json( int $response_code, array $response_data, string $environment = 'prod' ) {
        
        return self::print_json_to_screen( $response_code, $response_data, $environment );

    }

    /** 
     * API response helper
     * 
     * Standardized format for responding to HTTP requests or internal API
     * 
     * @param array $args = [
     *      'error'    => true, // (boolean) Denotes whether error occurred
     *      'issue_id' => '', // (string) unique identifier within entire application
     *      'message'  => '', // (string) 
     *      'data'     => array(), // (string) An array based on another computation
     *      'status'   => 500, // (int) HTTP status used in response header 
     *      'log'      => false, // (boolean) Should this information should be logged
     *      'private'  => true, // (boolean) Should the error information can be seen in production
     *      'continue' => true, // (boolean) Should this program keep running if it encounters an error
     *      'source'   => get_class() // (boolean) Class, function, or file name containing this information
     * ]
     * 
     * @return array [
     *      'status' => 500, // (int) HTTP status used in response header 
     *      'error'  => true, // (boolean) Denotes whether error occurred
     *      'logged_at', => 'Y-m-d H:i:s' // Time response logged 
     *      'system' => [
     *          'issue_id'  => '', // (string) unique identifier within entire application
     *          'log'       => false, // (boolean) Should this information should be logged
     *          'private'   => true, // (boolean) Should the error information can be seen in production
     *          'continue'  => true, // (boolean) Should this program keep running if it encounters an error
     *      ],
     *      'source' => '', // (boolean) Class, function, or file name containing this information
     *      'message' => '', // (string) Friendly description of the outcome
     *      'data' => [] // (mixed) Payload
     * ]
     */

    Private static function response_helper( $args = array() ) : array {

        $default_args = array(
            'error'       => true,
            'issue_id'    => 'standard_api_response_004', // string 
            'message'     => '', // string
            'data'        => array(),
            'status'      => 500, 
            'log'         => false,
            'private'     => true,
            'continue'    => true,
            'source'      => get_class()
        ); 

        $args = array_merge( $default_args, $args );

        return array( 
            'status' => $args[ 'status' ],
            'error'  => $args[ 'error' ],
            'logged_at' => date( 'Y-m-d H:i:s' ),
            'system' => array(
                'issue_id' => $args[ 'issue_id' ],
                'log'      => $args[ 'log' ], 
                'private'  => $args[ 'private' ], 
                'continue' => $args[ 'continue' ]
            ),
            'source'  => $args[ 'source' ],
            'message' => $args[ 'message' ], 
            'data' => $args[ 'data' ]
        );
        
    }

    /**
     * Get response 
     * 
     * Alias for response_helper()
     * 
     * @param array $args = [
     *      'error'    => true, // (boolean) Denotes whether error occurred
     *      'issue_id' => '', // (string) unique identifier within entire application
     *      'message'  => '', // (string) 
     *      'data'     => array(), // (string) An array based on another computation
     *      'status'   => 500, // (int) HTTP status used in response header 
     *      'log'      => false, // (boolean) Should this information should be logged
     *      'private'  => true, // (boolean) Should the error information can be seen in production
     *      'continue' => true, // (boolean) Should this program keep running if it encounters an error
     *      'source'   => get_class() // (boolean) Class, function, or file name containing this information
     * ]
     * 
     * @return array [
     *      'status' => 500, // (int) HTTP status used in response header 
     *      'error'  => true, // (boolean) Denotes whether error occurred
     *      'logged_at', => 'Y-m-d H:i:s' // Time response logged 
     *      'system' => [
     *          'issue_id'  => '', // (string) unique identifier within entire application
     *          'log'       => false, // (boolean) Should this information should be logged
     *          'private'   => true, // (boolean) Should the error information can be seen in production
     *          'continue'  => true, // (boolean) Should this program keep running if it encounters an error
     *      ],
     *      'source' => '', // (boolean) Class, function, or file name containing this information
     *      'message' => '', // (string) Friendly description of the outcome
     *      'data' => [] // (mixed) Payload
     * ]
     */

    Public static function get_response( $args ) : array {

        return self::response_helper( $args );

    }

    Public static function set_log_file( $id, $path ) {

        self::$log_manifest[ $id ] = $path;

    }

    Public static function log_response( $response = array(), $id = 'site_log' ) {

        if( isset( self::$log_manifest[ $id ] ) ) {
            $filename = self::$log_manifest[ $id ]; 
            $log_file = fopen( $filename, 'a' );
            fwrite( $log_file, json_encode( $response ) . "\r\n" );
            fclose( $log_file );
        }

    }

}