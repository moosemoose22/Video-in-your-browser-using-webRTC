<?
	include("_sharedIncludes/globals.php");
	include("_sharedIncludes/dbconnect.php");
	
	$client_form_data = json_decode(file_get_contents('php://input'), true);
	
	function getAllUsers()
	{
		$mysqli = $GLOBALS["mysqli"];
		
		$allLoggedOutCallIDs = "SELECT DISTINCT videoCallID
							FROM videoUsers
							WHERE videoUserID IN
								(SELECT videoUserID
								FROM videoUsers
								WHERE TIME_TO_SEC(TIMEDIFF(now(), videoUserTimestamp)) > 30)
							;";
		$allCallIDSql = $mysqli->query($allLoggedOutCallIDs);
		$allUsersArray = array();
		/*
		while ($row = $allCallIDSql->fetch_assoc())
		{
			$mysqli->query("DELETE FROM videoICEcandidates
							WHERE videoCallID = {$row['videoCallID']};");
			$mysqli->query("DELETE FROM videoSDP
							WHERE videoCallID = {$row['videoCallID']};");
			$mysqli->query("DELETE FROM videoCalls
							WHERE videoCallID = {$row['videoCallID']};");
		}
		*/
		$mysqli->query("DELETE FROM videoUsers
						WHERE TIME_TO_SEC(TIMEDIFF(now(), videoUserTimestamp)) > 30;");

		$allUsers = "SELECT videoUserID, videoUsername
					FROM videoUsers;";
		$allUsersSql = $mysqli->query($allUsers);
		$allUsersArray = array();
		while ($row = $allUsersSql->fetch_assoc())
		{
			$allUsersArray[$row["videoUserID"]] = $row["videoUsername"];
		}
		$allUsersSql->free();
		return $allUsersArray;
	}
	
	if (isset($client_form_data['login']))
	{
		$checkIfExists = "SELECT *
							FROM videoUsers
							WHERE videoUsername = '{$client_form_data['login']}';";
		$userExists = false;
		if ($stmt = $mysqli->prepare($checkIfExists))
		{
			$stmt->execute();
			$stmt->store_result();
			$userExists = ($stmt->num_rows > 0);
			$stmt->close();
		}
		$ip = getMyIP();

		if (!$userExists)
		{
			$mysqli->query("INSERT INTO videoUsers (videoUsername, videoUserIP, videoUserTimestamp)
								VALUES ('{$client_form_data['login']}', '{$ip}', now());");
		}
		$allUsersArray = getAllUsers();
		echo json_encode(array("allData" => Array("users" => $allUsersArray)));
	}
	else if (isset($client_form_data['logout']))
	{
		$mysqli->query("DELETE FROM videoUsers
						WHERE videoUsername = '{$client_form_data['logout']}';");
	}
	else if (isset($client_form_data['sdp_message']))
	{
		$sdp_array = $client_form_data['sdp_message'];

		if (strcmp($sdp_array['type'], "offer") == 0)
		{
			$mysqli->query("INSERT INTO videoCalls VALUES (DEFAULT, DEFAULT);");
			$callID = $mysqli->insert_id;

			$mysqli->query("INSERT INTO videoCallUsers (videoCallUserCallID, videoCallUserUserID)
							VALUES ($callID, '{$client_form_data['from_video_user']}');");
			$mysqli->query("INSERT INTO videoCallUsers (videoCallUserCallID, videoCallUserUserID)
							VALUES ($callID, '{$client_form_data['to_video_user']}');");
		}
		else
			$callID = $client_form_data['call_ID'];

		if (empty($callID))
			$callID = 0;

		if (strcmp($sdp_array['type'], "candidate") == 0)
		{
			$mysqli->query("INSERT INTO videoICEcandidates (videoCallID, sendVideoUsername, receiveVideoUsername,
							videoIceCandidateMLineIndex, videoIceCandidateMediaType, videoIceCandidateCandidate)
							VALUES ($callID,
							'{$client_form_data['from_video_user']}',
							'{$client_form_data['to_video_user']}',
							'{$sdp_array['mLineIndex']}',
							'{$sdp_array['mediaType']}',
							'{$sdp_array['candidate']}');");
		}
		else
		{
			
			$mysqli->query("INSERT INTO videoSDP (videoCallID, sendVideoUsername, receiveVideoUsername, videoSDP, videoSDPtype)
							VALUES ($callID,
							'{$client_form_data['from_video_user']}',
							'{$client_form_data['to_video_user']}',
							'{$sdp_array['sdp']}',
							'{$sdp_array['type']}');");
		}
		echo json_encode(array("allData" => array("message" => "{$sdp_array['type']} message sent")));
	}
	else if (isset($client_form_data['check_messages']))
	{
		// Get SDP messages
		$checkMessageTxt = "SELECT videoCallID, sendVideoUsername, receiveVideoUsername, videoSDP as message, '' as mLineIndex,
								videoSDPtype as type
							FROM videoSDP
							WHERE receiveVideoUsername = '{$client_form_data['user_id']}'
							AND videoSDPread = 0
							UNION
							SELECT videoCallID, sendVideoUsername, receiveVideoUsername, videoIceCandidateCandidate as message,
								videoIceCandidateMLineIndex as mLineIndex, 'candidate' as type
							FROM videoICEcandidates
							WHERE receiveVideoUsername = '{$client_form_data['user_id']}'
							AND videoIceCandidateRead = 0";

		$messagesSql = $mysqli->query($checkMessageTxt);
		$allDataArray = Array();
		$messageArray = Array();
		while ($row = $messagesSql->fetch_assoc())
		{
			if (isset($row['sendVideoUsername']))
			{
				if (strcmp($row['type'], "candidate") == 0)
				{
					array_push($messageArray, Array("callID" => $row['videoCallID'],
														"from_user" => $row['sendVideoUsername'],
														"type" => $row['type'],
														"mLineIndex" => $row['mLineIndex'],
														"candidate" => $row['message'])
								);
				}
				else
				{
					array_push($messageArray, Array("callID" => $row['videoCallID'],
														"from_user" => $row['sendVideoUsername'],
														"type" => $row['type'],
														"sdp" => $row['message'])
								);
				}
			}
		}
		$messagesSql->free();
		$allDataArray["messages"] = $messageArray;

		$mysqli->query("UPDATE videoSDP
						SET videoSDPread = 1
						WHERE receiveVideoUsername = '{$client_form_data['user_id']}';");

		$mysqli->query("UPDATE videoICEcandidates
						SET videoIceCandidateRead = 1
						WHERE receiveVideoUsername = '{$client_form_data['user_id']}';");

		$mysqli->query("UPDATE videoUsers
						SET videoUserTimestamp = now()
						WHERE videoUserID = {$client_form_data['user_id']};");

		$allDataArray["users"] = getAllUsers();

		echo json_encode(array("allData" => $allDataArray));
	}
?>