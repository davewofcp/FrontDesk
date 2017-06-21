<?php
if (isset($_GET["ajax"])) {
	if (!isset($USER)) exit;

	switch ($_GET["ajax"]) {
		default:
			break;
	}

	exit;
}

if(!isset($USER)) {
  header("Location: /login.php");
  exit;
}

display_header();

$BUG_STATUS = array(
0 => "New",
1 => "Working",
2 => "Done");

// new/diagnosed    0 => "#00CCFF",
// geen/do it       1 => "#00FF00",
// yellow finished  2 => "#CCFF00"

$STATUS_COLOR = array(
0 => "#00CCFF",
1 => "#00FF00",
2 => "#CCFF00");

$BUG_IMPORTANCE = array(
1 => "Normal",
2 => "Urgent");

$BUG_CATEGORIES = array (
		0 => "None",
		1 => "Feature Request",
		2 => "Feature Modification",
		3 => "Error Report",
		4 => "Bug",
		5 => "Idea",
		6 => "Question"
);


if (isset($_GET["do"])) {
	switch ($_GET["do"]) {
		case "new":
			if(isset($_POST["action"]) && $_POST["action"]=="New"){
				$BUG = new_bug();
				include "views/index.php";
			} else {
				include "views/new.php";
			}
			break;
		case "view":
			if(isset($_POST["action"]) && $_POST["action"]=="new_note"){
				new_note();
				$_GET["id"] = intval($_POST["bug_id"]);
				include "views/view.php";
			} else {
				include "views/view.php";
			}
			break;
		case "update":
			update();
			$_GET["id"] = intval($_POST["bug_id"]);
			include "views/view.php";
			break;
		case "delete":
			delete();
			//header("Location: ?module=bugs");
      		include "views/index.php";
			break;
		default:
			include "views/index.php";
			break;
	}
} else {
	include "views/index.php";
}

display_footer();

function new_bug() {
  global $USER;

  mysql_query("INSERT INTO bugs (users__id,descr,varref_status,importance,category,org_entities__id) VALUES ('".intval($USER["id"])."','".mysql_real_escape_string($_POST["descr"])."','".mysql_real_escape_string(intval($_POST["status"]))."','".intval($_POST["importance"])."',".intval($_POST["category"]).",{$USER['org_entities__id']})");

  return mysql_insert_id();

}

function new_note() {
  global $USER;

  mysql_query("INSERT INTO bugs_notes (users__id,note,bugs__id,org_entities__id) VALUES ('".intval($USER["id"])."','".mysql_real_escape_string($_POST["new_note"])."','".intval($_POST["bug_id"])."',{$USER['org_entities__id']})");

}

function update() {
  global $USER;

  mysql_query("UPDATE bugs SET created_ts = created_ts, varref_status='". mysql_real_escape_string(intval($_POST["status"])) ."',importance='". intval($_POST["importance"]) ."' WHERE org_entities__id = {$USER['org_entities__id']} AND id=". intval($_POST["bug_id"]));

}

function delete() {
  global $USER;

  mysql_query("UPDATE bugs SET is_deleted=1 WHERE org_entities__id = {$USER['org_entities__id']} AND id=".intval($_GET["id"]));

}
?>
