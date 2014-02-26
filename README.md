##Video communication system in your browser!##


**_Peer-to-peer audio/video communications system using the HTML5 Javascript API webRTC video engine_**

This project demonstrates a basic peer-to-peer audio/video communications system using HTML5 as the audio/video engine.

It was originally built as a proof-of-concept: could I get video to work without any additional plug-ins? And it works!! :)  

I wanted to create a demo that could be installed on most web hosting service provider accounts. This meant that I couldn't use node.js or a unix service in C for signaling.

I also wanted to build the basics of a communications service. It would log all users and messaging. The uploaded version deletes all old data in order to keep a clean database. You can easily change that to log all calls.

**You need to be using the latest versions of either Chrome or Firefox.**  
*Chrome users cannot call Firefox users without getting a special Firefox build.* You might also need to go to about:config and set the media.peerconnection.enabled preference to “true.” Note that in Firefox 27.0.1, this is on by default. You can read more about it here: http://thenextweb.com/apps/2013/02/04/google-and-mozilla-show-off-video-chat-between-chrome-and-firefox-thanks-to-webrtc-support/#!xymz4.  
Firefox currently doesn't support the CSS filters on the page.

We use AJAX, PHP, and a MySQL database for signaling and logging all communications.

Breakdown of files:  
*videologin.html--*	Initial login page. We take your login name and put it in the database for use in video calls.  
*videochat.php--*		Main video page. This is where all the video calls happen.  
*videoControlObjects.js--*	JavaScript page containing core video functionality.  
*videoController.php--*		PHP page that handles signaling. Videochat.php contacts this page via AJAX.  
*webrtcVideo.sql--*			SQL for building tables used by this video chat program.  
*_sharedIncludes--*			This directory has files that should be included everywhere, including database connection files and global function files.

**Thanks for reading!!**

Special thanks to Sam Dutton, whose webRTC code (https://bitbucket.org/webrtc) and articles (http://www.html5rocks.com/en/tutorials/webrtc/basics/, http://www.html5rocks.com/en/tutorials/webrtc/infrastructure/) helped me tremendously in making this project happen! Check out some of his super cool projects here: https://github.com/samdutton/simpl.