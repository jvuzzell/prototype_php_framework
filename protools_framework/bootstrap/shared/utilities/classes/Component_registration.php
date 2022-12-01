<?php 

namespace Bootstrap\Shared\Utilities\Classes;

use Bootstrap\Shared\Utilities\Classes\Static\Api_response as Api_response;
use Bootstrap\Shared\Utilities\Classes\Json_validator as Json_validator;
use MatthiasMullie\Minify\CSS as Minify_css; 
use MatthiasMullie\Minify\JS as Minify_js;
use Symfony\Component\OptionsResolver\OptionsResolver as Options_resolver;

/** 
 * @package Component Registration
 * @version 1.0
 * 
 * @author Joshua Uzzell 
 *
 * Purpose: 
 * A way to include only javascript and css required to render the page
 *  
 * Public Methods: 
 *     @method register_components
 * 
 */

class Component_registration {

    Private $javascript_manifest = array();
    Private $css_manifest = array();
    Private $api_data_source_options = null; // Options resolver
    Private $data_source_manifest = array(
        'api' => array(), 
        'database' => array()
    );

    Public function __construct( array $env_variables ) {

        $this->env_variables = $env_variables;

        $this->minifiers = array(
            'header' => array( 
                'css' => new Minify_css(),
                'js' => new Minify_js(), 
            ), 
            'footer' => array(
                'js' => new Minify_js()
            )
        );

        $this->api_response = new Api_response; 

        $this->component_options = $this->configure_component_options();
        $this->api_data_source_options = $this->configure_api_data_source_options();

    }

    Private function configure_component_options() {

        $component_options = new Options_resolver; 

        $component_options
            ->setDefault( 'name', null )
            ->setDefault( 'assets_path', array() )
            ->setDefault( 'data_source', array() )
            ->setAllowedTypes( 'name', [ 'null', 'string' ] )
            ->setAllowedTypes( 'assets_path', 'array' )
            ->setAllowedTypes( 'data_source', 'array' )
        ;

        return $component_options; 

    }

    Private function configure_api_data_source_options() {

        $api_options = new Options_resolver; 

        $api_options
            ->setDefault( 'publish_to_page_model', false )
            ->setDefault( 'render_server_side', false )
            ->setDefault( 'client_name', null )
            ->setDefault( 'endpoint_uri', null )
            ->setDefault( 'input_request_parameters', array() )
            ->setDefault( 'request_method', 'POST' )
            ->setDefault( 'request_agent', 'protools' )
            ->setAllowedTypes( 'publish_to_page_model', 'bool' )
            ->setAllowedTypes( 'render_server_side', 'bool' )
            ->setAllowedTypes( 'request_method', 'string' )
            ->setAllowedTypes( 'request_agent', 'string' )
            ->setAllowedTypes( 'client_name', [ 'null', 'string' ] )
            ->setAllowedTypes( 'endpoint_uri', [ 'null', 'string' ] )
            ->setAllowedTypes( 'input_request_parameters', [ 'array' ] )
            ->setAllowedValues( 'request_method', [ 'post', 'POST', 'get', 'GET' ] )
            ->setAllowedValues( 'request_agent', [ 'protools', 'user' ] )
        ;

        return $api_options;

    }

    Public function register_theme_assets( string $theme_dir, $static_asset_dir ) {

        $response = array();
        $theme_asset_filename = $theme_dir . 'theme_assets.json'; 

        if( file_exists( $theme_asset_filename ) ) {
            
            $json_validator = new Json_validator;
            $theme_assets_response = $json_validator->validate( file_get_contents( $theme_asset_filename ), true );

            if( $theme_assets_response[ 'error' ] ) {
                $response = $theme_assets_response;
            }

            foreach( $theme_assets_response[ 'data' ][ 'css' ] as $asset_key => $asset ) {

                $asset_path = $static_asset_dir . $asset[ 'asset_path' ];
                
                if( file_exists( $asset_path ) ) {
                    $this->manifest_css( 
                        $asset_key, 
                        $asset_path, 
                        $asset[ 'extract' ], 
                        $asset[ 'minify' ], 
                        $asset[ 'in_footer' ]
                    );
                }

            }

            foreach( $theme_assets_response[ 'data' ][ 'plugin_css' ] as $asset_key => $asset ) {

                $asset_path = $static_asset_dir . $asset[ 'asset_path' ];
                
                if( file_exists( $asset_path ) ) {
                    $this->manifest_css( 
                        $asset_key, 
                        $asset_path, 
                        $asset[ 'extract' ], 
                        $asset[ 'minify' ], 
                        $asset[ 'in_footer' ]
                    );
                }

            }

            foreach( $theme_assets_response[ 'data' ][ 'plugin_js' ] as $asset_key => $asset ) {

                $asset_path = $static_asset_dir . $asset[ 'asset_path' ];
                
                if( file_exists( $asset_path ) ) {
                    $this->manifest_javascript( 
                        $asset_key, 
                        $asset_path, 
                        $asset[ 'extract' ], 
                        $asset[ 'minify' ], 
                        $asset[ 'in_footer' ]
                    );
                }

            }

            $response = $this->api_response->format_response(array(
                'status' => 200, 
                'error' => false, 
                'source' => get_class(),
                'issue_id' => 'component_registration_012', 
                'message' => 'Theme assets loaded'
            ));

        } else {

            $response = $this->api_response->format_response(array(
                'status' => 201, 
                'error' => false, 
                'source' => get_class(),
                'issue_id' => 'component_registration_011', 
                'message' => 'No theme assset file detected'
            ));

        }

        return $response;

    }

    Public function register_component_assets( string $component_dir, array $registry ) {

        foreach( $registry[ 'components' ] as $component ) {  

            foreach( $component[ 'assets_path' ] as $asset ) {

                // CSS
                foreach( glob( $component_dir . $asset . '/*.css' ) as $filename ) {

                    $basename = basename( $filename, '.css' ) . "_css";
                    $this->manifest_css( $asset . "_" . $basename, $filename, true );

                } 

                // JS
                foreach( glob( $component_dir . $asset . '/*.js' ) as $filename ) {

                    $handle = basename( $filename, '.js' ) . "_js";
                    $in_footer = true; 
                    $print = true; 

                    $this->manifest_javascript( 
                        $asset . "_" . $handle, 
                        $filename, 
                        $in_footer, 
                        $print 
                    );

                } 

            }

        }

    }

    Public function register_component_data_sources( string $component_dir, array $registry, array $page_settings ) {

        // Page Settings > override > Component Registry > override > Component Registration

        // Loop over Template Component Registry
        $response = $this->api_response->format_response(array(
            'status' => 200, 
            'error' => false, 
            'issue_id' => 'component_registration_011', 
            'source' => get_class(),
            'message' => 'Component data sources registered' 
        ));

        $response = $this->get_component_registration_data_source( $component_dir, $registry[ 'components' ] );
 
        if( !$response[ 'error' ] ) { 
            // override component data sources with Template component registry data
            if( isset( $registry[ 'components' ] ) ) {
                $response = $this->override_data_sources( $registry[ 'components' ], 'template' );
            }

        }

        if( !$response[ 'error' ] ) { 
            // override template component registry with
            // $page_settings
            if( isset( $page_settings[ 'components' ] ) ) {
                $response = $this->override_data_sources( $page_settings[ 'components' ], 'page' ); 
            }

        }

        return $response;

    }

    Private function override_data_sources( array $components, string $override_type = 'none' ) {

        $response = array( 'status' => 200, 'error' => false );
        
        foreach( $components as $component_key => $component ) { 

            $component_registration = $this->component_options->resolve( $component );
            $data_sources = $component_registration[ 'data_source' ]; 

            $this->filter_data_source_into_manifest( $data_sources, $component_key, $override_type );

        }

        return $response;

    }

    Private function get_component_registration_data_source( string $component_dir, array $components ) {

        $response = array( 'status' => 200, 'error' => false ); 

        foreach( $components as $component_key => $component ) {  

            // Include individual component/registration.php
            if( file_exists( $component_dir . $component_key . '/registration.json' ) ) {

                $json_validator = new Json_validator;
                $component_registration_settings_response = $json_validator->validate( file_get_contents( $component_dir . $component_key . '/registration.json' ), true );
     
                if( $component_registration_settings_response[ 'error' ] ) {
                   
                    $response = $this->api_response->format_response(array(
                        'status' => 500, 
                        'issue_id' => 'component_registration_010', 
                        'source' => get_class(), 
                        'log' => true, 
                        'message' => $component_registration_settings_response[ 'message' ] . ' - ' . $component_key
                    ));

                    break;

                }

                $component_registration = $this->component_options->resolve( $component_registration_settings_response[ 'data' ] );
                $data_sources = $component_registration[ 'data_source' ]; 

                $this->filter_data_source_into_manifest( $data_sources, $component_key );

            } 

        }

        return $response;
        
    }

    Private function remove_component_from_data_source_manifest( string $source_type, string $component_key ) {
       
        foreach( $this->data_source_manifest[ $source_type ] as $src_variant_key => $data_source_variants ) {

            foreach( $this->data_source_manifest[ $source_type ][ $src_variant_key ] as $specific_src_index => $specific_data_source ) {

                $search_result = array_search( $component_key, $this->data_source_manifest[ $source_type ][ $src_variant_key ][ $specific_src_index ][ 'components' ], true );
                
                if( $search_result !== false ) {

                    array_splice( $this->data_source_manifest[ $source_type ][ $src_variant_key ][ $specific_src_index ][ 'components' ], $search_result );

                    if( count( $this->data_source_manifest[ $source_type ][ $src_variant_key ][ $specific_src_index ][ 'components' ] ) === 0 ) {

                        unset(  $this->data_source_manifest[ $source_type ][ $src_variant_key ][ $specific_src_index ] );
                   
                    }

                }

            }

        }

    }

    Private function filter_data_source_into_manifest( array $data_sources, string $component_key, string $override_type = 'none' ) {
        
        $override_run = false; 

        foreach( $data_sources as $data_source_key => $data_source ) {

            if( array_key_exists( 'api', $data_source ) ) { 

                if( $override_type !== 'none' && !$override_run ) { 
                    $override_run = true; 
                    $this->remove_component_from_data_source_manifest( 'api', $component_key );
                }

                $api_data_source = $this->api_data_source_options->resolve( $data_source[ 'api' ] );

                // Create a key to identify this source 
                $source_key = 'client/'; 
                $api_client = $api_data_source[ 'client_name' ];
                $source_key .= $api_client . '/uri/' . $api_data_source[ 'endpoint_uri' ];

                $api_data_source[ 'components' ][] = $component_key;

                // Find key in manifest and compare request_body 
                if( array_key_exists( $source_key, $this->data_source_manifest[ 'api' ] ) ) {

                    $source_matches_existing_source = false; 
 
                    foreach( $this->data_source_manifest[ 'api' ][ $source_key ] as $index => $manifested_api_source ) { 
                        
                        $api_source_diff = $this->array_diff_assoc_recursive( 
                            $manifested_api_source[ 'input_request_parameters' ][ 'fields' ], 
                            $api_data_source[ 'input_request_parameters' ][ 'fields' ] 
                        );

                        $api_source_diff_2 = $this->array_diff_assoc_recursive( 
                            $api_data_source[ 'input_request_parameters' ][ 'fields' ],
                            $manifested_api_source[ 'input_request_parameters' ][ 'fields' ]
                        );

                        $diff = array_merge( $api_source_diff, $api_source_diff_2 );
 
                        if( count( $diff ) === 0 ) { 

                            // This component uses the same data source as another
                            $source_matches_existing_source = true;  
                            $this->data_source_manifest[ 'api' ][ $source_key ][ $index ][ 'components' ][] = $component_key;
                            break;

                        }

                    }

                    if( !$source_matches_existing_source ) {

                        $this->data_source_manifest[ 'api' ][ $source_key ][] = $api_data_source;

                    }

                    $component_key = null;
                } else {

                    $this->data_source_manifest[ 'api' ][ $source_key ] = array( $api_data_source );
                    
                }

            }

        }

    }

    Public function get_data_source_manifest() { 

        return $this->data_source_manifest;

    }

    Private function replace_special_char( $string ) {

        $search =  '!"#$%&/()=?*+\'.,;:' ;
        $search = str_split( $search );

        return str_replace( $search, "_", $string );

    }

    // Extract Javascript from file and add it to minifier
    Public function manifest_javascript( string $handle = '', string $path = '', bool $extract_content = true, bool $minify = true, bool $in_footer = false ) {
        
        $response = array();
        $manifested = false; 
        $handle = $this->replace_special_char( $handle ); 

        // Do not add file if it is file already manifested
        foreach( $this->javascript_manifest as $manifested_handle => $code ) {

            if( $manifested_handle === $handle ) {

                $manifested = true; 
                $response = $this->api_response->format_response(array(
                    'status' => 201, 
                    'error' => true, 
                    'source' => get_class(), 
                    'issue_id' => 'component_registration_001', 
                    'message' => 'File already manifested ' . $manifested_handle
                ));

                break; 

            }
        
        }

        // If file manifested then enqueue
        if( !$manifested ) {

            // Retrieve file contents
            if( !file_exists( $path ) ) {
                
                return $this->api_response->format_response(array(
                    'status' => 404, 
                    'error' => true, 
                    'source' => get_class(), 
                    'issue_id' => 'component_registration_002', 
                    'message' => 'File not found ' . $handle
                ));

            }

            $content = file_get_contents( $path );

            // Do not include blank javascript files
            if( $content === "" ) { 

                return $this->api_response->format_response(array(
                    'status' => 201, 
                    'error' => true, 
                    'source' => get_class(), 
                    'issue_id' => 'component_registration_003', 
                    'message' => 'No content to minify ' . $handle
                ));

            }
            
            // Update manifest 
            $this->javascript_manifest[ $handle ] = array(); 

            if( $minify ) {

                if( $in_footer ) {

                    // $this->minifiers[ 'footer' ][ 'js' ]->add( '<script id="' . $handle . '">' . $content . '</script>' );
                    $this->minifiers[ 'footer' ][ 'js' ]->add( $content );

                } else {
                    
                    // $this->minifiers[ 'header' ][ 'js' ]->add( '<script id="' . $handle . '">' . $content . '</script>' );
                    $this->minifiers[ 'header' ][ 'js' ]->add( $content );

                }

            } else {

                // Ensure 'src' is file and filetype is .js
                if( $extract_content ) {

                    $code = '<script id="' . $handle . '">' . "\n" . $content . "\n" . '</script>' . "\n";

                } else {

                    $version = $this->generate_random_string();
                    $code = '<script id="' . $handle . '" type="text/javascript" src="' . $path . '?id='. $version . ' "></script>' . "\n";

                }

                // Insert HTML code
                $this->add_to_unminified_manifest( $handle, $code, $in_footer, 'js' );

            }

        }

        $response = $this->api_response->format_response(array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class(), 
            'issue_id' => 'component_registration_004', 
            'message' => 'Asset manifested - ' . $handle
        ));

        return $response; 

    }

    // Extract CSS from file and add it to minifier
    Public function manifest_css( string $handle = '', string $src = '', bool $extract_content = true, bool $minify = true, bool $in_footer = false ) {
        
        $response = array();
        $manifested = false; 
        $handle = $this->replace_special_char( $handle ); 

        // Do not add file if it is file already manifested
        foreach( $this->css_manifest as $manifested_handle => $code ) {

            if( $manifested_handle === $handle ) {

                $manifested = true; 
                $response = $this->api_response->format_response(array(
                    'status' => 201, 
                    'error' => true, 
                    'source' => get_class(), 
                    'issue_id' => 'component_registration_005', 
                    'message' => 'File already manifested ' . $manifested_handle
                ));

                break; 

            }
        
        }

        // If file manifested then enqueue
        if( !$manifested ) {

            // Retrieve file contents
            if( !file_exists( $src ) ) {
                
                return $this->api_response->format_response(array(
                    'status' => 404, 
                    'error' => true, 
                    'source' => get_class(), 
                    'issue_id' => 'component_registration_006', 
                    'message' => 'File not found ' . $handle
                ));

            }

            $path_parts = pathinfo( $src ); 
            $content = file_get_contents( $src );

            // Do not include blank javascript files
            if( $content === "" ) { 

                return $this->api_response->format_response(array(
                    'status' => 201, 
                    'error' => true, 
                    'source' => get_class(), 
                    'issue_id' => 'component_registration_007', 
                    'message' => 'No content to minify ' . $handle
                ));

            }
            
            // Update manifest 
            $this->css_manifest[ $handle ] = array(); 

            if( $minify ) {

                $this->minifiers[ 'header' ][ 'css' ]->add( '<style id="' . $handle . '">' . $content . '</style>' );

            } else {

                // Ensure 'src' is file and filetype is .js
                if( $extract_content ) {

                    $code = '<style id="' . $handle . '">' . "\n" . $content . "\n" . '</style>' . "\n";

                } else {

                    $version = $this->generate_random_string();
                    $code = '<link id="' . $handle . '" rel="stylesheet" href="' . $src . '?id=' . $version . '"/>' . "\n";

                }

                // Insert HTML code
                $this->add_to_unminified_manifest( $handle, $code, $in_footer, 'css' );

            }

        }

        $response = $this->api_response->format_response(array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class(), 
            'issue_id' => 'component_registration_008', 
            'message' => 'Asset manifested - ' . $handle
        ));

        return $response; 

    }


    Public function add_to_unminified_manifest( string $handle, string $code, bool $in_footer = false, string $css_or_js = 'css' ) {

        if( $css_or_js == 'css' ) {
            $this->css_manifest[ $handle ] = array(
                'code'      => $code, 
                'in_footer' => $in_footer
            );
        }

        if( $css_or_js == 'js' ) {
            $this->javascript_manifest[ $handle ] = array(
                'code'      => $code, 
                'in_footer' => $in_footer
            );
        }

    }

    Public function get_manifest( string $css_or_js = 'css' ) {
        
        $data = ( $css_or_js == 'css' ) ? $this->css_manifest : $this->javascript_manifest; 
        
        return array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class(), 
            'issue_id' => 'component_registration_009', 
            'data' => array( 'manifest' => $data )
        );

    }

    Public function get_minified_assets( string $css_or_js = 'css', string $header_or_footer = 'header' ) {

        $data = $this->minifiers[ $header_or_footer ][ $css_or_js ]->minify();
        
        return array(
            'status' => 200, 
            'error' => false, 
            'source' => get_class(), 
            'issue_id' => 'component_registration_010', 
            'data' => array( 'minified' => $data )
        );

    }

    Public function generate_random_string( $charLength = 10, $charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789" ) {
    
        $arr = str_split( $charset ); // get all the characters into an array
        shuffle( $arr ); // randomize the array
        $arr = array_slice( $arr, 0, $charLength ); // get the first six (random) characters out
        $str = implode( '', $arr ); // smush them back into a string
        return $str;
    
    }

    Private function array_diff_assoc_recursive($array1, $array2)
    {
        $difference=array();
        foreach($array1 as $key => $value) {
            if( is_array($value) ) {
                if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = $this->array_diff_assoc_recursive($value, $array2[$key]);
                    if( !empty($new_diff) )
                        $difference[$key] = $new_diff;
                }
            } else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }
}
