function trace(text)
{
	console.log((performance.now() / 1000).toFixed(3) + ": " + text);
}
function handleSuccess(text)
{
	trace(text);
}
function handleError(err)
{
	var errString = "HandleError\n";
	if (typeof err == "object")
	{
		for (var x in err)
			errString += (x + ": " + err[x] + "\n");
	}
	else if (typeof err == "string")
		errString += err;
	console.error(errString);
}

var sdpConstraints = {
	optional: [],
	mandatory: {
		OfferToReceiveAudio: true,
		OfferToReceiveVideo: true
	}
};

function onfailure()
{
	trace("answer failure");
}

var VideoStreamManager = new function()
{
	this.init = function()
	{
		this.connectionServers = {"iceServers":[{"url":"stun:23.21.150.121"}]};
		this.serverConstraints = { 'optional': [{'DtlsSrtpKeyAgreement': true}, {'RtpDataChannels': true }] };
		this.localVideo = document.getElementById("localVideo");
		this.remoteVideo = document.getElementById("remoteVideo");
		this.startButton = document.getElementById("startButton");
		this.hangupButton = document.getElementById("hangupButton");
		this.addedLocalCandidate = false;
		this.streamingLocalVideo = false;
		startButton.disabled = false;
		hangupButton.disabled = true;
		//startButton.onclick = VideoStreamManager.start;
		//hangupButton.onclick = VideoStreamManager.hangup;
		this.localPeerConnection = new BrowserVideoFunctions.RTCPeerConnection(this.connectionServers, this.serverConstraints);
		trace("Created local peer connection object localPeerConnection");
		this.localPeerConnection.onicecandidate = this.gotLocalIceCandidate;
		this.localPeerConnection.onaddstream = this.gotLocalStream;
	}

	this.processIncomingMessage = function(serverMessages)
	{
		var messageCounter, messageLength, serverMessageObj;
		for (messageCounter = 0, messageLength = serverMessages.length; messageCounter < messageLength; messageCounter++)
		{
			serverMessageObj = serverMessages[messageCounter];
			var isSDPmessage = (serverMessageObj.type == "offer") || (serverMessageObj.type == "answer");
			if (isSDPmessage)
			{
				this.caller = (serverMessageObj.type == "offer") ? serverMessageObj["from_user"] : videoUsername;
				this.recipient = (serverMessageObj.type == "offer") ? videoUsername : serverMessageObj["from_user"];
				var remoteRequest = new BrowserVideoFunctions.RTCSessionDescription({type:serverMessageObj["type"], sdp:serverMessageObj["sdp"]});
				this.remoteDescription = remoteRequest;
				this.localPeerConnection.onaddstream = this.gotRemoteStream;
				this.localPeerConnection.setRemoteDescription(remoteRequest, this.gotRemoteDescription, handleError);
				if (serverMessageObj.type == "offer")
					updateCallStatus(serverMessageObj["from_user"] + " is calling you");
			}
			else if (serverMessageObj.type == "candidate")
			{
				//alert(serverMessageObj.label);
				//alert(serverMessageObj.candidate);
				var candidate = new BrowserVideoFunctions.RTCIceCandidate({sdpMLineIndex: serverMessageObj.label,
													candidate: serverMessageObj.candidate}, handleSuccess, handleError);
				this.gotRemoteIceCandidate(candidate);
			}
		}
		/*
		 setRemote(message);
		 	setremotedescription
		 	waitForRemoteVideo
		 	transitionToActive
    	doAnswer();
		*/
	}
	
	this.onSetRemoteDescriptionSuccess = function()
	{
		console.log("Set remote session description success.");
		return;
		// By now all addstream events for the setRemoteDescription have fired.
		// So we can know if the peer is sending any stream or is only receiving.
		if (this.remoteStream)
			this.waitForRemoteVideo();
		else
		{
			console.log("Not receiving any stream.");
			//BrowserVideoFunctions.attachMediaStream();
		}
	}

	this.waitForRemoteVideo = function()
	{
		// Call the getVideoTracks method via adapter.js.
		videoTracks = VideoStreamManager.remoteStream.getVideoTracks();
		if (videoTracks.length === 0 || remoteVideo.currentTime > 0)
			transitionToActive();
		else
			setTimeout(waitForRemoteVideo, 100);
	}

	this.gotLocalStream = function(stream)
	{
		VideoStreamManager.localVideo.src = URL.createObjectURL(stream);
		VideoStreamManager.localStream = stream;
		VideoStreamManager.localPeerConnection.addStream(VideoStreamManager.localStream);
		VideoStreamManager.streamingLocalVideo = true;
		VideoStreamManager.hangupButton.disabled = false;
		// Enable all call buttons
		UserManager.createCallHTML();
	}

/*
	this.gotRemoteStream = function(stream)
	{
		trace("Received remote stream " + stream);
		VideoStreamManager.remoteVideo.src = URL.createObjectURL(stream);
		VideoStreamManager.remoteStream = stream;
		VideoStreamManager.localPeerConnection.addStream(VideoStreamManager.remoteStream);
	}
*/
	this.gotRemoteStream = function(event)
	{
		//alert("gotRemoteStream yo!");
		VideoStreamManager.remoteStream = event.stream;
		VideoStreamManager.remoteVideo.src = URL.createObjectURL(event.stream);
		//VideoStreamManager.localPeerConnection.addStream(VideoStreamManager.remoteStream);
		BrowserVideoFunctions.attachMediaStream(VideoStreamManager.remoteVideo, event.stream);
		trace("Received remote stream");
		var otherVideoDude = VideoStreamManager.isCaller() ? VideoStreamManager.recipient : VideoStreamManager.caller;
		updateCallStatus("You're in a video call with " + otherVideoDude);
	}

	this.start = function()
	{
		trace("Requesting local stream");
		this.startButton.disabled = true;
		BrowserVideoFunctions.getUserMedia({
			audio:true,
			video:true
		}, VideoStreamManager.gotLocalStream,
		function(error) {
			trace("getUserMedia error: ", error);
		});
	}

	this.isCaller = function()
	{
		return videoUsername == this.caller;
	}

	this.isRecipient = function()
	{
		return videoUsername == this.recipient;
	}

	this.call = function(caller, recipient, remoteSDP)
	{
		//alert("calling!");
		this.caller = caller;
		this.recipient = recipient;
		trace("Starting call to " + recipient);

		if (this.localStream.getVideoTracks().length > 0) {
			trace('Using video device: ' + this.localStream.getVideoTracks()[0].label);
		}
		if (this.localStream.getAudioTracks().length > 0) {
			trace('Using audio device: ' + this.localStream.getAudioTracks()[0].label);
		}

		if (this.isCaller())
		{
			VideoStreamManager.localPeerConnection.createOffer(VideoStreamManager.gotLocalDescription, handleError, sdpConstraints);
			trace("Added localStream to localPeerConnection");
			updateCallStatus("Calling " + recipient);
		}
		else if (this.isRecipient())
		{
			var remoteRequest = new BrowserVideoFunctions.RTCSessionDescription({type:remoteSDP["type"], sdp:remoteSDP["sdp"]});
			this.gotRemoteDescription(remoteRequest);
		}
		else
			trace("Error; neither caller nor recipient is this user :(");
	}

	this.handleCreateOfferError = function(event)
	{
		//alert("handlecreateoffer");
		console.log('createOffer() error: ', e);
	}

	this.gotLocalDescription = function(description)
	{
		//if (description.type != "offer")
			VideoStreamManager.localPeerConnection.setLocalDescription(description, VideoStreamManager.onAddLocalDescription, handleError);
		//alert("type: " + description.type + "\nCaller? " + VideoStreamManager.isCaller() + "\nsdp: " + description.sdp);
		var from = VideoStreamManager.isCaller() ? VideoStreamManager.caller : VideoStreamManager.recipient;
		var to = VideoStreamManager.isCaller() ? VideoStreamManager.recipient : VideoStreamManager.caller;
		ServerInterface.request({ sdp_message: description,
									from_video_user: from,
									to_video_user: to});
	}
	
	this.onAddLocalDescription = function(description)
	{
		trace("onAddLocalDescription description is " + description);
		//alert("onAddLocalDescription " + description);
		var stream = VideoStreamManager.localStream;
		trace("onAddLocalDescription stream is " + stream);
	}

	this.gotRemoteDescription = function(event)
	{
		var description = VideoStreamManager.remoteDescription;
		//alert("gotRemoteDescription! description is " + description + "\nEvent is " + event);
		VideoStreamManager.localPeerConnection.setRemoteDescription(description);
		trace("Answer from remote connection: \n" + description.sdp);
		if (VideoStreamManager.isRecipient())
		{
			trace("Creating answer");
			VideoStreamManager.localPeerConnection.createAnswer(VideoStreamManager.gotLocalDescription, handleError, sdpConstraints);
		}
	}

	this.hangup = function()
	{
		trace("Ending call");
		this.localPeerConnection.close();
		this.localPeerConnection = null;
		this.hangupButton.disabled = true;
		this.streamingLocalVideo = false;
		// Disable all call buttons
		UserManager.createCallHTML();
	}


	this.gotLocalIceCandidate = function(event)
	{
		//for (var x in event)
		//	trace(x + ": " + event[x]);
		if (event.candidate && !this.addedLocalCandidate)
		{
			//alert("gotLocalIceCandidate " + event.candidate);
			trace("Local ICE candidate: \n" + event.candidate.candidate);
			VideoStreamManager.localPeerConnection.addIceCandidate(new BrowserVideoFunctions.RTCIceCandidate(event.candidate, handleSuccess, handleError));
			var candidateObj = {type: 'candidate',
								label: event.candidate.sdpMLineIndex,
								id: event.candidate.sdpMid,
								candidate: event.candidate.candidate};
			var from = VideoStreamManager.isCaller() ? VideoStreamManager.caller : VideoStreamManager.recipient;
			var to = VideoStreamManager.isCaller() ? VideoStreamManager.recipient : VideoStreamManager.caller;
			ServerInterface.request({ sdp_message: candidateObj,
									from_video_user: from,
									to_video_user: to});
			this.addedLocalCandidate = true;
		}
	}

	this.gotRemoteIceCandidate = function(candidate)
	{
		if (candidate)
		{
			VideoStreamManager.localPeerConnection.addIceCandidate(candidate);
			trace("Remote ICE candidate: \n " + candidate.candidate);
		}
	}
	
	this.connectionServers;
	this.serverConstraints;
	this.localVideo;
	this.remoteVideo;
	this.localStream;
	this.remoteStream;
	this.remoteDescription;
	this.caller;
	this.recipient;
	this.localPeerConnection;
	this.addedLocalCandidate;
	this.startButton;
	this.hangupButton;
	this.streamingLocalVideo;
};

var BrowserVideoFunctions = new function()
{
	this.RTCPeerConnection = null;
	this.getUserMedia = null;
	this.attachMediaStream = null;
	this.reattachMediaStream = null;
	this.webrtcDetectedBrowser = null;
	this.webrtcDetectedVersion = null;
	this.RTCSessionDescription = null;
	this.RTCIceCandidate = null;
	
	this.isFirefox = function()
	{
		return this.webrtcDetectedBrowser == "firefox";
	}
	
	this.isChrome = function()
	{
		return this.webrtcDetectedBrowser == "chrome";
	}
	
	this.init = function()
	{
		if (navigator.mozGetUserMedia)
		{
			console.log("This appears to be Firefox");

			this.webrtcDetectedBrowser = "firefox";

			this.webrtcDetectedVersion = parseInt(navigator.userAgent.match(/Firefox\/([0-9]+)\./)[1]);

			// The RTCPeerConnection object.
			this.RTCPeerConnection = mozRTCPeerConnection;

			// The RTCSessionDescription object.
			this.RTCSessionDescription = mozRTCSessionDescription;

			// The RTCIceCandidate object.
			this.RTCIceCandidate = mozRTCIceCandidate;

			// Get UserMedia (only difference is the prefix).
			// Code from Adam Barth.
			this.getUserMedia = navigator.mozGetUserMedia.bind(navigator);

			// Creates iceServer from the url for FF.
			this.createIceServer = function(url, username, password)
			{
				var iceServer = null;
				var url_parts = url.split(':');
				if (url_parts[0].indexOf('stun') === 0)
				{
					// Create iceServer with stun url.
					iceServer = { 'url': url };
				} else if (url_parts[0].indexOf('turn') === 0 &&
				   (url.indexOf('transport=udp') !== -1 ||
					url.indexOf('?transport') === -1))
				{
					// Create iceServer with turn url.
					// Ignore the transport parameter from TURN url.
					var turn_url_parts = url.split("?");
					iceServer = { 'url': turn_url_parts[0],
						'credential': password,
						'username': username };
				}
				return iceServer;
			};

			// Attach a media stream to an element.
			this.attachMediaStream = function(element, stream)
			{
				console.log("Attaching media stream");
				element.mozSrcObject = stream;
				element.play();
			};

			this.reattachMediaStream = function(to, from)
			{
				console.log("Reattaching media stream");
				to.mozSrcObject = from.mozSrcObject;
				to.play();
			};

			// Fake get{Video,Audio}Tracks
			MediaStream.prototype.getVideoTracks = function() {
				return [];
			};

			MediaStream.prototype.getAudioTracks = function() {
				return [];
			};
		}
		else if (navigator.webkitGetUserMedia)
		{
			console.log("This appears to be Chrome");

			this.webrtcDetectedBrowser = "chrome";
			this.webrtcDetectedVersion = parseInt(navigator.userAgent.match(/Chrom(e|ium)\/([0-9]+)\./)[2]);

			// The RTCPeerConnection object.
			this.RTCPeerConnection = webkitRTCPeerConnection;

			// The RTCSessionDescription object.
			this.RTCSessionDescription = RTCSessionDescription;

			// The RTCIceCandidate object.
			this.RTCIceCandidate = RTCIceCandidate;

			// Creates iceServer from the url for Chrome.
			this.createIceServer = function(url, username, password)
			{
				var iceServer = null;
				var url_parts = url.split(':');
				if (url_parts[0].indexOf('stun') === 0)
				{
					// Create iceServer with stun url.
					iceServer = { 'url': url };
				}
				else if (url_parts[0].indexOf('turn') === 0)
				{
					if (webrtcDetectedVersion < 28)
					{
						// For pre-M28 chrome versions use old TURN format.
						var url_turn_parts = url.split("turn:");
						iceServer = { 'url': 'turn:' + username + '@' + url_turn_parts[1],
						  'credential': password };
					}
					else
					{
						// For Chrome M28 & above use new TURN format.
						iceServer = { 'url': url,
						  'credential': password,
						  'username': username };
					}
				}
				return iceServer;
			};

			// Get UserMedia (only difference is the prefix).
			// Code from Adam Barth.
			this.getUserMedia = navigator.webkitGetUserMedia.bind(navigator);

			// Attach a media stream to an element.
			this.attachMediaStream = function(element, stream)
			{
				if (typeof element.srcObject !== 'undefined')
					element.srcObject = stream;
				else if (typeof element.mozSrcObject !== 'undefined')
					element.mozSrcObject = stream;
				else if (typeof element.src !== 'undefined')
					element.src = URL.createObjectURL(stream);
				else
					console.log('Error attaching stream to element.');
			}

			this.reattachMediaStream = function(to, from)
			{
				to.src = from.src;
			};

			// The representation of tracks in a stream is changed in M26.
			// Unify them for earlier Chrome versions in the coexisting period.
			if (!webkitMediaStream.prototype.getVideoTracks)
			{
				webkitMediaStream.prototype.getVideoTracks = function() {
					return this.videoTracks;
				};
				webkitMediaStream.prototype.getAudioTracks = function() {
					return this.audioTracks;
				};
			}

			// New syntax of getXXXStreams method in M26.
			if (!webkitRTCPeerConnection.prototype.getLocalStreams)
			{
				webkitRTCPeerConnection.prototype.getLocalStreams = function() {
					return this.localStreams;
				};
				webkitRTCPeerConnection.prototype.getRemoteStreams = function() {
					return this.remoteStreams;
				};
			}
		}
		else
			console.log("Browser does not appear to be WebRTC-capable");
	}
};
