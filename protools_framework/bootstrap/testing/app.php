<?php 

use Symfony\Component\Filesystem\Filesystem as Filesystem; 

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_configuration; 
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\App_routes as App_routes;

use Bootstrap\Testing\Library\Classes\Test_plan as Test_plan;

use \Dump_var as Dump_var;
use PHPUnit\Util\TestDox\NamePrettifier;

$app_path = ENV_VAR[ 'app_path' ];
$request_params = ENV_VAR[ 'request' ];

if( ! IS_CLI && ENV_VAR[ 'app_path' ][ 'application' ] === 'results' ) {
    
    // Route to Test Results Application
    $filename_site_routes = ENV_VAR[ 'directories' ][ 'tmp' ][ 'site_specific' ] . 'cache/site_routes.json'; 
    if( file_exists( $filename_site_routes  ) ) {
        $app_routes_response = $Json_validator->validate( file_get_contents( $filename_site_routes ) );
    } else {

        $response = Api_response::get_response([
            'status' => 404, 
            'message' => 'Page not found', 
            'source' => 'testing_app', 
            'issue_id' => 'testing_app_001'
        ]); 

        Api_response::route_to_custom_page( $response[ 'status' ], $response, ERROR_PAGE, ENV_NAME );
    }

    if( $app_routes_response[ 'error' ] ) {
        Api_response::route_to_custom_page( $app_routes_response[ 'status' ], $app_routes_response, ERROR_PAGE, ENV_NAME );
    }

    $App_routes = new App_routes( $app_routes_response[ 'data' ], $app_path, new Api_response );
    $routes_response = $App_routes->get_view();

    if( $routes_response[ 'error' ] ) {
        Api_response::route_to_custom_page( $routes_response[ 'status' ], $routes_response, ERROR_PAGE, ENV_NAME );
    } else {
        $Environment_config->set_view_route( SITE_PORTFOLIO_DIR . 'apps/' . $routes_response[ 'data' ][ 'view_path' ] );
        require_once( SITE_PORTFOLIO_DIR . 'apps/' . $routes_response[ 'data' ][ 'view_path' ] );
    }

    exit;

} 

$Test_plan = new Test_plan( 
    $Environment_config,
    new Json_validator, 
    new Api_response, 
    new Filesystem, 
    new NamePrettifier()
);

$report = $Test_plan->get_test_report(); 

if( !IS_CLI ) {
    set_time_limit(0);

    ob_start();
    header ( 'Location: /results?execution_id=' . $report[ 'data' ][ 'summary' ][ 'execution_id' ] );
    ob_end_flush();
    @ob_flush();
    flush();
}

$Test_plan->run_php_tests();
$updated_report = $Test_plan->get_test_report(); 

if( 
    isset( $request_params[ 'data' ][ 'verbose' ] ) &&
    $request_params[ 'data' ][ 'verbose' ] === 'false'
) {
    printf( 'done' );
    exit;
}

if( IS_CLI && $updated_report[ 'error' ] ) {
    Api_response::print_stderr( $updated_report, ENV_NAME );
    exit;
}

Api_response::print_json( $updated_report[ 'status' ], $updated_report, ENV_NAME );

die();