<?php 

use Symfony\Component\Filesystem\Filesystem as Filesystem; 

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_configuration; 
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\App_routes as App_routes;

use Bootstrap\Testing\Library\Classes\Test_plan as Test_plan;
use PHPUnit\Util\TestDox\NamePrettifier;

$app_path = ENV_VAR[ 'app_path' ];
$request_params = ENV_VAR[ 'request' ];

if( ! IS_CLI && ENV_VAR[ 'app_path' ][ 'application' ] !== '' ) {

    require_once( '../shared/router.php' );
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
    exit;
}

if( IS_CLI && $updated_report[ 'error' ] ) {
    Api_response::print_stderr( $updated_report, ENV_NAME );
    exit;
}

Api_response::print_json( $updated_report[ 'status' ], $updated_report, ENV_NAME );

die();