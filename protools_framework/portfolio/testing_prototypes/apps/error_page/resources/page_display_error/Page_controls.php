<?php 

namespace Portfolio\Testing_Prototypes\Apps\Error_Page\Resources\Page_Display_Error;

use Bootstrap\Shared\Controllers\Page_controls as Bootstrap_page_controls;

class Page_controls extends Bootstrap_page_controls {

    Public function get_system_error_from_session( $session ) {
        
        if( isset( $session[ 'last_system_error' ] ) ) { 

            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'error' => false, 
                'source' => get_class(), 
                'issue_id' => 'page_display_error_001', 
                'data' => $session[ 'last_system_error' ]
            ));

        } else {
             
            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'source' => get_class(), 
                'issue_id' => 'page_display_error_001', 
                'message' => 'No errors Detected'
            ));

        }

        return $response; 

    }
    
}