<?php

// This PHP script can safely be included at any time to trigger a task rollover.
// For now it will be included in init.php.

require_once(dirname(__FILE__) ."/../mysql_connect.php");
require_once(dirname(__FILE__) ."/../core/sessions.php");
require_once(dirname(__FILE__) ."/../core/common.php");

$task_action = "rollover";
if (isset($_POST["task_action"])) {
	$task_action = $_POST["task_action"];
}

function escape($str) {
	$str = str_replace("'","\\'",$str);
	$str = str_replace("\n","\\n",$str);
	$str = str_replace("\r","",$str);
	return $str;
}

switch ($task_action) {
	case "did":
		did();
		break;
	case "didnt":
		didnt();
		break;
	default:
		rollover();
		break;
}

function rollover() {
  global $USER;
	$resets = array();
	$result = mysql_query("SELECT * FROM recurring_tasks WHERE org_entities__id = {$USER['org_entities__id']} AND reset_date < '".date("Y-m-d")."'");
	while ($row = mysql_fetch_assoc($result)) {
		$resets[] = $row["task_id"];
		if ($row["done_by"] == "") continue;
		$sql = "INSERT INTO completed_tasks (date_done,task_id,user_ids,points,org_entities__id) VALUES (";
		$sql .= "'". $row["reset_date"] ."',";
		$sql .= $row["task_id"] .",";
		$sql .= "'". $row["done_by"] ."',";
		$sql .= "'". $row["points"] ."',{$USER['org_entities__id']})";
		mysql_query($sql) or die("{action:'error',error:'MySQL error: ".escape(mysql_error() ."::". $sql)."'}");
	}
	if (count($resets) > 0) {
		mysql_query("UPDATE recurring_tasks SET reset_date = '".date("Y-m-d")."', done_by = '' WHERE org_entities__id = {$USER['org_entities__id']} AND task_id IN (".join(",",$resets).")");
	}
}

function did() {
	global $USER;

	if (!isset($USER)) exit;

	$TID = intval($_POST["id"]);
	if ($TID == 0) die("{action:'error',error:'Invalid task ID.'}");

	$result = mysql_query("SELECT * FROM recurring_tasks WHERE org_entities__id = {$USER['org_entities__id']} AND task_id = $TID");
	$data = mysql_fetch_assoc($result);

	// Make sure report has been submitted
	if ($data["report_id"]) {
		$result = mysql_query("SELECT 1 FROM user_rpt_submissions WHERE template_id = ".$data["report_id"]." AND user_id = ".$USER["id"]." AND CAST(submitted_ts AS date) = '{$data["reset_date"]}'");
		if (!mysql_num_rows($result)) {
			die("{action:'didnt',id:'$TID',msg:'You must submit the report before checking the box.'}");
		}
	}

	if ($data["done_by"] != '') {
		$done_by = explode(",",$data["done_by"]);
		foreach ($done_by as $uid) {
			if ($uid == $USER["id"]) die("{action:'did',id:'$TID'}");
		}
	}
	$done_by[] = $USER["id"];
	mysql_query("UPDATE recurring_tasks SET done_by = '".join(",",$done_by)."' WHERE org_entities__id = {$USER['org_entities__id']} AND task_id = $TID");
	die("{action:'did',id:'$TID'}");
}

function didnt() {
	global $USER;

	if (!isset($USER)) exit;

	$TID = intval($_POST["id"]);
	if ($TID == 0) die("{action:'error',error:'Invalid task ID.'}");

	$result = mysql_query("SELECT * FROM recurring_tasks WHERE org_entities__id = {$USER['org_entities__id']} AND task_id = $TID");
	$data = mysql_fetch_assoc($result);

	$done_by = explode(",",$data["done_by"]);
	$new_done_by = array();
	foreach ($done_by as $uid) {
		if ($uid == $USER["id"]) continue;
		$new_done_by[] = $uid;
	}

	mysql_query("UPDATE recurring_tasks SET done_by = '".join(",",$new_done_by)."' WHERE org_entities__id = {$USER['org_entities__id']} AND task_id = $TID");
	die("{action:'didnt',id:'$TID'}");
}

?>