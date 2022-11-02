<?php 

namespace Bootstrap\Shared\Controllers;

use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Controllers\Model as Model; 
use Bootstrap\Shared\Controllers\Settings as Settings; 
use Bootstrap\Shared\Controllers\Properties as Properties;
use \Twig\Loader\FilesystemLoader as Twig_filesystem_loader; 
use \Twig\Environment as Twig_environment; 

use \Dump_var;
use ErrorException;

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

        if( $env_variables[ 'view' ][ 'route' ] !== '' ) {
            $this->app_dir = dirname( $env_variables[ 'view' ][ 'route' ] );
        } else {
            throw new ErrorException( 'View route missing from environment config.' );
        }

        $this->error_page = $env_variables[ 'directories' ][ 'site' ] . 'error_page.php';
        
        $this->get_page_settings();
        $this->id = $this->set_controller_id();

        $this->template_info = $this->get_tmpl_info();
        $this->view_template = $this->set_view_template();
        // Get component registry
   
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

    Private function set_controller_id() {

        $charLength = 10; 
        $charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $arr = str_split( $charset ); // get all the characters into an array
        shuffle( $arr ); // randomize the array
        $arr = array_slice( $arr, 0, $charLength ); // get the first six (random) characters out
        $str = implode( '', $arr ); // smush them back into a string
        
        return $str;

    }

    Public function get_page_settings() {

        $page_settings_response = $this->json_validator->validate( file_get_contents( $this->app_dir . '/page_settings.json' ) );
      
        if( $page_settings_response[ 'error' ] ) {
            $this->api_response::route_to_custom_page( 
                $page_settings_response[ 'status' ], 
                $page_settings_response, 
                $this->error_page, 
                $this->env_config->get_env_variables()[ 'env_name' ] 
            );
        }

        $this->settings->update( $page_settings_response[ 'data' ] );

    }

    Public function get_tmpl_info() {

        $template_info = array();
        $template = $this->settings->get( 'template' ); 
        $theme = $this->settings->get( 'theme' );
        
        $template_info[ 'theme_dir' ] = $this->env_variables[ 'directories' ][ 'portfolio' ][ 'shared' ] . 'themes/' . $theme . '/'; 
        $template_info[ 'template_dir' ] = $template_info[ 'theme_dir' ] . 'templates/';
        $template_info[ 'component_dir' ] = $template_info[ 'theme_dir' ] . 'components/';
        
        $template_info[ 'twig_loader_paths' ] = array_merge( 
            array(
                $template_info[ 'theme_dir' ] . 'templates/',
                $template_info[ 'theme_dir' ] . 'components/',
            ),
            glob( $template_info[ 'theme_dir' ] . '*/' ), 
            glob( $template_info[ 'template_dir' ] . '*/' ), 
            glob( $template_info[ 'component_dir' ] . '*/' )
        );
        
        $template_info[ 'target_template' ] = $template;
        $template_info[ 'cache_dir' ] = $this->env_variables[ 'directories' ][ 'tmp' ][ 'shared' ] . 'page_cache/';
        
        return $template_info; 

    }

    Public function set_clients( array $client_data_source_meta ) {
        // Clients derived from Template Component Registry 
    }

    Public function build_page_model() {

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