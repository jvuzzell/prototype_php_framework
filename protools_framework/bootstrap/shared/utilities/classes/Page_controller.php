<?php 

namespace Bootstrap\Shared\Utilities\Classes; 

class Page_controller implements Page_controller_interface {

    protected $_instance;

    public function __construct( $instance ) {
        $this->_instance = $instance;
    }

    public function __call( $method, $args ) {
        return call_user_func_array( array( $this->_instance, $method ), $args );
    }

    public function __get( $key ) : mixed {
        return $this->_instance->$key;
    }

    public function __set( $key, $val ) {
        return $this->_instance->$key = $val;
    }

}