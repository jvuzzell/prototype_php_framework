<?php 
 
namespace Portfolio\Shared\api\Jwt\Create_jwt\Resolvers\Requests;

use Bootstrap\Api_gateway\Library\Classes\Jwt_management as Jwt_mgmt; 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 
use Dump_var;

class Post extends Api_resolver {

    Public function create_jwt( array $request_body ) {

        $user = $request_body[ 'username' ];
        $request_time = $this->env_var[ 'request' ][ 'request_time' ]; 
        $expiration_in_days = $request_body[ 'expiration' ];
        $ttl = $request_time + ( $expiration_in_days * 24 * 60 * 60 ); 

        $claims = array(
            "iss"  => $this->env_var[ 'request' ][ 'headers' ][ 'Host' ],
            "aud"  => $this->env_var[ 'request' ][ 'headers' ][ 'Host' ], 
            "iat"  => $request_time, 
            "user" => $user, 
            "exp"  => $ttl
        );

        $Jwt_mgmt = new Jwt_mgmt( $this->env_var, $this->api_response );
        $jwt = $Jwt_mgmt->encode_jwt( $claims ); 
        
        return $this->api_response->format_response( array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class( $this ),
            'issue_id' => 'post_create_jwt_001',
            'data' => array(
                'jwt' => $jwt
            )
        ));

    }

}