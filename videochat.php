<?
	include("_sharedIncludes/globals.php");
	include("_sharedIncludes/dbconnect.php");
?>

<!DOCTYPE html>
<html>
<head>
<title>Video chat!</title>
<script src="http://code.jquery.com/jquery-2.0.0.js"></script>
<script src="videoControlObjects.js"></script>
<!--<script src="http://code.jquery.com/jquery-2.0.0.min.js"></script>-->
<script>
	var videoUsername = "<?=$_POST['videoUsername']?>";
	var checkMessageIntervalObj;

	var ServerInterface = new function()
	{
		this.request = function(dataToSend)
		{
			$.ajax({
				type : "POST",
				url : "videoController.php",
				data: JSON.stringify(dataToSend),
				dataType : "json", // data type to be returned
				contentType: "application/json",
				success: function(data) {
					//alert( data ); // shows whole dom
					ServerInterface.processServerData( data );
					//alert( $(data).find('#wrapper').html() ); // returns null
				},
				error: function(jqXHR, exception)
				{
					ErrString = "";
					if (jqXHR.status === 0)
						ErrString += 'Not connect.\n Verify Network.';
					else if (jqXHR.status == 404)
						ErrString += 'Requested page not found. [404]';
					else if (jqXHR.status == 500)
						ErrString += 'Internal Server Error [500].';
					else if (exception === 'parsererror')
						ErrString += 'Requested JSON parse failed.';
					else if (exception === 'timeout')
						ErrString += 'Time out error.';
					else if (exception === 'abort')
						ErrString += 'Ajax request aborted.';
					else
						ErrString += 'Uncaught Error.\n' + jqXHR.responseText;
					trace(ErrString + "\nStatus:" + jqXHR.status + "\nResponseText:" + jqXHR.responseText);
					//alert(ErrString);
					//alert(jqXHR.status);
					//alert(jqXHR.responseText);
					//alert(exception);
				}
			});
		}
		
		this.processServerData = function(server_data)
		{
			var allDataSetsObj = server_data["allData"];
			if ("users" in allDataSetsObj)
			{
				var allUsers = allDataSetsObj["users"];
				var userHTMLbody = "";
				var x, allUserCount;
				//for (x = 0, allUserCount = allUsers.length; x < allUserCount; x++)
				var timeAdded = Date.now();
				UserManager.usersUpdatedTimestamp = timeAdded;
				for (var userID in allUsers)
				{
					if (allUsers[userID] != videoUsername)
						UserManager.add(userID, allUsers[userID], timeAdded);
				}
				UserManager.handleUsersAdded();
			}
			if ("messages" in allDataSetsObj)
				VideoStreamManager.processIncomingMessage(allDataSetsObj["messages"]);
			if ("message" in allDataSetsObj)
				trace(allDataSetsObj.message);
			if ("error" in allDataSetsObj)
				alert(allDataSetsObj.error);
		}
		
		var p_ajaxObj;
	};
	
	var g_userArray = [];
	
	function updateTimestamp()
	{
		ServerInterface.request("videoUsername=" + videoUsername);
	}
	
	//var timestampUpdate = setInterval(updateTimestamp, 60000);
	
//var constraints = {audio: true, video: true};
/* HD
video: {
    mandatory: {
      minWidth: 1280,
      minHeight: 720
    }
  }
*/

var CssManager = new function()
{
	this.styles =
	{
		'hue-rotate': {title: 'Hue', styleSuffix: 'deg', val: 0, defaultVal: 0, step: 15, min: 0, max: 360},
		grayscale: {title: 'Grayscale', styleSuffix: '%', val: 0, defaultVal: 0, step: 5, min: 0, max: 100},
		brightness: {title: 'Brightness', styleSuffix: '%', val: 100, defaultVal: 100, step: 5, min: 0, max: 500},
		saturate: {title: 'Saturate', styleSuffix: '%', val: 100, defaultVal: 100, step: 5, min: 0, max: 500},
		contrast: {title: 'Contrast', styleSuffix: '%', val: 100, defaultVal: 100, step: 5, min: 0, max: 500},
		sepia: {title: 'Sepia', styleSuffix: '%', val: 0, defaultVal: 0, step: 5, min: 0, max: 100},
		invert: {title: 'Invert', styleSuffix: '%', val: 0, defaultVal: 0, step: 5, min: 0, max: 100}
	};
	/*
		blur: {title: 'Blur', styleSuffix: 'px', val: 0, defaultVal: 0, step: 1, min: 0, max: 20},
	this.alert = true;
		if (this.alert)
		{
			alert(styleText);
			this.alert = false;
		}
	*/
	this.setCss = function(newBrowserStyle, val)
	{
		this.styles[newBrowserStyle].val = val;
		var filterName;
		if (BrowserVideoFunctions.isFirefox())
			filterName = "filter";
		else if (BrowserVideoFunctions.isChrome())
			filterName = "-webkit-filter";
		var styleText = "";
		for (var browserStyle in this.styles)
			styleText += browserStyle + "(" + this.styles[browserStyle].val + this.styles[browserStyle].styleSuffix + ") ";
		$("#localVideo").css(filterName, styleText);
		$("#" + newBrowserStyle + "Text").html(val + this.styles[newBrowserStyle].styleSuffix);
	}
	
	this.reset = function(browserStyle, val)
	{
		this.setCss(browserStyle, val);
		$("#" + browserStyle).val(this.styles[browserStyle].val);
	}
	
	this.init = function()
	{
		var styleObj;
		for (var browserStyle in this.styles)
		{
			styleObj = this.styles[browserStyle];
			$("#styleContainer").append("<tr><td>" + styleObj.title +
				"</td><td><input id=\"" + browserStyle + "\" type=\"range\" onchange=\"CssManager.setCss('" + browserStyle +
				"', this.value" + ");\" value=\"" + styleObj.val + "\" step=\"" + styleObj.step + "\" min=\"" +
				styleObj.min + "\" max=\"" + styleObj.max + "\" /></td><td id=\"" + browserStyle + "Text\"></td>" + 
				"<td><button type=\"button\" onclick=\"CssManager.reset('" + browserStyle +
				"', " + styleObj.val + ")\">Reset</button></td></tr>");
			$("#" + browserStyle + "Text").html(styleObj.val + styleObj.styleSuffix);
			$("#" + browserStyle).val(styleObj.val);
		}
	}
};
var UserManager = new function()
{
	this.add = function(id, user_name, time_added)
	{
		this.users[id] = {username: user_name, timeAdded : time_added};
	}
	
	this.handleUsersAdded = function()
	{
		this.removeOldUsers();
		this.createCallHTML();
	}

	this.removeOldUsers = function()
	{
		var userObj;
		for (var userID in this.users)
		{
			userObj = this.users[userID];
			if (userObj.timeAdded != this.usersUpdatedTimestamp)
				delete this.users[userID];
		}
	}

	this.createCallHTML = function()
	{
		var userHTMLbody = "";
		for (var userID in this.users)
		{
			userHTMLbody += "<button type=\"button\" id=\"call" + userID + "\"";
			if (!VideoStreamManager.streamingLocalVideo)
				userHTMLbody += " disabled";
			userHTMLbody += " onclick=\"call('" + userID + "');\">Call " + this.users[userID].username + "</button>";
		}
		if (this.users == {})
			userHTMLbody = "You are the only user online";
		$("#usersOnline").html(userHTMLbody);
	}
	this.users = {};
	this.usersUpdatedTimestamp;
};

function call(recipientID)
{
	var recipient = UserManager.users[recipientID].username;
	VideoStreamManager.call(videoUsername, recipient);
}

function initPage()
{
	BrowserVideoFunctions.init();
	VideoStreamManager.init();
	CssManager.init();
	ServerInterface.request({ login: videoUsername });
	checkMessageIntervalObj = setInterval(function(){ServerInterface.request({ check_messages: "true", user_name: videoUsername })},10000);
}
</script>
</head>
<body onload="initPage()">
<table>
<tr><td><video id="localVideo" style="width:400px; height: 300px" autoplay></video></td>
<td><video id="remoteVideo" style="width:400px; height: 300px" autoplay></video></td></tr>
</table>

<div>
  <button type="button" id="startButton" onclick="VideoStreamManager.start()">Start</button>
  <button type="button" id="hangupButton" onclick="VideoStreamManager.hangup()">Hang Up</button>
</div>
<div id="usersOnline"></div>
<table>
<tbody id="styleContainer">
</tbody>
</table>
<!--
<tr><td>Invert</td><td><input type="range" onchange="CssManager.setCss('invert', this.value + '%');" value="0" step="5" min="0" max="100" /></td></tr>
-->
</body>
</html>