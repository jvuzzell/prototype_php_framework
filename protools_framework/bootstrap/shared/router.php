<?php 

use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\App_routes as App_routes;

// Route to Test Results Application
$filename_site_routes = ENV_VAR[ 'directories' ][ 'tmp' ][ 'site_specific' ] . 'cache/site_routes.json'; 
if( file_exists( $filename_site_routes  ) ) {
    $app_routes_response = $Json_validator->validate( file_get_contents( $filename_site_routes ) );
} else {

    $response = Api_response::format_response([
        'status' => 404, 
        'message' => 'Page not found', 
        'source' => 'bootstrap_router_', 
        'issue_id' => 'bootstrap_router__001'
    ]); 

    Api_response::route_to_custom_page( $response[ 'status' ], $response, ERROR_PAGE, ENV_NAME );
    
}

if( $app_routes_response[ 'error' ] ) {
    Api_response::route_to_custom_page( $app_routes_response[ 'status' ], $app_routes_response, ERROR_PAGE, ENV_NAME );
}

$App_routes = new App_routes( $app_routes_response[ 'data' ], ENV_VAR[ 'app_path' ], new Api_response );
$routes_response = $App_routes->get_view();

if( $routes_response[ 'error' ] ) {
    Api_response::route_to_custom_page( $routes_response[ 'status' ], $routes_response, ERROR_PAGE, ENV_NAME );
} else {
    $Environment_config->set_view_route( SITE_PORTFOLIO_DIR . 'apps/' . $routes_response[ 'data' ][ 'view_path' ] );
    require_once( SITE_PORTFOLIO_DIR . 'apps/' . $routes_response[ 'data' ][ 'view_path' ] );
}

exit; 