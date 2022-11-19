<?php 
 
namespace Portfolio\Shared\api\Sample_api\Resolvers\Requests;
 
use Bootstrap\Api_gateway\Library\Classes\Api_resolver as Api_resolver; 
use Dump_var;

class Get extends Api_resolver {

    Public function sample_request_callback() {
        Dump_var::print( 'Called - sample request callback' );
    }

}