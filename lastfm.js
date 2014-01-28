API_KEY = "YOUR API KEY GOES HERE";
SECRET = "YOUR SECRET KEY GOES HERE";


function doNoAuthCall(method, params, callback, failCallback) {

	if(!params) {
		params = {
			method : method,
			api_key : API_KEY					
		};		
	} else {
		params.method = method;
		params.api_key = API_KEY;
	}

	var sig = getSignature(params, SECRET);
	var url = "http://ws.audioscrobbler.com/2.0/?" + $.param(params) + "&api_sig=" + sig + "&format=json";
	$.getJSON(url, callback)
		.fail(function(err) {
			if(failCallback) {
				failCallback(err);
			}
	});
}

function doAuthCall(method, params, sessiontoken, callback, failCallback) {

	if(!params) {
		params = {
			method : method,
			api_key : API_KEY,
			sk : sessiontoken						
		};		
	} else {
		params.method = method;
		params.api_key = API_KEY;
		params.sk = sessiontoken;
	}	
	
	var sig = getSignature(params, SECRET);
	params.api_sig = sig;
	params.format = "json";
	$.post(
		"http://ws.audioscrobbler.com/2.0/",
		params,
		callback,
		"json" // expected data type	
	).fail(function(err) {
		if(failCallback) {
			failCallback(err);	
		}
	});	
}

function getSignature(params, secret) {
	var keys = Object.keys(params);
	var input = "";
	keys.sort();
	for(var i=0; i<keys.length; i++) {
		input += keys[i];
		input += params[keys[i]];	
	}
	input += secret;
	var result = md5(input);
	return result;
}