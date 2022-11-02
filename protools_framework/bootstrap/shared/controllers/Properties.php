<?php 

namespace Bootstrap\Shared\Controllers;

class Properties {

    Public $props = array();

    Public function __get( $key ) : mixed {
        return $this->get( $key );
    }

    Public function __set( $key, $val ) {
        $this->set( $key, $val );
    }

    Public function __isset( $key ) {
        return isset($this->props[ $key ]);
    }

    Public function __unset( $key ) {
        unset($this->props[ $key ]);
    }

    Public function get_props() {
        return $this->props;
    }

    Public function set_props( array $props ) {
        $this->props = $props;
    }

    Public function get( string $key ) {
        if ( array_key_exists( $key, $this->props ) ) {
            return $this->props[ $key ];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property .. ' . $key .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    Public function set( string $key, mixed $value ) {
        $this->props[ $key ] = $value;
    }

    
}