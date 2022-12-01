<?php 
 
namespace Portfolio\Shared\Api\Error_page\Resolvers\Responses;

use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 

class Get extends Api_resolver {

    Public function output_error( $request_body ) {

        if( sizeof( $request_body ) === 0 ) { 

            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'error' => false,
                'issue_id' => 'api_error_page_003',
                'source' => get_class(),
                'message' => 'No errors detected'
            ));

        } else {
             
            $response = $this->api_response->format_response(array(
                'status' => 500, 
                'error' => true, 
                'log' => true,
                'source' => get_class(), 
                'issue_id' => 'api_error_page_004', 
                'message' => 'Error detected - Please try again. If issue continues, please contact the site administrator.',
                'data' => array( 'last_system_error' => $request_body )
            ));

        }

        return $response;

    }

}