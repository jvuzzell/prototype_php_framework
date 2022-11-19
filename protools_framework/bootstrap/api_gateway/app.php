<?php

header('Cache-Control: no-cache, must-revalidate');

require_once( SHARED_LIBRARY_DIR . '/utilities/functions/generate_random_string.php' );

use Bootstrap\Api_gateway\Library\Classes\Api_router as Api_router;
use Bootstrap\Api_gateway\Library\Classes\Api_handler as Api_handler;
use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response; 
use Bootstrap\Api_gateway\Library\Classes\Jwt_authorization as Jwt_authorization;
use Bootstrap\Api_gateway\Library\Classes\User_authorization;

$request_method = strtolower( ENV_VAR[ 'request' ][ 'method' ] ); 

/**
 * Validate the format of request data is JSON 
 * 
 * @todo Expand gateway to receive XML, Note: we'd need to check the content-type heard at that point
 * 
 */

if( $_SERVER[ 'REQUEST_METHOD' ] !== 'GET' ) {
    
    // Retrieve an array describing data received via client request; if the data is in a Json format
    // the data will be decoded and wrapped in a standardized API response format. This data is made
    // available to the resolver later in the program
    $request_data_validation = $Json_validator->validate( ENV_VAR[ 'request' ][ 'data' ] );

    if( 
        $request_data_validation[ 'status' ] !== 200 && // 200 - Ok; file good to go
        $request_data_validation[ 'status' ] !== 100    // 100 - Continue; blank file received keep processing
    ) {
        
        $validation = Api_response::format_response( array(
            'status' => 500, 
            'log' => true,
            'message' => $request_data_validation[ 'message' ], 
            'source' => 'api_gateway', 
            'issue_id' => 'api_gateway_001', 
            'data' => $request_data_validation
        ));

        // Closes program as well
        Api_response::print_json_to_screen( 
            $validation[ 'status' ], 
            $validation, 
            ENV_NAME 
        );

    }
    
    $endpoint_request_data = Api_response::format_response( array(
        'status' => 200,
        'error' => false,  
        'message' => ENV_VAR[ 'request' ][ 'data' ], 
        'source' => 'api_gateway', 
        'data' => $request_data_validation[ 'data' ]
    ));

} else {

    $endpoint_request_data = Api_response::format_response( array(
        'status' => 200,
        'error' => false,  
        'message' => 'GET request provided', 
        'source' => 'api_gateway', 
        'data' =>  ENV_VAR[ 'request' ][ 'data' ]
    ));

}

$request_data = ( $endpoint_request_data[ 'data' ] === NULL ) ? array() : $endpoint_request_data[ 'data' ];

/**
 * Route request
 */

$Api_router = new Api_router( $Environment_config, $Api_response, $Json_validator );
$api_route_meta = $Api_router->get_api_route_meta();

$Environment_config->set_api_route( $api_route_meta[ 'directory' ] );

/**
 * Supported Request Method?
 */

$is_request_method_supported = $Api_router->is_request_method_supported( 
    $request_method, 
    $api_route_meta[ 'request_methods' ] 
);

if( $is_request_method_supported[ 'error' ] ) {

    Api_response::route_to_custom_page( 
        $is_request_method_supported[ 'status' ], 
        $is_request_method_supported, 
        ERROR_PAGE, 
        ENV_NAME 
    );

} 

/**
 * Authorize User 
 */

$permission_type = $api_route_meta[ 'request_methods' ][ $request_method ][ 'permission_type' ];

if( $permission_type !== "none" ) {

    $is_auth_required = true; 

    $User_authorization = new User_authorization( $Environment_config, $Api_response, $Json_validator );

    $authorization_response = Api_response::on_error(
        'print_json_to_screen',
        $User_authorization->verify_user( $permission_type )
    );

}

/**
 * Initialize resolvers and schema
 */

$Api_handler = new Api_handler(
    $Environment_config, 
    $Api_response, 
    $Json_validator
);

$load_response = $Api_handler->load_endpoint(); 

if( $load_response[ 'error' ] ) {
    
    Api_response::print_json_to_screen( 
        $load_response[ 'status' ], 
        $load_response, 
        ENV_NAME
    );

}

/**
 * Validate user input
 */

$request_validation_response = $Api_handler->validate_client_request_body( 'requests', $request_data );

if( $request_validation_response[ 'error' ] ) {

    Api_response::print_json_to_screen( 
        $request_validation_response[ 'status' ], 
        $request_validation_response, 
        ENV_NAME
    );

}

/**
 * Resolve endpoint
 */

$endpoint_resolution = $Api_handler->resolve_endpoint( $request_validation_response[ 'data' ] ); 
Api_response::print_json_to_screen( $endpoint_resolution[ 'status' ], $endpoint_resolution, ENV_NAME );