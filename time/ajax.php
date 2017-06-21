<?php

require_once("../config.php");

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) { exit; }

//$_POST["action"] = "search";
//$_POST["str"] = "wol";

//$_POST["action"] = "refresh";
//$_POST["start"] = "2013-01-14";
//$_POST["display"] = "week";

if (!isset($_POST["action"])) exit;

if ($USER["isadmin"]) {
	switch ($_POST["action"]) {
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
		default:
			die("{action:['error'],error:'Invalid AJAX call.'}");
	}
} else {
	switch ($_POST["action"]) {
		case "refresh":
			refresh();
			break;
		default:
			die("{action:['error'],error:'Invalid AJAX call.'}");
	}
}

function escape($str) {
	return str_replace("'","\\'",$str);
}

function new_event() {
	global $USER;

	$sql = "INSERT INTO timesheets (start,start_time,rec_end,end_time,user_id,created_by,recurring,rec_type,org_entities__id) VALUES (";
	$sql .= "'". mysql_real_escape_string($_POST["date"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["startTime"]) .":00',";
	if (intval($_POST["recurring"]) == 0) {
		$sql .= "NULL,";
	} else {
		$sql .= "'". mysql_real_escape_string($_POST["rec_endDate"]) ."',";
	}
	$sql .= "'". mysql_real_escape_string($_POST["endTime"]) .":00',";
	if (intval($_POST["user_id"]) == 0) {
		$sql .= "NULL,";
	} else {
		$sql .= intval($_POST["user_id"]) .",";
	}
	$sql .= $USER["id"] .",";
	$sql .= (intval($_POST["recurring"]) == 0 ? "0":"1") .",";
	if (intval($_POST["recurring"]) == 0) {
		$sql .= "NULL";
	} else {
		$sql .= "'". mysql_real_escape_string($_POST["rec_type"]) ."'";
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
	$result = mysql_query("SELECT * FROM timesheets WHERE event_id = ".intval($idp[0]));
	if (!mysql_num_rows($result)) {
		die("{action:['error'],error:'Oops... That event doesn\'t exist in the database.'}");
	}
	if (count($idp) == 1) {
		mysql_query("DELETE FROM timesheets WHERE event_id = ".intval($idp[0]));
		die("{action:['delete']}");
	}
	if (!preg_match('/\d{4}\-\d{2}\-\d{2}/',$idp[1])) die("{action:['error'],error:'Invalid event ID.'}");
	$base_event = mysql_fetch_assoc($result);
	$result = mysql_query("SELECT * FROM timesheets WHERE start = '".$idp[1]."' AND parent = ".intval($idp[0]));
	if (mysql_num_rows($result)) {
		$sub_event = mysql_fetch_assoc($result);
		mysql_query("DELETE FROM timesheets WHERE event_id = ".$sub_event["event_id"]);
		die("{action:['delete']}");
	}
	$sql = "INSERT INTO timesheets (start,rec_end,recurring,rec_type,updated_by,parent,org_entities__id) VALUES (";
	$sql .= "'". $idp[1] ."',";
	$sql .= "'". $base_event["rec_end"] ."',";
	$sql .= "1,";
	$sql .= "'del',";
	$sql .= $USER["id"] .",";
	$sql .= $base_event["event_id"] .",{$USER['org_entities__id']})";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	die("{action:['delete']}");
}

function delete_all() {
	$result = mysql_query("SELECT * FROM timesheets WHERE event_id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) {
		die("{action:['error'],error:'Oops... That event doesn\'t exist in the database.'}");
	}
	mysql_query("DELETE FROM timesheets WHERE event_id = ".intval($_POST["id"])." OR parent = ".intval($_POST["id"]));
	die("{action:['delete'],delete_all:'".intval($_POST["id"])."'}");
}

function save_event() {
	global $USER;
	$idp = explode("#",$_POST["id"]);
	$result = mysql_query("SELECT * FROM timesheets WHERE event_id = ".intval($idp[0]));
	if (!mysql_num_rows($result)) {
		die("{action:['error'],error:'Save Failed: Event does not exist.'}");
	}
	$original_event = mysql_fetch_assoc($result);
	$action = array();
	$delete_all = "";
	if (count($idp) == 1) {
		$sql = "UPDATE timesheets SET ";
		$sql .= "start = '".mysql_real_escape_string($_POST["date"])."',";
		$sql .= "start_time = '".mysql_real_escape_string($_POST["startTime"]).":00',";
		if ($_POST["rec_endDate"] == "") {
			$sql .= "rec_end = NULL,";
		} else {
			$sql .= "rec_end = '".mysql_real_escape_string($_POST["rec_endDate"])."',";
		}
		$sql .= "end_time = '".mysql_real_escape_string($_POST["endTime"]).":00',";
		if (intval($_POST["user_id"]) == 0) {
			$sql .= "user_id = NULL,";
		} else {
			$sql .= "user_id = ".intval($_POST["user_id"]).",";
		}
		$sql .= "updated_by = ".intval($USER["id"]) .",";
		$sql .= "recurring = ".intval($_POST["recurring"]).",";
		$sql .= "rec_type = '".mysql_real_escape_string($_POST["rec_type"])."' ";
		$sql .= "WHERE event_id = ".intval($idp[0]);
		mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
		if ($original_event["recurring"] && !intval($_POST["recurring"])) {
			$action[] = "'delete'";
			$delete_all = ",delete_all:'".intval($idp[0])."'";
			mysql_query("DELETE FROM timesheets WHERE parent = ".intval($idp[0]));
		} else if (!$original_event["recurring"] && intval($_POST["recurring"])) {
			$json_events = generateRecurringEvents(intval($idp[0]),1);
			$action[] = "'delete'";
			$delete_all = ",delete_all:'".intval($idp[0])."'";
			$action[] = "'add'";
			$events = ",events:[".join(",",$json_events)."]";
		} else if ($original_event["recurring"]) {
			if ($_POST["rec_type"] != $original_event["rec_type"] || $_POST["rec_endDate"] != $original_event["rec_end"]) {
				mysql_query("DELETE FROM timesheets WHERE parent = ".intval($idp[0]));
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
	$result = mysql_query("SELECT * FROM timesheets WHERE start = '".$idp[1]."' AND parent = ".intval($idp[0]));
	if (mysql_num_rows($result)) {
		$sub_event = mysql_fetch_assoc($result);
		$sql = "UPDATE timesheets SET ";
		$sql .= "start = '".mysql_real_escape_string($_POST["date"])."',";
		$sql .= "start_time = '".mysql_real_escape_string($_POST["startTime"]).":00',";
		if ($_POST["rec_endDate"] == "") {
			$sql .= "rec_end = NULL,";
		} else {
			$sql .= "rec_end = '".mysql_real_escape_string($_POST["rec_endDate"])."',";
		}
		$sql .= "end_time = '".mysql_real_escape_string($_POST["endTime"]).":00',";
		if (intval($_POST["user_id"]) == 0) {
			$sql .= "user_id = NULL,";
		} else {
			$sql .= "user_id = ".intval($_POST["user_id"]).",";
		}
		$sql .= "recurring = ".intval($_POST["recurring"]).",";
		$sql .= "rec_type = '".mysql_real_escape_string($_POST["rec_type"])."' ";
		$sql .= "WHERE event_id = ".$sub_event["event_id"];
		die("{action:['save']}");
	}
	$sql = "INSERT INTO timesheets (start,start_time,rec_end,end_time,user_id,recurring,rec_type,parent,org_entities__id) VALUES (";
	$sql .= "'". $idp[1] ."',";
	$sql .= "'". mysql_real_escape_string($_POST["startTime"]).":00',";
	$sql .= "'". $original_event["rec_end"] ."',";
	$sql .= "'". mysql_real_escape_string($_POST["endTime"]).":00',";
	if (intval($_POST["user_id"]) == 0) {
		$sql .= "NULL,";
	} else {
		$sql .= intval($_POST["user_id"]) .",";
	}
	$sql .= "1,";
	$sql .= "'edit',";
	$sql .= $idp[0] .",{$USER['org_entities__id']}";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	die("{action:['save']}");
}

function save_all() {

}

function refresh() {
	$json_objects = generateRecurringEvents();
	echo "{action:['refresh'],events:[";
	echo join(",",$json_objects);
	echo "]}";
}

function generateRecurringEvents($id = 0,$include_original = 0) {
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
	if (!$id) $sql = "SELECT * FROM timesheets WHERE recurring = 0 AND start >= '".$start->format('Y-m-d')."' AND start < '".$end->format('Y-m-d')."'";
	if (!$id) $sql .= " UNION ";
	$sql .= "SELECT * FROM timesheets WHERE recurring = 1 AND rec_end > '".$start->format('Y-m-d')."'";
	if ($id) $sql .= " AND event_id = $id";
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
				if ($this_del["parent"] == $this_rec["event_id"] && $this_del["start"] == $day->format("Y-m-d")) {
					$day->add(new DateInterval("P$len$step"));
					continue 2;
				}
			}
			foreach($REC_EDIT as $this_edit) {
				if ($this_edit["parent"] == $this_rec["event_id"] && $this_edit["start"] == $day->format("Y-m-d")) {
					$new_edit = $this_edit;
					$new_edit["event_id"] = $this_edit["parent"]."#".$day->format("Y-m-d");
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
				$new_rec["event_id"] = $this_rec["event_id"]."#".$day->format("Y-m-d");
				$new_rec["start"] = $day->format("Y-m-d");
				$EVENTS[] = $new_rec;
			}
			$day->add(new DateInterval("P$len$step"));
		}
	}

	$json_objects = array();
	foreach ($EVENTS as $event) {
		$obj = "{";
		$obj .= "id:'".$event["event_id"]."',";
		$obj .= "user_id:'".intval($event["user_id"])."',";
		$obj .= "startTime:'".hhmm($event["start_time"])."',";
		$obj .= "endTime:'".hhmm($event["end_time"])."',";
		$obj .= "date:'".$event["start"]."',";
		$obj .= "recurring:".($event["recurring"] ? "true":"false").",";
		$obj .= "rec_type:'".$event["rec_type"]."',";
		$obj .= "rec_endDate:'".$event["rec_end"]."'";
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