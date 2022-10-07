<?php 

namespace Bootstrap\Shared\Utilities\Classes;

use Bootstrap\Shared\Utilities\Classes\Api_response as Api_response; 
use \Dump_var;

class App_routes {

    Private $Response; 
    Private $app_path; 
    Private $route; 

    Public function __construct( array $routes, array $app_path, Api_response $Api_response ) {

        $this->Response = $Api_response;
        $this->app_path = $app_path;
        $this->routes = $routes;

    }

    Public function get_view() {

        $requested_module = $this->app_path[ 'module' ]; 
        $requested_view = $this->app_path[ 'view' ];

        if( 
            isset( $this->routes[ $this->app_path[ 'application' ] ][ 'modules' ] ) && 
            array_key_exists( $requested_module, $this->routes[ $this->app_path[ 'application' ] ][ 'modules' ] )
         ) {

            $modules = $this->routes[ $this->app_path[ 'application' ] ][ 'modules' ]; 
            $module = $modules[ $requested_module ]; 

            if( 
                isset( $module[ 'views' ] ) && 
                array_key_exists( $requested_view, $module[ 'views' ] ) 
            ) {

                $views = $module[ 'views' ];
                $view = $views[ $requested_view ];
    
                $response = $this->Response->get_response([
                    'status' => 200, 
                    'error' => false,
                    'issue_id' => 'app_routes_003',
                    'message' => 'Module found', 
                    'source' => get_class(), 
                    'data' => array( 'view_path' => $view )
                ]);

            } else {

                $response = $this->Response->get_response([
                    'status' => 404,
                    'issue_id' => 'app_routes_005',
                    'message' => 'View not found', 
                    'source' => get_class()
                ]);

            }

        } else {

            $response = $this->Response->get_response([
                'status' => 404,
                'issue_id' => 'app_routes_004',
                'message' => 'Module not found', 
                'source' => get_class()
            ]);

        }

        if( $response[ 'error' ] ) {
            $response[ 'message' ] = 'Page not found';   
        }

        return $response;

    }

    Public function get_app_include() {

        if( 
            isset( $this->routes[ $this->app_path[ 'application' ] ] ) &&
            isset( $this->routes[ $this->app_path[ 'application' ] ][ 'app_path' ] )
        ) {

            $app_routes = $this->routes[ $this->app_path[ 'application' ] ]; 

            $response = $this->Response->get_response([
                'status' => 200, 
                'error' => false,
                'issue_id' => 'app_routes_001',
                'message' => 'Application found', 
                'source' => get_class(), 
                'data' => $app_routes
            ]);

        } else {

            $response = $this->Response->get_response([
                'status' => 404,
                'issue_id' => 'app_routes_002',
                'message' => 'Application not found', 
                'source' => get_class()
            ]);

        }

        return $response;

    }

}