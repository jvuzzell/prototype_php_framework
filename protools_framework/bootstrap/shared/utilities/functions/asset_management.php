<?php 

use Bootstrap\Shared\Utilities\Vendor\Voku\Helper\Hooks as Hooks;
use Bootstrap\Shared\Utilities\Classes\Enqueue_assets as Enqueue_assets; 
use Bootstrap\Shared\Utilities\Classes\Asset_whitelist_registration as Asset_whitelist_registration; 
use MatthiasMullie\Minify as Minify;

$Hooks = Hooks::getInstance();
$Enqueue_assets = new Enqueue_assets( $Hooks, ENV_NAME, new Minify\CSS(), new Minify\JS(), new Minify\JS() ); 
$Asset_whitelist_registration = new Asset_whitelist_registration( $Enqueue_assets );