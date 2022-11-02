<?php  
namespace Portfolio\Testing_Prototypes\Apps\Test_Results\Resources\Page_Test_Results;

use \Dump_var;

$page_controls_classname = 'Portfolio\Testing_Prototypes\Apps\Test_Results\Resources\Page_Test_Results\Page_controls';
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