<?php

class Response_helper {

    public static function compileResponseMsg( $message="", $data=array(), $source="ResponseHelper", $status=200 )
    {
        if( empty( $data ) ) {
            $status = 204; 
            $message = "Request succeeded, but no content returned";
        }
        return self::compileMsg(
            $error=FALSE,
            $issue_id="ResponseHelper_00001",
            $message=( strlen($message) > 0 && empty($data) )?$message:"Success",
            $data=$data, 
            $status_code=( empty($data) ) ? 201 : 200, 
            $log=FALSE, 
            $private=FALSE, 
            $continue=TRUE, 
            $email=FALSE, 
            $source=$source
        );
    }
    public static function compileSuccessMsg($issue_id="ResponseHelper_00002", $message="Success",$data=array(), $status_code=200, $log=false, $private=false, $continue=true, $email=false, $source="ResponseHelper"){
        return self::compileMsg(
            $error=FALSE,
            $issue_id=$issue_id, 
            $message=$message,
            $data=$data, 
            $status_code=$status_code, 
            $log=$log, 
            $private=$private, 
            $continue=$continue, 
            $email=$email, 
            $source=$source
        );
    }

    public static function compileErrorMsg($issue_id="ResponseHelper_00003", $message="",$data=array(), $status_code=500, $log=false, $private=false, $continue=true, $email=false, $source="ResponseHelper") {
        return self::compileMsg(
            $error=TRUE,
            $issue_id=$issue_id, 
            $message=$message,
            $data=$data, 
            $status_code=$status_code, 
            $log=$log, 
            $private=$private, 
            $continue=$continue, 
            $email=$email, 
            $source=$source
        );

    }

    public static function compileMsg($error=false, $issue_id="", $message="",$data=array(), $status_code=500, $log=false, $private=false, $continue=false, $email=false, $source="ResponseHelper") {
        
        if(empty($data) && $error ) {
            $data = array( 
                'display_message' => $message
            );
        }

        $results = array( 
            'status' => $status_code,
            'error'  => $error,
            'system' => array(
                'issue_id' => $issue_id,
                'log'      => $log, 
                'private'  => $private, 
                'continue' => $continue, 
                'email'    => $email
            ),
            'source'  => $source,
            'message' => strlen( $message ) > 0 ? $message : " Unknown Error". "\n", 
            'data' => $data
        );

        return $results;

    }

}