<?php 

namespace Bootstrap\Shared\Utilities\Classes;

use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response; 

class App_routes {

    Private $Response; 
    Private $app_path;
    Private $routes; 

    Public function __construct( array $routes, array $app_path, Api_response $Api_response ) {

        $this->Response = $Api_response;
        $this->app_path = $app_path;
        $this->routes = $routes;

    }

    Public function get_view() {

        $requested_resource = $this->app_path[ 'resource' ]; 
        $requested_view = $this->app_path[ 'view' ];

        if( 
            isset( $this->routes[ $this->app_path[ 'application' ] ][ 'resources' ] ) && 
            array_key_exists( $requested_resource, $this->routes[ $this->app_path[ 'application' ] ][ 'resources' ] )
         ) {

            $resources = $this->routes[ $this->app_path[ 'application' ] ][ 'resources' ]; 
            $resource = $resources[ $requested_resource ]; 

            if( 
                isset( $resource[ 'views' ] ) && 
                array_key_exists( $requested_view, $resource[ 'views' ] ) 
            ) {

                $views = $resource[ 'views' ];
                $view = $views[ $requested_view ];
    
                $response = $this->Response->format_response([
                    'status' => 200, 
                    'error' => false,
                    'issue_id' => 'app_routes_003',
                    'message' => 'Resource found', 
                    'source' => get_class(), 
                    'data' => array( 'view_path' => $view )
                ]);

            } else {

                $response = $this->Response->format_response([
                    'status' => 404,
                    'issue_id' => 'app_routes_005',
                    'message' => 'View not found', 
                    'log' => true, 
                    'private' => true,
                    'source' => get_class()
                ]);

            }

        } else {

            $response = $this->Response->format_response([
                'status' => 404,
                'issue_id' => 'app_routes_004',
                'log' => true, 
                'private' => true,
                'message' => 'Resource not found', 
                'source' => get_class()
            ]);

        }

        if( $response[ 'error' ] ) {
            $response[ 'system' ][ 'log' ] = true; 
            $response[ 'system' ][ 'private' ] = true;
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

            $response = $this->Response->format_response([
                'status' => 200, 
                'error' => false,
                'issue_id' => 'app_routes_001',
                'message' => 'Application found', 
                'source' => get_class(), 
                'data' => $app_routes
            ]);

        } else {

            $response = $this->Response->format_response([
                'status' => 404,
                'issue_id' => 'app_routes_002',
                'log' => true, 
                'private' => true,
                'message' => 'Application not found', 
                'source' => get_class()
            ]);

        }

        return $response;

    }

}