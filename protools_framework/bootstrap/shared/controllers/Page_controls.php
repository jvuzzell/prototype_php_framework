<?php 

namespace Bootstrap\Shared\Controllers;

use Bootstrap\Api_gateway\Library\Classes\Api_schema;
use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Controllers\Properties;
use Bootstrap\Shared\Controllers\Model;
use Bootstrap\Shared\Controllers\Settings;
use Bootstrap\Shared\Utilities\Classes\Environment_configuration;

use Bootstrap\Shared\Utilities\Classes\Component_registration as Component_registration;
use MatthiasMullie\Minify\CSS as Minify_css; 
use MatthiasMullie\Minify\JS as Minify_js; 

use \Twig\Environment as Twig; 

class Page_controls {

    Public $model;
    Public $props;
    Public $view; 
    Public $env_config;
    Public $api_response;
    Public $json_validator; 
    Public $settings; 

    Public function __construct( 
        Environment_configuration $environment_config, 
        Model $model, 
        Properties $properties, 
        Twig $twig,
        Api_response $api_response, 
        Json_validator $json_validator, 
        Settings $settings, 
        Api_schema $api_schema
    ) {
        $this->model = $model;
        $this->props = $properties;
        $this->env_config = $environment_config; 
        $this->view = $twig;
        $this->api_response = $api_response;
        $this->json_validator = $json_validator; 
        $this->page_settings = $settings;
        $this->component_registration = new Component_registration( $this->env_config->get_env_variables() );
        $this->api_schema = $api_schema;
    }

}