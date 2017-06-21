<?php

if (!isset($USER)) { header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit; }

display_header();

if (isset($_GET["do"])) {
	switch ($_GET["do"]) {
		case "delete":
			delete_message();
			$RESPONSE = "Message deleted.";
			include "views/index.php";
			break;
		case "send":
			include "views/send.php";
			break;
		case "send_sub":
			send_message();
			$RESPONSE = "Message sent.";
			include "views/index.php";
			break;
		case "view":
			include "views/view.php";
			break;
		default:
			include "views/index.php";
			break;
	}
} else {
	include "views/index.php";
}

display_footer();

function delete_message() {
	global $USER;
	$id = intval($_GET["id"]);
	mysql_query("DELETE FROM messages WHERE id = ".$id." AND (users__id__1 = ".$USER["id"]." OR users__id__2 = ".$USER["id"].")");
}

function send_message() {
	global $USER;//,$DB,$db_host,$db_user,$db_pass,$db_database;
	$TO = intval($_POST["to"]);
	if ($TO == 0) $_POST["subject"] = "TO ALL: ". $_POST["subject"];
	$SUBJECT = mysql_real_escape_string($_POST["subject"]);
	$MESSAGE = mysql_real_escape_string($_POST["message"]);
	//$LOCATION = mysql_real_escape_string($_POST["location"]);

	$FRN_USERNAME = array();
//	$here = 0;

	//$result = mysql_query("SELECT * FROM locations WHERE is_here = 1 OR store_number = '{$LOCATION}'");

//	while ($row = mysql_fetch_assoc($result)) {
//		if ($row["id"]) $here = $row["location_code"];
// 		else {
// 			$DB2 = mysql_connect($row["db_host"],$row["db_user"],$row["db_pass"],true);
// 			if ($DB2) {
// 				mysql_select_db($row["db_db"],$DB2);
// 				if ($TO == 0) $result2 = mysql_query("SELECT user_id,username FROM users WHERE disabled = 0",$DB2);
// 				else $result2 = mysql_query("SELECT user_id,username FROM users WHERE user_id = {$TO}",$DB2);
// 				if (mysql_num_rows($result2)) {
// 					while ($data = mysql_fetch_assoc($result2)) {
// 						$FRN_USERNAME[$data["user_id"]] = $data["username"];
// 					}
// 				}
// 			}
// 		}
//	}

	if (!isset($DB2)) {
		if ($TO == 0) $result = mysql_query("SELECT u.id,u.username,u.org_entities__id,oe.location_code FROM users u, org_entities oe WHERE u.is_disabled = 0 AND oe.id = u.org_entities__id");//,$DB);
		else $result = mysql_query("SELECT u.id,u.username,u.org_entities__id,oe.location_code FROM users u, org_entities oe WHERE u.id = {$TO} AND oe.id = u.org_entities__id");//,$DB);
		if (mysql_num_rows($result)) {
			while ($data = mysql_fetch_assoc($result)) {
				$FRN_USERNAME[$data["id"]] = ARRAY( 'uname' => $data["username"] , 'entity' => $data['org_entities__id'] , 'loc_code' => $data['location_code'] );
			}
		}
	}

	foreach ($FRN_USERNAME as $id => $dest) {
// 		if (isset($DB2) && $DB2) {
// 			mysql_query("INSERT INTO messages (user1,user2,box,subject,message,ts,is_read,frn_code) VALUES (".$id.",".$USER["user_id"].",1,'".$SUBJECT."','".$MESSAGE."',NOW(),0,'".$here.":".$USER["username"]."')",$DB2);
// 		} else {
			mysql_query("INSERT INTO messages (users__id__1,users__id__2,box,subject,message,ts,is_read) VALUES (".$id.",".$USER["id"].",1,'".$SUBJECT."','".$MESSAGE."',NOW(),0)");
//		}
		mysql_query("INSERT INTO messages (users__id__1,users__id__2,box,subject,message,ts,is_read) VALUES (".$USER["id"].",".$id.",2,'".$SUBJECT."','".$MESSAGE."',NOW(),0,)";//,$DB);
	}
	//if (isset($DB2) && $DB2) mysql_close($DB2);
	//mysql_close($DB);
	//$DB = mysql_connect($db_host,$db_user,$db_pass,true);
	//mysql_select_db($db_database,$DB);
}

?>
