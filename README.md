WebRTC-video
============

Proof-of-concept peer-to-peer video using the HTML5 Javascript API webRTC video engine

This is a demo of the new webRTC functonality in your browser. You can make video calls now without any plug-ins!!

There are tons of webRTC demos on the web. I wanted to do webRTC video too! I wanted to create a demo that could be installed on most web hosting service provider accounts. This meant that I couldn't use node.js or a unix service in C for signaling.

We use AJAX, PHP, and a MySQL database for signalling.

Breakdown of files:
videologin.html--	Initial login page. We take your login name and put it in the database for use in video calls.
videochat.php--		Main video page. This is where all the video calls happen.
videoControlObjects.js--	JavaScript page containing core video functionality.
videoController.php--		PHP page that handles signaling. Videochat.php contacts this page via AJAX.
webrtcVideo.sql--			SQL for building tables used by this video chat program.

Thanks for reading!!

Special thanks to Sam Dutton, whose webRTC code (https://bitbucket.org/webrtc) and articles (http://www.html5rocks.com/en/tutorials/webrtc/basics/, http://www.html5rocks.com/en/tutorials/webrtc/infrastructure/) helped me tremendously in making this project happen! Check out some of his super cool projects here: https://github.com/samdutton/simpl.