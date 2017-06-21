<?php
ini_set("display_errors","1");
error_reporting(E_ALL);

require_once("init.php");

if (isset($_GET['module']) && (in_array($_GET['module'],array_keys($HAS_MODULE))))
  $ACTIVE_MODULE = $_GET['module'];

if (!isset($ACTIVE_MODULE)) {
  $ACTIVE_MODULE = $DEFAULT_MODULE;

  $inbox = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM messages WHERE users__id__1 = ".$USER["id"]." AND box = 1 AND is_read = 0"));

	if (intval($inbox["count"]) > 0) $ACTIVE_MODULE = "msg";

  $rc = mysql_fetch_assoc(mysql_query("SELECT IFNULL(MAX(id),0) AS rcid FROM changes"));
  if ($rc["rcid"] > $USER["rc_read"]) $ACTIVE_MODULE = "core";

  $tasks = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM tasks WHERE is_completed = 0 AND users__id__assigned_to = {$USER["id"]}"));

	if ($tasks["count"] > 0) $ACTIVE_MODULE = "core";

	if (!isset($ACTIVE_MODULE))
    $ACTIVE_MODULE = "msg";
}

include($ACTIVE_MODULE ."/index.php");

?>