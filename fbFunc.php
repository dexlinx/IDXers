<?php
// FACEBOOK LOGIN FUNCTION - DOES ALL THE WORK
//----------------------------------------------
//----------------------------------------------
function fbFunc(){
?>
<script>

//Here we run a very simple test of the Graph API after login is
// successful.  See statusChangeCallback() for when this call is made.
function testApi() {

	console.log('Welcome!  Fetching your information.... ');
	
	FB.api('/me', function(response) {
		
		console.log('Successful login for: ' + response.name);
		console.log('user ID: ' + response.id);
		document.getElementById('status').innerHTML = '';

		//Hide FB Button; Already Logged In

		addParameterToURL("blue");
		
		function addParameterToURL(param){
		    _url = location.href;
		    _url += (_url.split('?')[1] ? '&':'?') + param;
		    return _url;
		}

		console.log(addParameterToURL("blue"));

		
		document.getElementsByClassName('fbbutton')[0].innerHTML = "<center><input type='button' value='Refresh To See Your Votes' onClick='window.location.reload()'></center>";


		//Set Cookie with Fb User ID
		document.cookie = "FbUserId="+response.id;

		//TRYING TO GET A CHECK TO SEE IF THE COOKIE EXISTS; IF NOT CREATE AND THEN RELOAD PAGE

	
	});
}


// This is called with the results from from FB.getLoginStatus().
		//---------------------------------
		//CONNECTION COMPLETED
		//---------------------------------
function statusChangeCallback(response) {
	//console.log('statusChangeCallback');
	//console.log(response);
	// The response object is returned with a status field that lets the
	// app know the current login status of the person.
	if (response.status === 'connected') {
		// Logged into your app and Facebook.
		testApi();


		//---------------------------------
		// LOGGED INTO FB - NOT THE APP
		//---------------------------------
	} else if (response.status === 'not_authorized') {
		
		// The person is logged into Facebook, but not your app.
		document.getElementById('status').innerHTML = '<b>To Vote Please log into this app.</b><p>';

		//Hide the Vote Form If Not Logged In
		document.getElementsByClassName("voteform")[0].innerHTML = "<style>.voteform{display:none !important;}</style>";

		//Hide the Logout Link
		document.getElementsByClassName("fblogout")[0].innerHTML = "";

		//---------------------------------
		//NOT LOGGED INTO FB
		//---------------------------------
	} else {

		// The person is not logged into Facebook
		document.getElementById('status').innerHTML = '<b>To Vote Please log into Facebook.</b>	<p>';

		//Hide the Vote Form If Not Logged In
		document.getElementsByClassName("voteform")[0].innerHTML = "<style>.voteform{display:none !important;}</style>";

		//Hide the Logout Link
		document.getElementsByClassName("fblogout")[0].innerHTML = "";
	}
}

// This function is called when someone finishes with the Login
// Button.  See the onlogin handler attached to it in the sample
// code below.
function checkLoginState() {
	FB.getLoginStatus(function(response) {
		statusChangeCallback(response);
		window.location.reload();
	});
}


window.fbAsyncInit = function() {
	FB.init({
		appId      : '1116440061761832',
		cookie     : true,  // enable cookies to allow the server to access the session
		xfbml      : true,  // parse social plugins on this page
		version    : 'v2.5' // use graph api version 2.5
	});

		FB.getLoginStatus(function(response) {
			statusChangeCallback(response);
			
		});

};

// Load the SDK asynchronously
(function(d, s, id) {
	var js, fjs = d.getElementsByTagName(s)[0];
	if (d.getElementById(id)) return;
	js = d.createElement(s); js.id = id;
	js.src = "//connect.facebook.net/en_US/sdk.js";
	fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

</script>

<!-- <div class="fblogout"><a href="" onclick="fbLogoutUser()">Facebook Logout</a></div> -->


		<div class="fbbutton">
		<fb:login-button scope="public_profile,email" onlogin="checkLoginState();">
		</fb:login-button>
		</div>


		<div id="status">
		</div>
		
		<?php 
}
?>
