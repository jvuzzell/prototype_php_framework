var jsHelper = (function() {
var instance; 

function createInstance( window, document ) {
    
    var object = (function() {
        var methods = {};

        methods.get = function (selector, scope) {
            if (!selector) throw new Error('Please provide a selector.');
            return document.querySelector(selector);
        };

        methods.getAll = function (selector, scope) {
            if (!selector) throw new Error('Please provide a selector.');
            return scope ? scope.querySelectorAll(selector) : document.querySelectorAll(selector);
        };

        methods.on = function (elem, eventString, callback, useCaptureBool) {
            if (!elem) throw new Error('Please provide an element to attach the event to.');
            if (!eventString) throw new Error('Please provide an event to listen for.');
            if (!callback || typeof callback !== 'function') throw new Error('Please provide a valid callback function to run');

            window.addEventListener( eventString, function( e ) {
                if( e.target.isEqualNode( methods.get( elem, document ) ) ) {  
                    callback( methods.get( elem, document ) );
                }
            }, useCaptureBool );
        };

        methods.addClass = function( elemsAddClassTo, classesToAdd ) {
            if ( !elemsAddClassTo ) throw new Error( 'Please provide Node or NodeList.' );
            if ( !classesToAdd ) throw new Error( 'Please provide classes to add to Node.' );

            // Check for Node or NodeList
            if( elemsAddClassTo.keys != undefined ) {
                for( var i = 0; i < elemsAddClassTo.length; i++ ){
                    elemsAddClassTo[i].className = elemsAddClassTo[i].className + " " + classesToAdd;
                }
            } else {
                elemsAddClassTo.className = elemsAddClassTo.className + " " + classesToAdd;
            }
        }
        
        methods.removeClass = function( elemsRemoveClassFrom, classesToRemove, dataType ) {
            if ( !elemsRemoveClassFrom ) throw new Error( 'Please provide Node or NodeList.' );
            if ( !classesToRemove ) throw new Error( 'Please provide classes to remove to Node.' );

            var classes = classesToRemove.split( ' ' );
            var elemArray = [];

            // Check for Node or NodeList
            if( elemsRemoveClassFrom.keys != undefined ) {
                elemArray = elemsRemoveClassFrom;
            } else {
                elemArray[0] = elemsRemoveClassFrom;
            }

            for( var x = 0; x < elemArray.length; x++ ) {
                for( var y = 0; y < classes.length; y++ ) {
                    var exp = [
                        new RegExp( " "+classes[y],'g' ),
                        new RegExp( classes[y]+" ",'g' ),
                        new RegExp( classes[y],'g' )
                    ];

                    for( var z = 0; z < exp.length; z++ ) {
                        elemArray[x].className = elemArray[x].className.replace( exp[z], '' );
                    }
                }
            }
        }
        
        methods.isObjectEmpty = function( obj ) {
            for( var key in obj ) {
            if( obj.hasOwnProperty( key ) )
                return false;
            }
            return true;
        }
        
        methods.hasClass = function( elem, classesToFind ){
            var findThese = new RegExp( classesToFind,'g' );
            return findThese.test( elem.className );
        }
        
        methods.toggleClass = function( elem, className  ) {
            if( this.hasClass( elem, className ) ) {
                this.removeClass( elem, className, 'string' );
            } else {
                this.addClass( elem, className, 'string' );
            }
        }
            
        methods.windowWidth = function() {
            return Math.max( window.innerWidth, window.outerWidth, document.documentElement.clientWidth );
        }
        
        methods.currentScrollPosY = function() {
            return window.pageYOffset;  
        }
        
        methods.toggleData = function( elem, dataAttr, state, newState ) {
            if( elem.getAttribute( dataAttr ) == state ) {
                elem.setAttribute( dataAttr, newState );
            } else {
                elem.setAttribute( dataAttr, state );
            }
        }
        
        methods.setCookie = function( cname, cvalue, exdays ) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            var expires = "expires="+ d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        methods.getCookie = function( cname ) {
            var name = cname + "=";
            var decodedCookie = decodeURIComponent(document.cookie);
            var ca = decodedCookie.split(';');
            for(var i = 0; i <ca.length; i++) {
              var c = ca[i];
              while (c.charAt(0) == ' ') {
                c = c.substring(1);
              }
              if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
              }
            }
            return null;
        }

        methods.checkCookie = function( cname ) {
            var username = this.getCookie("username");
            if (username != "") {
             alert("Welcome again " + username);
            } else {
              username = prompt("Please enter your name:", "");
              if (username != "" && username != null) {
                this.setCookie("username", username, 365);
              }
            }           
        }

        methods.debounce = function( func, wait, immediate ) {
            var timeout;
        
            return function executedFunction() {
                var context = this;
                var args = arguments;
                    
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
            
                var callNow = immediate && !timeout;
                
                clearTimeout(timeout);
            
                timeout = setTimeout(later, wait);
                
                if (callNow) func.apply(context, args);
            };
        }     
        
        methods.getQueryVariable = function ( variable )  {
            var query = window.location.search.substring(1);
            var vars = query.split("&");
            for (var i=0;i<vars.length;i++) {
                var pair = vars[i].split("=");
                if(pair[0] == variable){ return pair[1]; }
            }
            return(false);
        }
        
        methods.getUrlPath = function ()  {
            var query = window.location.pathname.substring(1);
            var vars = query.split("/");

            return(vars);
        }      
        
        methods.classSearchChildren = function ( HTMLCollection, classes ) {
            if(  HTMLCollection.children == "undefined" || HTMLCollection.children.length < 1 ) {
                console.error( "Function: jsHelper, Method: childrenWithClassName, Error: Cannot not process object provided" );
                return false;
            }
        
            var childFound = false,  
                noChildren = false, 
                children = HTMLCollection.children, 
                childrenFound = [];
        
            while( !childFound ){
                if( children.length < 1 ) {
                    noChildren = true; 
                    console.error( "Error: Failed to find DOM element using class(es) \'" + classes + "\'. No more children." );
                    
                    break;
                }
            
                for( var i=0; i < children.length; i++){
                    var child = children[i];
                    if( hasClass( child, classes ) ) {
                    childFound = true;
                    childrenFound.push( child );
                    }
                }
            }
            
            return ( childFound ) ? childrenFound : false; 
        }// end childrenWithClassName 

        methods.browserDetect = function() {
            var ua = navigator.userAgent, tem, M = ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || []; 
            var browser = {
                name: 'unknown', 
                version: 'unknown'
            }

            // IE 10 or Older
            if( /MSIE/i.test( M[1] ) ){
                tem=/\brv[ :]+(\d+)/g.exec(ua) || []; 

                browser.name = 'IE'; 
                browser.version = ( tem[1] || '' ); 
            }   

            // IE 11
            if( /Trident/i.test( M[1] ) ){
                tem=/\brv[ :]+(\d+)/g.exec(ua) || []; 

                browser.name = 'IE'; 
                browser.version = ( tem[1] || '' ); 
            }   

            if( M[1] === 'Chrome' ){
                tem = ua.match( /\bOPR|Edge\/(\d+)/ ); 

                // Opera
                if( tem != null )   { 
                    browser.name = 'Opera'; 
                    browser.version = tem[1];
                }
            }   

            M = M[2] ? [M[1], M[2]] : [navigator.appName, navigator.appVersion, '-?'];

            if( ( tem = ua.match(/version\/(\d+)/i ) ) != null ) {
                M.splice( 1, 1, tem[1] );
            }

            // Add browser data to Body tag
            document.body.setAttribute( 'data-browser',  M[0] )
            document.body.setAttribute( 'data-browser-version', M[1] );

            // Other
            browser.name = M[0]; 
            browser.version = M[1]; 

            return browser; 

        }

        /**
         * @param {Node}    app      The element to inject markup into
         * @param {String}  template  The string to inject into the element
         * @param {Boolean} append    [optional] If true, append string to existing content instead of replacing it
         */

        methods.saferInnerHTML = function ( app, template, append ) {

            'use strict';

            //
            // Variables
            //

            var parser = null;

            //
            // Methods
            //

            var supports = function () {
                if ( !Array.from || !window.DOMParser ) return false;
                parser = parser || new DOMParser();
                try {
                    parser.parseFromString('x', 'text/html');
                } catch(err) {
                    return false;
                }
                return true;
            };

            /**
             * Add attributes to an element
             * @param {Node}  elem The element
             * @param {Array} atts The attributes to add
             */
            var addAttributes = function (elem, atts) {
                atts.forEach(function (attribute) {
                    // If the attribute is a class, use className
                    // Else if it starts with `data-`, use setAttribute()
                    // Otherwise, set is as a property of the element
                    if (attribute.att === 'class') {
                        elem.className = attribute.value;
                    } else if (attribute.att.slice(0, 5) === 'data-') {
                        elem.setAttribute(attribute.att, attribute.value || '');
                    } else {
                        elem[attribute.att] = attribute.value || '';
                    }
                });
            };

            /**
             * Create an array of the attributes on an element
             * @param  {NamedNodeMap} attributes The attributes on an element
             * @return {Array}                   The attributes on an element as an array of key/value pairs
             */
            var getAttributes = function (attributes) {
                return Array.from(attributes).map(function (attribute) {
                    return {
                        att: attribute.name,
                        value: attribute.value
                    };
                });
            };

            /**
             * Make an HTML element
             * @param  {Object} elem The element details
             * @return {Node}        The HTML element
             */
            var makeElem = function (elem) {

                // Create the element
                var node = elem.type === 'text' ? document.createTextNode(elem.content) : document.createElement(elem.type);

                // Add attributes
                addAttributes(node, elem.atts);

                // If the element has child nodes, create them
                // Otherwise, add textContent
                if (elem.children.length > 0) {
                    elem.children.forEach(function (childElem) {
                        node.appendChild(makeElem(childElem));
                    });
                } else if (elem.type !== 'text') {
                    node.textContent = elem.content;
                }

                return node;

            };

            /**
             * Render the template items to the DOM
             * @param  {Array} map A map of the items to inject into the DOM
             */
            var renderToDOM = function (map) {
                if (!append) { app.innerHTML = ''; }
                map.forEach(function (node, index) {
                    app.appendChild(makeElem(node));
                });
            };

            /**
             * Create a DOM Tree Map for an element
             * @param  {Node}   element The element to map
             * @return {Array}          A DOM tree map
             */
            var createDOMMap = function (element) {
                var map = [];
                Array.from(element.childNodes).forEach(function (node) {
                    map.push({
                        content: node.childNodes && node.childNodes.length > 0 ? null : node.textContent,
                        atts: node.nodeType === 3 ? [] : getAttributes(node.attributes),
                        type: node.nodeType === 3 ? 'text' : node.tagName.toLowerCase(),
                        children: createDOMMap(node)
                    });
                });
                return map;
            };

            /**
            * Convert a template string into HTML DOM nodes
            * @param  {String} str The template string
            * @return {Node}       The template HTML
            */
            var stringToHTML = function (str) {
                parser = parser || new DOMParser();
                var doc = parser.parseFromString(str, 'text/html');
                return doc.body;
            };


            //
            // Inits
            //

            // Don't run if there's no element to inject into
            if (!app) throw new Error('safeInnerHTML: Please provide a valid element to inject content into');

            // Check for browser support
            if (!supports()) throw new Error('safeInnerHTML: Your browser is not supported.');

            // Render the template into the DOM
            renderToDOM(createDOMMap(stringToHTML(template)));

        };

        return methods;

    })();
    
    return object;     
}

return {
    getInstance: function() {
        if( !instance ) {
            instance = createInstance( window, document );
        }
        return instance; 
    }
}

})( 
    window, 
    document
);

JsHelper = jsHelper.getInstance();
