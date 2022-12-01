<?php  
namespace Portfolio\Testing_Prototypes\Apps\Test_Results\Resources\Page_Test_Results;

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

// $display_env_variable = ( ENV_NAME !== 'prod' ) ? "(" . strtoupper( ENV_NAME ) . ")" : '';

// Dump_var::print( $report = $Test_plan->get_test_report() ); 