<?php

// Initialization...
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once("core/common.php");
require_once("mysql_connect.php");
require_once("core/sessions.php");

//TODO: Replace this require with a cron job
require_once("core/task_ajax.php"); // Should be included at least once a day to log previous day's tasks

if (!isset($USER)) { header("Location: /login.php"); exit; }

$result = mysql_query("SELECT * FROM modules WHERE 1 ORDER BY title");
$MODULES = array();
$DEFAULT_MODULE = NULL;
while ($module = mysql_fetch_assoc($result)) {
	$HAS_MODULE[$module["module"]] = 1;
	$MODULES[] = $module;
	if ($module["is_default"]) $DEFAULT_MODULE = $module["module"];
}

//$result = mysql_query("SELECT * FROM config");
$CONFIG = array();
while ($row = mysql_fetch_assoc($result)) {
 	$CONFIG[$row["setting"]] = $row["value"];
}


$IS = array();

$idresult = mysql_query("SELECT id FROM option_values WHERE category='issue_step'");

while($row = mysql_fetch_assoc($idresult)){
  $IS[] = $row["id"];
}

$DT = array();

$dtresult = mysql_query("SELECT id FROM option_values WHERE category='device_cat'");

while($row = mysql_fetch_assoc($dtresult)){
  $DT[] = $row["id"];
}

$result = mysql_query("SELECT * FROM option_values WHERE category='service_type' OR category='device_type' OR category='issue_step'");

$ISSUE_STEP = array();
$DEVICE_STEP = array(0=>"None");
$ISS_ONLY = array();
$x=0;
while ($STEP = mysql_fetch_assoc($result)) {
  switch($STEP["category"]){
    case "service_type":
      $ISSUE_STEP[$STEP["id"]]["value"] = $STEP["value"];
      break;
    case "issue_step":
      $ISS_ONLY[$STEP["id"]]["value"] =  $STEP["value"];
      break;
    case "device_type":
      $DEVICE_STEP[$STEP["id"]]["value"] = $STEP["value"];
      break;
    default:
      break;
  }
}

function sql($action,$table,$column,$val,$is,$equal){
  switch($action){
    case "UPDATE":
      $sql = mysql_query("UPDATE ".$table." SET ".$column."='".mysql_real_escape_string($val)."' WHERE ".$is."=".$equal."");
      return $sql;
      break;
    case "SELECT":
      if($table=="option_values"){
        $sql = mysql_query("SELECT ".$column." FROM ".$table." WHERE ".$is."='". $val ."' ORDER BY value");
      } else {
        $sql = mysql_query("SELECT ".$column." FROM ".$table." WHERE ".$is."='". $val ."'");
      }
      if($column=="*"){
        return $sql;
      } else {
        $sql = mysql_fetch_array($sql);
        $sql = $sql[0];
        return $sql;
      }
      break;
    case "DELETE":
      break;
  }
}

include "header.php";
include "footer.php";

?>