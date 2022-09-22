<?php 

class Test_router {

    Private $args = array();

    Public static function get_available_routes( $plan_path = '' ) {
        
        return array();

    }

    Public static function get_test_plan( array $client_routes ) {

        Dump_var::print( $client_routes );
        
    }


    /** 
     * API response helper
     */

    Private static function response_helper( $args = array() ) {

        $default_args = array(
            'error'       => true,
            'issue_id'    => 'test_router_1001', // string 
            'message'     => '', // string
            'data'        => array(),
            'status'      => 500, 
            'log'         => false,
            'private'     => true,
            'continue'    => true,
            'email'       => false,
            'source'      => get_class()
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