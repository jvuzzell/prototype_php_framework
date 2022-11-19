<?php 
 
namespace Portfolio\Shared\api\Sample_api\Resolvers\Responses;

use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 

class Post extends Api_resolver {

    Public function sample_response_callback( $request_body ) {

        return $this->api_response->format_response( array(
                'status' => 200, 
                'error' => false, 
                'message' => 'Response successful',
                'source' => get_class( $this ),
                'issue_id' => 'jira_ticket_001', 
                'public_data' => true,
                'data' => $request_body
            ));

    }

}