<?php 

namespace Bootstrap\Testing\Library\Classes;

use Symfony\Component\Filesystem\Filesystem as Filesystem; 
use Symfony\Component\Filesystem\Path as Path; 
use Symfony\Component\Process\Process as Process;
use Symfony\Component\Process\Exception\ProcessFailedException as ProcessFailedException;

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_configuration; 
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;

use \Exception;
use \ErrorException;
use \Dump_var as Dump_var;
use \XMLWriter;
use \DOMDocument;
use \SimpleXMLElement;

/** 
 * @TODO 
 * 
 * 1. Route - get_plan, get_test_suites
 * 2. Report 
 * 3. Persist
 * 
 * Public Methods 
 * 
 * @method get_test_report
 * 
 * Private Methods 
 * 
 * @method get_plan 
 * @method get_test_suites
 */

class Test_plan { 

    Private $Env_config = null; // Object
    Private $get_response = null; // Function
    Private $system = array(
        'status' => 200,
        'error' => false, 
        'message' => 'Test plan initailized'
    ); 

    Private $results_manifest = array();

    Private $report_summary = array(
        'test_results' => array(
            'build_passed' => false,
            'details' => array(
                'execution_id' => '', 
                'results_file' => '',
                'start_time' => '', 
                'end_time' => '',
                'stats' => array(
                    'test_suites' => 0,  
                    'test_scripts' => 0, 
                    'assertions' => 0, 
                    'errors' => 0, 
                    'warnings' => 0,
                    'failures' => 0, 
                    'skipped' => 0
                )
            )
        ),
        'test_plan' => array(
            'name' => '', 
            'description' => ''
        ),
        'environment' => array(
            'name' => '', 
            'host_name' => ''
        ), 
        'code' => array(
            'repository' => array(
                'git_branch' => '', 
                'tag' => '',
                'commit' => ''
            )
        ), 
        'test_suites' => array() 
    );

    Private $plan_summary = array(
        'test_plan' => array(), 
        'test_suites' => array()
    );

    Public function __construct( Environment_configuration $Env_config, Json_validator $Json_validator, Api_response $Api_response, Filesystem $Filesystem ) {
        
        $this->Env_config = $Env_config;
        $this->Json_validator = $Json_validator;
        $this->Response = $Api_response;
        $this->Filesystem = $Filesystem;

        $env_config = $this->Env_config->get_env_config();
        $app_path = $env_config[ 'app_path' ];

        // Step 1. Get test plan
        $test_directories = $env_config[ 'directories' ][ 'tests' ];
        $test_plan_response = $this->get_plan( $app_path, $test_directories );

        if( $test_plan_response[ 'error' ] ) {
            throw new Exception( $test_plan_response[ 'message' ] );
        } else {
            $test_plan = $test_plan_response[ 'data' ];
        }

        // Step 2. Get Test suites
        $test_suites_response = $this->get_test_suites( $env_config[ 'app_path' ][ 'module' ], $test_plan[ 'test_suites' ], $test_directories );

        if( $test_suites_response[ 'error' ] ) {
            throw new Exception( $test_suites_response[ 'message' ] );
        } else {
            $test_suites = $test_suites_response[ 'data' ];
        }

        $git_response = $this->get_branch();
        if( !$git_response[ 'error' ] ) {
            $this->code[ 'repository' ] = $git_response[ 'data' ];
        }

        // Step 3. Set plan summary
        $this->plan_summary[ 'test_plan' ] = $test_plan; 
        $this->plan_summary[ 'test_suites' ] = $test_suites;

        // Step 4. Set report meta 
        $this->report_summary[ 'environment' ][ 'name' ] = $env_config[ 'env_name' ];
        $this->report_summary[ 'environment' ][ 'host_name' ] = $env_config[ 'app_path' ][ 'domain' ];
        $this->report_summary[ 'test_plan' ][ 'name' ] = $test_plan[ 'name' ];
        $this->report_summary[ 'test_plan' ][ 'description' ] = $test_plan[ 'description' ];
        $this->report_summary[ 'test_suites' ] = $test_suites;
        $this->report_summary[ 'code' ] = $this->code;

        $test_directory = $env_config[ 'directories' ][ 'tests' ][ 'shared' ]; 
        $this->plan_summary[ 'timestamp' ] = $this->Env_config->get_datetime_now( "Ymd_His" ); 
        $plan_summary = $this->plan_summary; 
        $plan_name = strtolower( str_replace( ' ', '-', $plan_summary[ 'test_plan' ]['name' ] ) );
        $execution_id = $this->plan_summary[ 'timestamp' ] . '_' . $plan_name;

        $this->report_summary[ 'test_results' ][ 'details' ][ 'execution_id' ] =  $execution_id;

    }

    Private function get_branch() : array {

        // Get the current branch name
        $branch_response = shell_exec('git branch -v --no-abbrev');

        if (is_string( $branch_response ) && 1 === preg_match('{^\* (.+?)\s+([a-f0-9]{40})(?:\s|$)}m', $branch_response, $matches)) { 
            $branch = $matches[ 0 ]; 
            $branch_data = explode( " ", $branch );
        } 
        
        // Get remote repository name
        $remote_repo_response = shell_exec('git remote -v'); 
        $remote_data = explode( "	", $remote_repo_response );
        $remote = str_replace( " (fetch)", "", $remote_data[ 1 ] ); 
        $remote = str_replace( "origin", "", $remote );
        $remote = preg_replace('~[[:cntrl:]]~', '', $remote); // remove all control chars

        // Git commit message
        $git_log_response = shell_exec( 'git log -1 --pretty=%B' );
        $git_last_commit_message = preg_replace('~[[:cntrl:]]~', '', $git_log_response);

        return $this->Response->get_response( array(
            'status' => 200, 
            'error' => false,
            'issue_id' => 'test_plan_013', 
            'message' => 'Success, branch data found', 
            'source' => get_class(), 
            'data' => array(
                'branch_name' => $branch_data[ 1 ],
                'commit' => $branch_data[ 2 ], 
                'commit_message' => $git_last_commit_message,
                'repo' => $remote
            )
        ) );

    }

    Private function get_plan( array $app_path, array $test_directories ) : array {

        if( $app_path[ 'application' ] === 'default' ) {

            $get_plan_response = $this->Response->get_response( array(
                'status' => 404, 
                'issue_id' => 'test_plan_001',
                'message' => 'Test plan not provided', 
                'source' => get_class()
            ) );

        } else {  
            
            $test_plan_file_name = $test_directories[ 'shared' ] . '/plans/' . $app_path[ 'application' ] . '.json'; 
         
            if( file_exists( $test_plan_file_name ) ) {

                $test_plan_json = file_get_contents( $test_plan_file_name );
                $test_plan_data_response = $this->Json_validator->validate( $test_plan_json );

                if( $test_plan_data_response[ 'error' ] ) {

                    $get_plan_response = $test_plan_data_response;    

                } else {

                    $get_plan_response = $this->Response->get_response( array(
                        'status' => 200,
                        'error' => false, 
                        'issue_id' => 'test_plan_003',
                        'message' => 'Success, test plan found', 
                        'source' => get_class(), 
                        'data' => $test_plan_data_response[ 'data' ]
                    ) );

                }

            } else {

                $get_plan_response = $this->Response->get_response( array(
                    'status' => 404, 
                    'issue_id' => 'test_plan_002',
                    'message' => 'Test plan not found', 
                    'source' => get_class()
                ) );

            }

        }

        return $get_plan_response;

    }

    Private function get_test_suites( string $specific_test_suite, array $test_plan_suites, array $test_directories ) : array {

        // Validation
        $test_suite_data = array();
        $is_existing_suite = in_array( $specific_test_suite, $test_plan_suites ) || ( $specific_test_suite === 'default' );
        $response[ 'error' ] = false; 
        
        if( !$is_existing_suite ) {

            return $this->Response->get_response( array(
                'status' => 404, 
                'issue_id' => 'test_plan_006', 
                'message' => 'Test suite not found', 
                'source' => get_class() 
            ) );

        }

        if( $specific_test_suite === 'default' ) {
            
            // Include all test suites
            for( $i = 0; $i < count( $test_plan_suites ); $i++ ) {

                $specific_test_suite_file = $test_directories[ 'shared' ] . 'suites/' . $test_plan_suites[ $i ] . '.json'; 
         
                if( file_exists( $specific_test_suite_file ) ) {

                    $specific_test_suite_response = $this->Json_validator->validate( file_get_contents( $specific_test_suite_file ) );

                    if( $specific_test_suite_response[ 'error' ] ) {

                        // Report errors
                        return $this->Response->get_response( array(
                            'status' => $specific_test_suite_response[ 'status' ], 
                            'issue_id' => 'test_plan_008', 
                            'message' => $specific_test_suite_response[ 'message' ] . '; Test Suite - ' . $test_plan_suites[ $i ],
                            'source' => get_class() 
                        ) );

                    } else {

                        // Success
                        $test_suite_data[ $test_plan_suites[ $i ] ] = $specific_test_suite_response[ 'data' ];

                    }
                    
                } else { 

                    // Report errors
                    $response = $this->Response->get_response( array(
                        'status' => 404, 
                        'issue_id' => 'test_plan_007', 
                        'message' => 'Test suite not found. Test name - ' . $test_plan_suites[ $i ], 
                        'source' => get_class() 
                    ) );

                }

            }
            
        } else {

            // Include one test suite
            $specific_test_suite_file = $test_directories[ 'shared' ] . 'suites/' . $specific_test_suite . '.json'; 

            if( file_exists( $specific_test_suite_file ) ) {
                
                $specific_test_suite_response = $this->Json_validator->validate( file_get_contents( $specific_test_suite_file ) );

                if( $specific_test_suite_response[ 'error' ] ) {

                    // Report rrors
                    return $this->Response->get_response( array(
                        'status' => $specific_test_suite_response[ 'status' ], 
                        'issue_id' => 'test_plan_009', 
                        'message' => $specific_test_suite_response[ 'message' ] . '; Test Suite - ' . $specific_test_suite,
                        'source' => get_class() 
                    ) );

                } else {

                    // Success
                    $test_suite_data[ $specific_test_suite ] = $specific_test_suite_response[ 'data' ];

                }
                
            }
            
        } 

        if( $response[ 'error' ] ) {
            return $response;
        } else {
            // Return test suites
            return $this->Response->get_response( array(
                'error' => false, 
                'status' => 200, 
                'issue_id' => 'test_plan_010', 
                'message' => 'Test suites found', 
                'source' => get_class(), 
                'data' => $test_suite_data
            ) );
        }

    }

    Public function get_test_report() : array {

        return $get_plan_response = $this->Response->get_response( array(
            'status' => $this->system[ 'status' ],
            'error' => $this->system[ 'error' ],  
            'issue_id' => 'test_plan_004', 
            'message' => $this->system[ 'message' ], 
            'private' => false,
            'source' => get_class(),
            'data' => $this->report_summary
        ) );

    }

    Public function get_plan_summary() : array {

        return $get_plan_response = $this->Response->get_response( array(
            'error' => $this->system[ 'error' ],
            'status' => 200, 
            'issue_id' => 'test_plan_011',
            'message' => 'Test plan summary found', 
            'source' => get_class(),
            'data' => $this->plan_summary
        ) );

    }

    Private function get_test_manifest( array $test_suites, string $test_script_directory, string $test_result_directory ) : array {

        $tmp_manifest = array();

        // Compile cases for testing and reporting
        foreach( $test_suites as $suite_key => $test_suite_data ) {
            
            // Loop over test cases  
            foreach( $test_suite_data[ 'test_scripts' ] as $script_key => $script_data ) {

                $test_script = $test_script_directory . $script_data[ 'script_location' ];

                if( is_dir( $test_script ) ) {

                    $tmp_manifest = glob( $test_script . '*.php' ); 

                    for( $i = 0; $i < count( $tmp_manifest ); $i++ ) {

                        $tmp_script_key = str_replace( '.php', '', basename( $tmp_manifest[ $i ] ) ); // In theory this is the same as the class
                        $test_manifest[ $suite_key ][ $tmp_script_key ] = $this->set_manifest( $suite_key, $tmp_script_key, $tmp_manifest[ $i ], $test_result_directory );

                        $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $tmp_script_key ][ 'script_location' ] = $tmp_manifest[ $i ];
                        $this->report_summary[ 'test_results' ][ 'details' ][ 'stats' ][ 'test_scripts' ]++; 

                    }

                    unset( $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $script_key ] ); // Clean up directory, because it is not used in the report

                } else if( file_exists( $test_script ) ) { 

                    $test_manifest[ $suite_key ][ $script_key ] = $this->set_manifest( $suite_key, $script_key, $test_script, $test_result_directory  );

                    $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $script_key ][ 'script_location' ] = $test_script;
                    $this->report_summary[ 'test_results' ][ 'details' ][ 'stats' ][ 'test_scripts' ]++; 

                } else {
                    
                    throw new Exception( 'Test case not found; Suite - ' . $suite_key . ', case - ' . $script_key );

                }

            }

        }

        return $test_manifest;

    }

    Private function set_manifest( $suite_key, $script_key, $filename, $test_result_directory ) {
        // Create file for storing results of test case
        $test_results_file = $test_result_directory . '/' . $script_key . '.xml'; 

        return array(
            'test_script' => $filename, 
            'results_file' => $test_results_file
        );

    }

    Private function compile_phpunit_xml_config( string $execution_id, array $test_manifest, string $test_config_file, string $test_results_directory ) {

        $test_manifest_xml = array( 'testsuite' => array() );
        $env_config = $this->Env_config->get_env_config(); 

        // Compile PHPUnit Config File
        $xml = new DOMDocument( "1.0", "utf-8" );
        $xml->formatOutput = true;
        $xml->preserveWhiteSpace = false;
        
        $xml_phpunit = $xml->createElement( "phpunit" );
        $xml_phpunit->setAttribute( "bootstrap", $env_config[ 'directories' ][ 'framework' ] . 'bootstrap/testing/bootstrap.php' );
        $xml_phpunit->setAttribute( "xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance" );
        $xml_phpunit->setAttribute( "xsi:noNamespaceSchemaLocation", "http://schema.phpunit.de/3.7/phpunit.xsd" );
        
        $xml_suites = $xml->createElement( "testsuites" );

        foreach( $test_manifest as $suite_key => $suite ) {

            $xml_suite = $xml->createElement( "testsuite" );
            $xml_suite->setAttribute( 'name', $suite_key );

            foreach( $suite as $file_key => $file ) {     
            
                $xml_file = $xml->createElement( "file", $file[ 'test_script' ] );
                $xml_suite->appendChild( $xml_file );

            }

            $xml_suites->appendchild( $xml_suite );
           
        }

        $xml_phpunit->appendChild( $xml_suites );
       

        // Logging 
        $xml_logging = $xml->createElement( "logging" );

        $xml_log = $xml->createElement( "log" );
        $xml_log->setAttribute( "type", "junit" );
        $xml_log->setAttribute( "target", $test_results_directory . $execution_id . ".xml" ); 
        $xml_logging->appendChild( $xml_log );

        // $xml_log = $xml->createElement( "log" );
        // $xml_log->setAttribute( "type", "testdox-html" );
        // $xml_log->setAttribute( "target", $test_results_directory . $execution_id . ".html" ); 
        // $xml_logging->appendChild( $xml_log );

        // $xml_log = $xml->createElement( "log" );
        // $xml_log->setAttribute( "type", "testdox-text" );
        // $xml_log->setAttribute( "target", $test_results_directory . $execution_id . ".txt" ); 
        // $xml_logging->appendChild( $xml_log );

        $xml_phpunit->appendChild( $xml_logging );

        $xml->appendChild( $xml_phpunit );
        $xml->save( $test_config_file );

        return file_exists( $test_config_file );

    }

    Private function add_test_case_results_to_report( SimpleXMLElement $test_results_xml  ) {

        $results_manifest = $this->recursively_manifest_test_results_xml( $test_results_xml );
     
        foreach( $results_manifest as $keys => $entry ) {

            $filepath = $entry[ 'file' ]; 
            $filepath_segments = explode( '/', $filepath );
            $filename = $filepath_segments[ count( $filepath_segments ) - 1 ];
            $classname = str_replace( '.php', '', $filename );

            foreach( $this->report_summary[ 'test_suites' ] as $suite_key => $suite ) {

                if( 
                    isset( $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ] ) &&
                    $filepath === $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ][ 'script_location' ]
                ) {
                    $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ][ 'results' ] = $entry;
                    unset( $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ][ 'results' ][ 'file' ] );
                    break;
                }
    
            }

        }

    }

    Private function recursively_manifest_test_results_xml( $xml, $parent = '', $testsuite_name = '' ) {

        foreach( $xml as $key => $value)  {
   
            if( isset( $value->children()->testcase ) ) {

                $testsuite_attributes = json_decode( json_encode( $value->attributes() ), true )[ '@attributes' ];
                $testsuite_name = $testsuite_attributes[ 'name' ]; 
                $this->results_manifest[ $testsuite_name ] = $testsuite_attributes;

            }

            if( $key == 'testcase' ) {

                $testcase_attributes = json_decode( json_encode( $value->attributes() ), true )[ '@attributes' ]; 
                $testcase_name = $testcase_attributes[ 'name' ];
                $this->results_manifest[ $testsuite_name ][ 'test_cases' ][ $testcase_name ] = $testcase_attributes;
                
                if( isset( $value->error ) ) {
                    $this->results_manifest[ $testsuite_name ][ 'test_cases' ][ $testcase_name ][ 'error' ] = $value->error;
                }

                if( isset( $value->failure ) ) {
                    $this->results_manifest[ $testsuite_name ][ 'test_cases' ][ $testcase_name ][ 'failure' ] = $value->failure;
                }
                
            } else {

                $this->recursively_manifest_test_results_xml( $value, $parent . "." . $key, $testsuite_name );

            }

        }

        return $this->results_manifest;

    }


    Public function run_php_tests() : array {
    
        $Filesystem = $this->Filesystem;
        $env_config = $this->Env_config->get_env_config(); 
        $test_directory = $env_config[ 'directories' ][ 'tests' ][ 'shared' ]; 
    
        $execution_id = $this->report_summary[ 'test_results' ][ 'details' ][ 'execution_id' ];
        $test_manifest = array();

        // Relevant file paths
        $test_report_file = Path::normalize( $test_directory . 'reports/report_' . $execution_id . '.json' );
        $test_script_directory = Path::normalize( $test_directory . 'scripts/' ); 
        $test_result_directory = Path::normalize( $test_directory . 'test_results/' ); 
        $test_config_file = $test_directory . 'active_tests/active_' . $execution_id . '.xml';
        $test_config_file_archive = $test_directory . 'archive/archived_' . $execution_id . '.xml';

        // Prevent the same test from running simultaneously
        if( file_exists( $test_config_file ) ) {
            throw new Exception( 'Test already running' );
        }

        // Verify that PHPUnit is installed
        $phpunit_location = $env_config[ 'directories' ][ 'vendor' ] . 'phpunit/phpunit/phpunit'; 
        if( !file_exists( $phpunit_location ) ) {
            throw new Exception( 'PHPUnit not available' );
        }

        $test_manifest = $this->get_test_manifest( 
            $this->report_summary[ 'test_suites' ], 
            $test_script_directory, 
            $test_result_directory 
        );

        // Publish report as semaphore to prevent other tests from running concurrently
        $this->report_summary[ 'test_results' ][ 'details' ][ 'start_time' ] = $this->Env_config->get_datetime_now( 'Y-m-d h:m:s.u' );

        $test_results_file = $test_result_directory . $execution_id . '.xml'; 
        $xml_file_exists = $this->compile_phpunit_xml_config( $execution_id, $test_manifest, $test_config_file, $test_result_directory );

        if( ! $xml_file_exists ) {
            throw new Exception( 'Failed to create config file for PHPUnit' );
        }
 
        // Run PHPUnit
        $process = new Process([ $phpunit_location, '-c', $test_config_file ]);
        $process->run();

        // @todo Log errors to a stream or database
        // throw new ProcessFailedException($process);

        // Read test result XML
        $test_results_xml = simplexml_load_file( $test_results_file );
        $testsuite_stats = json_decode( json_encode( $test_results_xml->testsuite->attributes() ), true )[ '@attributes' ];

        $this->report_summary[ 'test_results' ][ 'details' ][ 'end_time' ] = $this->Env_config->get_datetime_now( 'Y-m-d h:m:s.u' );
        $this->report_summary[ 'test_results' ][ 'details' ][ 'results_file' ] = $test_results_file;
        $stats = array_merge( $this->report_summary[ 'test_results' ][ 'details' ][ 'stats' ], $testsuite_stats );
        $this->report_summary[ 'test_results' ][ 'details' ][ 'stats' ] = $stats;

        if( (int) $stats[ 'failures' ] === 0 && (int) $stats[ 'errors' ] === 0 ) {
            $this->report_summary[ 'test_results' ][ 'build_passed' ] = true;
            $this->system[ 'message' ] = "All tests passed";
        } else {
            $this->report_summary[ 'test_results' ][ 'build_passed' ] = false;
            $this->system[ 'error' ] = true; 
            $this->system[ 'status' ] = 500;
            $this->system[ 'message' ] = "Failed tests detected";
        }

        $this->add_test_case_results_to_report( $test_results_xml );

        $this->Filesystem->touch( $test_report_file );
        $this->Filesystem->appendToFile( $test_report_file, json_encode( $this->report_summary, JSON_PRETTY_PRINT ), true );

        // When test is complete then move sempaphore to archive
        $this->Filesystem->rename( $test_config_file, $test_config_file_archive, true );

        return $this->Response->get_response( array(
            'status' => $this->system[ 'status' ],
            'error' => $this->system[ 'error' ],  
            'issue_id' => 'test_plan_015', 
            'message' => $this->system[ 'message' ], 
            'source' => get_class(), 
            'data' => array(
                'test_report_file' => $test_report_file 
            )
        ));

    }

}