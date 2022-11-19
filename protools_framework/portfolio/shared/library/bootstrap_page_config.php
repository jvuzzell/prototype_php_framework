<?php 

require( $page_controls_location );

use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Controllers\Page_controller as Page_controller; 
use Bootstrap\Shared\Controllers\Model as Model; 
use Bootstrap\Shared\Controllers\Settings as Settings; 
use Bootstrap\Shared\Controllers\Properties as Properties;

use \Twig\Loader\FilesystemLoader as Twig_filesystem_loader; 
use \Twig\Environment as Twig_environment; 

if( ! defined( 'APP_DIR' )  ) {
    define( 'APP_DIR', __DIR__ );
};

$page_cache_dir = ENV_VAR[ 'directories' ][ 'tmp' ][ 'shared' ] . 'page_cache/'; 

$twig_options = array(
    'strict_variables' => false,
    'debug' => false,
    'cache'=> ( ENV_NAME == 'prod' ) ? $page_cache_dir : false
);

$Page = new Page_controller ( 
    new $page_controls_classname(
        $Environment_config,
        new Model,
        new Properties, 
        new Twig_environment( new Twig_filesystem_loader(), $twig_options ), 
        new Api_response, 
        new Json_validator, 
        new Settings
    )
);