<?php 

namespace Bootstrap\Api_gateway\Library\Classes;

use Bootstrap\Api_gateway\Library\Classes\Api_schema; 
use \Dump_var;
use ErrorException;

class Api_resolver extends Api_schema {

    Protected $clients; // data access clients 
    Protected $transaction_type; // string; 'request' or 'response'
    Protected $env_var; // array; environment variables

    Public function __construct( $args ) {

        $this->env_var = $args[ 'env_var' ]; 
        $this->clients = $args[ 'clients' ]; 
        $this->transaction_type = $args[ 'transaction_type' ];

        // Plugins 
        $this->api_response = $args[ 'api_response' ];

        // Schema Parent Class
        parent::__construct( $args[ 'schema' ] );

        // @TODO: eventually check for cached responses and return that instead

    }

    Public function resolve_request( $request_body = array() ) {

        $callback = $this->schema_settings[ 'resolver_callback' ];

        if( isset( $callback ) && $callback !== '' ) {

            $response = $this->{ $callback }( $request_body );
    
        } else {

            $response = $this->api_response->format_response( array( 
                'status' => 500, 
                'private' => true, 
                'log' => true,
                'message' => 'Missing resolver callback',
                'source' => get_class( $this ), 
                'issue_id' => 'api_resolver_001'
            ));

        }

        return $response;

    }

    Public function return_cached_response() { 
        // @TODO: eventually check for cached responses and return that instead
    }

    /**
     * in_request
     * 
     * Detects whether a variable within an array isset() or blank or an empty string
     * 
     * @param  array    $haystack  array to be searched       
     * @param  string   $needle    Name of field expected within haystack  
     * @return mixed               Returns expected value if isset and not blank, or method returns 
     *                             NULL indicating variable was not set or empty
     */

    public function isset_in_request( $needle, $haystack ) {

        $value = NULL;

        if( 
            isset( $haystack[ $needle ] ) &&  
            $haystack[ $needle ] !== '' &&
            $haystack[ $needle ] !== ' '    
        ) {
            $value = $haystack[ $needle ];
        }

        return $value;

    }

}