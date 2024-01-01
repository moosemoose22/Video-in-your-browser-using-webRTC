<?php	$mysqli = new mysqli("localhost.localdomain", "db_username", "db_password", "db_name");

	if ($mysqli->connect_errno)
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;

	session_start();
?>
