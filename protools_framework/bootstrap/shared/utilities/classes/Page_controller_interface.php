<?php 

namespace Bootstrap\Shared\Utilities\Classes; 

interface Page_controller_interface {
        
    public function return_controller_id();

    public function set_model_attr( string $attr, mixed $value );

    public function set_model( array $model );

    public function get_model();

    public function get_model_attr( string $attr );

}