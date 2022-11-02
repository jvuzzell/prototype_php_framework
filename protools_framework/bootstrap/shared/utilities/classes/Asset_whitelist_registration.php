<?php 

namespace Bootstrap\Shared\Utilities\Classes;

/** 
 * @package Asset Whitelist Registration
 * @version 1.0
 * 
 * @author Joshua Uzzell 
 *
 * Purpose: 
 * A way to include only javascript and css required to render the page
 *  
 * Public Methods: 
 *     @method register_whitelist
 * 
 */


class Asset_whitelist_registration {

    function __construct( 
        $Enqueue_assets 
    ) {
        $this->Enqueue_assets = $Enqueue_assets;
    }

    /**
     * White List Asset Registration  
     */

    PUBLIC function register_whitelist( $whitelist ) {
        
        foreach( $whitelist as $asset_type ) {  

            $asset_dir = $asset_type[ 'directory' ];
            
            foreach( $asset_type[ 'asset_path' ] as $asset ) {
                
                // CSS
                foreach( glob( $asset_dir . $asset . '/*.css' ) as $filename ) {
                    $basename = basename( $filename, '.css' ) . "_css";
                    $this->Enqueue_assets->css( $asset . "_" . $basename, $filename, '1.0', true );
                } 

                // JS
                foreach( glob( $asset_dir . $asset . '/*.js' ) as $filename ) {
                    $basename = basename( $filename, '.js' ) . "_js";
                    $this->Enqueue_Aasets->javascript( $asset . "_" . $basename, $filename, '1.0', true, true );
                } 

            }

        }

    }

}
