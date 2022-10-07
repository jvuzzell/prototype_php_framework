var StandardModule = (function( StandardApplication ) {

    var instanceStore = {};

    // Module Features
    var Constructor = function( moduleConfig = {}, inlineTemplateNode ) {

        // Public Methods
        var public = {
            get      : {}, 
            commit   : {},
            dispatch : {}, 
            hooks    : {

                beforeCreate : function( state ) { console.error( '1. This will not run unless defined during module registration', state ); },
                beforeUpdate : function( state ) { console.log( '2. Module will update: ' + state.key ); }, 
                onUpdate     : function( state ) { console.log( '3. Module updating: ' + state.key ); },
                afterUpdate  : function( state ) { console.log( '4.  Module updated: ' + state.key );  }, 
                afterCreate  : function( state ) { console.log( '5. Module was created: ' + state.key ) },
                beforeMount  : function( state ) { console.log( '6. Module will mount: ' + state.key ); }, 
                onMount      : function( state ) { console.log( '7. Module mounting: ' + state.key ); },                
                afterMount   : function( state ) { console.log( '8. Module has mounted: ' + state.key ); }, 
            }

        };

        // Private
        var module = {
            ref       : '',
            props     : {}, 
            state     : {
                key : '',                    // Placeholder; created dynamically at the time the module is instantiated
                moduleName : '',             // Placeholder; this the common name of the module determined by the developer manually, is not unique
                parentApplicationNames : [], // Placeholder; this is updated when the the module is registered to application
                eventListeners : {}
            }, 
            inlineTemplateNode : inlineTemplateNode
        };

        var private = {};

        /**
         * Update application, notifies application(s) that a change has occured within a given module 
         */

        private.notifyApplication = function( notifierKey = '', notifierStateDelta = {} ) {

            var parentApplicationNames = public.get.state( 'parentApplicationNames' );

            for( var i = 0; i < parentApplicationNames.length; i++ ) {
                StandardApplication.getInstance( parentApplicationNames[ i ] ).dispatch(
                    'updateApplication', 
                    {
                        'notifierType'       : 'module',
                        'notifierKey'        : notifierKey,  
                        'notifierStateDelta' : notifierStateDelta
                    }
                );
            }

        }

        private.hasStateChanged = function( newState ) {

            var currentState      = public.get.state(); 
            var comparison        = {};
            var intentionalChange = false; // True if any value within comparison.diff !== null 
            
            comparison.stateChanged = false;
            comparison.diff         = private.compareObjects( currentState, newState );

            for( var key in comparison.diff ) {
                
                // Handling Unintentional Changes in State changes
                // 
                // Evaluate the comparison.diff for values other than null. 
                // If the current state has key-value pairs that the new state 
                // does not then the comparison.diff will contain the key 
                // with a value of null. This counts as true change however, 
                // we are going to interpret null as an unintentional change in state.
                // 
                // In order for a developer to commit an intentional change where 
                // the new state of includes a key-value pair with value = null, 
                // then consider removing the key from the currentState or setting the
                // value of currentState at the given key-value pair to an undefined, 
                // empty string, zero, negative one or false.

                if( comparison.diff[ key ] === null ) {
                    continue; 
                } else {
                    intentionalChange = true; 
                    break; // if even one field has intentionally changed then exit loop
                }

            }     
                
            if( Object.keys( comparison.diff ).length > 0 && intentionalChange === true ) {
                comparison.stateChanged = true;
            }

            return comparison;
            
        }

        /* Getters */

        public.get.state = function( fieldName = '' ) {

            return ( fieldName == '' ) ? module.state : module.state[ fieldName ];

        }

        public.get.props = function( fieldName = '' ) {

            return ( fieldName == '' ) ? module.props : module.props[ fieldName ];

        }

        public.get.ref = function() {

            return module.ref;

        }

        public.get.observers = function( observerName = '' ) {

            if( observerName !== '' ) {
                observerIndex = module.observers.indexOf( observerName );
                observer = module.observers[ observerIndex ];
            }

            return ( observerName == '' ) ? module.observers : observer;

        }

        public.get.inlineTemplateNode = function() {

            return module.inlineTemplateNode;

        }

        /* Mutations */

        public.commit.state = function( newState = {}, triggerRender = true, triggerNotification = true ) {
            
            // 1) If state is different then update 

            var differencesInState = private.hasStateChanged( newState ); // Returns object

            if( differencesInState.stateChanged === true ) {

                // 2) Update current state object with only the things that changed

                for( var key in differencesInState.diff ) {
                
                    // Skip so that we don't overwrite values in current state with null
                    if( differencesInState.diff[ key ] === null ) continue;

                    module.state[ key ] = differencesInState.diff[ key ];

                }

                console.log( 'Change in state: ', newState );

                // 3) Re-render existing node
                if( !triggerRender ) return; 
                public.dispatch.render();
                
                // 4) Notify observers
                if( !triggerNotification ) return;
                private.notifyApplication( public.get.state ('key' ), newState );
            
            }

        }

        public.commit.props = function( newProps = {} ) {

            for( var key in newProps ) {
                module.props[ key ] = newProps[ key ];
            }     

        }

        public.commit.ref = function( newRef = '' ) { 

            module.ref = newRef;

        }

        /* Actions */

        public.dispatch.update = function( notifierKey, notifierStateDelta ) {

            // This is a response to an Application Notification
            // Developers are responsible for defining this method
            
        }

        public.dispatch.notifyApplication = function( notifierKey, notifierStateDelta ) {

            private.notifyApplication( notifierKey, notifierStateDelta );

        }

        public.dispatch.mount = function() {

            // 1) Hook beforeMount - compile template
            public.hooks.beforeMount( public.get.state() );

            // 2) Hook onMount - add template to DOM
            public.hooks.onMount( public.get.state() );

            // 3) Hook afterMount - post processing
            public.hooks.afterMount( public.get.state() );

            // 4) Notify the rest of the application
            public.dispatch.notifyApplication( public.get.state( 'key' ), public.get.state() );

        }

        public.dispatch.render = function() {

            // 1) Hook beforeUpdate- compile template
            public.hooks.beforeUpdate( public.get.state() );

            // 2) Hook onUpdate - add template to DOM
            public.hooks.onUpdate( public.get.state() );

            // 3) Hook afterUpdate - post processing
            public.hooks.afterUpdate( public.get.state() );

        }

        public.dispatch.registerInstance = function( moduleConfig = {} ) {

            // Update Gets

            if( 
                moduleConfig.get !== undefined && 
                moduleConfig.get !== null && 
                typeof( moduleConfig.get ) !== "object" 
            ) {

                var getKeys = Object.keys( moduleConfig.get );

                for( var i = 0; i < getKeys.length; i++ ) {
                    public.get[ getKeys[ i ] ] = moduleConfig.get[ getKeys[ i ] ];
                }
                
            }
            
            // Update Commits (Changes in state)

            if( 
                moduleConfig.commit !== undefined && 
                moduleConfig.commit !== null && 
                typeof( moduleConfig.commit ) === "object" 
            ) {

                var commitKeys = Object.keys( moduleConfig.commit );

                for( var i = 0; i < commitKeys.length; i++ ) {
                    public.commit[ commitKeys[ i ] ] = moduleConfig.commit[ commitKeys[ i ] ];
                }

            }

            // Update dispatches (Actions)

            if( 
                moduleConfig.dispatch !== undefined && 
                moduleConfig.dispatch !== null && 
                typeof( moduleConfig.dispatch ) === "object" 
            ) {

                var dispatchKeys = Object.keys( moduleConfig.dispatch );

                for( var i = 0; i < dispatchKeys.length; i++ ) {
                    public.dispatch[ dispatchKeys[ i ] ] = moduleConfig.dispatch[ dispatchKeys[ i ] ];
                }

            }

            // Update Hooks (Intrinsic/Custom)

            if( 
                moduleConfig.hooks !== undefined && 
                moduleConfig.hooks !== null && 
                typeof( moduleConfig.hooks ) === "object" 
            ) {

                var hookKeys = Object.keys( moduleConfig.hooks );
                var keyToUse = ''; // Used to identify/select public key

                for( var i = 0; i < hookKeys.length; i++ ) {

                    switch( hookKeys[ i ] ) {
                        
                        case 'beforeCreate' : 
                            keyToUse = 'beforeCreate';
                            break;

                        case 'created' : 
                            keyToUse = 'created';
                            break;

                        case 'beforeMount' : 
                            keyToUse = 'beforeMount';
                            break;

                        case 'onMount' : 
                            keyToUse = 'onMount';
                            break;

                        case 'afterMount' : 
                            keyToUse = 'afterMount';
                            break;

                        case 'beforeUpdate' : 
                            keyToUse = 'beforeUpdate';
                            break;

                        case 'onUpdate' : 
                            keyToUse = 'onUpdate';
                            break;

                        case 'afterUpdate' : 
                            keyToUse = 'afterUpdate';
                            break;

                        default : 
                            keyToUse = hookKeys[ i ];
                            break;
                    
                    }

                    // Replace the 'create', 'beforeMount', 'onMount', and 'afterMount' functions
                    // with the incoming method specified by the developer OR add a custom hook 
                    // specified by the developer 

                    public.hooks[ keyToUse ] = moduleConfig.hooks[ hookKeys[ i ] ];
            
                }

            }

            var triggerRender = false; // Waiting to mount check 

            // Update the module's internal module object
            public.commit.ref( moduleConfig.ref );
            public.commit.props( moduleConfig.props );           
            public.commit.state( moduleConfig.state, triggerRender );

            return public;

        }

        public.dispatch.createInlineTemplate = function( template, moduleKey ) {

            var inlineTemplateNode = StandardModule.templateToHTML( template ).querySelector( 'div' );
            inlineTemplateNode.setAttribute( 'data-key', moduleKey );

            module.inlineTemplateNode = inlineTemplateNode; 

            return public.get.inlineTemplateNode();

        }

        // Grant access to all public methods within each of the children of the public class

        public.get.parent = function() {

            return {
                commit   : public.commit, 
                dispatch : public.dispatch, 
                hooks    : public.hooks
            };

        };

        public.dispatch.parent = function() {

            return {
                get    : public.get, 
                commit : public.commit, 
                hooks  : public.hooks
            };

        };

        public.commit.parent = function() {

            return {
                get      : public.get, 
                dispatch : public.dispatch, 
                hooks    : public.hooks
            };

        };

        public.hooks.parent = function() {

            return {
                get      : public.get,  
                commit   : public.commit, 
                dispatch : public.dispatch, 
                hooks    : public.hooks
            }; 

        };

        /*!
         * Find the differences between two objects and push to a new object
         * (c) 2019 Chris Ferdinandi & Jascha Brinkmann, MIT License, https://gomakethings.com & https://twitter.com/jaschaio
         * @param  {Object} currentState The original object
         * @param  {Object} newState The object to compare against it
         * @return {Object} An object of differences between the two
         */

        private.compareObjects = function( currentState = {}, newState = {} ) {

            // Make sure an object to compare is provided
            if (!newState || Object.prototype.toString.call(newState) !== '[object Object]') {
                return currentState;
            }

            //
            // Variables
            //

            var diffs = {};
            var key;

            //
            // Methods
            //

            /**
             * Check if two arrays are equal
             * @param  {Array}   arr1 The first array
             * @param  {Array}   arr2 The second array
             * @return {Boolean}      If true, both arrays are equal
             */
            var arraysMatch = function (arr1, arr2) {

                // Check if the arrays are the same length
                if (arr1.length !== arr2.length) return false;

                // Check if all items exist and are in the same order
                for (var i = 0; i < arr1.length; i++) {
                    if (arr1[i] !== arr2[i]) return false;
                }

                // Otherwise, return true
                return true;

            };

            /**
             * Compare two items and push non-matches to object
             * @param  {*}      item1 The first item
             * @param  {*}      item2 The second item
             * @param  {String} key   The key in our object
             */
            var compare = function (item1, item2, key) {

                // Get the object type
                var type1 = Object.prototype.toString.call(item1);
                var type2 = Object.prototype.toString.call(item2);

                // If type2 is undefined it has been removed
                if (type2 === '[object Undefined]') {
                    diffs[key] = null;
                    return;
                }

                // If items are different types
                if (type1 !== type2) {
                    diffs[key] = item2;
                    return;
                }

                // If an object, compare recursively
                if (type1 === '[object Object]') {
                    var objDiff = private.compareObjects(item1, item2);
                    if (Object.keys(objDiff).length > 1) {
                        diffs[key] = objDiff;
                    }
                    return;
                }

                // If an array, compare
                if (type1 === '[object Array]') {
                    if (!arraysMatch(item1, item2)) {
                        diffs[key] = item2;
                    }
                    return;                }

                // Else if it's a function, convert to a string and compare
                // Otherwise, just compare
                if ( type1 === '[object Function]' ) {

                    if ( item1.toString() !== item2.toString() ) {

                        diffs[ key ] = item2;

                    }

                } else {

                    if ( item1 !== item2 ) {

                        diffs[ key ] = item2;

                    }

                }

            };

            //
            // Compare our objects
            //

            // Loop through the first object
            for (key in currentState) {

                if (currentState.hasOwnProperty(key)) {

                    compare(currentState[key], newState[key], key);

                }

            }

            // Loop through the second object and find missing items
            for (key in newState) {

                if (newState.hasOwnProperty(key)) {

                    if (!currentState[key] && currentState[key] !== newState[key] ) {
                        diffs[key] = newState[key];
                    }

                }

            }

            // Return the object of differences
            return diffs;

        }

        // This runs immediately when the constructor is called
        public.dispatch.registerInstance( moduleConfig );

        // This binds the public to the module exposing them for consumption publically
        return (function(){
            
            return public;

        })();

    };

    // Plugin Features
    var ensureUniqueKey = function( newKey, existingKeys, moduleName ) {

        var needNewKey = false; 

        // TODO: Create a hash table of module instances
        for( var i = 0; i < existingKeys.length; i++ ) {

            if( newKey === existingKeys[ i ] ) { 
                needNewKey = true;
                break;
            }

        }

        return ( needNewKey ) ? ensureUniqueKey( returnRandomKey( moduleName ), existingKeys, moduleName ) : newKey;
        
    }

    var returnRandomKey = function( moduleName ) {

        return 'module_' + moduleName + '_' + Math.floor(Math.random() * Math.floor(100000));

    }

    var registration = function( moduleConfig, manualRegistration = false ) {

        var state = moduleConfig.state; 
        var newModuleKey = '';
        var modulesCreated = {}; // More than one module can be created at this time because of inline templating

        if( state.moduleName === undefined || state.moduleName === null ) {
            console.warn( 'Standard Application: module instance not registered; state.moduleName not specified' );
            return false;
        }

        // If Inline Templates are detected then each item becomes a sub module of the parent module 

        // If there are inline templates then create an instance of the module with that inline template
        // Otherwise the module will be created as an standalone instance of the module 

        var inlineTemplateSelector = '[data-inline-template="' + moduleConfig.state.moduleName + '"]';
        var inlineTemplateNodeList = document.querySelectorAll( inlineTemplateSelector );
       
        if( inlineTemplateNodeList.length > 0 ) {
          
            for( var i = 0; i < inlineTemplateNodeList.length; i++ ) {
                
                // Make sure that we are not we are not duplicating module registration
                // TODO: Detect submodules
                var existingModuleInstance = StandardModule.getInstance( inlineTemplateNodeList[ i ].getAttribute( 'data-key' ) );
              
                if( !existingModuleInstance ) {

                    if( inlineTemplateNodeList[ i ].getAttribute( 'data-ref' ) !== null ) {

                        moduleConfig.ref = inlineTemplateNodeList[ i ].getAttribute( 'data-ref' );

                    }

                    newModuleKey = instantiate( moduleConfig, inlineTemplateNodeList[ i ] );

                    // add data-key attribute to prevent a dupe module instance the next time a new 
                    inlineTemplateNodeList[ i ].setAttribute( 'data-key', newModuleKey ); 
                    modulesCreated[ newModuleKey ] = getInstance( newModuleKey );

                    // Custom tasks performed after module has been created and reactivity has been established.
                    // Otherwise, standard routines will be performed which including notifications to related 
                    // applications that the state of a module has been updated, and the module itself will render

                    modulesCreated[ newModuleKey ].dispatch.mount(); 

                    // Set Event Listeners
                    setEventListeners( moduleConfig );

                } 

            }

        } else { 

            if( !manualRegistration ) {
                // Register without an inline template
                newModuleKey = instantiate( moduleConfig );
                modulesCreated[ newModuleKey ] = getInstance( newModuleKey );
                modulesCreated[ newModuleKey ].dispatch.mount(); 

                // Set Event Listeners
                setEventListeners( moduleConfig );
            }

        }

        if( manualRegistration ) {

            newModuleKey = instantiate( moduleConfig );
            modulesCreated[ newModuleKey ] = getInstance( newModuleKey );
            modulesCreated[ newModuleKey ].dispatch.mount(); 

        }

        // Return module
        return modulesCreated;

    }

    var setEventListeners = function( moduleConfig ) {

        // Add Eventlisteners (Eventlisteners are added to the Window as named functions)
        if(
            moduleConfig.props !== undefined && 
            moduleConfig.props.eventListeners !== undefined && 
            moduleConfig.props.eventListeners !== null && 
            typeof( moduleConfig.props.eventListeners ) === "object" 
        ) {

            // loop through events 
            var moduleEventSeriesKeys = Object.keys( moduleConfig.props.eventListeners );
            var moduleKey = moduleConfig.state.key;
            var currentModule = getInstance( moduleKey ); 

            for( var i = 0; i < moduleEventSeriesKeys.length; i++ ) {

                var moduleEventSeries = moduleConfig.props.eventListeners[ moduleEventSeriesKeys[ i ] ]; 

                // has the event been registered 
                var individualEvents = Object.keys( moduleEventSeries );
            
                for( var n = 0; n < individualEvents.length; n++ ) {

                    // Initialize event
                    moduleEventSeries[ individualEvents[ n ] ].eventInit( 
                        moduleKey, 
                        currentModule
                    );

                }

            }

            var triggerRender = false; 
            currentModule.commit.state( { eventListenersExist : true }, triggerRender );

        }

    }

    var instantiate = function( moduleConfig, inlineTemplateNode = null ) {

        // Be sure not to re-register nodes that have already been registered
        var allModules = StandardModule.getAllInstances();
        var moduleKey = '';
        var moduleState = moduleConfig.state;
        var beforeCreateExists = false; 

        // Determine whether user has defined custom 
        if( 
            moduleConfig.hooks !== undefined && 
            moduleConfig.hooks !== null && 
            typeof( moduleConfig.hooks ) === "object" 
        ) {

            if( 
                moduleConfig.hooks.beforeCreate !== undefined && 
                moduleConfig.hooks.beforeCreate !== null && 
                typeof( moduleConfig.hooks.beforeCreate ) === "function" 
            ) {
                
                beforeCreateExists = true; 

            }

        } 

        // Validate Module ID is unique
        if( moduleState.key === undefined ) {
            moduleKey = ensureUniqueKey( returnRandomKey( moduleState.moduleName ), Object.keys( allModules ), moduleState.moduleName );
        } else {
            moduleKey = ensureUniqueKey( moduleState.key, Object.keys( allModules ), moduleState.moduleName );
        }

        // Update ID within state to ensure that the state is consistent with valid value
        moduleConfig.state.key = moduleKey; 

        if( beforeCreateExists ) {
            // Perform tasks before module has been created
            moduleConfig.hooks.beforeCreate( moduleConfig.state ); 
        }

        // Store module in StandardModule
        storeInstance(
            moduleKey, 
            new StandardModule.construct( moduleConfig, inlineTemplateNode )
        );

        // Register module with parent applications
        if(
            moduleConfig.parentApplicationNames !== undefined && 
            moduleConfig.parentApplicationNames !== null && 
            typeof( moduleConfig.parentApplicationNames ) === "object" 
        ) {
            
            setParentApplication( moduleKey, moduleConfig ); 

        }

        // Register modules that this module should react to
        if(
            moduleConfig.subscriptions !== undefined && 
            moduleConfig.subscriptions !== null && 
            typeof( moduleConfig.subscriptions ) === "object" 
        ) {

            setSubscriptions( moduleKey, moduleConfig );

        } else {
            // Check to see if parentApplications have been specified
            if(
                moduleConfig.parentApplicationNames !== undefined && 
                moduleConfig.parentApplicationNames !== null && 
                typeof( moduleConfig.parentApplicationNames ) === "object" 
            ) {
                for( var i = 0; i < moduleConfig.parentApplicationNames.length; i++ ) {
                    subscribeToAllAppNotifications( moduleKey, moduleConfig.parentApplicationNames[ i ] );
                }
            }
        }
      
        // Custom tasks performed after module has been created and reactivity has been established.
        // Otherwise, standard routines will be performed which including notifications to related 
        // applications that the state of a module has been updated, and the module itself will render

        getInstance( moduleKey ).hooks.afterCreate( moduleConfig.state ); 
        
        return moduleKey;

    }

    var setParentApplication = function( moduleKey, moduleConfig ) {

        for( var i = 0; i < moduleConfig.parentApplicationNames.length; i++ ) {
            
            var application = StandardApplication.getInstance( moduleConfig.parentApplicationNames[ i ] );

            // Register module to given applications
            application.dispatch( 
                'registerModule', { 
                    'key'       : moduleKey, 
                    'moduleObj' : getInstance( moduleKey )
                });

        }

    }

    var storeInstance = function( instanceKey, instanceObj ) {

        instanceStore[ instanceKey ] = instanceObj;

    }

    var getInstance = function( nameOfInstance = '' ) {

        return ( nameOfInstance !== '' && nameOfInstance !== null && nameOfInstance !== undefined ) ? instanceStore[ nameOfInstance ] : false;

    }

    var getAllInstances = function() {
 
        return instanceStore;

    }

    var getModulesByModuleName = function( moduleName = '' ) {

        var moduleKeys = Object.keys( getAllInstances() );
        var modulesByModuleName = {};

        for( var i = 0; i < moduleKeys.length; i++ ) {

            var currentModule = getInstance( moduleKeys[ i ] ); 

            if( currentModule.get.state( 'moduleName' ) === moduleName ) {
 
                modulesByModuleName[ moduleKeys[ i ] ] = currentModule;
                
            }
    
        }  

        return modulesByModuleName; 

    }

    var setSubscriptions = function( subscriberKey = '', subscriptionPlan = {} ) {

        var applicationKeys = Object.keys( subscriptionPlan.subscriptions );

        for( var x = 0; x < applicationKeys.length; x++ ) {

            // Ensure that the module is registered with the application first
            var subscriptionApp = StandardApplication.getInstance( applicationKeys[ x ] );

            // Ensure that that the module that is subscribing to the application
            // is registered to the application first 
            if( !subscriptionApp.get( 'getModuleFromStore', { 'moduleKey' : subscriberKey } ) ) {
            
                StandardModule.setParentApplication( subscriberKey, {
                    parentApplicationNames : [ applicationKeys[ x ] ]
                });

            }

        }

        /**
         * Subscribe module to the given observable modules
         */

        for( var i = 0; i < applicationKeys.length; i++ ) {
  
            var application = StandardApplication.getInstance( applicationKeys[ i ] );
            var publisherKeys = subscriptionPlan.subscriptions[ applicationKeys[ i ] ]; 

            for( var j = 0; j < publisherKeys.length; j++ ) {
                // Subscribe observers  
                application.dispatch(
                    'addSubscribers', {
                        publisherKey  : publisherKeys[ j ], 
                        subscriberKey : subscriberKey
                    });                  

                // Subscribe to observers     
                application.dispatch(
                    'addSubscribers', {
                        publisherKey  : subscriberKey, 
                        subscriberKey : publisherKeys[ j ]
                    });                  
            }
            
        }

    }

    var subscribeToAllAppNotifications = function( moduleKey, applicationId ) {
        
        if( applicationId === undefined ) {
            console.warn( 'StandardModule Plugin, subscribeToAllAppNotifications: applicationId is undefined. Subscriptions failed.' );
            return false;
        }

        // Find all modules subscribed to the given application
        var subscriptionApp = StandardApplication.getInstance( applicationId );

        if( subscriptionApp !== undefined && subscriptionApp.hasOwnProperty( 'get' ) ) {

            // Retrieve all modules from that application 
            var modulesOfSubscriptionApp = subscriptionApp.get( 'getAllModulesFromStore' ); 

            // Subscribe to each module
            var allSubscriptionModuleKeys = Object.keys( modulesOfSubscriptionApp );
            var indexOfthisModuleKey = allSubscriptionModuleKeys.indexOf( moduleKey );

            // We do not want to subscribe this module to itself
            allSubscriptionModuleKeys.splice( indexOfthisModuleKey, 1 ); 

            StandardModule.setSubscriptions( moduleKey, {
                'subscriptions': {
                    [applicationId] : allSubscriptionModuleKeys
                },
            });

        } else {

            console.warn( 'Module subscriptions for application failed: ' + applicationId );

        }
         
    }

    /**
    * Convert a template string into HTML DOM nodes
    * @param  {String} str The template string
    * @return {Node}       The template HTML
    */
   var templateToHTML = function (str) {

        var support = (function () {
            if (!window.DOMParser) return false;
            var parser = new DOMParser();
            try {
                parser.parseFromString('x', 'text/html');
            } catch(err) {
                return false;
            }
            return true;
        })();

       // If DOMParser is supported, use it
       if (support) {
           var parser = new DOMParser();
           var doc = parser.parseFromString(str, 'text/html');
           return doc.body;
       }
   
       // Otherwise, fallback to old-school method
       var dom = document.createElement('div');
       dom.innerHTML = str;
       return dom;
   
   };

    // Polyfill for making sure elements are unique within an array

    if( Array.prototype.unique === undefined ) {

        Array.prototype.unique = function() {
            var a = this.concat();
            for(var i=0; i<a.length; ++i) {
                for(var j=i+1; j<a.length; ++j) {
                    if(a[i] === a[j])
                        a.splice(j--, 1);
                }
            }
        
            return a;
        };

    }

    return {
        construct              : Constructor,
        registration           : registration,
        storeInstance          : storeInstance,
        getInstance            : getInstance, 
        getAllInstances        : getAllInstances, 
        getModulesByModuleName : getModulesByModuleName, 
        setSubscriptions       : setSubscriptions, 
        setParentApplication   : setParentApplication,
        subscribeToAllAppNotifications : subscribeToAllAppNotifications, 
        templateToHTML         : templateToHTML, 
        setEventListeners      : setEventListeners
    }

})(
    StandardApplication
);
