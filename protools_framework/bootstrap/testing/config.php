<?php 

// @TODO Exit if accessed directly 

/**
 * @TODO 
 * 
 * 4. Include classes for testing framework 
 * 5. Include classes for bootstrap frameworks 
 */

 /**
 * Create Timestamp
 */

$now       = new DateTime( "now", new DateTimeZone( 'US/EASTERN' ) );
$timestamp = $now->format( "m-d-Y_h-i-s" );  

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
    define( 'SHARED_LIBRARY_DIR', __DIR__ . '/../shared_library' );
}

/**
 * Include Class Files
 */

foreach( glob( LIBRARY_DIR . '/classes/static/*.php' ) as $filename ) {
    require_once( $filename );
} 

foreach( glob( LIBRARY_DIR . '/interfaces/*.php' ) as $filename ) {
    require_once( $filename );
} 

foreach( glob( LIBRARY_DIR . '/classes/*.php' ) as $filename ) {
    require_once( $filename );
} 

foreach( glob( SHARED_LIBRARY_DIR . '/utilities/interfaces/*.php' ) as $filename ) {
    require_once( $filename );
} 

foreach( glob( SHARED_LIBRARY_DIR . '/utilities/classes/*.php' ) as $filename ) {
    require_once( $filename );
} 

foreach( glob( SHARED_LIBRARY_DIR . '/utilities/classes/static/*.php' ) as $filename ) {
    require_once( $filename );
}

require_once( SHARED_LIBRARY_DIR . '/utilities/functions/universal_error_handling.php' );

/**
 * Encryption classes
 */

require_once( SHARED_LIBRARY_DIR . '/vendor/sodium_compat-1.18.0/autoload.php' );

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

/**
 * Environment Configuration
 */

$env_args = array(
    'site_directory' => __DIR__,
    'encryption_key' => ENCRYPT_KEY,
    'encryption_nonce' => ENCRYPT_NONCE,
    'dependencies' => array(
        'classes' => array(
            'directory_search_class' => $Directory_search
        ), 
        'methods' => array(
            'get_response' => 'Response_helper::get_message', 
            'encryption_method' => '\Sodium\crypto_secretbox',
            'decryption_method' => '\Sodium\crypto_secretbox_open'
        )  
    ),
    'clients' => array(
        // environment_config will attemtpt to detect client config in data source 
        // file based on array key, otherwise it will create a class with the same name as the key
        'curl' => array(
            'protools_api' => new Curl_client(), 
            'atlassian_rest_api'  => new Curl_client()
        ), 
        'databases' => array(
            'cms_database' => new Database_pdo_client() 
        ), 
        'email' => array()
    )
); 

$Environment_config = new Environment_configuration( $env_args );

if( ! defined( 'ENV_NAME' ) ) {
    define( 'ENV_NAME', $Environment_config->get_env_config()[ 'env_name' ] );
}

// Validate clients
if( $Environment_config->get_client( 'protools_api' )[ 'error' ] ) { 

    $env_config_client_response = $Environment_config->get_client( 'protools_api' )[ 'error' ]; 

    if( IS_CLI ) {
        Api_response::print_json( $env_config_client_response[ 'status' ], $env_config_client_response, ENV_NAME );
    } else {
        Api_response::route_to_custom_page( $env_config_client_response[ 'status' ], $env_config_client_response, ERROR_PAGE, ENV_NAME );
    }

} 

if( $Environment_config->get_client( 'atlassian_rest_api' )[ 'error' ] ) { 

    $env_config_client_response = $Environment_config->get_client( 'atlassian_rest_api' )[ 'error' ]; 

    if( IS_CLI ) {
        Api_response::print_json( $env_config_client_response[ 'status' ], $env_config_client_response, ENV_NAME );
    } else {
        Api_response::route_to_custom_page( $env_config_client_response[ 'status' ], $env_config_client_response, ERROR_PAGE, ENV_NAME );
    }

} 

if( $Environment_config->get_client( 'cms_database' )[ 'error' ] ) { 

    $env_config_client_response = $Environment_config->get_client( 'cms_database' )[ 'error' ]; 

    if( IS_CLI ) {
        Api_response::print_json( $env_config_client_response[ 'status' ], $env_config_client_response, ENV_NAME );
    } else {
        Api_response::route_to_custom_page( $env_config_client_response[ 'status' ], $env_config_client_response, ERROR_PAGE, ENV_NAME );
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