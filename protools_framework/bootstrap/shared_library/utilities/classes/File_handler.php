<?php 

/** 
 * @package File Handler
 * @version 1.0
 * 
 * @author Joshua Uzzell 
 * 
 * Purpose:
 *      Read and save data to files 
 * 
 * Private Variables: 
 *      @var array open_files
 * 
 * Private Methods:
 *      @method response_helper
 *      @method manifest_open_file
 *      @method format_bytes
 * 
 * Public Methods:
 *      @method get_file_info
 *      @method fsave
 *      @method fread
 *      @method fopen
 *      @method fclose 
 *      @method save_csv
 *      @method read_csv
 *      @method exists
 *      @method delete_file
 *      @method move_file
 *      @method copy_file
 * 
 */
 
class File_handler{

    private $open_files = array(); 

    /**
     * Manifest open files 
     * 
     * @param string $path
     * @param string $file_content
     */
    
    Private function manifest_open_files( $path, $file_content ) {

        $this->open_files[ $path ] = $file_content; 
        
    }

    /**
     * @param integer $size  value in bytes to be formatted
     * 
     * @return string
     * 
     */
    
     Private function format_bytes( int $size ){
        $base = log($size, 1024);
        $suffixes = array('', 'KB', 'MB', 'GB', 'TB');  

        return round(pow(1024, $base-floor($base)), 2).''.$suffixes[floor($base)];
    }

    /** 
     * API response helper
     */

    Private function response_helper( $args = array() ) {

        $default_args = array(
            'error'       => true,
            'issue_id'    => 'file_handler_1001', // string 
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

    Public function get_file_info( $path ) {
        
        clearstatcache();

        $error_messages = array();

        if( !file_exists( $path ) ) {

            return $this->response_helper(
                array(
                    'status'      => 404, 
                    'issue_id'    => 'file_handler_1002', 
                    'message'     => 'File not found. Path: ' . $path
                )
            );

        } 

        try {
            
            $file_stats = stat( $path );

        } catch( ErrorException $e ) {

            return $this->response_helper(
                array(
                    'status'      => 500, 
                    'issue_id'    => 'file_handler_1005', 
                    'message'     => 'Stats from file could not be retrieved. Path: ' . $path, 
                    'data'        => array(
                        'error_message' => $e->getMessage()
                    )
                )
            );

        }

        try {

            $mime_type = mime_content_type( $path );

        } catch( ErrorExecption $e ) {

            return $this->response_helper(
                array(
                    'status'      => 500, 
                    'issue_id'    => 'file_handler_1003', 
                    'message'     => 'Mime type of file could not be retrieved. Path: ' . $path, 
                    'data'        => array(
                        'error_message' => $e->getMessage()
                    )
                )
            );

        }
        
        $path_info     = path_info( $path );
        $real_path     = realpath( $path );
        
        $response = $this->response_helper(
            array(
                'error'       => false,
                'status'      => 200, 
                'issue_id'    => 'file_handler_1004', 
                'message'     => 'File info successfully retrieved. Path: ' . $path,
                'data'        => array(
                    'last_modified'        => $file_stats[ 'mtime' ],
                    'last_modified_string' => date( 'r', $file_stats[ 'mtime' ] ),
                    'last_access'          => $file_stats[ 'atime' ], 
                    'last_access_string'   => date( 'r', $file_stats[ 'atime' ] ),
                    'file_size'            => $file_stats[ 'size' ],
                    'file_size_string'     => $this->format_bytes( $file_stats[ 'size' ] ), 
                    'mimetype'             => $mime_type, 
                    'real_path'            => $real_path,
                    'directory_name'       => $path_info[ 'dirname' ], 
                    'basename'             => $path_info[ 'basename' ], 
                    'filename'             => $path_info[ 'filename' ],
                    'extension'            => $path_info[ 'extension' ]
                )
            )
        ); 

        return $response;
    }

    /**
     * Save file
     * 
     * @param string  $content
     * @param string  $filename
     * @param string  $location
     * @param boolean $append
     * 
     * @return array  $response 
     */
    
    Public function fsave( $content, $filename, $location, $append ) {

        $response = array();
	    $flag = "w";
		$success = false;
	
		( $append === 0 ) ? $flag = "w" : $flag = "a";
		 
        if( !file_exists( $location ) ) {

            mkdir( $location, 0655, true );
            
        } 
        
        if ( $file_content = fopen( $location.$filename, $flag ) ) {

	     	$response = fwrite( $file_content, $content );
            fclose( $file_content );

        } else { 

            $response = $this->response_helper( 
                array(
                    'status'      => 501,
                    'issue_id'    => 'file_handler_001',
                    'message'     => 'Could not open file. Path: ' . $location.$filename
                ) 
            );
            
        }

        return $response;
    }

    /**
     * Read file 
     * 
     * @param string $path
     * 
     * @return array
     */

    Public function fread( $path ) {

        $response = array();

        if( file_exists( $path ) ) {

            $file_content = fopen( $path, "r" );
            if( filesize( $path ) === 0 ) {

                $response = $this->response_helper(
                    array(
                        'error'       => false,
                        'status'      => 204,
                        'issue_id'    => 'file_handler_002',
                        'message'     => 'File empty, nothing to read. Path: ' . $path
                    )
                );

            } else {

                try {

                    $response = $this->response_helper(
                        array(
                            'error'       => false,
                            'status'      => 200,
                            'issue_id'    => 'file_handler_003',
                            'message'     => 'File read successfully. Path: ' . $path, 
                            'data'        => fread( $file_content, filesize( $path ) )
                        )
                    );

                } catch( Exception $e ) {

                    $response = $this->response_helper(
                        array(
                            'status'      => 500,
                            'issue_id'    => 'file_handler_004',
                            'message'     => 'Error occured when reading file. Path: ' . $path
                        )
                    );

                }
	            
                
            }
        } else {
        
            $response = $this->response_helper(
                array(
                    'status'      => 404,
                    'issue_id'    => 'file_handler_005',
                    'message'     => 'File not found. Path: ' . $path
                )
            );

        }

        if( isset( $file_content ) ) {
            fclose( $file_content );
        }

        return $response; 
    }

    /**
     * Open file 
     * 
     * @param string $path
     * 
     * @return array
     */

    Public function fopen( $path ) {

        $response = array();

        if( file_exists( $path ) ) {

            $file_content = fopen( $path, "r" );
            if( filesize( $path ) === 0 ) {

                $response = $this->response_helper(
                    array(
                        'error'       => false,
                        'status'      => 204,
                        'issue_id'    => 'file_handler_006',
                        'message'     => 'File empty, nothing to read. Path: ' . $path
                    )
                );

            } else {

	            $this->manifest_open_files( $path, $file_content );

                $response = $this->response_helper(
                    array(
                        'error'       => false,
                        'status'      => 200,
                        'issue_id'    => 'file_handler_007',
                        'message'     => 'File opened. Path: ' . $path, 
                        'data'        => $file_content
                    )
                );

            } 

        } else {

            $response = $this->response_helper(
                array(
                    'status'      => 404, 
                    'issue_id'    => 'file_handler_008', 
                    'message'     => 'File not found. Path: ' . $path
                )
            );

        }

        return $response; 
    }

    /**
     * Close file
     * 
     * @param string $path
     * 
     * @param array $response 
     */

    Public function fclose( $path ) {

        $response = array();

        if( isset( $this->open_files[ $path ] ) ) {

            unset( $this->open_files[ $path ] ); 
            fclose( $this->open_files[ $path ] );

            $response = $this->response_helper(
                array(
                    'error'       => false,
                    'status'      => 404, 
                    'issue_id'    => 'file_handler_009', 
                    'message' => 'File closed. Path: ' . $path
                )
            );

        } else {

            $response = $this->response_helper(
                array(
                    'error'       => false,
                    'status'      => 404, 
                    'issue_id'    => 'file_handler_010', 
                    'message' => 'File not found. Path: ' . $path
                )
            );

        }

        return $response;
    }

    /**
     * Read CSV
     * 
     * @param string $path
     * 
     * @param array $response
     */

    Public function read_csv( $path ) {
        
        $response = array();

        if( file_exists( $path ) ) {

            $file_content = fopen( $path, "r" );

            if( filesize( $path ) === 0 ) {

                $response = $this->response_helper(
                    array(
                        'error'       => false,
                        'status'      => 204, 
                        'issue_id'    => 'file_handler_011', 
                        'message'     => 'File empty. Path: ' . $path, 
                        'data'        => null
                    )
                );

            }  else {

                $response = $this->response_helper(
                    array(
                        'error'       => false,
                        'status'      => 200, 
                        'issue_id'    => 'file_handler_012', 
                        'message'     => 'Success. Path: ' . $path, 
                        'data'        => array_map( 'str_getcsv', file( $path ) )
                    )
                );
        
            } 

            fclose( $file_content );
            
        } else {

            $response = $this->response_helper(
                array(
                    'status'      => 404, 
                    'issue_id'    => 'file_handler_013', 
                    'message'     => 'File not found. Path: ' . $path,
                    'data'        => array_map( 'str_getcsv', file( $path ) )
                )
            );
        
        }
   
        return $response;       

    }

    /**
     * Save CSV 
     * 
     * @param  array $data
     * @param  string $path
     * 
     * @return array
     */

    Public function save_csv( $data, $path ) {

        try {

            $file_content = fopen( $path, "w" );

        } catch( exception $e ) {

            $response = $this->response_helper(
                array(
                    'status'      => 500, 
                    'issue_id'    => 'file_handler_014', 
                    'message'     => 'Could not open file to be saved. Path: ' . $path,
                )
            );

        }
        
        try {

            foreach ( $data as $row ) {
                fputcsv( $file_content, $row );
            }

        } catch( exception $e ) {

            $response = $this->response_helper(
                array(
                    'status'      => 500, 
                    'issue_id'    => 'file_handler_015', 
                    'message'     => 'Error writing data to the file. Path: ' . $path,
                )
            );

        }

        $response = $this->response_helper(
            array(
                'error'       => false,
                'status'      => 200, 
                'issue_id'    => 'file_handler_016', 
                'message'     => 'File created. Path: ' . $path
            )
        );

        fclose( $file_content );

        return $response;
    }

    /**
     * Delete the file at a given path.
     *
     * @param  string|array  $paths
     * @return array
     */

    Public function delete_file( $paths ) {

        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        try {
            if (! @unlink($path)) {
                $success = false;
            }
        } catch (ErrorException $e) {
            $success = false;
            $message = $e->getMessage();
        }
    

        if( $success ) {
            
            $response = $this->response_helper(
                array(
                    'error'       => false,
                    'status'      => 200, 
                    'issue_id'    => 'file_handler_017', 
                    'message'     => 'File(s) deleted. Path: ' . $path
                )
            );

        } else {

            $response = $this->response_helper(
                array(
                    'status'      => 500, 
                    'issue_id'    => 'file_handler_018', 
                    'message'     => 'Files failed to be deleted. Path: ' . $path . ';\n $message'
                )
            );

        }

        return $response;

    }

    /**
     * Move a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return array
     */

    Public function move_file( $path, $target ) {

        $success = true; 

        if( rename( $path, $target ) ) {
            
            $response = $this->response_helper(
                array(
                    'error'       => false,
                    'status'      => 200, 
                    'issue_id'    => 'file_handler_019', 
                    'message'     => 'File moved. Path: ' . $path
                )
            );

        } else {

            $response = $this->response_helper(
                array(
                    'status'      => 500, 
                    'issue_id'    => 'file_handler_020', 
                    'message'     => 'File failed to move. Path: ' . $path
                )
            );

        }

        return $response;

    }

    /**
     * Copy a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return array
     */

    Public function copy_file( $path, $target ) {

        $success = true; 

        if( copy( $path, $target ) ) {
            
            $response = $this->response_helper(
                array(
                    'error'       => false,
                    'status'      => 200, 
                    'issue_id'    => 'file_handler_019', 
                    'message'     => 'File moved. Path: ' . $path
                )
            );

        } else {

            $response = $this->response_helper(
                array(
                    'status'      => 500, 
                    'issue_id'    => 'file_handler_020', 
                    'message'     => 'File failed to move. Path: ' . $path
                )
            );

        }

        return $response;

    }

 }
 