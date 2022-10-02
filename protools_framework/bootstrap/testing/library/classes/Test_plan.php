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
use \Dump_var;
use \XMLWriter;
use \DOMDocument;

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
        'error' => false, 
        'message' => 'Test plan initailized'
    ); 

    Private $report_summary = array(
        'test_results' => array(
            'details' => array(
                'execution_id' => '', 
                'results_file' => '',
                'start_time' => '', 
                'end_time' => '',
                'stats' => array(
                    'total_duration_seconds' => '0.0000',
                    'test_suite_total' => 0,  
                    'test_script_total' => 0
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
        $test_suites_response = $this->get_test_suites( $env_config[ 'app_path' ][ 'application' ], $test_plan[ 'test_suites' ], $test_directories );

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
        $this->report_summary[ 'environment' ][ 'host_name' ] = $env_config[ 'domain' ];
        $this->report_summary[ 'test_plan' ][ 'name' ] = $test_plan[ 'name' ];
        $this->report_summary[ 'test_plan' ][ 'description' ] = $test_plan[ 'description' ];
        $this->report_summary[ 'test_suites' ] = $test_suites;
        $this->report_summary[ 'code' ] = $this->code;

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

        if( $app_path[ 'module' ] === 'default' ) {

            $get_plan_response = $this->Response->get_response( array(
                'status' => 404, 
                'issue_id' => 'test_plan_001',
                'message' => 'Test plan not provided', 
                'source' => get_class()
            ) );

        } else {  
            
            $test_plan_file_name = $test_directories[ 'shared' ] . '/plans/' . $app_path[ 'module' ] . '.json'; 
         
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
                        'message' => $specific_test_suite_response[ 'message' ] . '; Test Suite - ' . $test_plan_suites[ $i ],
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
            'error' => $this->system[ 'error' ],
            'status' => 200, 
            'issue_id' => 'test_plan_004',
            'message' => 'Test report found', 
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

    Public function run_tests() : array {
    
        $Filesystem = $this->Filesystem;
        $env_config = $this->Env_config->get_env_config(); 
        $test_directory = $env_config[ 'directories' ][ 'tests' ][ 'shared' ]; 
    
        $timestamp = $this->Env_config->get_datetime_now( "Ymd_His" ); 
        $plan_summary = $this->plan_summary; 
        $plan_name = strtolower( str_replace( ' ', '-', $plan_summary[ 'test_plan' ]['name' ] ) );
        $execution_id = $timestamp . '_' . $plan_name;
        $test_manifest = array();

        // Relevant file paths
        $test_report_file = Path::normalize( $test_directory . 'reports/report_' . $execution_id . '.json' );
        $test_script_directory = Path::normalize( $test_directory . 'scripts/' ); 
        $test_result_directory = Path::normalize( $test_directory . 'test-results/' ); 
        $test_config_file = $test_directory . 'active_tests/active_' . $execution_id . '.xml';
        $test_config_file_archive = $test_directory . 'archive/archived_' . $execution_id . '.xml';

        $test_config_file_archive = $test_directory . 'archive/archived_debug_test.xml';

        // Prevent the same test from running simultaneously
        if( file_exists( $test_config_file ) ) {
            throw new Exception( 'Test already running' );
        }

        // Verify that PHPUnit is installed
        $phpunit_location = $env_config[ 'directories' ][ 'vendor' ] . 'phpunit/phpunit/phpunit'; 
        if( !file_exists( $phpunit_location ) ) {
            throw new Exception( 'PHPUnit not available' );
        }

        // Compile cases for testing and reporting
        foreach( $this->report_summary[ 'test_suites' ] as $suite_key => $test_suite_data ) {
            
            $this->report_summary[ 'test_results' ][ 'details' ][ 'stats' ][ 'test_suite_total' ]++; 
            
            // Loop over test cases  
            foreach( $test_suite_data[ 'test_scripts' ] as $script_key => $script_data ) {

                $test_script = $test_script_directory . $script_data[ 'script_location' ];

                if( file_exists( $test_script ) ) { 
                    
                    // Create file for storing results of test case
                    $test_results_file = $test_result_directory . '/' . $script_key . '.xml'; 

                    $test_manifest[ $suite_key ][ $script_key ] = array(
                        'test_script' => $test_script, 
                        'results_file' => $test_results_file,
                        'finished' => false
                    );

                    $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $script_key ][ 'script_location' ] = $test_script;
                    $this->report_summary[ 'test_results' ][ 'details' ][ 'stats' ][ 'test_script_total' ]++; 
                    
                } else {
                    
                    throw new Exception( 'Test case not found; Suite - ' . $suite_key . ', case - ' . $script_key );

                }

            }

        }

        // Publish report as semaphore to prevent other tests from running concurrently
        $this->report_summary[ 'test_results' ][ 'details' ][ 'execution_id' ] = $execution_id;
        $this->report_summary[ 'test_results' ][ 'details' ][ 'start_time' ] = $this->Env_config->get_datetime_now( 'Y-m-d h:m:s.u' );
        $start_time_microseconds = microtime(true);

        $test_manifest_xml = array( 'testsuite' => array() );

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
                
                // @todo check to see if file is a directory
                // $xml_file = $xml->createElement( "directory", $file[ 'test_script' ] );
                $xml_file = $xml->createElement( "file", $file[ 'test_script' ] );
                $xml_suite->appendChild( $xml_file );

            }

            $xml_suites->appendchild( $xml_suite ); 
           
        }

        $xml_phpunit->appendChild( $xml_suites );
        $xml->appendChild( $xml_phpunit ); 
        $xml->save( $test_config_file );

        $test_results_file = $test_result_directory . 'debug_test.xml'; 
 
        // Run PHPUnit
        $process = new Process([ $phpunit_location, '--log-junit', $test_results_file, '-c', $test_config_file ]);
        $process->run();

        // @todo Log errors to a stream or database

        // throw new ProcessFailedException($process);

        // Complete reports
        $end_time_microseconds = microtime(true);
        $total_time_lapsed_seconds = ( ( ( $end_time_microseconds - $start_time_microseconds ) * 1000 ) / 1000 ); // Convert microseconds > milliseconds > seconds
        $this->report_summary[ 'test_results' ][ 'details' ][ 'stats' ][ 'total_duration_seconds' ] =  $total_time_lapsed_seconds;  
        $this->report_summary[ 'test_results' ][ 'details' ][ 'end_time' ] = $this->Env_config->get_datetime_now( 'Y-m-d h:m:s.u' );
        $this->report_summary[ 'test_results' ][ 'details' ][ 'results_file' ] = $test_results_file;

        $this->Filesystem->touch( $test_report_file );
        $this->Filesystem->appendToFile( $test_report_file, json_encode( $this->report_summary, JSON_PRETTY_PRINT ), true );

        // When test is complete then move sempaphore to archive
        $this->Filesystem->rename( $test_config_file, $test_config_file_archive, true );

        return $this->Response->get_response( array(
            'status' => 200,
            'error' => false,  
            'issue_id' => 'test_plan_015', 
            'message' => 'Test plan ran successfully', 
            'source' => get_class(), 
            'data' => array(
                'test_report_file' => $test_report_file 
            )
        ));

    }

}