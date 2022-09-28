<?php 

use Symfony\Component\Filesystem\Filesystem as Filesystem; 
use Symfony\Component\Filesystem\Path as Path; 
use Symfony\Component\Process\Process as Process;
use Symfony\Component\Serializer\Encoder\XmlEncoder as Xml_encoder;

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_configuration; 
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;

use Bootstrap\Testing\Library\Classes\Test_plan as Test_plan;

/**
 * @TODO
 * 1. Route request to test plan
 * 2. Publish Test Plan Results to client 
 * 3. Commit Test Plan Summary to DB
 * 4. Add meta to complete context of test plan execution
 * 
 */

$Test_plan = new Test_plan( 
    $Environment_config,
    new Json_validator, 
    new Api_response, 
    new Filesystem
);

$test_response = $Test_plan->run_tests();

// if( $test_response[ 'error' ] ) {
//     Api_response::print_json( $test_response );
// } else { 
//     $Test_plan->compile_report( $test_response[ 'data' ][ 'test_report_file' ] );
// }

// Dump_var::print( $Test_plan->get_plan_summary()[ 'data' ] );

Dump_var::print( $Test_plan->get_test_report() );