<?php 

namespace Bootstrap\Api_gateway\Library\Classes;

use Bootstrap\Shared\Utilities\Classes\Environment_configuration;
use Bootstrap\Shared\Utilities\Classes\Static\Api_response;
use Bootstrap\Api_gateway\Library\Classes\Jwt_management as Jwt_mgmt; 
use \Dump_var;
use ErrorException;

class User_authorization {

    Protected $env_config; 

    Public function __construct( Environment_configuration $environment_configuration, Api_response $api_response ) {

        $this->env_config = $environment_configuration; 
        $this->env_var = $this->env_config->get_env_variables(); 
        $this->api_response = $api_response; 

    }

    Public function verify_user( $permission_type = '' ) { 
        
        $response = array();

        switch( $permission_type ) {
            case 'localhost':
        
                if( ENV_VAR[ 'request' ][ 'remote_address' ] !== '127.0.0.1' ) {
        
                    $response = $this->api_response->format_response( array(
                        'status' => 403, 
                        'message' => 'Unauthorized', 
                        'source' => get_class( $this ), 
                        'issue_id' => 'User_authorization_001', 
                        'log' => true
                    ));

                } else { 

                    $response = $this->api_response->format_response( array(
                        'status' => 200, 
                        'error' => false,
                        'message' => 'Localhost authorized', 
                        'source' => get_class( $this ), 
                        'issue_id' => 'User_authorization_003', 
                        'log' => true
                    ));

                }

                break;

            case 'basic' : 

                $bearer_token = $this->env_var[ 'request' ][ 'bearer_token' ];

                $Jwt_mgmt = new Jwt_mgmt( $this->env_var, $this->api_response );
                $response = $Jwt_mgmt->get_claims( $bearer_token );
                
                break;

            default : 

                $response = $this->api_response->format_response( array(
                    'status' => 403, 
                    'message' => 'Unauthorized', 
                    'source' => get_class( $this ), 
                    'issue_id' => 'User_authorization_002', 
                    'log' => true
                ));

                break;

        }

        return $response; 

    }

}