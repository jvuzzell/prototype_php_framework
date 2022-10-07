var SystemAlert = (function() {
	
	var store = {};
	
    var Constructor = function( options ) {
	
		var state = {
			active : true
		}
		
        var publicMethods = {};
        var privateMethods = {};
        
        var settings; 
        
        privateMethods.getNodeTemplate = function() {
	        
	        var nodeColumn   = document.createElement( 'DIV' );
	        var nodeTemplate = document.createElement( 'DIV' );
	        var messageNode  = document.createElement( 'P' );
	        var buttonNode   = document.createElement( 'BUTTON' );
	        
	        nodeColumn.className = 'v-col';
			nodeColumn.setAttribute( 'data-ref', settings.id );
			nodeTemplate.setAttribute( 'data-alert-message', settings.id );
			nodeTemplate.setAttribute( 'data-message-type', settings.messageType ); 
			nodeTemplate.setAttribute( 'data-status', 'active' );
			
			messageNode.innerHTML = settings.message;
			
			buttonNode.className = 'close-button';
			buttonNode.innerHTML = 'x';
			
			privateMethods.closeAlertEventListener( buttonNode );
			
			nodeTemplate.appendChild( messageNode ); 
			nodeTemplate.appendChild( buttonNode );
			
			nodeColumn.appendChild( nodeTemplate );
			
	        return nodeColumn;
	        
        }
        
        privateMethods.show = function( target ) {
	        
            target.setAttribute( 'data-status', 'active' ); 
            publicMethods.updateState( { 'active' : true } );

        }

        privateMethods.hide = function( target ) {

	        if( 
				target.getAttribute( 'data-alert-message' ) !== null &&
				target.parentElement.className === 'v-col'
			) {
				target.parentElement.remove();
			}

            target.setAttribute( 'data-status', 'inactive' );
            publicMethods.updateState( { 'active' : false } );

        }
        
        publicMethods.updateState = function( newState ) {
            
            for( var setting in newState ) {
                state[ setting ] = newState[ setting ];
            }

        }

        publicMethods.getState = function( fieldName = '' ) {
	        
	        if( fieldName == '' ) {
		        return state;
	        } else { 
		        return state[ fieldName ];
	        }
	        
        }
        
        publicMethods.hideAlert = function() {

            privateMethods.hide( settings.target );
            publicMethods.toggleParent();
            
        }

        publicMethods.toggleParent = function() {
		
			var showParentContainer = false; 
            var alertStore = getAllAlerts();
            var storeKeys = Object.keys( alertStore );

            // console.log( storeKeys );
			for( var i = 0; i < storeKeys.length; i++) {
			
				if( alertStore[ storeKeys[ i ] ].getState( 'active' ) ) {
					showParentContainer = true; 
                }
                
                if( i == storeKeys.length - 1 ) {
                    // console.log( '(' + storeKeys.length + ')' + ' show parent containers: ' + showParentContainer );
                    // console.log( storeKeys );
                    if( showParentContainer ) {
                        privateMethods.show( settings.parentContainer );
                    } else {
                        privateMethods.hide( settings.parentContainer );
                    }           

                }
				
            }
            
        }
		
		publicMethods.render = function() {
			
			var systemAlertContainer = privateMethods.getNodeTemplate( settings );
			settings.target = systemAlertContainer.querySelector( '[data-alert-message]' );
			
			if( !settings.parentContainer ) {
				alert( settings.message );
			} else {
				settings.parentContainer.prepend( systemAlertContainer );
				publicMethods.toggleParent();

				// Set time to disappear
				setTimeout( function() { 
					publicMethods.hideAlert();
				}, settings.messageTtl );
			}
				
		}
		
		publicMethods.init = function( options ) {
			
			settings = options;

            if( settings == null || settings == undefined ) { console.error( 'System Alert, settings not provided upon initialization' ); return false; } 
			
			return true;	
			
		}
		
	    privateMethods.closeAlertEventListener = function( targetButton ) {
		    
		    window.addEventListener( 'click', function( e ){
			    
				if( e.target == targetButton ) {
					publicMethods.hideAlert();
				}
				   
		    });
		    
	    }
		
		// Auto initialization
		var initSuccess = publicMethods.init( options );
		
		if( initSuccess ) {
			return publicMethods;	
		} else {
			console.warn( 'System Alert, failed to create ovalert id: ' + options.id, options );
			return false;
		}
		
    }
    
    var getAllAlerts = function( name, obj ) {

        return store; 

    }

	var storeSystemAlert = function( name, obj ) {
		
		store[ name ] = obj;
		
	}

	var registerSystemAlert = function( messageType = 'message', message = 'Message not provide', parentContainer = false, messageTtl = 20000 ) {

		var moduleName = 'systemAlert';
		var moduleId   = ensureUniqueKey( returnRandomKey( moduleName ), Object.keys( store ), moduleName ); 

		storeSystemAlert(
			moduleId, 
			new SystemAlert.launch({
				id          : moduleId,
				message     : message,
				messageTtl  : messageTtl,
				messageType : messageType, 
				parentContainer : parentContainer	
			})
		);
	
		store[ moduleId ].render();
		
	}

    var ensureUniqueKey = function( newKey, existingKeys, moduleName ) {
    
        for( var i = 0; i < existingKeys.length; i++ ) {

            if( newKey === existingKeys[ i ] ) { 
                ensureUniqueKey( newKey, existingKeys, moduleName );
            }

        }

        return newKey;
        
    }

    var returnRandomKey = function( moduleName ) {

        return 'module_' + moduleName + '_' + Math.floor( Math.random() * Math.floor( 100000 ) );

    }
	
    return {
        getAllAlerts : getAllAlerts,
        launch   : Constructor,
        transmit : registerSystemAlert
    }

})();

/*
// Potential Unit Test 
(function(){
		
	function initSystemAlerts() {
		var messageType = 'success'; 
		var message     = 'The system approves your decision';
		var messageTtl  = 3000; 
		var parentContainer = document.querySelector( '.system-alerts' );
	
		SystemAlert.transmit( messageType, message, parentContainer, messageTtl );	
		
		SystemAlert.transmit( 'error', message, parentContainer, messageTtl );		
	}
	
	runOnAppStart( 'initializeSystemAlertPlugin', initSystemAlerts );
	
})();
*/

/* 

// Determine whether all alerts have been closed

var alerts = SystemAlert.getAllAlerts();
var alertKeys = Object.keys( alerts );
for( var i = 0; i < alertKeys.length; i++ ) {
    console.log( alertKeys[ i ], alerts[ alertKeys[ i ] ].getState() );
}

*/