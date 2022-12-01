<?php 

namespace Bootstrap\Api_gateway\Library\Classes;

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_config;
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;

use ErrorException;

class Api_handler {

    Private $env_config; 
    Private $env_var;
    Private $api_response; 
    Private $json_validator; 
    Private $resolver_directory = '';
    Private $resolvers = array(
        'request' => null, 
        'response' => null
    );

    Public function __construct( Environment_config $environment_config, Api_response $api_response, Json_validator $json_validator ) {

        $this->env_config = $environment_config; 
        $this->env_var = $this->env_config->get_env_variables();
        $this->api_response = $api_response; 
        $this->json_validator = $json_validator; 

        $resolver_directory_response = $this->get_resolver_directory();
        
        if( $resolver_directory_response[ 'error' ] ) {
            throw new ErrorException( $resolver_directory_response[ 'message' ] );
        }
       
        $this->resolver_directory = $resolver_directory_response[ 'data' ][ 'directory' ];

    }

    /**
     * Get Resolver Directory 
     */

    Private function get_resolver_directory() : array {

        $response = array();

        $portfolio_directories = $this->env_var[ 'directories' ][ 'portfolio' ];

        switch( true ) {

            case ( is_dir( $portfolio_directories[ 'site_specific' ] . $this->env_var[ 'route' ][ 'api' ] ) ) :
                
                $resolver_directory = $portfolio_directories[ 'site_specific' ] . $this->env_var[ 'route' ][ 'api' ]; 
                break;

            case ( is_dir( $portfolio_directories[ 'shared' ] . 'api/' . $this->env_var[ 'route' ][ 'api' ] ) ) : 

                $resolver_directory = $portfolio_directories[ 'shared' ] . 'api/' . $this->env_var[ 'route' ][ 'api' ]; 
                break; 

            default :

                $response = $this->api_response->format_response( array(
                    'status' => 404, 
                    'log' => true,
                    'message' => "Resolver directory not found", 
                    'source' => get_class( $this ), 
                    'issue_id' => 'api_handler_004'
                ));
                break;

        }

        $response = $this->api_response->format_response( array(
            'status' => 200, 
            'error' => false,
            'message' => "Resolver directory found", 
            'source' => get_class( $this ), 
            'issue_id' => 'api_handler_003',
            'data' => array( 'directory' => $resolver_directory )
        ));

        return $response;
    }

    /**
     * Load Endpoint
     * 
     * @return array
     */

    Public function load_endpoint() { 

        $resolver_directory = $this->resolver_directory; 
        $request_method = strtolower( $this->env_var[ 'request' ][ 'method' ] );
        $response = array(
            'error' => false
        );

        $resolver_args = array(
            'env_var' => $this->env_var, 
            'clients' => $this->env_config->get_clients(),
            'transaction_type' => '',  // request or response
            'schema' => array(), 
            'json_validator' => $this->json_validator, 
            'api_response' => $this->api_response
        );

        $resolvers = array(
            'requests' => array(
                'paths' => array(
                    'resolver' => $resolver_directory . "/resolvers/requests/$request_method.php",
                    'schema' => $resolver_directory . "/schema/requests/$request_method.json"
                ), 
                'namespace' => '', 
                'classname' => '', 
                'resolver' => null // object
            ), 
            'responses' => array(
                'paths' => array(
                    'resolver' => $resolver_directory . "/resolvers/responses/$request_method.php",
                    'schema' => $resolver_directory . "/schema/responses/$request_method.json"
                ), 
                'namespace' => '', 
                'classname' => '', 
                'resolver' => null // object
            )
        );

        // Initialize request resolver
        foreach( $resolvers as $resolver_type => $resolver_meta ) {    
            
            $resolver_args[ 'transaction_type' ] = $resolver_type; 
            
            // Get schema 
            if( file_exists( $resolver_meta[ 'paths' ][ 'schema' ] ) ) {

                $json_validation_response = $this->json_validator->validate(
                    file_get_contents( $resolver_meta[ 'paths' ][ 'schema' ] ) 
                );

                if( $json_validation_response[ 'error' ] ) {
                    
                    $response = $json_validation_response;
                    break;

                } else {
                    $resolver_args[ 'schema' ] = $json_validation_response[ 'data' ];
                }

            } else {

                $response = $this->api_response->format_response( array(
                    'status' => 500, 
                    'log' => true,
                    'message' => "Endpoint schema not found - $resolver_type", 
                    'source' => get_class( $this ), 
                    'issue_id' => 'api_handler_007'
                ));
                break;

            }

            // Get resolver 
            if( file_exists( $resolver_meta[ 'paths' ][ 'resolver' ] ) ) {
       
                $namespace = $this->return_namespace_from_path( $resolver_directory . "/resolvers/$resolver_type/" );
                $classname = $namespace . ucfirst( "$request_method" );
       
                $resolvers[ $resolver_type ][ 'namespace' ] = $namespace;
                $resolvers[ $resolver_type ][ 'classname' ] = $classname; 

                $resolver_response = $this->get_endpoint_resolver( $classname, $resolver_args );
    
                if( $resolver_response[ 'error' ] ) { 

                    $response = $resolver_response;
                    break;

                } else {
                    
                    $resolvers[ $resolver_type ][ 'resolver' ] = $resolver_response[ 'data' ];
                
                }
    
            } else {
    
                $response = $this->api_response->format_response( array(
                    'status' => 500, 
                    'log' => true,
                    'message' => "Endpoint resolver not found - $resolver_type", 
                    'source' => get_class( $this ), 
                    'issue_id' => 'api_handler_001'
                ));
                break;

            }

        }

        if( !$response[ 'error' ] ) {

            $response = $this->api_response->format_response(
                array(
                    'status' => 200, 
                    'error' => false, 
                    'message' => 'Resolvers loaded', 
                    'source' => get_class( $this ),
                    'issue_id' => 'api_handler_002'
                ));

            $this->resolvers = $resolvers;

        }

        return $response;
        
    }

    /**
     * Get Endpoint Resolver 
     * 
     * @return object returrns a class representing the 
     */

    Private function get_endpoint_resolver( string $classname, array $resolver_args = array() ) {

        $response = array();

        if( class_exists( $classname ) ) {
                
            $resolver_class = new $classname( $resolver_args );

            $response = $this->api_response->format_response( array(
                'status' => 200, 
                'error' => false,
                'message' => 'Resolver found - ' . $classname, 
                'source' => get_class( $this ), 
                'issue_id' => 'api_handler_006', 
                'data' => $resolver_class
            ));

        } else {

            $response = $this->api_response->format_response( array(
                'status' => 500, 
                'log' => true,
                'message' => 'Resolver class could not be found; class name: ' . $classname, 
                'source' => get_class( $this ), 
                'issue_id' => 'api_handler_005'
            ));

        }

        return $response;

    }

    Private function return_namespace_from_path( string $path ) : string {

        $namespace = str_replace( $this->env_var[ 'directories' ][ 'framework' ], '', $path );
        return str_replace( '/', '\\', $namespace ); 

    }

    Public function resolve_endpoint( array $request_data ) {

        $request_resolver = $this->resolvers[ 'requests' ][ 'resolver' ];
        $response_resolver = $this->resolvers[ 'responses' ][ 'resolver' ];

        // Resolve request fields 
        $request_results = $request_resolver->resolve_request( $request_data );

        // Report errors 
        if( $request_results[ 'error' ] ) {
            return $request_results; 
        }
        
        // Resolve response fields w/ request results
        $endpoint_results = $response_resolver->resolve_request( $request_results[ 'data' ] );
    
        $response_data = ( isset( $endpoint_results[ 'data' ] ) ) ? $endpoint_results[ 'data' ] : $endpoint_results;

        $this->api_response->on_error(
            'print_json_to_screen', 
            $this->validate_client_request_body( 'responses', $response_data )
        );
    
        // Print results (Failure or Success)
        return $endpoint_results;

    }

    Public function validate_client_request_body( $transaction_type, array $request_data ) {

        return $this->resolvers[ $transaction_type ][ 'resolver' ]->validate_request_body( $request_data );

    }

}