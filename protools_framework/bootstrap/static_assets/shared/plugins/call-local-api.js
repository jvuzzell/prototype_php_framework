//purpose:    Call Local API
//parameters: input: the API path (e.g., "shipment/notes")
//                   the method (e.g., "GET")
//                   the data to pass to the webservice (pass this as an object, like: {'id': '1234567'})
//                   the callback function
//                   a variable to pass through to the callback
//notes:      GML - 8/3/20

function CallLocalAPI( api_path, method, data_in, callback_func, passthrough, stringify = true ) {

	if( api_path == undefined ) { console.error( 'Call_local_api: api_path not defined'); return; }
	if( method == undefined ) { console.error( 'Call_local_api: method (http request method) not defined' ); return; }
	
    var api_base = PAGE_MODEL[ 'meta' ][ 'base-api-endpoint' ];
	var ajaxRequest;  // The variable that makes Ajax possible!

	try{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	} catch (e){
		// Internet Explorer Browsers
		try{
			ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try{
				ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e){
				// Something went wrong
				alert("XMLHttpRequest not available to perform Ajax call.");
				return false;
			}
		}
	}

	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function() {
		if(ajaxRequest.readyState == 4){	
            if(callback_func != null) {
			    if (ajaxRequest.responseText == ""){
				    callback_func({}, passthrough);
			    } else {
				    callback_func(JSON.parse(ajaxRequest.responseText), passthrough);
			    }
            }
		}
	}

    ajaxRequest.open('POST', api_base + '?api_path=' + api_path + '&api_method=' + method, true);

	if( JsHelper.getCookie( 'user_id' ) !== null ) {
		ajaxRequest.setRequestHeader( 'Authorization', 'Bearer ' + JsHelper.getCookie( 'user_id' ) );
	} else {
		alert( "Not authorized to make server requests. Please refresh your browser or contact the site owner." );
	}

	if( stringify ) {
		ajaxRequest.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		ajaxRequest.send( JSON.stringify( data_in ) );
	} else {
		ajaxRequest.send( data_in );
	}

}
