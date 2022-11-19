<?php 

namespace Bootstrap\Shared\Utilities\Classes;

use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response; 

class App_settings {

    Public function __construct( array $settings, Api_response $Api_response ) {

        $this->settings = $settings;

    }

    Public function get( string $attribute_name ) {

        return $this->settings[ $attribute_name ];

    }

    Public function get_all() : array {

        return $this->settings;

    }

    Public function set( string $attribute_name, mixed $value ) {

        $this->settings[ $attribute_name ] = $value;

    }

}