<?php 

// @TODO Exit if accessed directly 

/**
 * Global File Paths
 */

if ( ! defined( 'SITE_DIR' ) ) {
    define( 'SITE_DIR', __DIR__ );
}

if ( !defined( 'LIBRARY_DIR' ) ) {
    define( 'LIBRARY_DIR', __DIR__ . '/library' );
}

if ( !defined( 'SHARED_LIBRARY_DIR' ) ) {
    define( 'SHARED_LIBRARY_DIR', __DIR__ . '/../shared' );
}

require_once( __DIR__ . '/../../../vendor/autoload.php' );
require_once( __DIR__ . '/../../autoload.php' );

/**
 * Encryption classes
 */

if( ! defined( 'ENCRYPT_KEY' ) ) {
    define( 'ENCRYPT_KEY', \Sodium\randombytes_buf( \Sodium\CRYPTO_SECRETBOX_KEYBYTES ) );
}

if( ! defined( 'ENCRYPT_NONCE' ) ) {
    define( 'ENCRYPT_NONCE', \Sodium\randombytes_buf( \Sodium\CRYPTO_SECRETBOX_KEYBYTES ) );
}

/**
 * Import classes
 */

use Bootstrap\Shared\Utilities\Classes\Static\Response_helper as Response_helper;
use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\Curl_client as Curl_client; 
use Bootstrap\Shared\Utilities\Classes\Database_pdo_client as Database_pdo_client;
use Bootstrap\Shared\Utilities\Classes\Directory_search as Directory_search;
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Sodium\crypto_secretbox;
use Sodium\crypto_secretbox_open;

require_once( SHARED_LIBRARY_DIR . '/utilities/functions/universal_error_handling.php' );
require_once( SHARED_LIBRARY_DIR . '/utilities/classes/static/Dump_var.php' );

/**
 * Instantiate Global Utilities
 */

$Directory_search = new Directory_search();
$Json_validator = new Json_validator();
$Api_response = new Api_response();

/**
 * Environment Configuration
 */

$env_args = array(

    'site_directory' => __DIR__,
    'encryption_key' => ENCRYPT_KEY,
    'encryption_nonce' => ENCRYPT_NONCE,
    'dependencies' => array(
        'classes' => array(
            'Directory_search' => $Directory_search, 
            'Api_response' => $Api_response
        ), 
        'methods' => array(
            'encryption_method' => '\Sodium\crypto_secretbox',
            'decryption_method' => '\Sodium\crypto_secretbox_open'
        )  
    ),
    'clients' => array(
        // environment_config will attemtpt to detect client config in data source 
        // file based on array key, otherwise it will create a class with the same name as the key
        'curl' => array(
            'protools_api' => new Curl_client, 
            'atlassian_rest_api'  => new Curl_client
        ), 
        'databases' => array(
            'cms_database' => new Database_pdo_client 
        ), 
        'email' => array()
    )
); 

$Environment_config = new Bootstrap\Shared\Utilities\Classes\Environment_configuration( $env_args );

if( ! defined( 'ENV_NAME' ) ) {
    define( 'ENV_NAME', $Environment_config->get_env_config()[ 'env_name' ] );
}

/**
 * Validate clients
 */

check_client_error( $Environment_config, 'protools_api' );
check_client_error( $Environment_config, 'atlassian_rest_api' );
check_client_error( $Environment_config, 'cms_database' );

function check_client_error( $Environment_config, $client_name ) {

    if( $Environment_config->get_client( $client_name )[ 'error' ] ) { 

        $env_config_client_response = $Environment_config->get_client( $client_name ); 
    
        if( IS_CLI ) {
            $Api_response::print_json( $env_config_client_response[ 'status' ], $env_config_client_response, ENV_NAME );
        } else {
            $Api_response::route_to_custom_page( $env_config_client_response[ 'status' ], $env_config_client_response, ERROR_PAGE, ENV_NAME );
        }
    
    } 

}

$_SERVER[ 'REQUEST_SCHEME' ] = $Environment_config->get_request_protocol();

/**
 * Configure Site Paths and URLs
 */

if ( ! defined( 'IS_CLI' ) ) {
    define( 'IS_CLI', $Environment_config->get_is_cli() );
}

if( !IS_CLI ) {

    if ( ! defined( 'SITE_URL' ) ) {
        define( 'SITE_URL', 'http://' . $_SERVER[ 'HTTP_HOST' ] );
    }

    if ( ! defined( 'ERROR_PAGE' ) ) {
        define( 'ERROR_PAGE', SITE_DIR .'/error_page.php' );
    }    

}

// $Atlassian_rest_api = $Environment_config->get_client( 'atlassian_rest_api' )[ 'data' ][ 'class' ]; 
// $atlassian_response = $Atlassian_rest_api->execute(
//     array( 
//         'append_uri'       => 'rest/agile/1.0/board/4/issue', // Board ID 9 = CT, DEV board ID = 1
//         'request_method'   => 'GET', 
//         'request_data'     => array(
//             'maxResults' => 500
//         ), 
//         'authorization_type' => 'basic', 
//         'additional_headers' => array('X-ExperimentalApi:true')
//     )
// ); 

// Dump_var::print( $atlassian_response );