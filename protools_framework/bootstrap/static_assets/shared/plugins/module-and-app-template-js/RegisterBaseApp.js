// Initialize application
StandardApplication.registerInstance({ 
    state: { id : 'Base-App' } 
});

// Make application available to other applications and modules
var MyApplication = StandardApplication.getInstance( 'Base-App' );

// Create global namespaces for modules 
if (typeof CustomModuleConfigs === 'undefined') {
    var CustomModuleConfigs = {};
}

if (typeof CustomModuleProps === 'undefined') {
    var CustomModuleProps = {};
}
