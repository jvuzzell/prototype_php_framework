<?php
	
$Execute_local_api->execute(  
    $_REQUEST[ 'api_path' ], 
    $_REQUEST[ 'api_method' ], 
    json_decode( urldecode( file_get_contents( 'php://input' ) ), true )
);