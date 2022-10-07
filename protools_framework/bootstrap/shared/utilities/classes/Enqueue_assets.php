<?php 

namespace Bootstrap\Shared\Utilities\Classes;

/** 
 * @package Enqueue Assets
 * @version 1.0
 * 
 * @author Joshua Uzzell 
 *
 * Purpose: 
 * A way to include, prioritize, and minimize Javascript and CSS assets
 *  
 * Public Methods: 
 *     @method javascript
 *     @method css 
 * 
 */

class Enqueue_assets {

    PRIVATE $javascript_manifest = [];
    PRIVATE $css_manifest        = [];
    PRIVATE $env_name            = [];
    PRIVATE $Hooks;   

    function __construct( 
        $Hooks, 
        $env_name, 
        $MinifierCSS, 
        $MinifierHeaderJS, 
        $MinifierFooterJS
    ) {
        $this->env_name         = $env_name; 
        $this->Hooks            = $Hooks;            // Object
        $this->MinifierCSS      = $MinifierCSS;      // Object
        $this->MinifierHeaderJS = $MinifierHeaderJS; // Object
        $this->MinifierFooterJS = $MinifierFooterJS; // Object
    }

    /**
     * Add Script/CSS to HTML 
     * 
     * @param HTML      {String}  HTML
     * @param in_footer {Boolean} Boolean, whether to print code to head tag or footer
     * 
     */

    PRIVATE function print_content( string $code, bool $in_footer ) {

        $action = ( $in_footer ) ? 'footer_actions' : 'header_actions';

        $this->Hooks->add_action( 
            $action,
            function() use ( $code ) {
                print( $code );
            }, 1
        ); 
        
    }

    PRIVATE function replace_special_char( $string ) {

        $search =  '!"#$%&/()=?*+\'.,;:' ;
        $search = str_split( $search );

        return str_replace( $search, "_", $string );

    }

    PUBLIC function print_minified_js_and_css() {

        $remove_extra_semicolon = '/<\/script>;/';
        $replacement = '</script>';

        $this->Hooks->add_action( 
            'header_actions',
            function() use ( $remove_extra_semicolon, $replacement ) {
                print( $this->MinifierCSS->minify() );
                print( preg_replace( $remove_extra_semicolon, $replacement, $this->MinifierHeaderJS->minify() ) );
            }, 1
        ); 

        $this->Hooks->add_action( 
            'footer_actions',
            function() use ( $remove_extra_semicolon, $replacement ) {
                print( preg_replace( $remove_extra_semicolon, $replacement, $this->MinifierFooterJS->minify() ) );
            }, 1
        );            

    }

    PUBLIC function print_unminified_js_and_css() {

        
        // CSS
        foreach( $this->css_manifest as $css => $data ) {

            $this->print_content( 
                $data[ 'code' ], 
                $data[ 'in_footer' ]
            );

        }

        // JS
        foreach( $this->javascript_manifest as $js => $data ) {
            $this->print_content( 
                $data[ 'code' ], 
                $data[ 'in_footer' ]
            );

        }

    }

    /** 
     * Enqueue Javascript
     * 
     * @param handle    {String}  
     * @param src       {String}  File path or URL
     * @param version   {String}
     * @param in_footer {Boolean}
     * @param print     {String
     * 
     */

    PUBLIC function javascript( $handle, $src, $version, $in_footer = false, $print = true ) {

        $path_parts = pathinfo( $src ); 
        $manifested = false; 

        $handle = $this->replace_special_char( $handle ); 

        // Do not add file if it is file already manifested
        foreach( $this->javascript_manifest as $manifested_handle => $code ) {

            if( $manifested_handle === $handle ) {
                $manifested = true; 
                break; 
            }

        }

        // If file manifested then enqueue
        if( !$manifested ) {

            // Retrieve file contents
            $content = file_get_contents( $src );

            // Do not include blank javascript files
            if( $content == "" ) { return; }
            
            // Update manifest 
            $this->javascript_manifest[ $handle ] = array(); 

            $appEnv = strtolower( $this->env_name ); 

            if( $appEnv ==  'x' ) {

                if( $in_footer ) {

                    $this->MinifierFooterJS->add( '<script id="' . $handle . '">' . $content . '</script>' );

                } else {
                    
                    $this->MinifierHeaderJS->add( '<script id="' . $handle . '">' . $content . '</script>' );

                }
                

            } else {

                // Ensure 'src' is file and filetype is .js
                if( ( $path_parts[ 'extension' ] == 'js' ) && $print ) {

                    $code = '<script id="' . $handle . '">' . "\n" . $content . "\n" . '</script>' . "\n";

                } else {

                    $code = '<script id="' . $handle . '" type="text/javascript" src="' . $src . '?version=' . $version . '"></script>' . "\n";

                }

                // Insert HTML code
                $this->add_to_unminified_print_manifest( $handle, $code, $in_footer, 'js' );
                
            }


        }

    }

    /** 
     * Enqueue CSS
     * 
     * @param handle  {String}   
     * @param src     {String}
     * @param version {String}
     * @param print   {Boolean}
     * 
     */

    PUBLIC function css( $handle, $src, $version, $print = true ) {

        $path_parts = pathinfo( $src ); 
        $manifested = false; 
        $in_footer = false; // Manually set this because CSS belongs in the head tag

        $handle = $this->replace_special_char( $handle ); 

        // Do not add file if it is file already manifested
        foreach( $this->css_manifest as $manifested_handle => $code ) {

            if( $manifested_handle === $handle ) {
                $manifested = true; 
                break; 
            }

        }

        // If file not manifested then manifest and enqueue
        if( !$manifested ) {

            // Update manifest 
            $this->css_manifest[ $handle ] = array(); 

            // Retrieve file contents
            $content = file_get_contents( $src );

            // Do not include blank CSS files
            $appEnv = strtolower( $this->env_name); 

            if( $appEnv ==  'x' ) {   
  
                $this->MinifierCSS->add( '<style id="' . $handle . '">' . $content . '</style>' );

            } else {

                // Ensure 'src' is file and filetype is .css
                if( ( $path_parts[ 'extension' ] == 'css' ) && $print ) {

                    $code = '<style id="' . $handle . '">' . "\n" . $content . "\n" . '</style>' . "\n";

                } else {

                    $code = '<link id="' . $handle . '" rel="stylesheet" href="' . $src . '?version=' . $version . '"/>' . "\n";

                }     

                // Insert HTML code
                $this->add_to_unminified_print_manifest( $handle, $code, $in_footer, 'css' );  

            }  

        }

    }

    PUBLIC function add_to_unminified_print_manifest( $handle, $code, $in_footer = false, $css_or_js = 'css' ) {

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

}
