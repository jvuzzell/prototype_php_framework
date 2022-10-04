<?php 

use Symfony\Component\Filesystem\Filesystem as Filesystem; 
use Symfony\Component\Filesystem\Path as Path; 
use Symfony\Component\Process\Process as Process;
use Symfony\Component\Serializer\Encoder\XmlEncoder as Xml_encoder;

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_configuration; 
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;

use Bootstrap\Testing\Library\Classes\Test_plan as Test_plan;
use \Dump_var as Dump_var;

$Test_plan = new Test_plan( 
    $Environment_config,
    new Json_validator, 
    new Api_response, 
    new Filesystem
);

$Test_plan->run_php_tests();
$report = $Test_plan->get_test_report(); 

if( IS_CLI && $report[ 'data' ][ 'test_results' ][ 'build_passed' ] === false ) {
    
    Api_response::print_stderr( 500, $report, 'prod' );
    exit;
}

if( IS_CLI ) {
    Api_response::print_json( $report[ 'status' ], $report, 'prod' );
} else {
    Api_response::route_to_custom_page( $report[ 'status' ], $report, 'test_report_page.php', 'prod' ); 
}
