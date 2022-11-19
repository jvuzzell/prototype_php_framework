<?php 
 
namespace Portfolio\Shared\api\Jwt\Create_jwt\Resolvers\Responses;
 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 
use Dump_var;

class Post extends Api_resolver {

    Public function response( array $resolved_request = [] ) {

        return $this->api_response->format_response( array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class( $this ),
            'issue_id' => 'post_create_jwt_001',
            'message' => 'JWT created',
            'public_data' => true,
            'data' => $resolved_request
        ));

    }

}