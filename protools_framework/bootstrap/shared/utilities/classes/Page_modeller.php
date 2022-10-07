<?php 

namespace Bootstrap\Shared\Utilities\Classes; 

class Page_modeller {

    function __construct( $model ) {

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