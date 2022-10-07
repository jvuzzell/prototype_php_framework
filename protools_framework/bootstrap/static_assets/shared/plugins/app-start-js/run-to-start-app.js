var PluginManifest = {}; 

function runOnAppStart( pluginName, func, args ) {
	
	var args = ( args == undefined || args.length == 0 ) ? [] : args;
	
    PluginManifest[ pluginName ] = { callback: func, arguments: args };
    
}

function launchAppPlugins() { 
	
    var PluginNames = Object.keys( PluginManifest ); 
	
    for( var i = 0; i < PluginNames.length; i++ ) {
        var callback = PluginManifest[ PluginNames[i] ][ 'callback' ]; 
        var args = PluginManifest[ PluginNames[i] ][ 'arguments' ];
		
        if( args.length == 0 ) {
            callback();
        } else {
            callback(  args  );
        }

    }
    
 }