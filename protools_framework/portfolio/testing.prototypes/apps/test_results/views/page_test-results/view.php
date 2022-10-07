<?php  

use Bootstrap\Shared\Utilities\Classes\Page_controller as Page_controller; 
use Bootstrap\Shared\Utilities\Classes\Page_modeller as Page_modeller; 

if( ! defined( 'APP_DIR' )  ) {
    define( 'APP_DIR', __DIR__ . '/../../' );
};

require_once( APP_DIR . 'config.php' );

Dump_var::print( $App_settings->get_all() );

$Page_controller = new Page_controller( $Environment_config );

// Include Page Controls
require_once( 'Page_model.php' );
require_once( 'Page_controller.php' );

// // $Page_controller = new Page_controller ( 
// //     new Page_presentation( new Page_model() )
// // );



// require_once( SHARED_LIBRARY_DIR . '/utilities/functions/asset_management.php' );
// require_once( SHARED_ASSET_DIR . 'components/standard_asset_includes.php' );
// require_once( SHARED_ASSET_DIR . 'components/standard-application-container/component.php' );

// $display_env_variable = ( ENV_NAME !== 'prod' ) ? "(" . strtoupper( ENV_NAME ) . ")" : '';

// Dump_var::print( $report = $Test_plan->get_test_report() ); 