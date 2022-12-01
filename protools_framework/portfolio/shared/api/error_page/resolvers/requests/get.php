<?php 
 
namespace Portfolio\Shared\Api\Error_page\Resolvers\Requests;
 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 

class Get extends Api_resolver {

    Public function retrieve_errors_from_session( $request_body ) {

        if( isset( $_SESSION[ 'last_system_error' ] ) ) { 

            $response = $this->api_response->format_response(array(
                'status' => 500, 
                'error' => false, 
                'source' => get_class(), 
                'issue_id' => 'api_error_page_001', 
                'data' => $_SESSION[ 'last_system_error' ]
            ));

        } else {
             
            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'error' => false,
                'source' => get_class(), 
                'issue_id' => 'api_error_page_002', 
                'message' => 'No errors Detected'
            ));

        }

        unset( $_SESSION[ 'last_system_error' ] );

        return $response;

    }

}