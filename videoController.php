<?
	include("_sharedIncludes/globals.php");
	include("_sharedIncludes/dbconnect.php");
	
	$client_form_data = json_decode(file_get_contents('php://input'), true);
	
	function getAllUsers()
	{
		$mysqli = $GLOBALS["mysqli"];
		
		$mysqli->query("DELETE FROM videoUsers
						WHERE TIME_TO_SEC(TIMEDIFF(now(), videoUserTimestamp)) > 30;");

		$allUsers = "SELECT videoUserID, videoUsername
					FROM videoUsers;";
		$allUsersSql = $mysqli->query($allUsers);
		$allUsersArray = array();
		while ($row = $allUsersSql->fetch_assoc())
		{
			$allUsersArray[$row["videoUserID"]] = $row["videoUsername"];
			//array_push($allUsersArray, $row["videoUsername"]);
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
		if (strcmp($sdp_array['type'], "candidate") == 0)
		{
			$mysqli->query("INSERT INTO videoICEcandidates (sendVideoUsername, receiveVideoUsername, videoIceCandidateLabel,
							videoIceCandidateID, videoIceCandidateCandidate)
							VALUES ('{$client_form_data['from_video_user']}',
							'{$client_form_data['to_video_user']}',
							'{$sdp_array['label']}', '{$sdp_array['id']}',
							'{$sdp_array['candidate']}');");
		}
		else
		{
			$mysqli->query("INSERT INTO videoSDP (sendVideoUsername, receiveVideoUsername, videoSDP, videoSDPtype)
							VALUES ('{$client_form_data['from_video_user']}',
							'{$client_form_data['to_video_user']}',
							'{$sdp_array['sdp']}',
							'{$sdp_array['type']}');");
		}
		echo json_encode(array("allData" => array("message" => "{$sdp_array['type']} message sent")));
	}
	else if (isset($client_form_data['check_messages']))
	{
		// Get SDP messages
		$checkMessageTxt = "SELECT sendVideoUsername, receiveVideoUsername, videoSDP as message, '' as candidateLabel, videoSDPtype as type
							FROM videoSDP
							WHERE receiveVideoUsername = '{$client_form_data['user_name']}'
							AND videoSDPread = 0
							UNION
							SELECT sendVideoUsername, receiveVideoUsername,
							videoIceCandidateCandidate as message, videoIceCandidateLabel as candidateLabel, 'candidate' as type
							FROM videoICEcandidates
							WHERE receiveVideoUsername = '{$client_form_data['user_name']}'
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
					array_push($messageArray, Array("from_user" => $row['sendVideoUsername'],
														"type" => $row['type'],
														"label" => $row['candidateLabel'],
														"candidate" => $row['message'])
								);
				}
				else
				{
					array_push($messageArray, Array("from_user" => $row['sendVideoUsername'],
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
						WHERE receiveVideoUsername = '{$client_form_data['user_name']}';");

		$mysqli->query("UPDATE videoICEcandidates
						SET videoIceCandidateRead = 1
						WHERE receiveVideoUsername = '{$client_form_data['user_name']}';");

		$mysqli->query("UPDATE videoUsers
						SET videoUserTimestamp = now()
						WHERE videoUsername = '{$client_form_data['user_name']}';");

		$allDataArray["users"] = getAllUsers();

		echo json_encode(array("allData" => $allDataArray));
	}
?>