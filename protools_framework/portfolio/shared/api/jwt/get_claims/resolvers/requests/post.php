<?php 
 
namespace Portfolio\Shared\api\Jwt\Get_claims\Resolvers\Requests;

use Bootstrap\Api_gateway\Library\Classes\Jwt_management as Jwt_mgmt; 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 


class Post extends Api_resolver {

    Public function get_claims( array $request_body ) {

        $jwt = $request_body[ 'jwt' ];
        $Jwt_mgmt = new Jwt_mgmt( $this->env_var, $this->api_response );

        $claims = $this->api_response->on_error(
            'print_json_to_screen', 
            $Jwt_mgmt->get_claims( $jwt )
        );

        return $this->api_response->format_response( array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class( $this ),
            'issue_id' => 'post_jwt_get_claims_001',
            'data' => array(
                'claims' => array(
                    'iss' => $claims->iss, 
                    'aud' => $claims->aud, 
                    'iat' => $claims->iat, 
                    'user' => $claims->user, 
                    'exp' => $claims->exp
                )
            )
        ));

    }

}