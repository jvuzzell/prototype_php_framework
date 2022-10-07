var expandablePlugin = (function() {

    var store = {};
  
    var Constructor = function( options ) {

        var publicMethods = {};
        var settings; 

        // Private
        var expand = function( target ) {
            var targetHeight = target.scrollHeight;
            publicMethods.updateState( { 'expanded' : true } );

            target.style.height = targetHeight + 'px';
            target.addEventListener('transitionend', function(e) {
                target.removeEventListener('transitionend', arguments.callee);

                target.style.height = null;
            });
        }

        var collapse = function( target ) {
            var targetHeight = target.scrollHeight;
            publicMethods.updateState( { 'expanded' : false } );

            var targetTransition = target.style.transition;
            target.style.transition = '';

            requestAnimationFrame( function() {
                target.style.height = targetHeight + 'px';
                target.style.transition = targetTransition;

                requestAnimationFrame( function() {
                target.style.height = 0 + 'px';
                })
            });      
        }

        var collapseSiblings = function( targetGroup ) {
            var prevTargetContainer = targetGroup.querySelector( '[data-expandable-container="expanded"]' );

            if ( prevTargetContainer == null ) return;

            var prevTarget = prevTargetContainer.querySelector( '[data-expandable-target]' );

            collapse( prevTarget );
            prevTargetContainer.setAttribute( 'data-expandable-container', 'collapsed' ); 
        }

        // Public functions

        publicMethods.toggle = function toggle() {
            if( settings.container.getAttribute( 'data-expandable-container' ) == 'collapsed' ) {
                if( settings.targetGroup != null ) collapseSiblings( settings.targetGroup );  
                expand( settings.target );
                settings.container.setAttribute( 'data-expandable-container', 'expanded' ); 
            } else {
                collapse( settings.target );
                settings.container.setAttribute( 'data-expandable-container', 'collapsed' );
            }        
        }
                
        publicMethods.init = function( options ) {
            
            settings = options; // This makes arguments available in the scope of other methods within this object

            if( settings == null || settings == undefined ) { console.error( 'Flyout Plugin, settings not provided upon initialization' ); return; } 

            var trigger      = settings.trigger;
            var triggerEvent = 'click'; 

            if( settings.override === 'true' ) {

                try {
                    window.addEventListener( triggerEvent, function( event ) {
                        if( event.target == trigger ) {
                            thisExpandableSettings = expandablePlugin.getFlyout( settings.id ).getSettings(); 
                            window[ thisExpandableSettings.customCallback ]( event );
                        }
                    });                 
                } catch( error ) {
                    console.error( 'Expandable Plugin, setting ' + settings.id + ' expandable custom callback failed: ' + error.message );
                }

            } else {

                window.addEventListener( triggerEvent, function( event ) {
                    if( event.target == trigger ) {
                        expandablePlugin.getExpandable( settings.id ).toggle();
                    }
                }); 

            }

            if( !settings.expanded ) {
                collapse( settings.target );
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

        publicMethods.isExpanded = function() {
            return settings.expanded;
        }

        // Initialize plugin
        publicMethods.init( options );

        return publicMethods;

    }

    var setExpandable = function( name, obj ) {
        store[ name ] = obj;
    }

    var getExpandable = function( name ) {
        return store[ name ];
    }
	
    var getExpandables = function( name ) {
        return store;
    }	

    var registerExpandable = function( expandable, iterator ) {

        var expandableTarget   = expandable.querySelector( '[data-expandable-target]' );
        var expandableName     = ( expandableTarget.getAttribute( 'data-expandable-target' ) !== '' ) ? expandableTarget.getAttribute( 'data-expandable-target' ) : 'expandable_' + iterator;
        var expandableTrigger  = expandable.querySelector( '[data-expandable-trigger]' );
        var expandableGroup    = findAncestor( expandable, '[data-expandable-group]' );
        var isExpanded         = ( expandable.getAttribute( 'data-expandable-container' ) == 'collapsed' ) ? false : true;
        var expandableCallback = null;
        var callbackName;            
    
        // expandableCallback will be called if you set expandableOverride to 'true' (string val).
        // The first (and only) argument to expandableCallback will be e (the click event).
        // If you do this, you will need to open the expandable on your own. You will also need to obtain the field to update.
        // See toggle() above for how to do this. Or, you could just call toggle() on your own and pass e along to it.
        var expandableOverride = expandable.getAttribute( 'data-expandable-override' );
        
        if( expandableOverride ) {
            callbackName = expandable.getAttribute( 'data-flyout-callback' ); 
            expandableCallback = ( callbackName == undefined ) ? null : callbackName; // string, name of function to call
            if( expandableCallback == null ) { console.warn( 'Expandables Plugin did not detect custom callback for override, Node:', expandable ); }
        }
    
        if( expandableTarget == null ) { console.warn( 'Expandables Plugin did not detect target, Node:', expandable ); }
        if( expandableTrigger == null ) { console.warn( 'Expandables Plugin did not detect trigger, Node:', expandable) ; }
    
        // As you instantiate new expandablePlugins, insert them in the expandableStore object, indexed by the name of the expandable.
        this.storeExpandable( 
            expandableName,
            new expandablePlugin.launch({
                id               : expandableName,
                container        : expandable, 
                trigger          : expandableTrigger, 
                target           : expandableTarget, 
                targetGroup      : expandableGroup, 
                override         : expandableOverride, 
                customCallback   : expandableCallback, 
                expanded         : isExpanded
            })
        );  
    
        function findAncestor(el, sel) {
            while ((el = el.parentElement) && !((el.matches || el.matchesSelector).call(el,sel)));
            return el;
        }
    
    }

    return { 
        launch             : Constructor, 
        registerExpandable : registerExpandable, 
        storeExpandable    : setExpandable, 
        getExpandable      : getExpandable
    };   
  
})();

(function() {
  
    var initExpandablesJS = function() {
        
        var expandables = document.querySelectorAll( '[data-expandable-container]' );
        if( expandables == null ) return; 

        for( var i = 0; i < expandables.length; i++ ) {
            expandablePlugin.registerExpandable( expandables[ i ], i );
        }

    }

    if( typeof runOnAppStart == "function" ) {
        runOnAppStart( 'initializeExpandablesJS', initExpandablesJS );
    } else { 
        initExpandablesJS();
    }

})();