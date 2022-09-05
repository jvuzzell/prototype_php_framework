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

    protected $current_working_directory;

    function __construct( $starting_directory = '' ) {

        $this->manifest = array();
        $this->current_working_directory = ( $starting_directory !== '' ) ? $starting_directory : getcwd();

    }

    private function scan_directory( $needle, $haystack ) {
    
        $results = glob( $haystack . $needle );
        
        return ( sizeof( $results ) == 0 ) ? false : $results;

    }

    private function traverse_parent_directories( $needle, $search_args ) {
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
            if( !$search_args[ '$greedy_search' ] ) {
                break;
            }

        }

        // 4. Return file path or false to end user
        return $manifest;

    }

    private function traverse_children_directories( $needle, $search_args ) {
        
        $search_path = $search_args[ 'starting_directory' ]; // Initial search directory
        $all_subdirectories = glob( $search_path . '*/' ); 

        $manifest = array();
        
        // Recursive search
        foreach( $all_subdirectories as $$current_sub_directory ) { 

            $results = $this->scan_directory( $needle, $$current_sub_directory );
            
            if( $results !== false ) {
                $this->manifest[ realpath( $$current_sub_directory ) ] = $results;
            }
            
            $sub_directory_count = count( glob( $$current_sub_directory ) );

            if( $sub_directory_count !== 0 ) {

                $recursive_search_args = array_merge(
                    $search_args, 
                    array(
                        'starting_directory' => $$current_sub_directory
                    )
                );
                
                $this->traverse_children_directories( $needle, $recursive_search_args );
                
            } 
            
        }

        return $manifest;

    }

    private function search_for_file( $needle, $search_args ) {

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

    public function search( $find_these_files = [], $args = [] ) {

        $manifest_of_files_found = [];
        $paths_to_files_found = [];
    
        // 1. Process arguments
        $default_args = [
            'starting_directory'  => $this->current_working_directory,
            'search_direction'    => 'parent', // TODO: children directory search or bi-direction support
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
                                
            $this->manifest[ $find_these_files[ $i ] ] = $paths_to_files_found;
            
        }

        // 3. Return the file paths that were found
        $return_manifest = $this->manifest; 
        $this->manifest = array(); // cleanup
        return $return_manifest;
        
    }

    public function calculate_search_depth( $path_to_directory = '' ) {
        
        $slash = "/";

        if( strpos( $path_to_directory, $slash ) === false ) {
            $slash = "\\";
        }

        $depth_of_path = 1; 
        $depth_of_path = count( explode( $slash, $path_to_directory ) ); // will return 1 if delimiter not detected

        return $depth_of_path;

    }

}