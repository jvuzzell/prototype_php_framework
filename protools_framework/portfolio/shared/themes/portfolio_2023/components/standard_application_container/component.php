<?php 
	
require_once( SHARED_ASSET_DIR . 'components/standard-application-container/component_registry.php' );

// Print minified CSS in UAT, DEMO, and PROD environments 
$appEnv = strtolower( ENV_NAME ); 

if( $appEnv ==  'x' ) {
    $Enqueue_assets->print_minified_js_and_css(); 
} else {
    $Enqueue_assets->print_unminified_js_and_css();
}

// require_once( SITE_LIBRARY_DIR . '/utilities/functions/detect-cache.php' ); 

// View 
include( SHARED_ASSET_DIR . 'components/header/component.php' );

?>

<div class="main-container">

    <!-- Main Content -->
    <div class="main-content">

        <div class="content-lock">

            <div class="h-row system-alerts" data-system-alert-cointainer data-status="inactive"></div>  

        </div>
        
    </div>
    
    <!-- Screen Overlay -->
    <div class="screen-overlay"></div>
</div>

<?php

include( SHARED_ASSET_DIR . 'components/footer/component.php' );