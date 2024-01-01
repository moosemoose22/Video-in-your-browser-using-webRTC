<?
	include("_sharedIncludes/globals.php");
	include("_sharedIncludes/dbconnect.php");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Video chat!</title>
<script src="https://code.jquery.com/jquery-2.0.0.js"></script>
<script src="videoControlObjects.js"></script>
<script type="text/javascript" src="fabric.js"></script>
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
					//alert(exception);
				}
			});
		}

		this.processServerData = function(server_data)
		{
			console.log(server_data);
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
					if (allUsers[userID] == videoUsername)
					{
						UserManager.myID = userID;
						UserManager.myUsername = videoUsername;
					}
					else
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
			filterName = "-moz-filter";
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
		this.users[id] = {username: user_name, timeAdded: time_added};
	}

	this.getUserName = function(userID)
	{
		if (this.users[userID])
			return this.users[userID].username;
		else
			return userID;
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
	//var recipient = UserManager.users[recipientID].username;
	VideoStreamManager.call(UserManager.myID, recipientID);
}

var canvas;
function initPage()
{
	BrowserVideoFunctions.init();
	VideoStreamManager.init();
	CssManager.init();
	ServerInterface.request({ login: videoUsername });
	checkMessageIntervalObj = setInterval(function(){ServerInterface.request({ check_messages: "true", user_id: UserManager.myID })},11000);
	initCanvas();
	var videoObj = document.getElementById("localVideo");
	//canvas.add(videoObj);
}

function initCanvas()
{
	canvas = this.__canvas = new fabric.Canvas('canvasObj');
	fabric.Object.prototype.transparentCorners = false;
	//canvas.selection = false; // disable group selection

	canvas.on('mouse:down', function(options) {
		startX = options.e.clientX;
		startY = options.e.clientY;
		console.log(options.e.clientX, options.e.clientY);
	});
	canvas.on('mouse:up', function(options) {
		var x1 = (options.e.clientX > startX) ? startX : options.e.clientX;
		var y1 = (options.e.clientY > startY) ? startY : options.e.clientY;
		var x2 = (options.e.clientX < startX) ? startX : options.e.clientX;
		var y2 = (options.e.clientY < startY) ? startY : options.e.clientY;
		var width = x2 - x1;
		var height = y2 - y1;
		var newrect = new fabric.Rect({
			width: width, height: height, left: x1, top: y1, angle: 0,
			fill: 'rgba(255,0,0,0.5)'
		});
		if (!objectMoving)
			canvas.add(newrect);
		objectMoving = false;

		console.log(options.e.clientX, options.e.clientY);
	});

	canvas.on({
		'object:moving': onChange,
		'object:scaling': onChange,
		'object:rotating': onChange,
	});
	function onChange(options)
	{
		objectMoving = true;
	}
	console.log(fabric);
}


document.addEventListener('DOMContentLoaded', function(){
	var v = document.getElementById('localVideo');
	/*
	var canvas = document.getElementById('canvasObj');
	var context = canvas.getContext('2d');

	var cw = Math.floor(canvas.clientWidth / 100);
	var ch = Math.floor(canvas.clientHeight / 100);
	canvas.width = cw;
	canvas.height = ch;
*/
	console.log("here");
	v.addEventListener('play', function(){
		//initCanvas();
		console.log("playing");
		//draw(this,context,cw,ch);
	},false);

},false);
/*
function draw(v,c,w,h) {
	//if(v.paused || v.ended) return false;
	//c.drawImage(v,0,0,w,h);
	setTimeout(draw,20,v,c,w,h)
}
setTimeout(function()
{
	;//var currLog = $("#logMediaEvent").html();
	//currLog +=
	//$("#logMediaEvent").html()
}, 1000);
*/
</script>
<style>
	td {vertical-align: top;}
	#canvasObj
	{
		position: absolute;
		border: 2px solid black;
		z-index: 10;
		left: 0px;
		top: 0px;
		width:400px;
		height: 600px
	}
</style>
</head>
<body onload="initPage()">
<table>
<!--<tr><td><video id="localVideo" style="width:200px; height: 150px" autoplay></video></td>-->
<tr><td>
	<video id="localVideo" style="width:400px; height: 400px" autoplay></video>
</td>
<td>
	<table id="remoteVideoContainer"></table>
</td></tr>
</table>

<div>
	<table><tr>
	<td><button type="button" id="startButton" onclick="VideoStreamManager.start()">Start</button></td>
	<td><button type="button" id="hangupButton" onclick="VideoStreamManager.hangup()">Hang Up</button></td>
	<td><div id="statusDiv"></div></td>
	</tr></table>
</div>
<div id="usersOnline"></div>
<table cellspacing="0">
<tr><td>
	<table>
	<tbody id="styleContainer">
	</tbody>
	</table>
</td>
<td>
	<div id="StatusList"></div>
	<div id="logMediaEvent"></div>
</td>
</tr></table>
	<canvas id="canvasObj"></canvas>
</body>
</html>
