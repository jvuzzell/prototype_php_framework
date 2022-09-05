<?php

class Response_helper {

    public static function compile_msg( $args ) {

        $default_args = array(
            'error'       => true,
            'issue_id'    => 'response_helper_001', // string 
            'message'     => '', // string
            'data'        => array(),
            'status_code' => 500, 
            'log'         => false,
            'private'     => true,
            'continue'    => true,
            'email'       => false,
            'source'      => get_class( $this )
        ); 

        $args = array_merge( $args, $default_args );

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