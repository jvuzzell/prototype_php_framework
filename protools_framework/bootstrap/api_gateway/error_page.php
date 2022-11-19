<?php 

http_response_code( $response_data[ 'status' ] );
header( 'content-type: application/json' ); 
header('Cache-Control: no-cache, must-revalidate');
echo( json_encode( $response_data, JSON_PRETTY_PRINT ) ); 
