<?php

if (isset($_GET["ajax"])) {
	if (!isset($USER)) exit;

	switch($_GET["ajax"]) {
		default:
			break;
	}
	exit;
}

if (!isset($USER)) { header("Location: /login.php"); exit; }

display_header();

if (isset($_GET['do'])) {
	switch ($_GET['do']) {
		case "submit_report":
			if (isset($_POST["report_id"])) {
				$RESPONSE = submit_report();
				include "views/index.php";
			} else {
				include "views/submit_report.php";
			}
			break;
		case "task_complete":
			$RESPONSE = task_complete();
			include "views/tasks.php";
			break;
		case "tasks":
			include "views/tasks.php";
			break;
		case "punch":
			include "views/punch.php";
			break;
		case "chpass":
			include "views/chpass.php";
			break;
		case "change_pass":
			$RESPONSE = change_password();
			include "views/index.php";
			break;
		case "rpt_config":
			include "views/rpt_config.php";
			break;
		case "rpt_config_sub":
			$RESPONSE = report_config();
			include "views/index.php";
			break;
		case "index":
			include "views/index.php";
			break;
		default:
			$tasks = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM tasks WHERE org_entities__id = {$USER['org_entities__id']} AND is_completed = 0 AND users__id__assigned_to = {$USER["id"]}"));
			if ($tasks["count"] > 0) {
				include "views/tasks.php";
			} else {
				include "views/index.php";
			}
			break;
	}
} else {
	$tasks = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM tasks WHERE org_entities__id = {$USER['org_entities__id']} AND is_completed = 0 AND users__id__assigned_to = {$USER["id"]}"));
	if ($tasks["count"] > 0) {
		include "views/tasks.php";
	} else {
		include "views/index.php";
	}
}

display_footer();

function submit_report() {
	global $USER;

	$REPORT_ID = intval($_POST["report_id"]);
	$result = mysql_query("SELECT * FROM user_rpt_templates WHERE template_id = $REPORT_ID");
	if (!mysql_num_rows($result)) return "REPORT NOT SUBMITTED: Report template $REPORT_ID was not found.";
	$REPORT = mysql_fetch_assoc($result);

	$COL = explode("~",$REPORT["column_data"]);
	$columns = array();
	foreach ($COL as $c) {
		$columns[] = explode("|",$c);
	}

	$answers = array();
	foreach($columns as $column) {
		$n = $column[0];
		switch ($column[2]) {
			case 1:
				if ($column[3] == 0) {
					$value = $_POST["n_$n"];
				} else {
					$value = substr($_POST["n_$n"],0,$column[3]);
				}
				break;
			case 2:
				$value = intval($_POST["n_$n"]);
				$lim = explode(",",$column[3]);
				if ($lim[0] != 0 && $value < $lim[0]) $value = $lim[0];
				if ($lim[1] != 0 && $value > $lim[1]) $value = $lim[1];
				break;
			case 3:
				$value = floatval($_POST["n_$n"]);
				$lim = explode(",",$column[3]);
				if ($lim[0] != 0 && $value < $lim[0]) $value = $lim[0];
				if ($lim[1] != 0 && $value > $lim[1]) $value = $lim[1];
				break;
			case 4:
				$options = explode(",",$column[3]);
				$valid = false;
				foreach ($options as $option) {
					if ($option == $_POST["n_$n"]) {
						$value = $option;
						$valid = true;
					}
				}
				if (!$valid) $value = "(INVALID)";
				break;
			case 5:
				if (isset($_POST["n_$n"])) $value = "1";
				else $value = "0";
				break;
			default:
				continue;
		}
		$answers[] = $n ."::". str_replace("::",":.:",str_replace("||","|.|",$value));
	}

	$sql = "INSERT INTO user_rpt_submissions (template_id,user_id,submitted_data,was_viewed) VALUES (";
	$sql .= $REPORT_ID .",";
	$sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string(join("||",$answers)) ."',";
	$sql .= "0)";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$SUB_ID = mysql_insert_id();

	return "Report submitted. Your submission number is $SUB_ID.";
}

function task_complete() {
	global $USER;
	mysql_query("UPDATE tasks SET is_completed = 1, toc = NOW() WHERE org_entities__id = {$USER['org_entities__id']} AND users__id__assigned_to = {$USER["id"]} AND id = ".intval($_GET["id"]));
	return "Task marked as complete.";
}

function change_password() {
	global $USER;

	if ($_POST["newpass1"] != $_POST["newpass2"]) {
		return "New passwords must match.";
	}

	if (md5($_POST["cpass"].$USER["salt"]) != $USER["password"]) {
		return "Current password is incorrect.";
	}

	$sql = "UPDATE users SET ";
	$password_plain = trim($_POST["newpass1"]);
	$salt = new_salt(10);
	$password_md5 = md5($password_plain . $salt);
	$sql .= "salt = '". $salt ."',";
	$sql .= "password = '". $password_md5 ."'";
	$sql .= " WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". $USER["id"];
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return "User <b>". $USER["username"] ."</b> updated successfully.";
}

function report_config() {
	global $USER;

	$reports_string = "";
	for ($i = 0; $i < 15; $i++) {
		if (isset($_POST["rpt".$i])) $reports_string .= "x";
		else $reports_string .= "_";
	}

	$STORES = array();
	if (isset($_POST["stores"])) {
		$STORE_MAX = intval($_POST["stores"]);
		for ($i = 1; $i <= $STORE_MAX; $i++) {
			if (isset($_POST["store".$i])) $STORES[] = $i;
		}
	}
	$store_string = implode(":",$STORES);
	if (isset($_POST["all_stores"])) $store_string = "all";
	$store_string = ":". $store_string .":";

	$USERS = array();
	if (isset($_POST["users"])) {
		$USER_MAX = intval($_POST["users"]);
		for ($i = 1; $i <= $USER_MAX; $i++) {
			if (isset($_POST["user".$i])) $USERS[] = $i;
		}
	}
	$user_string = implode(":",$USERS);
	if (isset($_POST["all_users"])) $user_string = "all";
	$user_string = ":". $user_string .":";

	$result = mysql_query("SELECT * FROM reports_config WHERE users__id = ".$USER["id"]);
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
		$sql = "UPDATE reports_config SET reports = '".$reports_string."', org_entities_list = '".$store_string."', users_list = '".$user_string."', email_every = ".intval($_POST["email_every"]).", hr = ".intval($_POST["hour"]).", do_attach = ".intval($_POST["attach"])." WHERE id = ".$USER["id"];
		mysql_query($sql) or die(mysql_error() ."::". $sql);
		return "Reports configuration updated.";
	} else {
		$sql = "INSERT INTO reports_config (users__id,reports,last_emailed,email_every,org_entities_list,hr,do_attach,users_list) VALUES (";
		$sql .= $USER["id"] .",";
		$sql .= "'". $reports_string ."',";
		$sql .= "'". mysql_real_escape_string($_POST["start_date"]) ."',";
		$sql .= intval($_POST["email_every"]) .",";
		$sql .= "'". $store_string ."',";
		$sql .= intval($_POST["hour"]) .",";
		$sql .= intval($_POST["attach"]) .",";
		$sql .= "'". $user_string ."')";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
		return "Reports configuration created.";
	}
}

?>