<?php

namespace Bootstrap\Shared\Utilities\Classes\Static;

class Response_helper {

    public static function compile_response( $args ) {

        $default_args = array(
            'error'    => true,
            'issue_id' => 'response_helper_001', // string 
            'message'  => '', // string
            'data'     => array(),
            'status'   => 500, 
            'log'      => false,
            'private'  => true,
            'continue' => true,
            'email'    => false,
            'source'   => get_class()
        ); 

        $args = array_merge( $default_args, $args );

        return array( 
            'status' => $args[ 'status' ],
            'error'  => $args[ 'error' ],
            'system' => array(
                'issue_id' => $args[ 'issue_id' ],
                'log'      => $args[ 'log' ], 
                'private'  => $args[ 'private' ], 
                'continue' => $args[ 'continue' ], 
                'email'    => $args[ 'email' ]
            ),
            'source'  => $args[ 'source' ],
            'message' => $args[ 'message' ], 
            'data' => $args[ 'data' ]
        );

    }

}