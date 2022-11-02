<?php 

namespace Bootstrap\Shared\Controllers;

use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Controllers\Properties;
use Bootstrap\Shared\Controllers\Model;
use Bootstrap\Shared\Controllers\Settings;

use \Twig\Loader\FilesystemLoader as Twig_filesystem_loader; 
use \Twig\Environment as Twig; 
use \Dump_var;

class Page_controls {

    Public $model;
    Public $props;
    Public $view; 
    Public $env_config;
    Public $api_response;
    Public $json_validator; 
    Public $settings; 

    Public function __construct( $environment_config, Model $model, Properties $properties, Twig $twig, Api_response $api_response, Json_validator $json_validator, Settings $settings ) {
        $this->model = $model;
        $this->props = $properties;
        $this->env_config = $environment_config; 
        $this->view = $twig;
        $this->api_response = $api_response;
        $this->json_validator = $json_validator; 
        $this->settings = $settings;
    }

}