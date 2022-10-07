var HTML5validation = ( function( window, document ) {

    callbacks = [];
    var validationMsgClass = 'validation-message';

    functionExists( window, document ); 

    function functionExists( window, document ) {

        if ( typeof HTML5validation == "undefined" ) {

            window.HTML5validation = this; 
            polyfill( window, document ); 

        }
        
    }
    
    function polyfill( window, document, undefined ) {

        'use strict';

        // Make sure that ValidityState is supported in full (all features)
        var supported = function () {
            var input = document.createElement( 'input' );
            return ('validity' in input && 'badInput' in input.validity && 'patternMismatch' in input.validity && 'rangeOverflow' in input.validity && 'rangeUnderflow' in input.validity && 'stepMismatch' in input.validity && 'tooLong' in input.validity && 'tooShort' in input.validity && 'typeMismatch' in input.validity && 'valid' in input.validity && 'valueMissing' in input.validity);
        };

        /**
         * Generate the field validity object
         * @param  {Node} field The field to validate
         * @return {Object}     The validity object
         */

        var getValidityState = function ( field ) {

            // Variables
            var type = field.getAttribute( 'type' ) || input.nodeName.toLowerCase();
            var isNum = type === 'number' || type === 'range';
            var length = field.value.length;
            var valid = true;

            // Run validity checks
            var checkValidity = {
                badInput: (isNum && length > 0 && !/[-+]?[0-9]/.test(field.value)), // value of a number field is not a number
                patternMismatch: (field.hasAttribute('pattern') && length > 0 && new RegExp(field.getAttribute('pattern')).test(field.value) === false), // value does not conform to the pattern
                rangeOverflow: (field.hasAttribute('max') && isNum && field.value > 1 && parseInt(field.value, 10) > parseInt(field.getAttribute('max'), 10)), // value of a number field is higher than the max attribute
                rangeUnderflow: (field.hasAttribute('min') && isNum && field.value > 1 && parseInt(field.value, 10) < parseInt(field.getAttribute('min'), 10)), // value of a number field is lower than the min attribute
                stepMismatch: (field.hasAttribute('step') && field.getAttribute('step') !== 'any' && isNum && Number(field.value) % parseFloat(field.getAttribute('step')) !== 0), // value of a number field does not conform to the stepattribute
                tooLong: (field.hasAttribute('maxLength') && field.getAttribute('maxLength') > 0 && length > parseInt(field.getAttribute('maxLength'), 10)), // the user has edited a too-long value in a field with maxlength
                tooShort: (field.hasAttribute('minLength') && field.getAttribute('minLength') > 0 && length > 0 && length < parseInt(field.getAttribute('minLength'), 10)), // the user has edited a too-short value in a field with minlength
                typeMismatch: (length > 0 && ((type === 'email' && !/^([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x22([^\x0d\x22\x5c\x80-\xff]|\x5c[\x00-\x7f])*\x22))*\x40([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d)(\x2e([^\x00-\x20\x22\x28\x29\x2c\x2e\x3a-\x3c\x3e\x40\x5b-\x5d\x7f-\xff]+|\x5b([^\x0d\x5b-\x5d\x80-\xff]|\x5c[\x00-\x7f])*\x5d))*$/.test(field.value)) || (type === 'url' && !/^(?:(?:https?|HTTPS?|ftp|FTP):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]-*)*[a-zA-Z\u00a1-\uffff0-9]+)*)(?::\d{2,5})?(?:[\/?#]\S*)?$/.test(field.value)))), // value of a email or URL field is not an email address or URL
                valueMissing: (field.hasAttribute('required') && (((type === 'checkbox' || type === 'radio') && !field.checked) || (type === 'select' && field.options[field.selectedIndex].value < 1) || (type !=='checkbox' && type !== 'radio' && type !=='select' && length < 1))) // required field without a value
            };

            // Check if any errors
            for ( var key in checkValidity ) {
                if ( checkValidity.hasOwnProperty( key ) ) {
                    // If there's an error, change valid value
                    if ( checkValidity[ key ] ) {
                        valid = false;
                        break;
                    }
                }
            }

            // Add valid property to validity object
            checkValidity.valid = valid;

            // Return object
            return checkValidity;

        };

        // If the full set of ValidityState features aren't supported, polyfill
        if ( !supported() ) {
            Object.defineProperty(HTMLInputElement.prototype, 'validity', {
                get: function ValidityState() {
                    return getValidityState( this );
                },
                configurable: true,
            });
        }

    }

    function init( $args ) {

        if( $args.length == 0 ) {
            console.warn( 'HTML5 Validation Plugin, Plugin initialized with no parameters; closing plugin' );
            return; // exit plugin
        } 

        if( $args[ 'formID' ] == undefined ) {
            console.warn( 'HTML5 Validation Plugin, Form ID missing. Form ID: ' + $args[ 'formID' ] );
            return; // exit plugin
        }

        if( $args[ 'callbackName' ] == undefined ) {
            console.warn( 'HTML5 Validation Plugin, name of callback not supplied. Form ID: ' + $args[ 'formID' ] );
            return; // exit plugin
        }

        var formId = $args[ 'formID' ]; 
        var callbackName = $args[ 'callbackName' ];

        var form = document.getElementById( formId );
        if( !form ) { console.warn( 'HTML5 Validation Plugin, Form ID: ' + formId + ', not found.' ); return }; 

        form.noValidate = true;
        form.addEventListener( 'submit', function( submitEvent ) {

            // Seriously, hold everything.
            submitEvent.preventDefault();
            submitEvent.stopImmediatePropagation();
            submitEvent.stopPropagation();

            validateForm( submitEvent, callbackName );

        });

    }

    /**
     * Check for form submissions by bots
     * @param {Node} honeypot The function to call after form is validated 
     */

    var checkHoneypot = function( form ) {
        
        var honeypot = form.querySelector( '.jar input[type="text"]' );
        if( honeypot == null ) {
            return; // Skip this
        } else {
            return ( honeypot.value !== '' ) ? true : false;
        }

    } 

    /** 
     * Validate form 
     * 
     * @param {Object} submitEvent  Event listener triggered on click of submit button
     * @param {String} callbackName Name of index where callback function was stored within this.forms
     */

    function validateForm( submitEvent, callbackName ) {

        // Prevent form submissions by bots 
        if ( checkHoneypot( submitEvent.target ) ) { return false };

        // Check field validity
        if ( !submitEvent.target.checkValidity() ) {

            removeUpValidationMessagesFromForm( submitEvent.target );
            addValidationMessagesToForm( submitEvent.target );

        } else {
            
            removeUpValidationMessagesFromForm( submitEvent.target );
            callbacks[ callbackName ]( submitEvent );
            return true; /* everything's cool, the form is valid! */

        }

    }

    /**
     * Add validation messages to form inputs 
     * 
     * @param {HTMLelement} form 
     */

    function addValidationMessagesToForm( form ) {

        var elements = form.elements;

        /* Loop through the elements, looking for an invalid one. */
        for ( var index = 0, len = elements.length; index < len; index++ ) {

            var element = elements[ index ], 
                message = element.validationMessage,
                parent  = element.parentNode; 

            /* If input is invalid */
            if ( element.willValidate === true && element.validity.valid !== true ) {
                
                var messageDiv = document.createElement( 'div' );

                messageDiv.classList.add( validationMsgClass );
                messageDiv.appendChild( document.createTextNode( message ) );
                parent.appendChild( messageDiv );

            } /* willValidate && validity.valid */

        }

    }

    /**
     * Remove validation messages from form 
     * 
     * @param {HTMLelement} form 
     */

    function removeUpValidationMessagesFromForm( form ) {

        var elements   = form.elements, 
            formErrors = [];

        /* Remove existing error messages after element. */
        for ( var index = 0, len = elements.length; index < len; index++ ) {
            
            var element = elements[ index ], 
                message = element.validationMessage,
                parent  = element.parentNode; 

            var findClasses = new RegExp( validationMsgClass,'g' );
            
            if( element.nextSibling !== null && findClasses.test( element.nextSibling.className ) ) {
                parent.removeChild( element.nextSibling );
            }

        }

    }

    function registerCallback( callbackName, func  ) {

        callbacks[ callbackName ] = func;

    }

    function registerForm( form ) {
        var formId = form.id;
        var nameOfCallbackToUseOnValidForm = form.getAttribute( 'data-form-callback' );
    
        if( formId == undefined ) {
            console.warn( 'HTML5 Validation Plugin, Form ID missing. Form ID: ' + formId );
        }
    
        if( nameOfCallbackToUseOnValidForm == undefined ) {
            console.warn( 'HTML5 Validation Plugin, callback not supplied. Form ID: ' + formId );
        }
    
        var formArguments = {
            'formID'  : formId, 
            'callbackName': nameOfCallbackToUseOnValidForm
        }
    
        this.init( formArguments );
    }

    return {
        init : init, 
        registerForm : registerForm,
        removeUpValidationMessagesFromForm : removeUpValidationMessagesFromForm, 
        addValidationMessagesToForm : addValidationMessagesToForm, 
        registerCallback: registerCallback
    }

})( window, document );

(function() {

    var initFormValidationPlugin = function() {

        var formsOnPage = document.querySelectorAll( 'form' );

        for( var i = 0; i < formsOnPage.length; i++  ) {
            if( formsOnPage[ i ].getAttribute( 'novalidate' ) == 'true' ) { continue; }
            HTML5validation.registerForm( formsOnPage[ i ] );
        }

    }

    // Initialize HTML5 validation on form 
    if( typeof runOnAppStart == "function" ) {
        runOnAppStart( 'initializeHTMLvalidationOnAllForms', initFormValidationPlugin );
    } else { 
        initFormValidationPlugin();
    }

})();

