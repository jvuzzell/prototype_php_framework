var PopupPlugin = (function() {

    var store = {};

    var Constructor = function( options ) {

        var publicMethods = {};
        var settings; 

        // Private
        var show = function( target ) {
            if( settings.targetGroup != null ) hideSiblings( settings.targetGroup );  
            target.setAttribute( 'data-popup-container', 'visible' ); 
            publicMethods.updateState( { 'visible' : true } );

            document.querySelector( 'body' ).setAttribute( 'data-popup', 'active' );
            document.querySelector( 'body' ).setAttribute( 'data-overlay', 'active' );
        }

        var hide = function( target ) {
            target.setAttribute( 'data-popup-container', 'hidden' );
            publicMethods.updateState( { 'visible' : false } );

            document.querySelector( 'body' ).setAttribute( 'data-popup', 'inactive' );
            document.querySelector( 'body' ).setAttribute( 'data-overlay', 'inactive' ); 
        }

        // Public

        publicMethods.toggle = function toggle( e ) {

            if( e !== undefined ) {
                // Check for field to update based on result of popup Transaction
                settings.fieldToUpdate = ( e.target.getAttribute( 'data-target-update-field' ) == undefined ) ? null : e.target.getAttribute( 'data-target-update-field' ); 
            }

            if( settings.container.getAttribute( 'data-popup-container' ) == 'hidden' ) {
                show( settings.target );
            } else {
                hide( settings.target );
            }        

        }

        publicMethods.getFieldToUpdate = function getFieldToUpdate() {
            return ( settings.fieldToUpdate == undefined ) ? null : settings.fieldToUpdate;  
        }
        
        publicMethods.init = function( options ) {
        
            settings = options; // This makes arguments available in the scope of other methods within this object

            if( settings == null || settings == undefined ) { console.error( 'Popup Plugin, settings not provided upon initialization' ); return; } 

            var triggerSelector = settings.triggerSelector;
            var triggerEvent    = 'click'; 
            
            if( settings.override === 'custom' || settings.override === 'true' ) {

                // Try to perform the custom callback, and handle errors gracefully
                try {    
                    window.addEventListener( triggerEvent, function( event ) {

                        // Accomodate multiple triggers for one popup
                        var triggers = document.querySelectorAll( triggerSelector );

                        // Narrow down trigger to exactly which trigger interaction
                        for( var i = 0; i < triggers.length; i++) {
                            if( event.target == triggers[ i ] ) {
                                    thisPopupSettings = PopupPlugin.getPopup( settings.id ).getSettings(); 
                                    window[ thisPopupSettings.customCallback ]( event );
                            }
                        }

                    });          
                } catch( error ) {
                    console.error( 'Popup Plugin, custom callback for Popup ID: ' + settings.id + '  failed. Message: ' + error.message );
                }

            } else {

                window.addEventListener( triggerEvent, function( event ) {

                    // Accomodate multiple triggers for one modal
                    var triggers = document.querySelectorAll( triggerSelector );

                    // Narrow down trigger to exactly which trigger interaction
                    for( var i = 0; i < triggers.length; i++) {
                        if( event.target == triggers[ i ] ) {
                            PopupPlugin.getPopup( settings.id ).toggle( event );
                        }
                    }
                }); 

            }

            if( !settings.visible ) {
                hide( settings.target );
            }

        };

        publicMethods.updateState = function( state ) {
            
            for( var setting in state ) {
                settings[ setting ] = state[ setting ];
            }

        }
        
        publicMethods.getSettings = function() {
            return settings;
        }

        publicMethods.isVisible = function() {
            return settings.visible;
        }

        // Initialize plugin
        publicMethods.init( options );
        
        return publicMethods;
        
    }

    var setPopup = function( name, obj ) {
        store[ name ] = obj;
    }

    var getPopup = function( name ) {
        return store[ name ];
    }
	
    var getPopups = function() {
        return store;
    }

    var addClosePopupEvents = function() {

        var popupParentContainer = document.querySelector( '.popup-containers' );

        // Collapse all containers if Fly-Container tag is clicked
        // Allow users to exit popup without having to click 'close' button
        window.addEventListener( 'click', function( event ) {

            var closeTrigger = event.target.getAttribute( 'data-close-popup' ); 
            
            //If you click in the gray overlay space (the main popup parent container), hide every popup.
            if( event.target == popupParentContainer ) { 
                var popups = document.querySelectorAll( '[data-popup-container]' );

                for( var i = 0; i < popups.length; i++ ) {
                    var popup = PopupPlugin.getPopup( popups[i].getAttribute('data-popup-target') );
                    if( popup.isVisible() ) {
                        popup.toggle();
                    }
                }     

                document.querySelector( 'body' ).setAttribute( 'data-popup', 'inactive' );
                document.querySelector( 'body' ).setAttribute( 'data-overlay', 'inactive' ); 
            }

            //If you click the X button, close that single popup.
            if( closeTrigger !== null ) { 
                PopupPlugin.getPopup( closeTrigger ).toggle();
            }

        });

    }

    var registerPopup = function( popup, iterator ) {

        var popupTarget          = popup;
        var popupName            = popupTarget.getAttribute( 'data-popup-target' );
        var popupTriggerSelector = '[data-popup-target=' + popupName + '][data-popup-trigger]';
        var popupTriggers        = document.querySelectorAll( popupTriggerSelector ); 
        var isVisible            = ( popup.getAttribute( 'data-popup-container' ) == 'hidden' ) ? false : true;
        var popupCallback        = null;
        var callbackName;

        //popupCallback will be called if you set popupOverride to 'true' (string val).
        //The first (and only) argument to popupCallback will be e (the click event).
        //If you do this, you will need to open the popup on your own. You will also need to obtain the field to update.
        //See toggle() above for how to do this. Or, you could just call toggle() on your own and pass e along to it.
        var popupOverride = popup.getAttribute( 'data-popup-override' );

        if( popupOverride !== undefined && popupOverride !== 'false' ) {
            callbackName = popup.getAttribute( 'data-popup-callback' ); 
            popupCallback = ( callbackName == undefined ) ? null : callbackName; // string, name of function to call
            if( popupCallback == null ) { console.warn( 'Popups Plugin did not detect custom callback for override, Node:', popup ); }
        }

        if( popupTarget == null ) { console.warn( 'Popup Plugin, did not detect target, Node:', popup ); }
        if( popupTriggers.length == 0 ) { console.warn( 'Popup Plugin, did not detect trigger, Node:', popup ) ; }

        //As you instantiate new popupPlugins, insert them in the popupStore object, indexed by the name of the popup.
        this.storePopup( 
            popupName,
            new PopupPlugin.launch({
                id               : popupName,
                container        : popupTarget, 
                triggerSelector  : popupTriggerSelector,   
                target           : popupTarget, 
                override         : popupOverride,
                customCallback   : popupCallback,
                visible          : isVisible,
            })
        );

        function findAncestor(el, sel) {
            while ((el = el.parentElement) && !((el.matches || el.matchesSelector).call(el,sel)));
            return el;
        }

    }
    
    var closePopup = function( popupName ) {
	    
	    if( popupName == '' || this.getPopup( popupName ) == undefined ) { console.warn( 'Popup Plugin, did not detect target popup, popupName:', popupName ); return; }
	    
	    this.getPopup( popupName ).toggle();
	    
    }

    return { 
        launch              : Constructor, 
        registerPopup       : registerPopup, 
        storePopup          : setPopup, 
        getPopup            : getPopup, 
		getPopups           : getPopups,
        addClosePopupEvents : addClosePopupEvents, 
        closePopup          : closePopup
    };  
  
})();

(function() {

    var initPopups = function(){

        var popupParentContainer = document.querySelector( '.popup-containers' );
        var popups = document.querySelectorAll( '[data-popup-container]' );
        if( popups == null ) return; 
    
        for( var i = 0; i < popups.length; i++ ) {
            PopupPlugin.registerPopup( popups[ i ], i );
        }

        PopupPlugin.addClosePopupEvents();
        
    }

    if( typeof runOnAppStart == "function" ) {
        runOnAppStart( 'initializePopupPlugin', initPopups );
    } else { 
        initPopups();
    }    

})();