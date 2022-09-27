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
                'process_id' => '',
                'start_time' => '', 
                'stats' => array(
                    'total_duration' => '00:00:00', 
                    'total' => 0, 
                    'remaining' => 0, 
                    'complete' => 0
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

        // Find the current branch
        $branch_response = shell_exec('git branch -v --no-abbrev');

        if (is_string( $branch_response ) && 1 === preg_match('{^\* (.+?)\s+([a-f0-9]{40})(?:\s|$)}m', $branch_response, $matches)) { 
            $branch = $matches[ 0 ]; 
            $branch_data = explode( " ", $branch );
        } 
        
        // Remote repository
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

    Public function launch_tests() : array {
    
        $Filesystem = $this->Filesystem;
        $env_config = $this->Env_config->get_env_config(); 
        $test_directory = $env_config[ 'directories' ][ 'tests' ][ 'shared' ]; 

        $timestamp = $this->Env_config->get_datetime_now( "Ymd_His" ); 
        $plan_summary = $this->plan_summary; 
        $plan_name = strtolower( str_replace( ' ', '-', $plan_summary[ 'test_plan' ]['name' ] ) );
        $execution_id = $timestamp;
        $process_ids = array();

        $test_manifest = array();

        // Relevant file paths
        $test_report_file = Path::normalize( $test_directory . 'active_tests/report_' . $plan_name . '.json' );
        $test_script_directory = Path::normalize( $test_directory . 'scripts' ); 
        $test_result_directory = Path::normalize( $test_directory . 'results/' . $plan_name . '/' . $execution_id ); 
        $test_report_file_archive = Path::normalize( $test_directory . 'archive/archive_' . $plan_name . '.json' );

        // Prevent the same test from running simultaneously
        if( file_exists( $test_report_file ) ) {
            throw new Exception( 'Test already running' );
        }

        // Verify that PHPUnit is installed
        $phpunit_location = $env_config[ 'directories' ][ 'shared' ] . 'vendor/phpunit/phpunit/phpunit'; 
        if( !file_exists( $phpunit_location ) ) {
            throw new Exception( 'PHPUnit not available' );
        }

        // Compile cases for testing and reporting
        foreach( $this->report_summary[ 'test_suites' ] as $suite_key => $test_suite_data ) {
             
            // Loop over test cases  
            foreach( $test_suite_data[ 'test_cases' ] as $case_key => $case_data ) {

                $test_script = $test_script_directory . '/' . $case_data[ 'script_location' ];

                if( file_exists( $test_script ) ) { 
                    
                    // Create file for storing results of test case
                    $test_results_file = $test_result_directory . '/' . $case_key . '.xml'; 

                    $test_manifest[ $suite_key ][ $case_key ] = array(
                        'test_script' => $test_script, 
                        'results_file' => $test_results_file,
                        'finished' => false
                    );

                    $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_cases' ][ $case_key ][ 'script_location' ] = $test_script;
                    $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_cases' ][ $case_key ][ 'results_location' ] = $test_results_file;
                    
                } else {
                    
                    throw new Exception( 'Test case not found; Suite - ' . $suite_key . ', case - ' . $case_key );

                }

            }

        }

        // Publish report as semaphore to prevent other tests from running concurrently
        $this->report_summary[ 'test_results' ][ 'details' ][ 'execution_id' ] = $execution_id;
        $this->report_summary[ 'test_results' ][ 'details' ][ 'start_time' ] = $this->Env_config->get_datetime_now();
        // $this->report_summary[ 'test_results' ][ 'details' ]

        $this->Filesystem->touch( $test_report_file );
        $this->Filesystem->appendToFile( $test_report_file, json_encode( $this->report_summary, JSON_PRETTY_PRINT ), true );

        // Create folder to store test results
        $this->Filesystem->mkdir( $test_result_directory );

        // Run tests - synchronously
        foreach( $test_manifest as $suite_key => $suite ) {

            foreach( $suite as $case_key => $case ) {

                $process = new Process([ $phpunit_location, '--log-junit', $case[ 'results_file' ], $case[ 'test_script' ] ]);
                $process->run();
                // @todo Log errors to a stream or database

            }

        }

        // // When test is complete then move sempaphore to archive
        // $this->Filesystem->rename( $test_report_file, $test_report_file_archive, true );

        return $this->Response->get_response( array(
            'status' => 200,
            'error' => false,  
            'issue_id' => 'test_plan_015', 
            'message' => 'Test plan ran succesfully', 
            'source' => get_class(), 
            'data' => array(
                'test_report_file' => $test_report_file 
            )
        ));

    }

    Public function compile_report( string $report_file ) : array {

        // Open report 

        // Tally completed tests based

        // Read test results into a manifest 

        // Tally total running time 

        // 

    }


}