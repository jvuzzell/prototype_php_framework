<?php 

// Core Styles
$Enqueue_Assets->css( 'Reset_CSS', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/reset.css', '1.0', true );
$Enqueue_Assets->css( 'Standard_Grid_CSS', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/grid.css', '1.0', true );
$Enqueue_Assets->css( 'Margin_Padding_CSS', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/margin-padding.css', '1.0', true );
$Enqueue_Assets->css( 'Button_Anchor_CSS', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/button-anchors.css', '1.0', true );
$Enqueue_Assets->css( 'Color_Palette_CSS', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/color-palette.css', '1.0', true );
$Enqueue_Assets->css( 'Fon_tCss', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/font.css', '1.0', true );
$Enqueue_Assets->css( 'Icon_CSS', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/icons.css', '1.0', true );
$Enqueue_Assets->css( 'Standard_Forms_CSS', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/form.css', '1.0', true );
$Enqueue_Assets->css( 'Main_CSS', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/main.css', '1.0', true );

// Plugin Styles
$Enqueue_Assets->css( 'Expandables_CSS', STYLE_GUIDE_URL . '/universal-plugins/expandables-js/expandables.css', '1.0', true );
$Enqueue_Assets->css( 'Flyout_CSS', STYLE_GUIDE_URL . '/universal-plugins/flyout-js/flyout.css', '1.0', true );
$Enqueue_Assets->css( 'Popup_CSS', STYLE_GUIDE_URL . '/universal-plugins/popup-js/popup.css', '1.0', true );
$Enqueue_Assets->css( 'Tabulator_CSS', STYLE_GUIDE_URL . '/universal-plugins/vendor/tabulator/dist/css/tabulator.min.css', '1.0', true );
$Enqueue_Assets->css( 'Table_Styles', STYLE_GUIDE_URL . '/' . SITE_NAME . '/styles/tables-style.css', '1.0', true );
$Enqueue_Assets->css( 'System_Alert_JS', STYLE_GUIDE_URL . '/universal-plugins/system-alert-js/system-alert.css', '1.0', true, true );
$Enqueue_Assets->css( 'Chart_JS_CSS', STYLE_GUIDE_URL . '/universal-plugins/chart-js/chart.css', '1.0', true );
$Enqueue_Assets->css( 'Tab_CSS', STYLE_GUIDE_URL . '/universal-plugins/tab-js/tab.css', '1.0', true );
$Enqueue_Assets->css( 'Form_Validation_CSS', STYLE_GUIDE_URL . '/universal-plugins/form-validation/validation.css', '1.0', false );

// Plugin JS
$Enqueue_Assets->javascript( 'Polyfills', STYLE_GUIDE_URL . '/universal-plugins/polyfills.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'JsHelper', STYLE_GUIDE_URL . '/universal-plugins/JsHelper.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Run_to_Start_App_JS', STYLE_GUIDE_URL . '/universal-plugins/app-start-js/run-to-start-app.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Common_JS', STYLE_GUIDE_URL . '/universal-plugins/common.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Expandables_JS', STYLE_GUIDE_URL . '/universal-plugins/expandables-js/expandables.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Flyouts_JS', STYLE_GUIDE_URL . '/universal-plugins/flyout-js/flyout.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'PopUps_JS', STYLE_GUIDE_URL . '/universal-plugins/popup-js/popup.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'System_Alert_JS', STYLE_GUIDE_URL . '/universal-plugins/system-alert-js/system-alert.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Tabulator_JS', STYLE_GUIDE_URL . '/universal-plugins/vendor/tabulator/dist/js/tabulator.min.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'HTML5_Validator_JS', STYLE_GUIDE_URL . '/universal-plugins/form-validation/HTML5validation.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Call_Local_API_JS', STYLE_GUIDE_URL . '/universal-plugins/call-local-api.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Standard_Application_JS', STYLE_GUIDE_URL . '/universal-plugins/plugin_gaea-js/StandardApplicationDesign.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Standard_Module_JS', STYLE_GUIDE_URL . '/universal-plugins/plugin_gaea-js/StandardModuleDesign.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Base_App_JS', STYLE_GUIDE_URL . '/universal-plugins/plugin_gaea-js/RegisterBaseApp.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Global_Tabulator_Namespace', STYLE_GUIDE_URL . '/universal-plugins/globalTabulatorNamespace.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Chart_JS', STYLE_GUIDE_URL . '/universal-plugins/chart-js/chart.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Chart_UTIL_JS', STYLE_GUIDE_URL . '/universal-plugins/chart-js/utils.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'Tab_JS', STYLE_GUIDE_URL . '/universal-plugins/tab-js/tab.js', '1.0', true, true );
$Enqueue_Assets->javascript( 'HTML5Validation', STYLE_GUIDE_URL . '/universal-plugins/form-validation/HTML5validation.js', '1.0', false, true );
