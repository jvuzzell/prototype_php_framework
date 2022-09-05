<?php

/**
 * Global File Paths
 */

if ( ! defined( 'BOOTSTRAP_SITE_DIR' ) ) {
    define( 'BOOTSTRAP_SITE_DIR', __DIR__ );
}

if ( ! defined( 'BOOTSTRAP_SHARED_LIBRARY_DIR' ) ) {
    define( 'BOOTSTRAP_SHARED_LIBRARY_DIR', BOOTSTRAP_SITE_DIR . '/../shared-library' );
}    

echo BOOTSTRAP_SITE_DIR . '</br>';
echo BOOTSTRAP_SHARED_LIBRARY_DIR . '</br>';

/**
 * Include Dependencies 
 * 
 * Note: Auto includes any php file within library/utilities/[interface, classes, or models]
 */

foreach( glob( BOOTSTRAP_SHARED_LIBRARY_DIR . '/utilities/classes/*.php' ) as $filename ) {
    require_once( $filename );
} 
