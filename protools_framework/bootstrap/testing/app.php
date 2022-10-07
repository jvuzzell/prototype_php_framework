<?php 

use Symfony\Component\Filesystem\Filesystem as Filesystem; 
use Symfony\Component\Filesystem\Path as Path; 
use Symfony\Component\Process\Process as Process;
use Symfony\Component\Serializer\Encoder\XmlEncoder as Xml_encoder;

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_configuration; 
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\App_routes as App_routes;

use Bootstrap\Testing\Library\Classes\Test_plan as Test_plan;

use \Dump_var as Dump_var;

$env_config = $Environment_config->get_env_config(); 

$app_path = $env_config[ 'app_path' ];

if( ! IS_CLI && $env_config[ 'app_path' ][ 'application' ] === 'results' ) {
    
    // Route to Test Results Application
    $filename_site_routes = $env_config[ 'directories' ][ 'tmp' ][ 'site_specific' ] . 'cache/site_routes.json'; 
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
    } else {;
        require_once( SITE_PORTFOLIO_DIR . 'apps/' . $routes_response[ 'data' ][ 'view_path' ] );
    }

    exit;

} 

$Test_plan = new Test_plan( 
    $Environment_config,
    new Json_validator, 
    new Api_response, 
    new Filesystem
);

$report = $Test_plan->get_test_report(); 

if( !IS_CLI ) {
    set_time_limit(0);

    ob_start();
    header ( 'Location: /results?execution_id=' . $report[ 'data' ][ 'test_results' ][ 'details' ][ 'execution_id' ] );
    ob_end_flush();
    @ob_flush();
    flush();
}

$Test_plan->run_php_tests();
$updated_report = $Test_plan->get_test_report(); 

if( 
    isset( $app_path[ 'params' ][ 'verbose' ] ) &&
    $app_path[ 'params' ][ 'verbose' ] === 'false'
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