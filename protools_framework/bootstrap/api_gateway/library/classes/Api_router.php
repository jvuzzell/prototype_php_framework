<?php 

namespace Bootstrap\Api_gateway\Library\Classes;

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_config;
use Bootstrap\Shared\Utilities\Classes\Json_validator;
use Bootstrap\Shared\Utilities\Classes\Static\Api_response;
use Symfony\Component\OptionsResolver\OptionsResolver as Options_resolver;

use ErrorException;

class Api_router {

    Private $env_config; 
    Private $api_response; 
    Private $json_validator; 
    Private $api_route_meta;  

    Public function __construct( Environment_config $environment_config, Api_response $api_response, Json_validator $json_validator ) {

        $this->env_config = $environment_config;
        $this->api_response = $api_response; 
        $this->json_validator = $json_validator;

        $cached_routes_response = $this->get_cached_routes();

        if( $cached_routes_response[ 'error' ] ) {
            throw new ErrorException( $cached_routes_response[ 'message' ] );
        } else {
            $cached_routes = $cached_routes_response[ 'data' ];
        }

        $valid_cached_routes = $api_response->on_error(
            'print_json_to_screen',
            $this->validate_routes( $cached_routes )
        );
        
        $this->api_route_meta = $api_response->on_error(
            'print_json_to_screen', 
            $this->get_api_route( $valid_cached_routes )
        );

    }

    Private function validate_routes( $routes = array() ) { 
        
        // @TODO Validate cached routes
        $response = $this->api_response->format_response( array(
                        'status' => 200, 
                        'error' => false, 
                        'message' => 'Routes found',
                        'source' => get_class( $this ), 
                        'issue_id' => 'api_router_006', 
                        'data' => $routes
                    ));

        return $response;

    }

    Public function is_request_method_supported( string $request_method, array $available_request_methods ) {
    
        if( isset( $available_request_methods[ strtolower( $request_method ) ] ) ) {
         
            $response = $this->api_response::format_response([
                'status' => 200, 
                'error' => false,
                'message' => 'Request method supported',
                'source' => get_class( $this ), 
                'issue_id' => 'api_router_005'
            ]); 
            
        } else {

            $response = $this->api_response::format_response([
                'status' => 501,
                'message' => 'Request method not supported', 
                'source' => get_class( $this ), 
                'issue_id' => 'api_router_004'
            ]); 

        }

        return $response; 

    }

    Private function get_api_route( array $cached_routes ) {

        $response = array(); 
        $route_found = false; 

        // Match URI against cache 
        foreach( $cached_routes as $serviceName => $route_data ) {

            $uri_pattern = str_replace( '/', '\\/', $route_data[ 'uri' ] );
            $uri_pattern = '/^\/' . $uri_pattern .'\/$/'; 

            if( preg_match( $uri_pattern, $this->env_config->get_env_variables()[ 'app_path' ][ 'uri' ] ) ) {
                
                $response = $this->api_response::format_response([
                    'status' => 200, 
                    'error' => false,
                    'message' => 'Endpoint found', 
                    'source' => get_class( $this ), 
                    'issue_id' => 'api_router_006', 
                    'data' => $route_data
                ]); 

                $route_found = true; 
                break;

            }
        
        }

        if( !$route_found ) {

            $response = $this->api_response::format_response([
                'status' => 404, 
                'message' => 'Endpoint not found', 
                'source' => get_class( $this ), 
                'issue_id' => 'api_router_002'
            ]);

        }

        return $response;

    }

    Private function configure_route_settings( Options_resolver $route_settings_resolver ) {

        $route_settings_resolver->setDefault( 'settings', function( Options_resolver $settings_resolver ) {

            $settings_resolver
                ->setDefaults([
                        'version' => "0.1", 
                        'experimental' => true, 
                        'deprecated' => false,
                        'title' => '', 
                        'description' => '', 
                        'reference_url' => '', 
                        'tags' => []
                    ])
                ->setAllowedTypes( 'version', 'string' )
                ->setAllowedTypes( 'experimental', 'boolean' )
                ->setAllowedTypes( 'deprecated', 'boolean' )
                ->setAllowedTypes( 'title', 'string' )
                ->setAllowedTypes( 'description', 'string' )
                ->setAllowedTypes( 'reference_url', 'string' )
                ->setAllowedTypes( 'tags', 'array' );

                $settings_resolver->setDefault( 'contact', function( Options_resolver $contact_resolver ) {
                    $contact_resolver
                        ->setDefaults([
                                'name' => '', 
                                'url' => '', 
                                'email' => ''
                            ])
                        ->setRequired([
                                'name', 
                                'email' 
                            ])
                        ->setAllowedTypes( 'name', 'string' )
                        ->setAllowedTypes( 'url', 'string' )
                        ->setAllowedTypes( 'email', 'string' );
                });

                // @TODO Define request method options
                $settings_resolver->setDefault( 'request_methods', array() );

                // @TODO Define redirect options
                $settings_resolver->setDefault( 'redirect', array() );

        });

    }

    Private function get_cached_routes() : array {

        $cached_api_filename = $this->env_config->get_env_variables()[ 'directories' ][ 'tmp' ][ 'site_specific' ] . 'cache/api_routes.json';

        if( file_exists( $cached_api_filename ) ) {
            $response = $this->json_validator->validate( file_get_contents( $cached_api_filename ) );

            if( $response[ 'error' ] ) {
            
                $response = $this->$this->api_response::format_response([
                    'status' => 500, 
                    'message' => 'Invalid cache format', 
                    'source' => get_class( $this ), 
                    'issue_id' => 'api_router_007'
                ]); 
    
            }

        } else {
        
            // @TODO If cache unavailable, create a new cache from database
            $response = $this->$this->api_response::format_response([
                'status' => 500, 
                'message' => 'Routes not found', 
                'source' => get_class( $this ), 
                'issue_id' => 'api_router_001'
            ]); 

        }

        return $response; 

    }

    Public function get_api_route_meta() {

        return $this->api_route_meta;

    }

}