<?php 

use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\App_settings as App_settings;

$app_settings_response = $Json_validator->validate( file_get_contents( APP_DIR . 'app_settings.json' ) );

if( $app_settings_response[ 'error' ] ) {
    Api_response::route_to_custom_page( $app_settings_response[ 'status' ], $app_settings_response, ERROR_PAGE, ENV_NAME );
}

$App_settings = new App_settings( $app_settings_response[ 'data' ], new Api_response );