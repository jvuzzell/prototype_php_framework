<?php 
 
namespace Portfolio\Shared\api\Sample_api\Resolvers\Requests;
 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 

class Post extends Api_resolver {

    Public function sample_request_callback( $request_body ) {

        // $this->clients 
        // $this->env_var
        // $this->json_validator
        // $this->api_response
        // $this->schema_settings
        // $this->schema_fields 
        
        return $this->api_response->format_response( array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class( $this ),
            'issue_id' => 'jira_ticket_001',
            'data' => array( "sampleField1_response" => $request_body[ 'sampleField1' ] )
        ));

    }

}