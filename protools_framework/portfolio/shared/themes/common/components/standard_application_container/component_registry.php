<?php 

// View Controller
$whitelist = array(
    'universal' => array(
        'directory' => SHARED_ASSET_DIR . 'components', // Additional trailing slash is required
        'asset_path' => array(
            'header',
            'footer', 
            'standard-application-container', 
            'screen-overlay'    
        )
    )
);

$Asset_whitelist_registration->register_whitelist( $whitelist );