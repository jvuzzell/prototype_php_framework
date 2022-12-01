<?php 
 
namespace Portfolio\Shared\api\Jwt\Get_claims\Resolvers\Responses;
 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 


class Post extends Api_resolver {

    Public function response( array $resolved_request = [] ) {

        return $this->api_response->format_response( array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class( $this ),
            'issue_id' => 'post_jwt_get_claims_002',
            'message' => 'Claims found',
            'public_data' => true,
            'data' => $resolved_request
        ));

    }

}