<?php  
namespace Portfolio\Testing_Prototypes\Apps\Error_Page\Resources\Page_Display_Error;

$namespace = str_replace( ENV_VAR[ 'directories' ][ 'framework' ], '', __DIR__ );
$namespace = str_replace( '/', '\\', $namespace ); 

$page_model_classname = $namespace . '\Page_model';
$page_controls_classname = $namespace . '\Page_controls';
$page_controls_location = __DIR__ . '/Page_controls.php';

include( ENV_VAR[ 'directories' ][ 'portfolio' ][ 'shared' ] . 'library/bootstrap_page_config.php' );

// Get Error Message
$system_error_response = $Page->get_system_error_from_session( $_SESSION );

if( $system_error_response[ 'error' ] ) { 
    $Page->model->set( 'system_error', $system_error_response );
} else { 
    $Page->model->set( 'system_error', $system_error_response[ 'data' ] );
}

// Page actions 
switch( ENV_VAR[ 'request' ][ 'method' ] ) {
    case 'GET' : 
        $Page->display();
        break;
}

unset( $_SESSION[ 'last_system_error' ] );