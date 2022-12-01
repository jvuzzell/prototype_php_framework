<?php 

session_start();

require_once( __DIR__ . '/../../../vendor/autoload.php' );
require_once( __DIR__ . '/../../autoload.php' );

/**
 * Global File Paths
 */

if ( !defined( 'LIBRARY_DIR' ) ) {
    define( 'LIBRARY_DIR', SITE_DIR . '/library' );
}

if ( !defined( 'SHARED_LIBRARY_DIR' ) ) {
    define( 'SHARED_LIBRARY_DIR', SITE_DIR . '/../shared' );
}

require_once( SHARED_LIBRARY_DIR . '/utilities/functions/universal_error_handling.php' );
require_once( SHARED_LIBRARY_DIR . '/utilities/classes/static/Dump_var.php' );

/**
 * Import classes
 */

use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\Curl_client as Curl_client; 
use Bootstrap\Shared\Utilities\Classes\Database_pdo_client as Database_pdo_client;
use Bootstrap\Shared\Utilities\Classes\Directory_search as Directory_search;
use Bootstrap\Shared\Utilities\Classes\Environment_configuration;
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;

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
 * Instantiate Global Utilities
 */

$Directory_search = new Directory_search();
$Json_validator = new Json_validator();
$Api_response = new Api_response();

/**
 * Environment Configuration
 */

$env_args = array(
    'site_directory' => SITE_DIR,
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
            'protools_api' => new Curl_client
        ), 
        'databases' => array(
            'cms_database' => new Database_pdo_client 
        ), 
        'email' => array()
    )
); 

$Environment_config = new Environment_configuration( $env_args );

if ( ! defined( 'IS_CLI' ) ) {
    define( 'IS_CLI', $Environment_config->get_is_cli() );
}

if( ! defined( 'ENV_VAR' ) ) { 
    define( 'ENV_VAR', $Environment_config->get_env_variables() );
}

if( ! defined( 'ENV_NAME' ) ) {
    define( 'ENV_NAME', ENV_VAR[ 'env_name' ] );
}

$_SERVER[ 'REQUEST_SCHEME' ] = $Environment_config->get_request_protocol();
$environment_folders = ENV_VAR[ 'directories' ]; 

if( !IS_CLI ) {

    if ( ! defined( 'REQUEST_URI' ) ) {
        define( 'REQUEST_URI', ENV_VAR[ 'app_path' ][ 'uri' ] );
    } 
    
    if ( ! defined( 'REQUEST_PROTOCOL' ) ) {
        define( 'REQUEST_PROTOCOL', ENV_VAR[ 'request' ][ 'protocol' ] );
    } 
    
    if ( ! defined( 'BASE_URL' ) ) {
        define( 'BASE_URL', REQUEST_PROTOCOL . '://' . ENV_VAR[ 'app_path' ][ 'domain' ] );
    } 

    if ( ! defined( 'SITE_URL' ) ) {
        define( 'SITE_URL', BASE_URL );
    }

    if ( ! defined( 'ERROR_PAGE' ) ) {
        define( 'ERROR_PAGE', SITE_URL .'/error/' );
    }    

    if ( !defined( 'SHARED_PORTFOLIO_DIR' ) ) {
        define( 'SHARED_PORTFOLIO_DIR', $environment_folders[ 'portfolio' ][ 'shared' ] );
    }

    if ( !defined( 'SITE_PORTFOLIO_DIR' ) ) {
        define( 'SITE_PORTFOLIO_DIR', $environment_folders[ 'portfolio' ][ 'site_specific' ] );
    }

}

/**
 * Set log file 
 */

$Api_response::set_log_file( 'site_log', ENV_VAR[ 'site_log' ] );

// $Environment_config->set_clients(array(
//     'curl' => array(
//         'atlassian_rest_api' => new Curl_client
//     )
// ));

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