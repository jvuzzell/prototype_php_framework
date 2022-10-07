var StandardApplication = (function() {

    var instanceStore = {};

    var Constructor = function( props = {}, state = {} ) {

        var private = {
            id            : state.id,           
            props         : {}, 
            state         : {}, 
            notification  : {
                publishers : [] // An array of arrays (not objects)
            }, 
            moduleStore   : {}, 
        };

        var public = {  
            getters       : {}, 
            mutations     : {}, 
            actions       : {}
        }
    
        /**
         * Notifications
         */

        private.notification.registerPublishers = function( moduleName ) {

            private.notification.publishers[ moduleName ] = [];

        }

        private.notification.notifySubscribers = function( noticeParams ) {

            var subscriberNames = private.notification.publishers[ noticeParams.notifierKey ];
            var subscriberModule = undefined;
            var applicationId = get( 'getAppId' );

            console.warn( 'dispatch notification: ', applicationId, noticeParams.notifierKey, subscriberNames );
   
            if( subscriberNames !== undefined ) {
                  
                for( var i = 0; i < subscriberNames.length; i++ ) {
  
                    subscriberModule = get( 'getModuleFromStore', { 'moduleKey' : subscriberNames[ i ] } );

                    if( subscriberModule !== undefined && subscriberModule !== false ) {
                        subscriberModule.dispatch.update( noticeParams.notifierKey, noticeParams.notifierStateDelta, applicationId );
                    } else {
                        console.warn( 'StandardApplication plugin; Application ID: "' + applicationId + '" - unable to retrieve module from module store. Expected module key: "' + subscriberNames[ i ] + '"' );
                    }
    
                }

            }

        }

        /* Internal actions */
        public.actions = {
    
            registerModule : function( state, parameters = {} ) {
                
                // Add parent ID to submodule 
                var submodule = parameters[ 'moduleObj' ]; 
                var submoduleParents = ( submodule[ 'parentApplicationNames' ] !== undefined ) ? submodule[ 'parentApplicationNames' ] : [];

                submoduleParents.push( get( 'getAppId' ) );
                submoduleParents = submoduleParents.unique(); // Remove duplicates

                parameters[ 'moduleObj' ].commit.state({ parentApplicationNames : submoduleParents });

                commit( 'addModuleToAppStore', { 
                        'key'    : parameters[ 'key' ], 
                        'object' : parameters[ 'moduleObj' ]
                    });
            
            },
 
            updateApplication : function( state, parameters = {} ) {

                private.notification.notifySubscribers( parameters );
                
            }, 

            addSubscribers : function( state, params = {} ) {

                var publisherKey  = params.publisherKey; // Expects an unnamed
                var subscriberKey = params.subscriberKey;    // Expects an unnamed array
                var publisher     = private.notification.publishers[ publisherKey ]; 

                if( publisher === '' || publisher === undefined ) {
    
                    console.warn( 'Standard Application: observers not registered; publisher ID not found' );
                    return false;
    
                }

                publisher.push( subscriberKey );

                // Remove Duplicates
                private.notification.publishers[ publisherKey ] = publisher.unique(); 
    
            }, 
            
        }
        
        /* Internal Mutations */
        public.mutations = {
    
            addModuleToAppStore : function( state, parameters = {} ) {
                
                var moduleKey = parameters[ 'key' ]; 
                var moduleObj = parameters[ 'object' ];
        
                if( moduleKey == undefined || moduleObj == undefined ) { 
                    console.warn( 'Standard Application: Module instance was not stored; id or object missing. Instance objection below: ' );
                    console.warn( ( moduleObj !== undefined ) ? '--> ' + moduleObj.get.state( 'key' ) : '--> key missing' );
                }
                
                // Add module to storage
                private.moduleStore[ moduleKey ] = moduleObj;

                // Initialize observer object for module. Object represents a list of modules
                // that should be notified if this module's state changes. 
                private.notification.registerPublishers( moduleKey );
        
            }
        
        };
    
        /* Internal Getters */
        public.getters = {
            
            getModuleFromStore : function( state, payload = {} ) {
                var moduleExists = private.moduleStore.hasOwnProperty( payload.moduleKey );
                return ( moduleExists ) ? private.moduleStore[ payload.moduleKey ] : moduleExists;
            },
        
            getAllModulesFromStore : function( state, payload = {} ) {
                return private.moduleStore;
            }, 

            getAppId : function( state, payload = {} ) {
                return private.id;
            }, 

            getPublishers : function( state, payload = {} ) {

                if( payload.publisherId == '' || payload.publisherId === undefined ) {

                    return private.notification.publishers;

                } else {

                    return private.notification.publishers[ payload.publisherId ];

                }

            }, 
        
        };
    
        // Access getters
        var get = function( getterName, parameters = {} ) {
            
            if( public.getters[ getterName ] === undefined ) {
                console.error( 'Standard Application: getter "' + getterName + '", not defined' );
                return;
            }

            return public.getters[ getterName ]( private.state, parameters );
    
        }
    
        // Access actions
        var dispatch = function( actionName, parameters = {} ) {

            if( public.actions[ actionName ] === undefined ) {
                console.error( 'Standard Application: action "' + actionName + '", not defined' );
                return;
            }

            return public.actions[ actionName ]( private.state, parameters );
    
        }
    
        // Access mutations
        var commit = function( mutationName, parameters = {} ) {

            if( public.mutations[ mutationName ] === undefined ) {
                console.warn( 'Standard Application: mutation "' + mutationrName + '", not defined' );
                return;
            }

            return public.mutations[ mutationName ]( private.state, parameters );
    
        }

        return {
            dispatch : dispatch, 
            commit   : commit, 
            get      : get, 
            app      : public
        }

    }

    var registerInstance = function( params = {} ) {

        // Handle expected parameters
        params.state = ( params === undefined ) ? undefined : params.state;

        // Do not create an application without a name
        if( params.state.id === undefined|| params.state.id == '' ) {
            console.error( 'Fatal Error - Standard Application: Failed to register new instance; ID not provided' );
            return false;
        }

        // Avoid overwriting existing modules
        var existingApplications = this.getAllInstances();

        for( var i = 0; i < existingApplications.length; i++ ) {

            if( this.getInstance( existingApplications[ i ] ).getAppId() ) {
                console.error( 'Fatal Error - Standard Application: Failed to register new instance; ID already exists' );
                return false; 
            }

        }

        this.storeInstance(
            params.state.id, 
            new StandardApplication.construct( params.props, params.state )
        );

    }

    var storeInstance = function( instanceId, instanceObj ) {
    
        instanceStore[ instanceId ] = instanceObj;

    }

    var getInstance = function( nameOfInstance = '' ) {

        return ( nameOfInstance !== '' ) ? instanceStore[ nameOfInstance ] : instanceStore;

    }

    var deleteInstance = function( nameOfInstance ) {
        
        // TODO: 1) Delete module object from store 
        //       2) Remove event listeners
    
    }

    // Polyfill for making sure elements are unique within an array

    if( Array.prototype.unique === undefined ) {

        Array.prototype.unique = function() {

            var a = this.concat();
 
            for(var i=0; i<a.length; ++i) {
                for(var j=i+1; j<a.length; ++j) {
                    if(a[i] === a[j]) {
                        a.splice(j--, 1);
                    }
                }
            }

            return a;
        };

    }

    return {
        construct        : Constructor,
        registerInstance : registerInstance,
        storeInstance    : storeInstance,
        getInstance      : getInstance, 
        getAllInstances  : getInstance,
        deleteInstance   : deleteInstance
    }
    
})(); // end of MyModule