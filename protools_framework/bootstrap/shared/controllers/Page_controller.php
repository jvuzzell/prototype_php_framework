<?php 

namespace Bootstrap\Shared\Controllers;

class Page_controller {

    Protected $id;
    Protected $_instance;
    Protected $_clients; 
    
    Public $template_info = array(
        'template_dir' => '', 
        'component_dir' => '', 
        'theme_dir' => '', 
        'template_file' => '', 
        'cache_dir' => '', 
        'twig_loader_paths' => array()
    );

    Public $error_page; 
    Public $app_dir; 

    Public function __construct( $instance ) {
     
        $this->_instance = $instance;
        $env_config = $instance->env_config; 
        $env_variables = $env_config->get_env_variables(); 
        $this->env_variables = $env_variables; 

        if( $env_variables[ 'route' ][ 'view' ] !== '' ) {
            $this->app_dir = dirname( $env_variables[ 'route' ][ 'view' ] );
        } else {
            throw new \ErrorException( 'View route missing from environment config.' );
        }

        $this->error_page = $env_variables[ 'request' ][ 'base_url' ] . 'error/'; 
        
        $page_settings = $this->api_response->on_error( 
            'route_to_custom_page',  
            $this->get_page_settings(),
            $this->error_page, 
            $this->env_config->get_env_variables()[ 'env_name' ] 
        );

        $this->page_settings->update( $page_settings );
        $this->id = $this->set_controller_id();

        $this->template_info = $this->get_tmpl_info();
        $this->view_template = $this->set_view_template();

        $component_registry = $this->api_response->on_error( 
            'route_to_custom_page', 
            $this->get_component_registry( $this->template_info[ 'component_registry' ] ), 
            $this->error_page, 
            $this->env_config->get_env_variables()[ 'env_name' ] 
        );

        $this->api_response->on_error( 
            'route_to_custom_page', 
            $this->register_components( $component_registry[ 'data' ] ), 
            $this->error_page, 
            $this->env_config->get_env_variables()[ 'env_name' ] 
        );

        $this->api_response->on_error( 
            'route_to_custom_page', 
            $this->render_page_model(), 
            $this->error_page, 
            $this->env_config->get_env_variables()[ 'env_name' ] 
        );

    }
    
    Public function __call( $method, $args ) {
        return call_user_func_array( array( $this->_instance, $method ), $args );
    }

    Public function __get( $key ) : mixed {
        return $this->_instance->$key;
    }

    Public function __set( $key, $val ) {
        return $this->_instance->$key = $val;
    }

    Public function get_controller_id() {
        return $this->id;
    }

    Private function register_components( array $registry ) {

        // Pull standard assets CSS & JS Plugins
        $this->component_registration->register_theme_assets( 
            $this->template_info[ 'theme_dir' ], 
            $this->env_variables[ 'directories' ][ 'static_assets' ][ 'shared' ] 
        );

        // Pull component specific css & js
        $this->component_registration->register_component_assets( 
            $this->template_info[ 'component_dir' ], 
            $registry
        );
        
        // Pull data sources from components, templates, and page settings
        $data_source_response = $this->component_registration->register_component_data_sources( 
            $this->template_info[ 'component_dir' ], 
            $registry, 
            $this->page_settings->get_all()
        );

        if( $data_source_response[ 'error' ] ) { 
            
            $response = $data_source_response;

        } else { 

            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'error' => false, 
                'source' => get_class(), 
                'issue_id' => 'page_controller_006'
            ));

        }

        return $response;

    }

    Private function get_component_registry( string $registry_filename ) { 

        $response = array();

        if( file_exists( $registry_filename ) ) {

            $registry_json_response = $this->json_validator->validate( file_get_contents( $registry_filename ) );
            $registry = $registry_json_response; 

            if( $registry_json_response[ 'error' ] ) {

                $response = $this->api_response->format_response(array(
                    'status' => 500, 
                    'source' => get_class(), 
                    'issue_id' => 'page_controller_005', 
                    'private' => true,
                    'log' => true,
                    'message' => $registry_json_response[ 'message' ]
                ));

            } else {

                $response = $this->api_response->format_response(array(
                    'status' => 200, 
                    'error' => false,
                    'source' => get_class(), 
                    'issue_id' => 'page_controller_002', 
                    'private' => true,
                    'log' => true,
                    'message' => 'Component registery found', 
                    'data' => $registry
                ));

            }

        } else { 

            $response = $this->api_response->format_response(array(
                'status' => 500, 
                'source' => get_class(), 
                'issue_id' => 'page_controller_001', 
                'private' => true,
                'log' => true,
                'message' => 'Component registery not found'
            ));

        }

        return $response;
        
    }

    Private function set_controller_id() {

        $charLength = 10; 
        $charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $arr = str_split( $charset ); // get all the characters into an array
        shuffle( $arr ); // randomize the array
        $arr = array_slice( $arr, 0, $charLength ); // get the first six (random) characters out
        $str = implode( '', $arr ); // smush them back into a string
        
        return 'page_controller_' . $str;

    }

    Public function get_page_settings() {

        $page_settings_response = $this->json_validator->validate( file_get_contents( $this->app_dir . '/page_settings.json' ) );

        if( $page_settings_response[ 'error' ] ) {

            $response = $page_settings_response;

            $response = $this->api_response->format_response(array(
                'status' => $page_settings_response[ 'status' ], 
                'issue_id' => 'page_controller_004', 
                'source' => get_class(), 
                'log' => true, 
                'private' => true,
                'message' => 'Error occurred while retrieving page settings'
            ));

        } else { 

            $response = $this->api_response->format_response(array(
                'status' => $page_settings_response[ 'status' ], 
                'error' => false,
                'issue_id' => 'page_controller_003', 
                'source' => get_class(), 
                'message' => 'Page settings found', 
                'data' => $page_settings_response[ 'data' ] 
            ));

        }

        return $response;
    }

    Public function get_tmpl_info() {

        $template_info = array();
        $template = $this->page_settings->get( 'template' ); 
        $target_template_directory = $this->page_settings->get( 'template_directory' ); 
        $theme = $this->page_settings->get( 'theme' );
        
        $template_info[ 'theme_dir' ] = $this->env_variables[ 'directories' ][ 'portfolio' ][ 'shared' ] . 'themes/' . $theme . '/'; 
        $template_info[ 'theme_templates_dir' ] = $template_info[ 'theme_dir' ] . 'templates/';
        $template_info[ 'component_dir' ] = $template_info[ 'theme_dir' ] . 'components/';
        $template_info[ 'cache_dir' ] = $this->env_variables[ 'directories' ][ 'tmp' ][ 'shared' ] . 'page_cache/';
        $template_info[ 'target_template_dir' ] = $template_info[ 'theme_templates_dir' ] .  $target_template_directory;
        $template_info[ 'component_registry' ] = $template_info[ 'target_template_dir' ]  . '/component_registry.json';
        $template_info[ 'target_template' ] = $target_template_directory . '/' . $template;

        $template_info[ 'twig_loader_paths' ] = array_merge( 
            glob( $template_info[ 'theme_dir' ] . '*/' ), 
            glob( $template_info[ 'theme_templates_dir' ] . '*/' ), 
            glob( $template_info[ 'component_dir' ] . '*/' )
        );

        return $template_info; 

    }

    Public function filter_request_data( array $client_request_data, array $expected_input_params ) {

        $response = array( 'status' => 200, 'error' => false, 'data' => null );

        // Validate expected input parameters as a schema
        $this->api_schema->set_schema( $expected_input_params, $this->api_response );
        $validation_response = $this->api_schema->validate_request_body( $client_request_data );

        if( $validation_response[ 'error' ] ) {
            return $validation_response;
        }

        foreach( $expected_input_params[ 'fields' ] as $param_key => $parameter ) { 
           
       
            if( isset( $client_request_data[ $param_key ] ) ) {
                
                $response[ 'data' ][ $param_key ] = $client_request_data[ $param_key ];

            } else if ( 
                !isset( $client_request_data[ $param_key ] ) && 
                $expected_input_params[ 'fields' ][ $param_key ][ 'field_validation' ][ 'required' ] &&
                isset( $expected_input_params[ 'fields' ][ $param_key ][ 'field_validation' ][ 'default_value' ] )
            ) {

                $response[ 'data' ][ $param_key ] = $expected_input_params[ 'fields' ][ $param_key ][ 'field_validation' ][ 'default_value' ];
                
            }

        }

        return $response;

    }

    Public function get_manifest_data() {

        $components_data_model = array();
        $response = array( 'status' => 200, 'error' => false, 'data' => null );

        $client_request_data = $this->env_config->get_env_variables()[ 'request' ][ 'data' ];

        $component_data_sources = $this->component_registration->get_data_source_manifest(); 

        if( isset( $component_data_sources[ 'api' ] ) ) {

            foreach( $component_data_sources[ 'api' ] as $source_type => $source_array ) {

                foreach( $source_array as $source_index => $source ) {
 
                    // Get client
                    $get_client_response = $this->env_config->get_client( $source[ 'client_name' ] ); 
                    $client = $get_client_response[ 'data' ][ 'class' ];

                    if( $get_client_response[ 'error' ] ) { 

                        $response = $get_client_response;
                        break;

                    }                    
                    
                    // Filter client request for inputs
                    $request_filter_response = $this->filter_request_data( $client_request_data, $source[ 'input_request_parameters' ] );

                    if( $request_filter_response[ 'error' ] ) {
                        $response = $request_filter_response; 
                        break;
                    }

                    $client_request_arguments = array(
                        'append_uri' => $source[ 'endpoint_uri' ], 
                        'request_method' => $source[ 'request_method' ], // Add request method to component options
                        'request_data' => $request_filter_response[ 'data' ]
                    );

                    // Make request
                    $client = $this->env_config->get_client( $source[ 'client_name' ] )[ 'data' ][ 'class' ]; 
                    $request_response = $client->execute( $client_request_arguments );

                    // Handle Errors
                    if( $request_response[ 'error' ] ) { 

                        $response = $request_response;

                    } else {

                        $components_data_model[ $source_type ][] = $request_response[ 'data' ];
                        
                    }

                }

                if( $response[ 'error' ] ) { 
                    break;
                }

            }   

        }

        if( !$response[ 'error' ] ) {

            $response[ 'data' ] = $components_data_model;

        }

        return $response;

    }

    Public function render_page_model() {

        $response = array( 'status' => 200, 'error' => false, 'data' => null );

        $manifest_data_response = $this->get_manifest_data( 
            $this->component_registration->get_data_source_manifest() 
        );

        if( $manifest_data_response[ 'error' ] ) { 

            $response = $manifest_data_response;

            $response[ 'system' ][ 'log' ] = true; 
            $response[ 'system' ][ 'private' ] = true; 

        } else { 

            // Environment Variables
            $this->model->set( 'environment', $this->env_variables );

            // Page Meta
            $this->model->set( 'page_meta', $this->page_settings->get( 'page_meta' ) );

            // Component Data
            $this->model->set( 'component_data', $manifest_data_response[ 'data' ] );

            // Javascript and CSS 
            $this->model->set( 'minified_css', $this->component_registration->get_minified_assets( 'css' )[ 'data' ][ 'minified' ] );
            $this->model->set( 'minified_header_js', $this->component_registration->get_minified_assets( 'js', 'header' )[ 'data' ][ 'minified' ] );
            $this->model->set( 'minified_footer_js', $this->component_registration->get_minified_assets( 'js', 'footer' )[ 'data' ][ 'minified' ] );

        }

        return $response;

    }

    Public function set_view_template() {

        $loader = $this->view->getLoader();
        $loader->setPaths( $this->template_info[ 'twig_loader_paths' ] );
        $this->view->setLoader( $loader );        
        return $this->view->load( $this->template_info[ 'target_template' ] );
        
    }

    Public function render() {

        // @todo Render with data
        return $this->view_template->render( $this->model->get_model() );

    }

    Public function display() {

        // @todo Add data to display
        echo $this->view_template->display( $this->model->get_model() );

    }

}