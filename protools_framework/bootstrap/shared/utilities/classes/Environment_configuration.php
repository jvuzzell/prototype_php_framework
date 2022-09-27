<?php 
   
namespace Bootstrap\Shared\Utilities\Classes;
use \Dump_var;
use \Exception;
use \DateTime; 
use \DateTimeZone;

/** 
 * @package Environment Configuration
 * @version 2.0
 * 
 * @author Joshua Uzzell 
 *
 * Purpose: 
 * Detects environment variables including file paths to modules, applications, and views 
 * Additionally, this class instantiates database and API clients
 *  
 * Public Methods: 
 *      @method get_env_config
 *      @method get_request_protocols
 *      @method get_project_root_directory
 *      @method get_site_directory
 *      @method get_is_cli
 *      @method get_client
 *
 * 
 * Private Methods 
 *      @method get_env_name     
 *      @method get_data_sources
 *      @method get_program_directories
 *      @method get_env_var_path
 *      @method set_request_protocol
 *      @method set_domain
 *      @method get_app_path
 *      @method is_cli
 *      @method get_cli_arg_context
 *      @method parse_path_for_params
 *      @method set_curl_client_config
 *      @method set_db_client_config
 */


class Environment_configuration {

    Private $directory_search = null; // Resource
    Private $get_response = null; // String
    Private $Curl_client = null; // Resource
    Private $Pdo_client = null; // Resource

    Private $key = ''; 
    Private $nonce = '';
    Private $env_config = array();
    Private $env_var_path = '';

    Private $expected_params = array(
        'domain',
        'module', 
        'application', 
        'view'
    );

    Private $program_directories_structure = array(
        'portfolio' => array(
            'site_specific' => null, 
            'shared' => null
        ),
        'environment_variables' => array(
            'site_specific' => null, 
            'developer_specific' => null,
            'shared' => null
        ),
        'file_storage' => array(
            'site_specific' => null, 
            'shared' => null
        ), 
        'tests' => array(
            'shared' => null
        ), 
        'static_assets' => array(
            'site_specific' => null, 
            'shared' => null
        )
    );

    Public function __construct( array $args ) {

        $this->site_directory = $args[ 'site_directory' ]; // string
        $this->key = $args[ 'encryption_key' ]; // byte
        $this->nonce = $args[ 'encryption_nonce' ]; // byte

        $this->Api_response = $args[ 'dependencies' ][ 'classes' ][ 'Api_response' ];
        $this->Directory_search = $args[ 'dependencies' ][ 'classes' ][ 'Directory_search' ]; 
        $this->encrypt = $args[ 'dependencies' ][ 'methods' ][ 'encryption_method' ]; 
        $this->decrypt = $args[ 'dependencies' ][ 'methods' ][ 'decryption_method' ];

        $is_cli = $this->is_cli();    
        $domain = $this->set_domain(); 
        $app_path = $this->get_app_path();
        $request_protocol = ( $is_cli ) ? null : $this->set_request_protocol();
        
        $full_domain_arr = explode( '.', $domain );
        array_pop( $full_domain_arr ); // Remove TLD
        $domain_name = implode( '.', $full_domain_arr );

        // Step 1. Discover path to environment variable folder.
        $env_var_path_response = $this->get_env_var_path( $domain );

        if( $env_var_path_response[ 'error' ] ) {
            throw new Exception( $env_var_path_response[ 'message' ] );
        } else {
            $this->get_env_var_path = $env_var_path_response[ 'data' ][ 'filepath' ];
        }
        
        // Step 2. Set program root
        $project_root_directory = realpath( $this->get_env_var_path . '/../' ); 

        // Step 3. Set site specific directories for Environment Variables, APIs, Assets, Testing, Themes, Portfolio
        $program_directories_response = $this->get_program_directories( $project_root_directory, $domain_name );

        if( $program_directories_response[ 'error' ] ) {
            throw new Exception( $program_directories_response[ 'message' ] );
        } else {
            $program_directories = $program_directories_response[ 'data' ][ 'program_directories' ]; 
        }

        // Step 4. Get environment name
        $env_name_response = $this->get_env_name( $this->get_env_var_path . '/shared/env_domains.inc', $domain );
      
        if( $env_name_response[ 'error' ] ) {
            throw new Exception( $env_name_response[ 'message' ] );
        } else {
            $env_name = $env_name_response[ 'data' ][ 'env_name' ]; 
        }

        // Step 5. Retrieve data_source data object from environment variable folder
        $data_source_response = $this->get_data_sources( $program_directories[ 'environment_variables' ] );

        if( $data_source_response[ 'error' ] ) {
            throw new Exception( $data_source_response[ 'message' ] );
        } else {
            $data_sources = $data_source_response[ 'data' ]; 
        }
        
        // Step 6. Generate clients
        if ( isset( $args[ 'clients' ] ) && !empty( $args[ 'clients' ] ) ) {

            $clients = $args[ 'clients' ];

            // Instantiate Curl Clients
            if ( isset( $clients[ 'curl' ] ) && !empty( $clients[ 'curl' ] ) ) {

                $curl_clients = $clients[ 'curl' ]; 

                foreach( $curl_clients as $client_key => $curl_client_class ) {

                    // Validation
                    if( !gettype( $curl_client_class ) === 'object' ) {
                        throw new Exception( 'Curl Clients - unable to initialize client; cURL client object missing' );
                    } 

                    // Crossmatch
                    if( isset( $data_sources[ 'curl' ][ $client_key ] ) ) {
                        
                        // Set client config
                        $data_config = $data_sources[ 'curl' ][ $client_key ]; 
                        $this->set_curl_client_config( $client_key, $data_config, $curl_client_class );

                    }

                }

            }

            // Instantiate Database Clients
            if ( isset( $clients[ 'databases' ] ) && !empty( $clients[ 'databases' ] ) ) {

                $database_clients = $clients[ 'databases' ]; 

                foreach( $database_clients as $client_key => $db_client_class ) {

                    // Validation
                    if( !gettype( $db_client_class ) === 'object' ) {
                        throw new Exception( 'Database Clients - unable to initialize client; database client object missing' );
                    } 
                    
                    // Crossmatch
                    if( isset( $data_sources[ 'databases' ][ $client_key ] ) ) {

                        // Set client config
                        $data_config = $data_sources[ 'databases' ][ $client_key ]; 
                        $this->set_db_client_config( $client_key, $data_config, $db_client_class );

                    }

                }

            }

        }

        // Final - Compile environment configuration
        $this->env_config = array(
            'is_cli' => $is_cli, 
            'env_name' => $env_name,
            'request_protocol' => $request_protocol, 
            'domain' => $domain, 
            'app_path' => $app_path,
            'directories' => $program_directories
        );

    }

    Private function set_curl_client_config( string $client_key, array $config, object $curl_class ) {
        
        $client_config = array(
            'encryption' => array(
                'nonce'             => $this->nonce, 
                'key'               => $this->key, 
                'encryption_method' => $this->encrypt, 
                'decryption_method' => $this->decrypt
            ),
            'request_url'        => ( isset( $config[ 'request_url' ] ) ) ? $config[ 'request_url' ] : null,
            'bearer_token'       => ( isset( $config[ 'bearer_token' ] ) ) ? $config[ 'bearer_token' ] : null,
            'username'           => ( isset( $config[ 'username' ] ) ) ? $config[ 'username' ] : null,
            'password'           => ( isset( $config[ 'password' ] ) ) ? $config[ 'password' ] : null,
            'authorization_type' => ( isset( $config[ 'authorization_type' ] ) ) ? $config[ 'authorization_type' ] : null
        ); 

        $curl_class->set_client_args( $client_config );

        // Store class once configured
        $this->clients[ $client_key ] = $curl_class;

    }

    Private function set_db_client_config( string $client_key, array $config, object $db_class ) {

        $client_config = array(
            'encryption' => array(
                'nonce'             => $this->nonce, 
                'key'               => $this->key, 
                'encryption_method' => $this->encrypt, 
                'decryption_method' => $this->decrypt
            ),
            'dsn'        => ( isset( $config[ 'dsn' ] ) ) ? $config[ 'dsn' ] : null,
            'host'       => ( isset( $config[ 'host' ] ) ) ? $config[ 'host' ] : null, 
            'username'   => ( isset( $config[ 'username' ] ) ) ? $config[ 'username' ] : null,
            'password'   => ( isset( $config[ 'password' ] ) ) ? $config[ 'password' ] : null,
            'port'       => ( isset( $config[ 'port' ] ) ) ? $config[ 'port' ] : null,
            'database'   => ( isset( $config[ 'database' ] ) ) ? $config[ 'database' ] : null, 
            'charset'    => ( isset( $config[ 'charset' ] ) ) ? $config[ 'charset' ] : null, 
            'pdo_driver' => ( isset( $config[ 'pdo_driver' ] ) ) ? $config[ 'pdo_driver' ] : null,
        ); 

        $db_class->set_client_args( $client_config );

        // Store class once configured
        $this->clients[ $client_key ] = $db_class;

    }

    Public function get_client( string $client_name ) { 

        if( isset( $this->clients[ $client_name ] ) ) {
            
            $response = $this->Api_response->get_response( array(
                'error'    => false,
                'status'   => 200,
                'issue_id' => 'environment_config_010', 
                'message'  => 'Success, client found', 
                'source'   => get_class(), 
                'data'     => array ( 'class' => $this->clients[ $client_name ] )
            ) );

        } else {

            $response = $this->Api_response->get_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_011', 
                'message'  => 'Client not found', 
                'source'   => get_class()
            ) );

        }

        return $response;

    }
    
    Private function get_env_name( string $env_domain_path, string $domain ) {

        $match = false; 
        $env_name = null;

        if( file_exists( $env_domain_path ) ) { 

            $env_domains = include( $env_domain_path );

        } else {

            $response = $this->Api_response->get_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_007', 
                'message'  => 'Environment domain file not found', 
                'source'   => get_class()
            ) );

            return $response; 

        }

        foreach( $env_domains as $env => $domains ) {

            foreach( $domains[ 'url' ] as $url ) {

                if( $url === $domain ) {
                    $env_name = $env; 
                    $match = true;
                    break;
                }

            }
            
        }

        if( $match ) {

            $response = $this->Api_response->get_response( array(
                'error'    => false, 
                'status'   => 200,
                'issue_id' => 'environment_config_006', 
                'message'  => 'Success, environment name found', 
                'source'   => get_class(), 
                'data'     => array(
                    'env_name' => $env_name
                )
            ) );

        } else {

            $response = $this->Api_response->get_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_005', 
                'message'  => 'Environment name not found', 
                'source'   => get_class()
            ) );

        }

        return $response;

    }

    Private function get_data_sources( array $environment_variables_directory, string $env = 'dev' ) {

        $data_sources = null; 
        $combined_data_sources = null;
        
        // Merge available data_sources, giving priority to developer_specific over site_specific over shared
        if( file_exists( $environment_variables_directory[ 'shared' ] . 'data_sources.inc' ) ) { 
            $data_sources = include( $environment_variables_directory[ 'shared' ] . 'data_sources.inc' ); 
            $combined_data_sources = $data_sources[ $env ];
        }
        
        if( file_exists( $environment_variables_directory[ 'site_specific' ] . 'data_sources.inc' ) ) { 
            $data_sources = include( $environment_variables_directory[ 'site_specific' ] . 'data_sources.inc' ); 
            $data_sources = $data_sources[ $env ];
            $combined_data_sources = array_merge( $combined_data_sources, $data_sources );
        }

        if( file_exists( $environment_variables_directory[ 'developer_specific' ] . 'data_sources.inc' ) ) { 
            $data_sources = include( $environment_variables_directory[ 'developer_specific' ] . 'data_sources.inc' ); 
            $data_sources = $data_sources[ $env ];
            $combined_data_sources = array_merge( $combined_data_sources, $data_sources );
        }

        if( $combined_data_sources === null ) {

            $response = $this->Api_response->get_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_003', 
                'message'  => 'data_sources - data sources not found', 
                'source'   => get_class()
            ) );

        } else {

            $response = $this->Api_response->get_response( array(
                'error'    => false,
                'status'   => 200,
                'issue_id' => 'environment_config_004', 
                'message'  => 'data_sources - data sources found', 
                'source'   => get_class(), 
                'data'     => $combined_data_sources
            ) );
            
        }

        return $response;

    }

    Private function get_program_directories( $search_directory, $domain ) : array {

        $response = array();
        $domain_dir = $domain . '/';
        $program_directories = $this->program_directories_structure;

        $matching_search_response = $this->Directory_search->search( 
            array( $domain_dir ), 
            array( 
                'search_direction' => 'children',
                'starting_directory' => $search_directory
            )
        );

        if( 
            $matching_search_response[ 'error' ] === true || 
            $matching_search_response[ 'status' ] === 404
        ) {

            $response = $this->Api_response->get_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_008', 
                'message'  => $matching_search_response[ 'message' ] . ' - missing site specific directories', 
                'source'   => get_class()
            ) );

        } else {
        
            $matching_domain_directories = $matching_search_response[ 'data' ][ $domain_dir ]; 

            foreach( $matching_domain_directories as $directory => $relative_path ) {

                $slash = "/";

                if( strpos( $directory, $slash ) === false ) {
                    $slash = "\\";
                }

                $path = explode( $slash, $directory );
                $app = $path[ count( $path ) - 1 ]; 

                switch( $app ) {

                    case 'environment_variables' : 
                        $program_directories[ 'environment_variables' ][ 'site_specific' ] = $directory . '/' . $domain_dir;
                        $program_directories[ 'environment_variables' ][ 'shared' ] = $directory . '/shared/'; 
                        $program_directories[ 'environment_variables' ][ 'developer_specific' ] = $directory . '/developer_specific/';
                        break;
                        
                    case 'file_storage' : 
                        $program_directories[ 'file_storage' ][ 'site_specific' ] = $directory . '/' . $domain_dir;
                        $program_directories[ 'file_storage' ][ 'shared' ] = $directory . '/'; 
                        break;

                    case 'static_assets' : 
                        $program_directories[ 'static_assets' ][ 'site_specific' ] = $directory . '/' . $domain_dir;
                        $program_directories[ 'static_assets' ][ 'shared' ] = $directory . '/'; 
                        break;

                    case 'portfolio' : 
                        $program_directories[ 'portfolio' ][ 'site_specific' ] = $directory . '/' . $domain_dir;
                        $program_directories[ 'portfolio' ][ 'shared' ] = $directory . '/'; 
                        break;

                }

            }

            $program_directories[ 'tests' ][ 'shared' ] = $search_directory . '/protools_framework/tests/';
            $program_directories[ 'shared' ] = $search_directory . '/protools_framework/bootstrap/shared/';

            $response = $this->Api_response->get_response( array(
                'error'    => false,
                'status'   => 200,
                'issue_id' => 'environment_config_009', 
                'message'  => 'Success, file found.', 
                'source'   => get_class(), 
                'data'     => array(
                    'program_directories' => $program_directories
                )
            ) );

        }

        return $response;

    }

    Private function get_env_var_path( $domain ) : array {

        $response = array();

        $matching_search_response = $this->Directory_search->search( 
            array( 'environment_variables/' ), 
            array( 'search_direction' => 'parent' ) 
        );
        
        if( 
            $matching_search_response[ 'error' ] === true || 
            $matching_search_response[ 'status' ] === 404
        ) {

            $response = $this->Api_response->get_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_001', 
                'message'  => $matching_search_response[ 'message' ] . ' - missing environment_variables', 
                'source'   => get_class()
            ) );

        } else {

            $files_found = $matching_search_response[ 'data' ][ 'environment_variables/' ]; 
            $directory_name = array_keys( $files_found )[ 0 ];
            $filepath = realpath( $files_found[ $directory_name ][ 0 ] ); 

            $response = $this->Api_response->get_response( array(
                'error'    => false,
                'status'   => 200,
                'issue_id' => 'environment_config_002', 
                'message'  => 'Success, file found.', 
                'source'   => get_class(), 
                'data'     => array(
                    'filepath' => $filepath
                )
            ) );

        }

        return $response;

    }

    Private function set_request_protocol() {

        // Note: $_SERVER[ 'REQUEST_SCHEME' ] is not available on IIS server
        if (    
            (! empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
            (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ) ||
            (! empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') 
        ) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }

        return $protocol; 

    }

    Private function set_domain() {

        if( $this->is_cli() ) { 

            $args = $this->get_cli_arg_context( $_SERVER[ 'argv' ] );
            $domain = $args[ 'domain' ];

        } else {
            
            $domain = $_SERVER[ 'HTTP_HOST' ];

        }

        return $domain;

    }

    Private function get_app_path() {
        
        if( $this->is_cli() ) { 

            $response = $this->get_cli_arg_context( $_SERVER[ 'argv' ] );

        } else {
            
            $response = $this->parse_path_for_params( $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] );

        }

        return $response;

    }

    Private function is_cli() : bool {

        $is_cli = false;
    
        switch( true ) {
            case defined('STDIN') : 
                $is_cli = true;
                break; 
    
            case ( php_sapi_name() === 'cli' ) :
                $is_cli = true;
                break; 
    
            case array_key_exists( 'SHELL', $_ENV ) :
                $is_cli = true;
                break;  
    
            case ( empty( $_SERVER['REMOTE_ADDR'] ) && !isset( $_SERVER['HTTP_USER_AGENT'] ) && count( $_SERVER['argv'] ) > 0 ) :
                $is_cli = true;
                break; 
    
            case !array_key_exists( 'REQUEST_METHOD', $_SERVER ) :
                $is_cli = true;
                break;        
        }
    
        return $is_cli; 
    
    }

    Private function get_cli_arg_context( array $server_argv ) : array {

        $args = array();

        $params = $this->parse_path_for_params( $server_argv[1] );

        $args = array_merge( $args, $params );

        return $args;

    }

    Private function parse_path_for_params( string $path ) : array {

        $has_get_parameters = strpos( $path, '?' ); 
        
        if( $has_get_parameters ) {
            $get_request = substr( $path, strpos( $path, '?' ) );
            $path = substr( $path, 0, strpos( $path, '?' ) );				
        }
        
        $tmp_routes = array(); 
        $tmp_routes = explode( '/', trim( $path, '/' ) ); 

        $expected_params = $this->expected_params;

        for( $i = 0; $i < count( $expected_params ); $i++) {
            
            $tmp_route = 'all'; 

            if( isset( $tmp_routes[ $i ] ) && $tmp_routes[ $i ] !== '' ) {
                $tmp_route = $tmp_routes[ $i ];                    
            } else {
                $tmp_route = 'default';
            }
            
            $routes[ $expected_params[ $i ] ] = $tmp_route; 

        }
        
        return $routes;

    }

    Public function get_request_protocol() {

        return $this->env_config[ 'request_protocol' ];

    }

    Public function get_is_cli() {

        return $this->env_config[ 'is_cli' ];

    }

    Public function get_project_root_directory() {

        return $this->project_root_directory;

    }

    Public function get_site_directory() {

        return $this->site_directory; 

    }

    Public function get_env_config() {

        return $this->env_config;

    }

    Public function get_datetime_now( string $format = "Y-m-d H:i:s" ) : string {

        $datetime_now = new DateTime( "now", new DateTimeZone( 'US/EASTERN' ) ); 
        return $datetime_now->format( $format );

    }

}