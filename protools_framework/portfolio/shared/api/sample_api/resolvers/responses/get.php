<?php 
 
namespace Portfolio\Shared\api\Sample_api\Resolvers\Responses;
 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 
use Dump_var;

class Get extends Api_resolver {

    Public function sample_request_callback( $request_body ) {
        Dump_var::print( 'Called - sample request callback' );

        return $this->api_response->format_response( array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class( $this ),
            'issue_id' => 'jira_ticket_001',
            'data' => $request_body
        ));

    }

}