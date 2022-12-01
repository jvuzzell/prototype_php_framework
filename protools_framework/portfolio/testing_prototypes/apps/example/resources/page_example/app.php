<?php  
namespace Portfolio\Testing_Prototypes\Apps\Example\Resources\Page_example;

$namespace = str_replace( ENV_VAR[ 'directories' ][ 'framework' ], '', __DIR__ );
$namespace = str_replace( '/', '\\', $namespace ); 

$page_model_classname = $namespace . '\Page_model';
$page_controls_classname = $namespace . '\Page_controls';
$page_controls_location = __DIR__ . '/Page_controls.php';

include( ENV_VAR[ 'directories' ][ 'portfolio' ][ 'shared' ] . 'library/bootstrap_page_config.php' );

// Page actions 
switch( ENV_VAR[ 'request' ][ 'method' ] ) {
    case 'GET' : 
        $Page->display();
        break;
}