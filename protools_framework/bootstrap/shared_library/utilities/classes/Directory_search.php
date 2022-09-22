<?php 

/** 
 * @package Directory Search
 * @version 1.0
 * 
 * @author Joshua Uzzell 
 *
 * Purpose: 
 * Traverse parent or children of a given directoy for a folder or a file
 *  
 * Private Methods:
 *      @method scan_directory
 *      @method traverse_parent_directories
 *      @method traverse_children_directories
 *      @method search_for_file
 * 
 * Public Methods: 
 *      @method search
 *      @method calculate_search_depth
 */


class Directory_search {

    private $manifest = array();
    Private static $instance = null; 

    Private function scan_directory( $needle, $haystack ) {
        
        $results = glob( $haystack . $needle );
        
        return ( sizeof( $results ) == 0 ) ? false : $results;

    }

    Private function traverse_parent_directories( $needle, $search_args ) {

        $search_path = $search_args[ 'starting_directory' ]; // Initial search directory
        $search_directories  = explode( '/', $search_path );
        $search_dir = '';
        $manifest = array();

        for( $i = 0; $i < $search_args[ 'max_search_depth' ]; $i++ ) {
            
            $relative_pointer  = ( $i == 0 ) ? './' : '../'; // Search current directory then all relative parents
            
            // 1. Determine parent directory
            $search_dir = $relative_pointer . $search_dir;
            
            // 2. Search 
            $results = $this->scan_directory( $needle, $search_dir );
            
            if( $results !== false ) {
                $manifest[ realpath( $search_dir ) ] = $results;
            }
    
            // 3. We are only looking for the first instance of the file upstream
            //    so end loop once found 
            if( !$search_args[ 'greedy_search' ] ) {
                break;
            }

        }

        // 4. Return file path or false to end user
        return $manifest;

    }

    Private function traverse_children_directories( $needle, $search_args ) {
        
        $search_path = $search_args[ 'starting_directory' ]; // Initial search directory
        $all_subdirectories = glob( $search_path . '*/' );
        
        // Recursive search
        foreach( $all_subdirectories as $current_sub_directory ) { 

            $results = $this->scan_directory( $needle, $current_sub_directory );

            if( $results !== false ) {
                $this->manifest[ realpath( $current_sub_directory ) ] = $results;
            }
            
            $sub_directory_count = count( glob( $current_sub_directory ) );

            if( $sub_directory_count !== 0 ) {

                $recursive_search_args = array_merge(
                    $search_args, 
                    array(
                        'starting_directory' => $current_sub_directory
                    )
                );
                
                $this->traverse_children_directories( $needle, $recursive_search_args );
                
            } 
            
        }

        return $this->manifest;

    }

    Private function search_for_file( $needle, $search_args ) {

        switch( $search_args[ 'search_direction' ] ) {

            case 'parent' :

                $paths_to_files_found = $this->traverse_parent_directories( $needle, $search_args );

                break;

            case 'children' :

                $paths_to_files_found = $this->traverse_children_directories( $needle, $search_args );

                break;

        }

        return $paths_to_files_found;

    }

    Private function calculate_search_depth( $path_to_directory = '' ) {
        
        $slash = "/";

        if( strpos( $path_to_directory, $slash ) === false ) {
            $slash = "\\";
        }

        $depth_of_path = 1; 
        $depth_of_path = count( explode( $slash, $path_to_directory ) ); // will return 1 if delimiter not detected

        return $depth_of_path;

    }

    Public function search( $find_these_files = [], $args = [] ) {

        $manifest = array();
        $paths_to_files_found = array();
        $files_found = false; 

        // 1. Process arguments
        $default_args = [
            'starting_directory'  => getcwd(),
            'search_direction'    => 'parent', // TODO: children or parent
            'greedy_search'       => true, 
            'include_files'       => false,
            'greedy_file_include'  => false, 
            'max_search_depth'     => -1
        ];
        
        $search_args = array_merge( $default_args, $args );

        // Determine maximum depth if $search_args[ 'max_search_depth' ] = -1
        if( $search_args[ 'max_search_depth' ] == -1 && $search_args[ 'search_direction' ] == 'parent' ) {
            $search_args[ 'max_search_depth' ] = $this->calculate_search_depth( $search_args[ 'starting_directory' ] );
        }

        // 2. Are we searching parent, children, or both
        for( $i = 0; $i < count( $find_these_files ); $i++ ) {

            $paths_to_files_found = $this->search_for_file( 
                                        $find_these_files[ $i ], 
                                        $search_args
                                    );
            
            if( !empty( $paths_to_files_found ) ) {
                $files_found = true;
            }

            $manifest[ $find_these_files[ $i ] ] = $paths_to_files_found;
            
        }

        if( $files_found ) {

            $response = $this->response_helper([
                'error'   => 'false', 
                'status'  => 200, 
                'issue_id'=> 'directory_search_001',
                'message' => 'Success. File found.', 
                'data'    => $manifest
            ]);

        } else {
            
            $response = $this->response_helper([
                'error'  => 'false', 
                'status' => 404, 
                'issue_id'=> 'directory_search_002',
                'message' => 'File not found'
            ]);

        }

        return $response;

    }

    Public function cwd() {

        return getcwd();

    }

    /** 
     * API response helper
     */

    Private function response_helper( $args = array() ) {

        $default_args = array(
            'error'       => true,
            'issue_id'    => 'directory_search_1001', // string 
            'message'     => '', // string
            'data'        => array(),
            'status'      => 500, 
            'log'         => false,
            'private'     => true,
            'continue'    => true,
            'email'       => false,
            'source'      => get_class( $this )
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