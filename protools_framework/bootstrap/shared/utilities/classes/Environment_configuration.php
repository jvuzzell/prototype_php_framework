<?php 
   
namespace Bootstrap\Shared\Utilities\Classes;
use \Dump_var;
use \Exception;
use \DateTime; 
use \DateTimeZone;
use ErrorException;

/** 
 * @package Environment Configuration
 * @version 2.0
 * 
 * @author Joshua Uzzell 
 *
 * Purpose: 
 * Detects environment variables including file paths to resources, applications, and views 
 * Additionally, this class instantiates database and API clients
 *  
 * Public Methods: 
 *      @method get_env_variables
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
 *      @method parse_path_for_params
 *      @method set_curl_client_config
 *      @method set_db_client_config
 */


class Environment_configuration {

    Private $Directory_search = null; // Resource
    Private $Api_response = null; // String
    Private $encrypt; // (String) method name
    Private $decrypt; // (String) method name

    Private $key = ''; 
    Private $nonce = '';
    Private $env_config = array(
        'is_cli' => '',
        'env_name' => '',
        'site_log' => '',
        'route' => array(
            'view' => '', 
            'api' => ''
        ),
        'app_path' => array(
            'domain' => '', 
            'application' => '', 
            'resource' => '', 
            'view' => ''
        ),
        'request' => array(
            'protocol' => '', 
            'method' => '', 
            'bearer_token' => '',
            'data' => array(), 
            'header' => array(), 
            'remote_address' => '', 
            'remote_port' => '', 
            'request_time_float' => 0, 
            'request_time' => 0
        ),
        'env_domains' => array(
            'main_site' => '', 
            'static_assets' => '',
            'testing' => '', 
            'builder' => '', 
            'cms' => '', 
            'api' => ''
        ),
        'directories' => array()
    );
    Private $env_var_path = '';

    Private $expected_params = array(
        'domain',
        'application', 
        'resource', 
        'view', 
    );

    Private $program_directories = array(
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
        ), 
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
        $request_meta = $this->get_request_meta();

        $full_domain_arr = explode( '.', $domain );
        array_pop( $full_domain_arr ); // Remove TLD
        $domain_name = implode( '.', $full_domain_arr );

        // Discover path to environment variable folder.
        $env_var_path_response = $this->get_env_var_path( $domain );

        if( $env_var_path_response[ 'error' ] ) {
            throw new Exception( $env_var_path_response[ 'message' ] );
        } else {
            $this->env_var_path = $env_var_path_response[ 'data' ][ 'filepath' ];
        }
        
        // Set program root
        $this->project_root_directory = realpath( $this->env_var_path . '/../' ); 

        // Set site specific directories for Environment Variables, APIs, Assets, Testing, Themes, Portfolio
        $program_directories_response = $this->get_program_directories( $this->project_root_directory, $domain_name );
        $domain_key = str_replace( '.', '_', $domain_name ) . '/';
        
        if( $program_directories_response[ 'error' ] ) {
            throw new Exception( $program_directories_response[ 'message' ] );
        } else {
            $this->program_directories = $program_directories_response[ 'data' ][ 'program_directories' ]; 
        }

        // Get environment name
        $env_domain_filename = $this->env_var_path . '/shared/env_domains.inc'; 
        $env_name_response = $this->get_env_name( $env_domain_filename, $domain );
        
        if( $env_name_response[ 'error' ] ) {
            throw new Exception( $env_name_response[ 'message' ] );
        } else {
            $env_name = $env_name_response[ 'data' ][ 'env_name' ]; 
        }

        // Get environment domains
        $env_domain_response = $this->get_env_domains( $env_domain_filename, $env_name );

        if( $env_domain_response[ 'error' ] ) {
            throw new Exception( $env_domain_response[ 'message' ] );
        } else {
            $env_domains = $env_domain_response[ 'data' ]; 
        }

        // Generate clients
        if ( isset( $args[ 'clients' ] ) && !empty( $args[ 'clients' ] ) ) {
            $clients = $args[ 'clients' ];
            $this->set_clients( $clients );
        }

        // Start log file
        $log_directory = $this->program_directories[ 'tmp' ][ 'site_specific' ] . 'log/'; 
        $log_filename = $log_directory . $this->get_datetime_now( 'Y-m-d' ) . '_log.txt';

        if( !is_dir( $log_directory ) ) {
            mkdir( $log_directory );
        }

        if( !file_exists( $log_filename ) ) {
            $log_file = fopen( $log_filename, 'w' );
            fclose( $log_file );
        } 

        // Final - Compile environment configuration
        $this->env_config = array_merge( $this->env_config, array(
            'is_cli' => $is_cli, 
            'env_name' => $env_name,
            'site_log' => $log_filename,
            'app_path' => $app_path,
            'request' => $request_meta,  
            'env_domains' => $env_domains[ 'url' ],
            'directories' => $this->program_directories, 
        ));

    }

    /**
     * Get Request Header
     */

    Private function get_request_header() {

        // Parse header values 
        $headers = array();
        foreach ( $_SERVER as $key => $value ) {
            if ( strpos( $key, 'HTTP_' ) === 0 ) {
                $headers[ str_replace( ' ', '', ucwords( str_replace( '_', ' ', strtolower( substr( $key, 5) ) ) ) ) ] = $value;
            }
        }

        return $headers; 

    }

    /**
     * @param $clients = array(
     *      'curl' => array(
     *          'client_name' => new Curl_client
     *      ), 
     *      'databases' => array(
     *          'client_name' => new Database_pdo_client
     *      ), 
     *      'email' => array()
     * )
     * 
     * Note: The client information must be in environment variables file of this will not work
     */

    Public function set_clients( array $clients ) {

        // Retrieve data_source data object from environment variable folder
        $data_source_response = $this->get_data_sources( $this->program_directories[ 'environment_variables' ] );

        if( $data_source_response[ 'error' ] ) {
            throw new Exception( $data_source_response[ 'message' ] );
        } else {
            $data_sources = $data_source_response[ 'data' ]; 
        }
        
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

    Private function get_env_domains( string $env_domain_path, string $env_name ) {

        if( file_exists( $env_domain_path ) ) { 

            $env_domains = include( $env_domain_path );

            $response = $this->Api_response->format_response( array(
                'status'   => 200,
                'error'    => false,
                'issue_id' => 'environment_config_015', 
                'message'  => 'Environment domains found', 
                'source'   => get_class(), 
                'data'     => $env_domains[ $env_name ]
            ) );

        } else {

            $response = $this->Api_response->format_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_014', 
                'message'  => 'Environment domain file not found', 
                'source'   => get_class()
            ) );

            return $response; 

        }

        return $response;

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

    Public function get_clients() {

        return $this->clients;

    }

    Public function get_client( string $client_name ) { 

        if( isset( $this->clients[ $client_name ] ) ) {
            
            $response = $this->Api_response->format_response( array(
                'error'    => false,
                'status'   => 200,
                'issue_id' => 'environment_config_010', 
                'message'  => 'Success, client found', 
                'source'   => get_class(), 
                'data'     => array ( 'class' => $this->clients[ $client_name ] )
            ) );

        } else {

            $response = $this->Api_response->format_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_011', 
                'message'  => 'Client not found', 
                'source'   => get_class(), 
                'data' => array( 'missing_client_name' => $client_name )
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

            $response = $this->Api_response->format_response( array(
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

            $response = $this->Api_response->format_response( array(
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

            $response = $this->Api_response->format_response( array(
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

            $response = $this->Api_response->format_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_003', 
                'message'  => 'data_sources - data sources not found', 
                'source'   => get_class()
            ) );

        } else {

            $response = $this->Api_response->format_response( array(
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
        $domain_dir = str_replace( '.', '_', $domain ) . '/';
        $program_directories = $this->program_directories;

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

            $response = $this->Api_response->format_response( array(
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
                        $program_directories[ 'file_storage' ][ 'shared' ] = $directory . '/shared/'; 
                        break;

                    case 'static_assets' : 
                        $program_directories[ 'static_assets' ][ 'site_specific' ] = $directory . '/' . $domain_dir;
                        $program_directories[ 'static_assets' ][ 'shared' ] = $directory . '/shared/'; 
                        break;

                    case 'portfolio' : 
                        $program_directories[ 'portfolio' ][ 'site_specific' ] = $directory . '/' . $domain_dir;
                        $program_directories[ 'portfolio' ][ 'shared' ] = $directory . '/shared/api/'; 
                        break;

                    case 'tmp' : 
                        $program_directories[ 'tmp' ][ 'site_specific' ] = $directory . '/' . $domain_dir;
                        $program_directories[ 'tmp' ][ 'shared' ] = $directory . '/shared/'; 

                }

            }

            $program_directories[ 'site' ] = getcwd();
            $program_directories[ 'root' ] = $search_directory;
            $program_directories[ 'framework' ] = $search_directory . '/protools_framework/';
            $program_directories[ 'vendor' ] = $search_directory . '/vendor/';
            $program_directories[ 'tests' ][ 'shared' ] = $search_directory . '/protools_framework/tests/';
            $program_directories[ 'shared' ] = $search_directory . '/protools_framework/bootstrap/shared/';
            $program_directories[ 'bootstrap' ] = $search_directory . '/protools_framework/bootstrap/'; 

            $response = $this->Api_response->format_response( array(
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

            $response = $this->Api_response->format_response( array(
                'status'   => 404,
                'issue_id' => 'environment_config_001', 
                'message'  => $matching_search_response[ 'message' ] . ' - missing environment_variables', 
                'source'   => get_class()
            ) );

        } else {

            $files_found = $matching_search_response[ 'data' ][ 'environment_variables/' ]; 
            $directory_name = array_keys( $files_found )[ 0 ];
            $filepath = realpath( $files_found[ $directory_name ][ 0 ] ); 

            $response = $this->Api_response->format_response( array(
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

            $args = $this->parse_path( $_SERVER[ 'argv' ][ 1 ] );
            $domain = $args[ 'domain' ];

        } else {
            
            $domain = $_SERVER[ 'HTTP_HOST' ];

        }

        return $domain;

    }

    Private function get_app_path() {
        
        if( $this->is_cli() ) { 
            $response = $this->parse_path( $_SERVER[ 'argv' ][ 1 ] );
        } else {
            $response = $this->parse_path( $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] );
        }

        return $response;

    }

    Private function get_request_meta() : array {

        $meta = array();
        $request_meta = array(
            'method' => null, // string, Request method
            'protocol' => null, // string, HTTP or HTTPS
            'bearer_token' => null, // string, JWT
            'headers' => null, // array, HTTP headers as an array
            'remote_address' => null, // string, IP address of client
            'remote_port' => null, // string, Port of client
            'request_time_float' => null, // int, Time since start of request
            'request_time' => null, // int, Time since start of request
        );

        if( $this->is_cli() ) { 
        
            $meta = array(
                'method' => 'cli', 
                'data' => $this->parse_args( $_SERVER[ 'argv' ][ 1 ] ), 
                'protocol' => null
            );

        } else {

            $meta = array(
                'method' => $_SERVER[ 'REQUEST_METHOD' ], 
                'protocol' => $this->set_request_protocol(),
                'bearer_token' => $this->get_bearer_token(),
                'headers' => $this->get_request_header(),
                'remote_address' => $_SERVER[ 'REMOTE_ADDR' ], 
                'remote_port' => $_SERVER[ 'REMOTE_PORT' ], 
                'request_time_float' => $_SERVER[ 'REQUEST_TIME_FLOAT' ], 
                'request_time' => $_SERVER[ 'REQUEST_TIME' ]
            );

            $request_meta = array_merge( $request_meta, $meta );

        }

        if( $_SERVER[ 'REQUEST_METHOD' ] === 'GET' ) {

            $meta = array( 
                'headers' => $this->get_request_header(),
                'data' => $_GET
            );
            
        } else {
            
            // Expecting JSON in the body not form data
            $meta = array( 
                'headers' => $this->get_request_header(),
                'data' => urldecode( file_get_contents( 'php://input' ) ) 
            );  
        
        }

        $request_meta = array_merge( $request_meta, $meta );
        
        return $request_meta;

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

    Private function parse_path( string $path ) : array {

        $has_get_parameters = strpos( $path, '?' ); 
        
        if( $has_get_parameters ) {
            $get_request = substr( $path, strpos( $path, '?' ) );
            $path = substr( $path, 0, strpos( $path, '?' ) );				
        }

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

        $routes[ 'uri' ] = substr( $path, strpos( $path, '/' ) );

        return $routes;

    }

    Private function parse_args( string $path ) : array {

        $has_get_parameters = strpos( $path, '?' ); 
        $params = array();
        
        if( $has_get_parameters ) {

            $get_request = substr( $path, strpos( $path, '?' ) + 1 );
            $tmp_params = explode( '&', $get_request );
            
            for( $i = 0; $i < count( $tmp_params ); $i++ ) {
                $param = explode( '=', $tmp_params[ $i ] );

                if( isset( $param[ 1 ] ) ) {
                    $params[ $param[0] ] = $param[ 1 ];
                } else {
                    $params[ $param[0] ] = null;
                }

            }

            $path = substr( $path, 0, strpos( $path, '?' ) );	
            
        }

        return $params;

    }

    /** 
     * Get header Authorization
     */

    Private function get_authorization_header() {
        
        $headers = null;
        if( isset( $_SERVER[ 'Authorization' ] ) ) {
            $headers = trim( $_SERVER[ 'Authorization' ] );
        }
        else if( isset( $_SERVER[ 'HTTP_AUTHORIZATION' ] ) ) { //Nginx or fast CGI
            $headers = trim( $_SERVER[ 'HTTP_AUTHORIZATION' ] );
        } 
        else if( function_exists( 'apache_request_headers' ) ) {

            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

            if ( isset( $requestHeaders[ 'Authorization' ] ) ) {
                $headers = trim( $requestHeaders[ 'Authorization' ] );
            }

        }

        return $headers;

    }

    /**
     * Get access token from header
     */

    Private function get_bearer_token() {

        $headers = $this->get_authorization_header();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;

    }

    Public function get_request_protocol() {

        return $this->env_config[ 'request' ][ 'protocol' ];

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

    Public function get_env_variables() {

        return $this->env_config;

    }

    Public function get_datetime_now( string $format = "Y-m-d H:i:s" ) : string {

        $datetime_now = new DateTime( "now", new DateTimeZone( 'US/EASTERN' ) ); 
        return $datetime_now->format( $format );

    }

    Public function set_view_route( string $path ) { 
     
        $this->env_config[ 'route' ][ 'view' ] = $path;
    
    }

    Public function set_api_route( string $path ) {

        $this->env_config[ 'route' ][ 'api' ] = $path;

    }

}