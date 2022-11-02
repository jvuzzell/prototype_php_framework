<?php 

namespace Bootstrap\Shared\Controllers;

class Model {

    Protected $model = array();

    Public function __get( $key ) : mixed {
        return $this->get( $key );
    }

    Public function __set( $key, $val ) {
        $this->set( $key, $val);
    }

    Public function __isset( $attr ) {
        return isset($this->model[ $attr ]);
    }

    Public function __unset( $attr ) {
        unset($this->model[ $attr ]);
    }

    Public function get_model() {
        return $this->model;
    }

    Public function set_model( array $model ) {
        $this->model = $model;
    }

    Public function get( string $key ) {

        if ( array_key_exists( $key, $this->model ) ) {
            return $this->model[ $key ];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property ' . $key .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;

    }

    Public function set( string $key, mixed $value ) {
        $this->model[ $key ] = $value;
    }

}