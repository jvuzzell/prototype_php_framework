<?php 
 
namespace Portfolio\Shared\api\Testing\Get_results\Resolvers\Responses;

use Bootstrap\Api_gateway\Library\Classes\Jwt_management as Jwt_mgmt; 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 


class Get extends Api_resolver {

    Public function get_results( $response_body ) {

        if( sizeof( $response_body ) === 0 ) {

            return $this->api_response->format_response(array(
                'status' => 404, 
                'source' => get_class(), 
                'issue_id' => 'Get_testing_results',
                'message' => 'Test results not found',
                'log' => true,
                'public_data' => false
            ));

        }

        return $this->api_response->format_response(array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class(), 
            'issue_id' => 'Get_testing_results',
            'message' => 'Test results found',
            'public_data' => true,
            'data' => $response_body
        ));

    }

}