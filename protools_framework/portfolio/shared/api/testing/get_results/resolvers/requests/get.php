<?php 
 
namespace Portfolio\Shared\api\Testing\Get_results\Resolvers\Requests;

use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator; 

class Get extends Api_resolver {

    Public function get_results( $request_body ) {
        
        $response = array();

        // $request_body[ 'execution_id' ]
        $test_report_directory = $this->env_var[ 'directories' ][ 'tests' ][ 'shared' ] . 'reports/'; 
        $test_report_filename = $test_report_directory . 'report_' . $request_body[ 'execution_id' ] . '.json';

        if( file_exists( $test_report_filename ) ) { 

            $test_report_file = fopen( $test_report_filename, 'r' ); 
            $test_report_json = fread( $test_report_file, filesize( $test_report_filename ) );
            fclose( $test_report_file );

            $json_validator = new Json_validator;
            
            $report = $this->api_response->on_error(
                'print_json_to_screen', 
                $json_validator->validate( $test_report_json )
            );
            
            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'error' => false, 
                'source' => get_class(), 
                'issue_id' => 'Get_testing_results_001',
                'data' => $report
            ));

        } else { 

            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'error' => false, 
                'source' => get_class(), 
                'issue_id' => 'Get_testing_results_002'
            ));

        }

        return $response;

    }

}