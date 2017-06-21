<?php

//date_default_timezone_set("America/New_York");
require_once('../config.php');

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) { exit; }

//$_POST["action"] = "search";
//$_POST["str"] = "wol";

//$_POST["action"] = "refresh";
//$_POST["start"] = "2013-01-14";
//$_POST["display"] = "week";

if (!isset($_POST["action"])) exit;

switch ($_POST["action"]) {
	case "set_view":
		set_view();
		break;
	case "delete_view":
		delete_view();
		break;
	case "save_view":
		save_view();
		break;
	case "new":
		new_event();
		break;
	case "delete":
		delete_event();
		break;
	case "delete_all":
		delete_all();
		break;
	case "save":
		save_event();
		break;
	case "save_all":
		save_all();
	case "refresh":
		refresh();
		break;
	case "search":
		search();
		break;
}

function escape($str) {
	return str_replace("'","\\'",$str);
}

function set_view() {
	global $USER;
	mysql_query("UPDATE calendar_views SET is_current = 0 WHERE org_entities__id = {$USER['org_entities__id']} AND users__id = {$USER["id"]}");
	mysql_query("UPDATE calendar_views SET is_current = 1 WHERE org_entities__id = {$USER['org_entities__id']} AND users__id = {$USER["id"]} AND id = ".intval($_POST["id"]));
	echo "{action:['status'],status:''}";
}

function delete_view() {
  global $USER;
	mysql_query("DELETE FROM calendar_views WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($_POST["id"]));
	echo "{action:['status'],status:'View Deleted'}";
}

function save_view() {
	global $USER;
	mysql_query("UPDATE calendar_views SET is_current = 0 WHERE org_entities__id = {$USER['org_entities__id']} AND users__id = {$USER["id"]}");
	if (intval($_POST["id"]) == 0) {
		$sql = "INSERT INTO calendar_views (users__id,name,is_current,event_types,users) VALUES (";
		$sql .= $USER["id"] .",";
		$sql .= "'". mysql_real_escape_string($_POST["name"]) ."',";
		$sql .= "1,";
		$sql .= "'". mysql_real_escape_string($_POST["event_types"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["users"]) ."')";
		mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
		$vid = mysql_insert_id();
		echo "{action:['new_view','status'],status:'View Saved',view:{id:".$vid.",name:'".escape($_POST["name"])."',";
		echo "event_types:[".escape($_POST["event_types"])."],users:['".str_replace(",","','",escape($_POST["users"]))."']}}";
		exit;
	}
	$sql = "UPDATE calendar_views SET ";
	$sql .= "name = '".mysql_real_escape_string($_POST["name"])."',";
	$sql .= "is_current = 1,";
	$sql .= "event_types = '".mysql_real_escape_string($_POST["event_types"])."',";
	$sql .= "users = '".mysql_real_escape_string($_POST["users"])."' ";
	$sql .= "WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($_POST["id"]);
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	echo "{action:['status'],status:'View Saved'}";
}

function search() {
	$search = mysql_real_escape_string(str_replace(" ","",$_POST["str"]));
	$sql = "SELECT * FROM customers WHERE firstname LIKE '%".$search."%' OR lastname LIKE '%".$search."%' OR CONCAT(firstname,lastname) LIKE '%".$search."%' OR company LIKE '%".$search."%' LIMIT 5";
	$result = mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	if (mysql_num_rows($result) < 1) {
		die("{action:['search'],content:'No matches.'}");
	}

	$ACCOUNTS = array();
	$ACCOUNTS_CUST = array();
	$_ACCOUNTS = mysql_query("SELECT * FROM customer_accounts WHERE 1");
	while($row = mysql_fetch_assoc($_ACCOUNTS)){
		$ACCOUNTS[$row["id"]]["id"] = $row["id"];
		$ACCOUNTS[$row["id"]]["customers__id"] = $row["customers__id"];
		$ACCOUNTS_CUST[$row["customers__id"]]["id"] = $row["id"];
	}

	$content = "<table border=\"0\" width=\"100%\">";
	while ($row = mysql_fetch_assoc($result)) {
		$var = "";
		$var .= '<option value=0>On-Site Device</option>';
		$result1 = mysql_query("SELECT * FROM inventory_type_devices WHERE customers__id = ".$row['customers__id']);
		while($row1 = mysql_fetch_assoc($result1)){
			$var .= '<option value='.$row1['id'].'># '.$row1['id'].' ('.$row1['device_model'].')</option>';
		}
		$content .= "<tr><td>";
		$content .= "<a href=\"#\" onclick=\"tsAdd('". $row['firstname'] ." ". $row['lastname'] ."',". $row['customers__id'] .",'".(isset($ACCOUNTS_CUST[$row['customers__id']]) ? $ACCOUNTS_CUST[$row['customers__id']]['id'] : '')."','".$var."')\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
		if($row["company"])$content .= " (<a href=\"#\" onclick=\"tsAdd('". $row['firstname'] ." ". $row['lastname'] ."',". $row['customers__id'] .",'".(isset($ACCOUNTS_CUST[$row['customers__id']]) ? $ACCOUNTS_CUST[$row['customers__id']]['id'] : '')."','".$var."')\">". $row['company'] ."</a>) ";
		$content .= "</td><td style='padding-left:10px'>". display_phone($row['phone_home']) ."</td>";
		$content .= "<td style='padding-left:10px'>". display_phone($row['phone_cell']) ."</td></tr>";
	}
	$content .= "</table>";
	echo "{action:['search'],content:'".escape($content)."'}";
}

function new_event() {
	global $USER;

	if (isset($_POST["customer_id"])) {
		$result = mysql_query("SELECT id FROM customer_accounts WHERE customers__id = ".intval($_POST["customer_id"]));
		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			$account = $data["id"];
		}

		$sql = "INSERT INTO issues (customers__id,varref_status,device_id,varref_issue_type,savedfiles,troubledesc,intake_ts,users__id__intake,"
		."users__id__assigned,final_summary,invoices__id,is_deleted,customer_accounts__id,last_status_chg,has_charger,org_entities__id) VALUES (";
		$sql .= intval($_POST["customer_id"]) .",";
		$sql .= "6,";
		if (intval($_POST["device_id"]) > 0) {
			$sql .= intval($_POST["device_id"]) .",";
		} else {
			$sql .= "NULL,";
		}
		$sql .= (intval($_POST["event_type"]) + 1) .",";
		$sql .= "'". mysql_real_escape_string($_POST["savedfiles"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["troubledesc"]) ."',";
		$sql .= "CURRENT_TIMESTAMP,";
		$sql .= $USER["id"] .",";
		if (intval($_POST["user_id"]) == 0)
			$sql .= "NULL,";
		else
			$sql .= intval($_POST["user_id"]) .",";
		$sql .= "'',";
		$sql .= "NULL,";
		$sql .= "0,";
		if (!isset($account)) {
			$sql .= "NULL,";
		} else {
			$sql .= intval($account) .",";
		}
		$sql .= "NOW(),";
		$sql .= (isset($_POST["with_charger"]) && $_POST["with_charger"] == "1" ? "1":"0");
		$sql .= ",{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		$issue_id = mysql_insert_id();

		if (intval($_POST["user_id"]) > 0) {
			//$result = mysql_query("SELECT name FROM locations WHERE is_here = 1");
      $result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
			if (mysql_num_rows($result)) {
				$data = mysql_fetch_assoc($result);
				$store_name = " at the ".$data["title"]." location";
			} else {
				$store_name = "";
			}

			$result = mysql_query("SELECT email FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND is_disabled = 0 AND id = ".intval($_POST["user_id"]));
			if (mysql_num_rows($result)) {
				$data = mysql_fetch_assoc($result);
				$TO = $data["email"];
				$FROM = "frontdesk@computer-answers.com";
				$SUBJECT = "You have been assigned ".($_POST["event_type"] == 1 ? "an onsite":"a remote support")." issue (# $issue_id )";
				$MESSAGE = "You have been assigned ".($_POST["event_type"] == 1 ? "an onsite":"a remote support")." issue$store_name. The issue ID is $issue_id.<br><br>\n\nPlease login and change this issue's status to 'Do It' (or 'Warranty' when applicable) to acknowledge that you received this notice.";
				$HEADERS = "From: $FROM\r\n";
				$HEADERS .= "Content-type: text/html\r\n";
				$OK = mail($TO, $SUBJECT, $MESSAGE, $HEADERS);
			}
		}
	}

	$sql = "INSERT INTO calendar (name,start,start_time,rec_end,end_time,descr,users__id__target,users__id__updated,is_recurring,rec_type,event_type,issues__id,org_entities__id) VALUES (";
	$sql .= "'". mysql_real_escape_string($_POST["name"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["date"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["startTime"]) .":00',";
	if (intval($_POST["recurring"]) == 0) {
		$sql .= "NULL,";
	} else {
		$sql .= "'". mysql_real_escape_string($_POST["rec_endDate"]) ."',";
	}
	$sql .= "'". mysql_real_escape_string($_POST["endTime"]) .":00',";
	$sql .= "'". mysql_real_escape_string($_POST["descr"]) ."',";
	if (intval($_POST["user_id"]) == 0) {
		$sql .= "NULL,";
	} else {
		$sql .= intval($_POST["user_id"]) .",";
	}
	$sql .= "NULL,";
	$sql .= (intval($_POST["recurring"]) == 0 ? "0":"1") .",";
	if (intval($_POST["recurring"]) == 0) {
		$sql .= "NULL,";
	} else {
		$sql .= "'". mysql_real_escape_string($_POST["rec_type"]) ."',";
	}
	$sql .= intval($_POST["event_type"]) .",";
	if (isset($issue_id)) {
		$sql .= intval($issue_id);
	} else {
		$sql .= "NULL";
	}
	$sql .= ",{$USER['org_entities__id']})";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");

	$event_id = mysql_insert_id();

	$add = "";
	if (intval($_POST["recurring"]) != 0) {
		$add = ",'add'";
	}

	echo "{action:['new'$add],event:{";
	echo "id:'$event_id',";
	echo "oldId:'".escape($_POST["id"])."'";
	if (isset($issue_id)) echo ",issue_id:'$issue_id'";
	echo "}";
	if ($add != "") {
		$json_objects = generateRecurringEvents($event_id);
		echo ",events:[";
		echo join(",",$json_objects);
		echo "]";
	}
	echo "}";
}

function delete_event() {
  global $USER;
	$idp = explode("#",$_POST["id"]);
	$result = mysql_query("SELECT * FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($idp[0]));
	if (!mysql_num_rows($result)) {
		die("{action:['error'],error:'Oops... That event doesn\'t exist in the database.'}");
	}
	if (count($idp) == 1) {
		mysql_query("DELETE FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($idp[0]));
		die("{action:['delete']}");
	}
	if (!preg_match('/\d{4}\-\d{2}\-\d{2}/',$idp[1])) die("{action:['error'],error:'Invalid event ID.'}");
	$base_event = mysql_fetch_assoc($result);
	$result = mysql_query("SELECT * FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND start = '".$idp[1]."' AND parent = ".intval($idp[0]));
	if (mysql_num_rows($result)) {
		$sub_event = mysql_fetch_assoc($result);
		mysql_query("DELETE FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".$sub_event["id"]);
		die("{action:['delete']}");
	}
	$sql = "INSERT INTO calendar (start,rec_end,is_recurring,rec_type,event_type,parent,org_entities__id) VALUES (";
	$sql .= "'". $idp[1] ."',";
	$sql .= "'". $base_event["rec_end"] ."',";
	$sql .= "1,";
	$sql .= "'del',";
	$sql .= $base_event["event_type"] .",";
	$sql .= $base_event["id"] .",{$USER['org_entities__id']})";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	die("{action:['delete']}");
}

function delete_all() {
  global $USER;
	$result = mysql_query("SELECT * FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) {
		die("{action:['error'],error:'Oops... That event doesn\'t exist in the database.'}");
	}
	mysql_query("DELETE FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($_POST["id"])." OR parent = ".intval($_POST["id"]));
	die("{action:['delete'],delete_all:'".intval($_POST["id"])."'}");
}

function save_event() {
  global $USER;
	$idp = explode("#",$_POST["id"]);
	$result = mysql_query("SELECT * FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($idp[0]));
	if (!mysql_num_rows($result)) {
		die("{action:['error'],error:'Save Failed: Event does not exist.'}");
	}
	$original_event = mysql_fetch_assoc($result);
	$action = array();
	$delete_all = "";
	if (count($idp) == 1) {
		$sql = "UPDATE calendar SET ";
		$sql .= "start = '".mysql_real_escape_string($_POST["date"])."',";
		$sql .= "start_time = '".mysql_real_escape_string($_POST["startTime"]).":00',";
		if ($_POST["rec_endDate"] == "") {
			$sql .= "rec_end = NULL,";
		} else {
			$sql .= "rec_end = '".mysql_real_escape_string($_POST["rec_endDate"])."',";
		}
		$sql .= "end_time = '".mysql_real_escape_string($_POST["endTime"]).":00',";
		$sql .= "name = '".mysql_real_escape_string($_POST["name"])."',";
		$sql .= "descr = '".mysql_real_escape_string($_POST["descr"])."',";
		if (intval($_POST["user_id"]) == 0) {
			$sql .= "users__id__target = NULL,";
		} else {
			$sql .= "users__id__target = ".intval($_POST["user_id"]).",";
		}
		$sql .= "is_recurring = ".intval($_POST["recurring"]).",";
		$sql .= "rec_type = '".mysql_real_escape_string($_POST["rec_type"])."',";
		$sql .= "event_type = ".intval($_POST["event_type"])." ";
		$sql .= "WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($idp[0]);
		mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
		if ($original_event["is_recurring"] && !intval($_POST["recurring"])) {
			$action[] = "'delete'";
			$delete_all = ",delete_all:'".intval($idp[0])."'";
			mysql_query("DELETE FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND parent = ".intval($idp[0]));
		} else if (!$original_event["recurring"] && intval($_POST["recurring"])) {
			$json_events = generateRecurringEvents(intval($idp[0]),1);
			$action[] = "'delete'";
			$delete_all = ",delete_all:'".intval($idp[0])."'";
			$action[] = "'add'";
			$events = ",events:[".join(",",$json_events)."]";
		} else if ($original_event["is_recurring"]) {
			if ($_POST["rec_type"] != $original_event["rec_type"] || $_POST["rec_endDate"] != $original_event["rec_end"]) {
				mysql_query("DELETE FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND parent = ".intval($idp[0]));
				$json_events = generateRecurringEvents(intval($idp[0]),1);
				$action[] = "'delete'";
				$delete_all = ",delete_all:'".intval($idp[0])."'";
				$action[] = "'add'";
				$events = ",events:[".join(",",$json_events)."]";
			}
		}
		echo "{action:[".join(",",$action)."]";
		if (isset($delete_all)) echo $delete_all;
		if (isset($events)) echo $events;
		echo "}";
		return;
	}
	if (!preg_match('/\d{4}\-\d{2}\-\d{2}/',$idp[1])) die("{action:['error'],error:'Invalid event ID.'}");
	$result = mysql_query("SELECT * FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND start = '".$idp[1]."' AND parent = ".intval($idp[0]));
	if (mysql_num_rows($result)) {
		$sub_event = mysql_fetch_assoc($result);
		$sql = "UPDATE calendar SET ";
		$sql .= "start = '".mysql_real_escape_string($_POST["date"])."',";
		$sql .= "start_time = '".mysql_real_escape_string($_POST["startTime"]).":00',";
		if ($_POST["rec_endDate"] == "") {
			$sql .= "rec_end = NULL,";
		} else {
			$sql .= "rec_end = '".mysql_real_escape_string($_POST["rec_endDate"])."',";
		}
		$sql .= "end_time = '".mysql_real_escape_string($_POST["endTime"]).":00',";
		$sql .= "name = '".mysql_real_escape_string($_POST["name"])."',";
		$sql .= "descr = '".mysql_real_escape_string($_POST["descr"])."',";
		if (intval($_POST["user_id"]) == 0) {
			$sql .= "users__id__target = NULL,";
		} else {
			$sql .= "users__id__target = ".intval($_POST["user_id"]).",";
		}
		$sql .= "is_recurring = ".intval($_POST["recurring"]).",";
		$sql .= "rec_type = '".mysql_real_escape_string($_POST["rec_type"])."',";
		$sql .= "event_type = ".intval($_POST["event_type"])." ";
		$sql .= "WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".$sub_event["id"];
		die("{action:['save']}");
	}
	$sql = "INSERT INTO calendar (start,start_time,rec_end,end_time,name,descr,users__id__target,is_recurring,rec_type,event_type,parent,org_entities__id) VALUES (";
	$sql .= "'". $idp[1] ."',";
	$sql .= "'". mysql_real_escape_string($_POST["startTime"]).":00',";
	$sql .= "'". $original_event["rec_end"] ."',";
	$sql .= "'". mysql_real_escape_string($_POST["endTime"]).":00',";
	$sql .= "'". mysql_real_escape_string($_POST["name"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["descr"]) ."',";
	if (intval($_POST["user_id"]) == 0) {
		$sql .= "NULL,";
	} else {
		$sql .= intval($_POST["user_id"]) .",";
	}
	$sql .= "1,";
	$sql .= "'edit',";
	$sql .= intval($_POST["event_type"]) .",";
	$sql .= $idp[0] .",{$USER['org_entities__id']})";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	die("{action:['save']}");
}

function save_all() {
  global $USER;

}

function refresh() {
	$json_objects = generateRecurringEvents();
	echo "{action:['refresh'],events:[";
	echo join(",",$json_objects);
	echo "]}";
}

function generateRecurringEvents($id = 0,$include_original = 0) {
  global $USER;
	$startDate = $_POST["start"];
	if (!preg_match('/\d{4}\-\d{2}\-\d{2}/',$startDate)) $startDate = date('Y-m-d');
	$start = new DateTime($startDate);
	switch ($_POST["display"]) {
		case "month":
			$end = clone $start;
			$end->add(new DateInterval("P6W"));
			break;
		case "week":
			$end = clone $start;
			$end->add(new DateInterval("P7D"));
			break;
		default:
			$end = clone $start;
			$end->add(new DateInterval("P1D"));
		break;
	}
	if ($id) $sql = "";
	if (!$id) $sql = "SELECT * FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND is_recurring = 0 AND start >= '".$start->format('Y-m-d')."' AND start < '".$end->format('Y-m-d')."'";
	if (!$id) $sql .= " UNION ";
	$sql .= "SELECT * FROM calendar WHERE org_entities__id = {$USER['org_entities__id']} AND is_recurring = 1 AND rec_end > '".$start->format('Y-m-d')."'";
	if ($id) $sql .= " AND id = $id";
	//echo $sql ."<br>\n";
	$result = mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	$EVENTS = array();
	$REC = array();
	$REC_DEL = array();
	$REC_EDIT = array();
	while ($row = mysql_fetch_assoc($result)) {
		if ($row["recurring"] == 0) {
			$EVENTS[] = $row;
		} else {
			switch($row["rec_type"]) {
				case "del":
					$REC_DEL[] = $row;
					break;
				case "edit":
					$REC_EDIT[] = $row;
					break;
				default:
					$REC[] = $row;
				break;
			}
		}
	}
	foreach($REC as $this_rec) {
		$endDate = new DateTime($this_rec["rec_end"]);
		$day = new DateTime($this_rec["start"]);
		$rt = explode('_',$this_rec["rec_type"]);
		$len = $rt[1];
		switch ($rt[0]) {
			case "month":
				$step = "M";
				break;
			case "week":
				$step = "W";
				break;
			default:
				$step = "D";
			break;
		}
		while ($day < $end) {
			if ($day < $start) {
				$day->add(new DateInterval("P$len$step"));
				continue;
			}
			if ($day >= $endDate) break;
			foreach($REC_DEL as $this_del) {
				if ($this_del["parent"] == $this_rec["id"] && $this_del["start"] == $day->format("Y-m-d")) {
					$day->add(new DateInterval("P$len$step"));
					continue 2;
				}
			}
			foreach($REC_EDIT as $this_edit) {
				if ($this_edit["parent"] == $this_rec["id"] && $this_edit["start"] == $day->format("Y-m-d")) {
					$new_edit = $this_edit;
					$new_edit["id"] = $this_edit["parent"]."#".$day->format("Y-m-d");
					$new_edit["start"] = $day->format("Y-m-d");
					$EVENTS[] = $new_edit;
					$day->add(new DateInterval("P$len$step"));
					continue 2;
				}
			}
			if ($id && $this_rec["start"] == $day->format("Y-m-d") && !$include_original) {
				// Skip first event
			} else {
				$new_rec = $this_rec;
				$new_rec["id"] = $this_rec["id"]."#".$day->format("Y-m-d");
				$new_rec["start"] = $day->format("Y-m-d");
				$EVENTS[] = $new_rec;
			}
			$day->add(new DateInterval("P$len$step"));
		}
	}

	$json_objects = array();
	foreach ($EVENTS as $event) {
		$obj = "{";
		$obj .= "id:'".$event["id"]."',";
		$obj .= "name:'".escape($event["name"])."',";
		$obj .= "descr:'".escape(str_replace("\n",'\\n',$event["descr"]))."',";
		$obj .= "user_id:'".intval($event["users__id__target"])."',";
		$obj .= "event_type:'".$event["event_type"]."',";
		$obj .= "startTime:'".hhmm($event["start_time"])."',";
		$obj .= "endTime:'".hhmm($event["end_time"])."',";
		$obj .= "date:'".$event["start"]."',";
		$obj .= "recurring:".($event["is_recurring"] ? "true":"false").",";
		$obj .= "rec_type:'".$event["rec_type"]."',";
		$obj .= "rec_endDate:'".$event["rec_end"]."',";
		$obj .= "issue_id:'".intval($event["issues__id"])."'";
		$obj .= "}";
		$json_objects[] = $obj;
	}
	return $json_objects;
}

function hhmm($time) {
	$tp = explode(":",$time);
	return $tp[0].":".$tp[1];
}

function display_phone($phone) {
	switch (strlen($phone)) {
		case 7:
			return substr($phone,0,3) ."-". substr($phone,3,4);
			break;
		case 10:
			return substr($phone,0,3) ."-". substr($phone,3,3) ."-". substr($phone,6,4);
			break;
		default:
			return $phone;
		break;
	}
}

?>
