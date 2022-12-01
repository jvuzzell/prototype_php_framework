<?php 

namespace Bootstrap\Testing\Library\Classes;

use Symfony\Component\Filesystem\Filesystem as Filesystem; 
use Symfony\Component\Filesystem\Path as Path; 
use Symfony\Component\Process\Process as Process; 

use Bootstrap\Shared\Utilities\Classes\Environment_configuration as Environment_configuration; 
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;

use PHPUnit\Util\TestDox\NamePrettifier as NamePrettifier;
use \Exception;
use \DOMDocument;
use \SimpleXMLElement;

/** 
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
    Private $system = array(
        'status' => 200,
        'error' => false, 
        'message' => 'Test plan initailized'
    ); 

    Private $results_manifest = array();

    Private $report_summary = array(
        'summary' => array(
            'build_passed' => false,
            'execution_id' => '', 
            'results_file' => '',
            'start_time' => '', 
            'end_time' => ''
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
        'test_plan' => array(
            'id' => '',
            'name' => '', 
            'description' => '', 
            'start-date' => '',
            'end-date' => '', 
            'stats' => array(
                'assertions' => 0,
                'failures' => 0, 
                'skipped' => 0,  
                'errors' => 0, 
                'tests_passed' => 0,
                'suites_passed' => 0,
                'test_suites' => 0,  
                'test_scripts' => 0
            )
        ),
        'test_suites' => array() 
    );

    Private $plan_summary = array(
        'test_plan' => array(), 
        'test_suites' => array()
    );

    Public function __construct( Environment_configuration $Env_config, Json_validator $Json_validator, Api_response $Api_response, Filesystem $Filesystem, NamePrettifier $NamePrettifier ) {
        
        $this->Env_config = $Env_config;
        $this->Json_validator = $Json_validator;
        $this->Response = $Api_response;
        $this->Filesystem = $Filesystem;
        $this->NamePrettifier = $NamePrettifier;

        $env_config = $this->Env_config->get_env_variables();
        $app_path = $env_config[ 'app_path' ];

        // Step 1. Get test plan
        $test_directories = $env_config[ 'directories' ][ 'tests' ];
        $test_plan_response = $this->get_plan( $app_path, $test_directories );

        if( $test_plan_response[ 'error' ] ) {
            throw new Exception( $test_plan_response[ 'message' ] );
        } else {
            $test_plan = $test_plan_response[ 'data' ];
        }

        // Step 2. Get test suites
        $test_suites_response = $this->get_test_suites( $env_config[ 'app_path' ][ 'resource' ], $test_plan[ 'test_suites' ], $test_directories );

        if( $test_suites_response[ 'error' ] ) {
            throw new Exception( $test_suites_response[ 'message' ] );
        } else {
            $test_suites = $test_suites_response[ 'data' ];
        }

        // Step 3. Filter test suite if a specific test script was requested
        $requested_view = $app_path[ 'view' ]; 

        if( $requested_view !== 'default' ) {

            $test_case_response = $this->get_specific_test_case( $app_path, $test_directories, $test_suites );

            if( $test_case_response[ 'error' ] ) {
                throw new Exception( $test_case_response[ 'message' ] );
            } else {
                $test_suites = $test_case_response[ 'data' ];
            }

        }

        // Step 4. Set plan summary
        $this->plan_summary[ 'test_plan' ] = $test_plan; 
        $this->plan_summary[ 'test_suites' ] = $test_suites;

        // Step 5. Set report meta 
        $git_response = $this->get_branch();
        if( !$git_response[ 'error' ] ) {
            $this->code[ 'repository' ] = $git_response[ 'data' ];
        }

        $this->report_summary[ 'environment' ][ 'name' ] = $env_config[ 'env_name' ];
        $this->report_summary[ 'environment' ][ 'host_name' ] = $env_config[ 'app_path' ][ 'domain' ];
        $this->report_summary[ 'test_plan' ][ 'id' ] = $test_plan[ 'id' ];
        $this->report_summary[ 'test_plan' ][ 'name' ] = $test_plan[ 'name' ];
        $this->report_summary[ 'test_plan' ][ 'description' ] = $test_plan[ 'description' ];
        $this->report_summary[ 'test_plan' ][ 'start-date' ] = $test_plan[ 'start-date' ];
        $this->report_summary[ 'test_plan' ][ 'end-date' ] = $test_plan[ 'end-date' ];
        $this->report_summary[ 'test_suites' ] = $test_suites;
        $this->report_summary[ 'code' ] = $this->code;

        $this->plan_summary[ 'timestamp' ] = $this->Env_config->get_datetime_now( "Ymd-His" ); 
        $plan_name = strtolower( str_replace( ' ', '-', $this->plan_summary[ 'test_plan' ]['name' ] ) );
        $execution_id = $this->plan_summary[ 'timestamp' ] . '_' . $plan_name;

        $this->report_summary[ 'summary' ][ 'execution_id' ] =  $execution_id;

    }

    Private function get_specific_test_case( array $app_path, array $test_directories, array $test_suites ) {

        $requested_resource = $app_path[ 'resource' ]; 
        $requested_view = $app_path[ 'view' ]; 
        $test_case_found = false; 

        if( 
            $requested_view !== 'default' &&
            isset( $test_suites[ $requested_resource  ][ 'test_scripts' ][ $requested_view ] ) && 
            !is_dir( $test_suites[ $requested_resource  ][ 'test_scripts' ][ $requested_view ][ 'script_location' ] )
        ) {

            $test_script = $test_suites[ $requested_resource  ][ 'test_scripts' ][ $requested_view ];
            $test_suites[ $requested_resource  ][ 'test_scripts' ] = array();
            $test_suites[ $requested_resource  ][ 'test_scripts' ][ $requested_view ] = $test_script;

            $response = $this->Response->format_response( array(
                'status' => 200, 
                'error' => false,
                'issue_id' => 'test_plan_016', 
                'source' => get_class(), 
                'message' => 'Test case ' . $requested_view . ' found', 
                'data' => $test_suites
            ));

            $test_case_found = true;

        } else {

            foreach( $test_suites[ $requested_resource ][ 'test_scripts' ] as $suite_keys => $suite_data ) {
    
                $tmp_manifest = glob( $test_directories[ 'shared' ] . 'scripts/' .$suite_data[ 'script_location' ]  . '*.php' ); 
 
                for( $i = 0; $i < count( $tmp_manifest ); $i++ ) {
    
                    $tmp_script_key = str_replace( '.php', '', basename( $tmp_manifest[ $i ] ) ); // In theory this is the same as the class
                    
                    if( $requested_view === $tmp_script_key ) {

                        $test_suites[ $requested_resource ][ 'test_scripts' ] = array();
                        $test_suites[ $requested_resource ][ 'test_scripts' ][ $requested_view ][ 'test_type' ] = $suite_data[ 'test_type' ];
                        $test_suites[ $requested_resource ][ 'test_scripts' ][ $requested_view ][ 'id' ] = $suite_data[ 'id' ];
                        $test_suites[ $requested_resource ][ 'test_scripts' ][ $requested_view ][ 'description' ] = $suite_data[ 'description' ];
                        $test_suites[ $requested_resource ][ 'test_scripts' ][ $requested_view ][ 'script_location' ] = $suite_data[ 'script_location' ] . $tmp_script_key . '.php';
                        $test_case_found = true;

                        $response = $this->Response->format_response( array(
                            'status' => 200, 
                            'error' => false,
                            'issue_id' => 'test_plan_015', 
                            'source' => get_class(), 
                            'message' => 'Test case ' . $requested_view . ' found', 
                            'data' => $test_suites
                        ));

                        break;

                    }
    
                }

                if( $test_case_found ) {
                    break;
                }
    
            }

        }

        if( !$test_case_found ) {

            $response = $this->Response->format_response( array(
                'status' => 500, 
                'issue_id' => 'test_plan_014', 
                'source' => get_class(), 
                'message' => 'Test case "' . $requested_view . '" not found; Test Suite - ' . $requested_resource
            ));

        }

        return $response;

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

        return $this->Response->format_response( array(
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

            $get_plan_response = $this->Response->format_response( array(
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

                    $get_plan_response = $this->Response->format_response( array(
                        'status' => 200,
                        'error' => false, 
                        'issue_id' => 'test_plan_003',
                        'message' => 'Success, test plan found', 
                        'source' => get_class(), 
                        'data' => $test_plan_data_response[ 'data' ]
                    ) );

                }

            } else {

                $get_plan_response = $this->Response->format_response( array(
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

            return $this->Response->format_response( array(
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
                        return $this->Response->format_response( array(
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
                    $response = $this->Response->format_response( array(
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
                    return $this->Response->format_response( array(
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
            return $this->Response->format_response( array(
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

        return $get_plan_response = $this->Response->format_response( array(
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

        return $get_plan_response = $this->Response->format_response( array(
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
        $dedupe_list = array();

        $app_path = $this->Env_config->get_env_variables()[ 'app_path' ];

        // Compile cases for testing and reporting
        foreach( $test_suites as $suite_key => $test_suite_data ) {
            
            // Loop over test cases  
            foreach( $test_suite_data[ 'test_scripts' ] as $script_key => $script_data ) {

                $test_script = $test_script_directory . $script_data[ 'script_location' ];
                $this->report_summary[ 'test_plan' ][ 'stats' ][ 'test_suites' ]++; 

                if( is_dir( $test_script ) ) {

                    $tmp_manifest = glob( $test_script . '*.php' ); 
                    
                    for( $i = 0; $i < count( $tmp_manifest ); $i++ ) {

                        $tmp_script_key = str_replace( '.php', '', basename( $tmp_manifest[ $i ] ) ); // In theory this is the same as the class

                        // Check for duplicates
                        if( !in_array( $tmp_manifest[ $i ], $dedupe_list ) ) {
                            $dedupe_list[] = $tmp_manifest[ $i ];
                        } else {
                            $test_manifest[ $suite_key ][ $tmp_script_key ][ 'duplicate_detected' ] = true;
                            $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $tmp_script_key ][ 'duplicate_detected' ] = true;
                        }

                        $this->report_summary[ 'test_plan' ][ 'stats' ][ 'test_scripts' ]++; 
                        $test_manifest[ $suite_key ][ $tmp_script_key ] = $this->set_manifest( $suite_key, $tmp_script_key, $tmp_manifest[ $i ], $test_result_directory );
                        $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $tmp_script_key ][ 'script_location' ] = $tmp_manifest[ $i ];

                    }

                    unset( $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $script_key ] ); // Clean up directory, because it is not used in the report

                } else if( file_exists( $test_script ) ) { 

                    // Check to see for a specific testcase
                    if( !in_array( $test_script, $dedupe_list ) ) {
                        $dedupe_list[] = $test_script;
                    } else {
                        $test_manifest[ $suite_key ][ $script_key ][ 'duplicate_detected' ] = true;
                        $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $script_key ][ 'duplicate_detected' ] = true;
                    }

                    $test_manifest[ $suite_key ][ $script_key ] = $this->set_manifest( $suite_key, $script_key, $test_script, $test_result_directory  );
                    $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $script_key ][ 'script_location' ] = $test_script;
                    $this->report_summary[ 'test_plan' ][ 'stats' ][ 'test_scripts' ]++; 

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
            'results_file' => $test_results_file, 
            'duplicate' => false
        );

    }

    Private function compile_phpunit_xml_config( string $execution_id, array $test_manifest, string $test_config_file, string $test_results_directory ) {

        $env_config = $this->Env_config->get_env_variables(); 

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
            $xml_suite->setAttribute( "name", $suite_key );

            foreach( $suite as $file_key => $file ) {     
                
                if( $file[ 'duplicate' ] ) {
                    $xml_exclude = $xml->createElement( 'exclude', $file[ 'test_script' ] );
                    $xml_exclude->setAttribute( 'duplicate', 'true' );
                    $xml_suite->appendChild( $xml_exclude );
                } else {
                    $xml_file = $xml->createElement( "file", $file[ 'test_script' ] );
                    $xml_suite->appendChild( $xml_file );
                }

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

    /**
     * Retrieve the relevant portion of the PHP source file with syntax highlighting
     *
     * @param string        $fileName   path to the source file
     * @param int           $firstLine  first line number to show
     * @param int           $numLines   number of lines to show
     * @param int           $markLine   line number to mark if required
     * @return  string                  highlighted source HTML formatted
     */
    protected function highlight_source($fileName, $firstLine = 1, $numLines = null, $markLine = null) {
      
        $lines = highlight_file($fileName, true);
        $lines = explode("<br />", $lines);
        $source[$fileName] = $lines;
        $lines = array_slice($lines, $firstLine - 1, $numLines);
  
        // $html = '<table class="code" cellpadding="0" cellspacing="0" border="0">';
        // $row = 0;
        // $lineno = $firstLine;
        $method_started = false;
        $method_patterns = '/public&nbsp;function|private&nbsp;function/iU';
        $tmp_lines = array();
        foreach ($lines as $line_no => $line) {

            if(  $method_started === false && ( preg_match( $method_patterns, $line, $matches ) > 0 ) ) {
                $method_started = true; 
            } else if ( $method_started && ( preg_match( $method_patterns, $line, $matches ) > 0 ) ) {
                break;
            }

            $tmp_lines[ $line_no ] = $line;

            // $html .= '<tr class="line'.($lineno == $markLine ? ' hilite' : '').($row & 1 ? ' odd' : ' even').'"><td class="linenum">'.$lineno.'</td><td class="linetxt"><span>'.$line.'</span></td></tr>';
            // $lineno++;
            // $row++;
        }
        // $html .= '</table>';
    
        return $tmp_lines;
    }

    Private function add_test_script_results_to_report( SimpleXMLElement $test_results_xml  ) {

        $results_manifest = $this->recursively_manifest_test_results_xml( $test_results_xml );
        $tmp_suite_stats = array();
        $this->report_summary[ 'test_plan' ][ 'stats' ][ 'suites_passed' ] = $this->report_summary[ 'test_plan' ][ 'stats' ][ 'test_suites' ];

        $script_passed = true; 
        $check_script_passed = false; 
        $is_cli = $this->Env_config->get_is_cli(); 

        foreach( $results_manifest as $script_key => $script ) {
     
            $filepath = $script[ 'file' ]; 
            $filepath_segments = explode( '/', $filepath );
            $filename = $filepath_segments[ count( $filepath_segments ) - 1 ];
            $classname = str_replace( '.php', '', $filename );

            if( !$is_cli ) {

                foreach( $script[ 'tests' ] as $test_key => $test_result ) {
                    $lines_of_code_as_html[ $test_key ][ $test_result[ 'method' ] ] = $this->highlight_source( $script[ 'file' ], $test_result[ 'line' ] );
                }

            }


            if( !$check_script_passed && $script[ 'stats' ][ 'tests_passed' ] !== $script[ 'stats' ][ 'tests' ] ) {
                $script_passed = false; 
                $check_script_passed = true;
                $this->report_summary[ 'test_plan' ][ 'stats' ][ 'suites_passed' ]--;
            } 

            $this->report_summary[ 'test_plan' ][ 'stats' ][ 'tests_passed' ] += $script[ 'stats' ][ 'tests_passed' ]; 

            foreach( $this->report_summary[ 'test_suites' ] as $suite_key => $suite ) { 
              
                if( 
                    isset( $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ] ) &&
                    $filepath === $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ][ 'script_location' ]
                ) {

                    if( !isset( $this->report_summary[ 'test_suites' ][ $suite_key ][ 'stats' ] ) ) {

                        $tmp_suite_stats = array(
                            'stats' => array(
                                'assertions' => $script[ 'stats' ][ 'assertions' ], 
                                'failures' => $script[ 'stats' ][ 'failures' ], 
                                'skipped' => $script[ 'stats' ][ 'skipped' ], 
                                'errors' => $script[ 'stats' ][ 'errors' ],
                                'passed' => $script[ 'stats' ][ 'tests_passed' ],  
                                'time' => $script[ 'stats' ][ 'time' ], 
                                'tests' => $script[ 'stats' ][ 'tests' ],
                                'suites_passed' => $script_passed,
                                'test_scripts' => 1
                            )
                        );

                    } else {
           
                        $tmp_suite_stats[ 'stats' ][ 'tests' ] += $script[ 'stats' ][ 'tests' ]; 
                        $tmp_suite_stats[ 'stats' ][ 'assertions' ] += $script[ 'stats' ][ 'assertions' ]; 
                        $tmp_suite_stats[ 'stats' ][ 'failures' ] += $script[ 'stats' ][ 'failures' ]; 
                        $tmp_suite_stats[ 'stats' ][ 'skipped' ] += $script[ 'stats' ][ 'skipped' ]; 
                        $tmp_suite_stats[ 'stats' ][ 'errors' ] += $script[ 'stats' ][ 'errors' ]; 
                        $tmp_suite_stats[ 'stats' ][ 'passed' ] += $script[ 'stats' ][ 'tests_passed' ]; 
                        $tmp_suite_stats[ 'stats' ][ 'time' ] += $script[ 'stats' ][ 'time' ];
                        $tmp_suite_stats[ 'stats' ][ 'test_scripts' ]++;          

                    } 

                    $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ][ 'results' ] = $script;
                    unset( $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ][ 'results' ][ 'file' ] );

                    // Move stats up in the final JSON output, so the stats are easier to find
                    $array_pos = 5;
                    $this->report_summary[ 'test_suites' ][ $suite_key ] = array_slice( $this->report_summary[ 'test_suites' ][ $suite_key ], 0, $array_pos ) + 
                                                                            $tmp_suite_stats + 
                                                                            array_slice( $this->report_summary[ 'test_suites' ][ $suite_key ], $array_pos, 
                                                                                ( count( $this->report_summary[ 'test_suites' ][ $suite_key ] ) - 1 ) 
                                                                            );
                    
                    if( !$is_cli ) {
                        
                        foreach( $lines_of_code_as_html as $html_test_key => $test_method_as_html ) {

                            foreach( $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ][ 'results' ][ 'tests' ] as $report_test_key => $report_test_result ){

                                if( $html_test_key === $report_test_key ) {
                                    $this->report_summary[ 'test_suites' ][ $suite_key ][ 'test_scripts' ][ $classname ][ 'results' ][ 'tests' ][ $report_test_key ][ 'html' ] = $test_method_as_html[ $html_test_key ];
                                }

                            }

                        }

                    }

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
                
                unset( $testsuite_attributes[ 'warnings' ] );

                $testsuite_attributes[ 'class' ] = $testsuite_name; 
                $testsuite_attributes[ 'name' ] = explode( ' (', $this->NamePrettifier->prettifyTestClass( $testsuite_name ) )[0]; 
                $testsuite_attributes[ 'stats' ][ 'assertions' ] = (int) $testsuite_attributes[ 'assertions' ];
                $testsuite_attributes[ 'stats' ][ 'failures' ] = (int) $testsuite_attributes[ 'failures' ]; 
                $testsuite_attributes[ 'stats' ][ 'skipped' ] = (int) $testsuite_attributes[ 'skipped' ];
                $testsuite_attributes[ 'stats' ][ 'errors' ] = (int) $testsuite_attributes[ 'errors' ];
                $testsuite_attributes[ 'stats' ][ 'tests_passed' ] = 0;
                $testsuite_attributes[ 'stats' ][ 'time' ] = (float) $testsuite_attributes[ 'time' ]; 
                $testsuite_attributes[ 'stats' ][ 'tests' ] = (int) $testsuite_attributes[ 'tests' ];

                // Filter Errors and Failures 
                $error_count = $testsuite_attributes[ 'stats' ][ 'errors' ]; 
                $failure_count = $testsuite_attributes[ 'stats' ][ 'failures' ];
                $skipped_count = $testsuite_attributes[ 'stats' ][ 'skipped' ]; 

                $failed_tests = $error_count + $failure_count + $skipped_count; 
                $testsuite_attributes[ 'stats' ][ 'tests_passed' ] =  $testsuite_attributes[ 'stats' ][ 'tests' ] - $failed_tests;

                if( $testsuite_attributes[ 'stats' ][ 'tests_passed' ] === $testsuite_attributes[ 'stats' ][ 'tests' ] ) {
                    $testsuite_attributes[ 'stats' ][ 'script_passed' ] = true; 
                } else {
                    $testsuite_attributes[ 'stats' ][ 'script_passed' ] = false; 
                }

                unset( $testsuite_attributes[ 'assertions' ] );
                unset( $testsuite_attributes[ 'failures' ] );
                unset( $testsuite_attributes[ 'errors' ] );
                unset( $testsuite_attributes[ 'skipped' ] );
                unset( $testsuite_attributes[ 'tests' ] );
                unset( $testsuite_attributes[ 'time' ] );

                $this->results_manifest[ $testsuite_name ] = $testsuite_attributes;

            }

            if( $key == 'testcase' ) {

                $testcase_attributes = json_decode( json_encode( $value->attributes() ), true )[ '@attributes' ]; 
                $testcase_name = $testcase_attributes[ 'name' ];
                $test_passed = true; 

                unset( $testcase_attributes[ 'file' ] );
                unset( $testcase_attributes[ 'class' ] );
                unset( $testcase_attributes[ 'classname' ] );

                $testcase_attributes[ 'assertions' ] = (int) $testcase_attributes[ 'assertions' ];
                $testcase_attributes[ 'time' ] = (float) $testcase_attributes[ 'time' ]; 
                $testcase_attributes[ 'method' ] = $testcase_attributes[ 'name' ]; 
                $testcase_attributes[ 'name' ] = $this->NamePrettifier->prettifyTestMethod( $testcase_name );

                $this->results_manifest[ $testsuite_name ][ 'tests' ][ $testcase_name ] = $testcase_attributes;

                if( isset( $value->skipped ) ) {
                    $this->results_manifest[ $testsuite_name ][ 'tests' ][ $testcase_name ][ 'skipped' ] = $value->skipped;
                    $test_passed = false; 
                }

                if( isset( $value->error ) ) {
                    $this->results_manifest[ $testsuite_name ][ 'tests' ][ $testcase_name ][ 'error' ] = $value->error;
                    $test_passed = false; 
                }

                if( isset( $value->failure ) ) {
                    $this->results_manifest[ $testsuite_name ][ 'tests' ][ $testcase_name ][ 'failure' ] = $value->failure;
                    $test_passed = false; 
                }

                if( isset( $value->{'system-out'} ) ) {
                    $this->results_manifest[ $testsuite_name ][ 'tests' ][ $testcase_name ][ 'system_output' ] = $value->{'system-out'};
                }

                if( isset( $value->{'system-err'} ) ) {
                    $this->results_manifest[ $testsuite_name ][ 'tests' ][ $testcase_name ][ 'system_error' ] = $value->{'system-err'};
                }
                
                $this->results_manifest[ $testsuite_name ][ 'tests' ][ $testcase_name ][ 'test_passed' ] = $test_passed;
                
            } else {

                $this->recursively_manifest_test_results_xml( $value, $parent . "." . $key, $testsuite_name );

            }

        }
        
        return $this->results_manifest;

    }

    Public function run_php_tests() : array {
    
        $Filesystem = $this->Filesystem;
        $env_config = $this->Env_config->get_env_variables(); 
        $test_directory = $env_config[ 'directories' ][ 'tests' ][ 'shared' ]; 
    
        $execution_id = $this->report_summary[ 'summary' ][ 'execution_id' ];
        $test_manifest = array();

        // Relevant file paths
        $test_report_file = Path::normalize( $test_directory . 'reports/report_' . $execution_id . '.json' );
        $test_script_directory = Path::normalize( $test_directory . 'scripts/' ); 
        $test_result_directory = Path::normalize( $test_directory . 'test-results/' ); 
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
        $this->report_summary[ 'summary' ][ 'start_time' ] = $this->Env_config->get_datetime_now( 'Y-m-d h:m:s.u' );

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

        $this->report_summary[ 'summary' ][ 'end_time' ] = $this->Env_config->get_datetime_now( 'Y-m-d h:m:s.u' );
        $this->report_summary[ 'summary' ][ 'results_file' ] = $test_results_file;

        $stats = array_merge( $this->report_summary[ 'test_plan' ][ 'stats' ], $testsuite_stats );
        unset( $stats[ 'name' ] );
        unset( $stats[ 'warnings' ] );

        $stats[ 'test_scripts' ] = (int) $stats[ 'test_scripts' ];
        $stats[ 'assertions' ] = (int) $stats[ 'assertions' ];
        $stats[ 'failures' ] = (int) $stats[ 'failures' ]; 
        $stats[ 'skipped' ] = (int) $stats[ 'skipped' ];
        $stats[ 'errors' ] = (int) $stats[ 'errors' ];
        $stats[ 'time' ] = (float) $stats[ 'time' ]; 
        $stats[ 'tests' ] = (int) $stats[ 'tests' ];

        $this->report_summary[ 'test_plan' ][ 'stats' ] = $stats;

        if( (int) $stats[ 'failures' ] === 0 && (int) $stats[ 'errors' ] === 0 ) {
            $this->report_summary[ 'summary' ][ 'build_passed' ] = true;
            $this->system[ 'message' ] = "All tests passed";
        } else {
            $this->report_summary[ 'summary' ][ 'build_passed' ] = false;
            $this->system[ 'error' ] = true; 
            $this->system[ 'status' ] = 500;
            $this->system[ 'message' ] = "Failed tests detected";
        }

        $this->add_test_script_results_to_report( $test_results_xml );
        $Filesystem->touch( $test_report_file );
        $Filesystem->appendToFile( $test_report_file, json_encode( $this->report_summary, JSON_PRETTY_PRINT ), true );

        // When test is complete then move sempaphore to archive
        $Filesystem->rename( $test_config_file, $test_config_file_archive, true );

        return $this->Response->format_response( array(
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