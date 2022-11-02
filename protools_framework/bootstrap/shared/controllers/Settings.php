<?php 

namespace Bootstrap\Shared\Controllers;

class Settings {

    Private $settings = array();

    Public function __construct( array $settings = array() ) {
        $this->settings = $settings;
    }

    Public function __get( $key ) : mixed {
        return $this->get( $key );
    }

    Public function __set( $key, $val ) {
        $this->set( $key, $val );
    }

    Public function __isset( $key ) {
        return isset($this->settings[ $key ]);
    }

    Public function __unset( $attr ) {
        unset($this->settings[ $attr ]);
    }

    Public function get( string $key ) {
        return $this->settings[ $key ];
    }

    Public function get_all() : array {
        return $this->settings;
    }

    Public function set( string $key, mixed $value ) {
        $this->settings[ $key ] = $value;
    }

    Public function update( array $settings ) {
        $this->settings = $settings;
    }

}