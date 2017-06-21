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

if (!TFD_HAS_PERMS('admin','use')) {
	echo "You do not have the needed permissions to access this module.";
	display_footer();
	exit;
}

if (isset($_GET['do'])) {
	switch ($_GET['do']) {
		case "user_rpt_subs":
			include "views/user_rpt_subs.php";
			break;
		case "user_rpt_delete":
			user_rpt_delete();
			include "views/user_rpt_list.php";
			break;
		case "user_rpt_edit":
			//TODO
			break;
		case "user_rpt_view":
			include "views/user_rpt_view.php";
			break;
		case "user_rpt_list":
			include "views/user_rpt_list.php";
			break;
		case "user_rpt_new":
			if (isset($_POST["name"])) {
				$_GET["id"] = user_rpt_new();
				include "views/user_rpt_view.php";
			} else {
				include "views/user_rpt_new.php";
			}
			break;
		case "rpt_invoice_chg_dts":
			include "views/rpt_invoice_chg_dts.php";
			break;
		case "rpt_invoice_chg":
			include "rpt/invoice_chg.php";
			break;
		case "rpt_fin_iss":
			include "rpt/fin_issues.php";
			break;
		case "rpt_invsold":
			include "views/rpt_inv_sold_dts.php";
			break;
		case "rpt_inv_sold":
			include "rpt/inv_sold.php";
			break;
		case "rpt_invadd":
			include "views/rpt_invadd_dts.php";
			break;
		case "rpt_inv_added":
			include "rpt/inv_added.php";
			break;
		case "score_dt":
			include "views/rpt_score_dts.php";
			break;
		case "user_score":
			include "rpt/user_score.php";
			break;
		case "feedback_questions":
			include "views/feedback.php";
			break;
		case "task_del_complete":
			if (isset($_GET["location"])) {

				//$result = mysql_query("SELECT * FROM locations WHERE store_number = '". mysql_real_escape_string($_GET["location"]) ."'");

        $result = mysql_query("SELECT * FROM org_entities WHERE id = '". mysql_real_escape_string($_GET["location"]) ."'");

				if (!mysql_num_rows($result)) die("ERROR: Invalid store.");
				$data = mysql_fetch_assoc($result);
// 				if ($data["id"]!==$USER['org_entities__id']) {
// 					$FOREIGN = 1;
// 					$DB2 = mysql_connect($data["db_host"],$data["db_user"],$data["db_pass"]);
// 					mysql_select_db($data["db_db"],$DB2);
// 				}
			}
			$eid = isset($data) ? $data['id'] : $USER['org_entities__id'];
			mysql_query("DELETE FROM tasks WHERE org_entities__id = {$eid} AND is_completed = 1");
			include "views/tasks.php";
			break;
		case "task_delete":
			$RESPONSE = task_delete();
			include "views/tasks.php";
			break;
		case "task_assign":
			$RESPONSE = task_assign();
			include "views/tasks.php";
			break;
		case "tasks":
			include "views/tasks.php";
			break;
		case "rec_tasks":
			include "views/rec_tasks.php";
			break;
		case "new_rec_task":
			$RESPONSE = new_rec_task();
			include "views/rec_tasks.php";
			break;
		case "rec_task_delete":
			$RESPONSE = rec_task_delete();
			include "views/rec_tasks.php";
			break;
		case "step_edit":
			include "views/steps_editor.php";
			break;
		case "svc_edit":
			include "views/services_editor.php";
			break;
		case "cat_edit":
			include "views/category_editor.php";
			break;
		case "punch":
			include "views/punch_cards.php";
			break;
		case "all_punch":
			include "views/all_punch_cards.php";
			break;
		case "punch_graph":
			include "views/punch_graphs.php";
			break;
		case "import_iif":
			include "views/import_iif.php";
			break;
		case "mass_edit_cust":
			include "views/mass_edit_cust.php";
			break;
		case "view_newsletter":
			if (isset($_GET["id"])) {
				$NEWSLETTER = mysql_fetch_assoc(mysql_query("SELECT * FROM newsletters WHERE id = ".intval($_GET["id"])));
				include "views/view_newsletter.php";
			} else {
				include "views/choose_newsletter.php";
			}
			break;
		case "create_newsletter":
			if (isset($_POST["html"])) {
				$NID = create_newsletter();
				$NEWSLETTER = mysql_fetch_assoc(mysql_query("SELECT * FROM newsletters WHERE id = ".$NID));
				include "views/view_newsletter.php";
			} else {
				include "views/create_newsletter.php";
			}
			break;
		case "send_newsletter":
			$NEWSLETTER = mysql_fetch_assoc(mysql_query("SELECT * FROM newsletters WHERE id = ".intval($_GET["id"])));
			$RESPONSE = send_newsletter();
			include "views/view_newsletter.php";
			break;
		case "delete_newsletter":
			mysql_query("DELETE FROM newsletters WHERE id = ".intval($_GET["id"]));
			include "views/choose_newsletter.php";
			break;
		case "edit_newsletter":
			$RESPONSE = edit_newsletter();
			$NEWSLETTER = mysql_fetch_assoc(mysql_query("SELECT * FROM newsletters WHERE id = ".intval($_GET["id"])));
			include "views/view_newsletter.php";
			break;
		case "export_cust":
			include "views/export_cust.php";
			break;
		case "rpt_drops":
			include "rpt/drops.php";
			break;
		case "rpt_user_dts":
			include "views/rpt_user_dts.php";
			break;
		case "rpt_user":
			include "rpt/user.php";
			break;
		case "rpt_deposits_dts":
			include "views/rpt_deposits_dts.php";
			break;
		case "rpt_deposits":
			include "rpt/deposits.php";
			break;
		case "rpt_marketing":
			include "rpt/marketing.php";
			break;
		case "rpt_cust":
			include "rpt/customers.php";
			break;
		case "rpt_store":
			include "rpt/store.php";
			break;
		case "rpt_store_select":
			include "views/rpt_store_select.php";
			break;
		case "rpt_cash":
			include "rpt/cash.php";
			break;
		case "rpt_cash_dts":
			include "views/rpt_cash_dts.php";
			break;
		case "rpt_tax":
			include "rpt/tax.php";
			break;
		case "rpt_tax_dts":
			include "views/rpt_tax_dts.php";
			break;
		case "rpt_punchcards":
			include "rpt/punchcards.php";
			break;
		case "rpt_punchcards_dts":
			include "views/rpt_punchcards_dts.php";
			break;
		case "rpt_cashlog":
			include "rpt/cashlog.php";
			break;
		case "rpt_cashlog_dts":
			include "views/rpt_cashlog_dts.php";
			break;
		case "rpt_store":
			include "rpt/store.php";
			break;
		case "rpt_inv_requests":
			include "rpt/inv_requests.php";
			break;
		case "new_user":
			if (isset($_POST['username'])) {
				$CREATED_USER = new_user();
				if (!$CREATED_USER) {
					$RESPONSE = "That username is already in use.";
					include "views/new_user.php";
				} else {
					include "views/user_created.php";
				}
			} else {
				include "views/new_user.php";
			}
			break;
		case "deposit":
			$LOG = mysql_query("SELECT * FROM pos_cash_log WHERE org_entities__id = {$USER['org_entities__id']} ORDER BY ts");
			include "views/deposit.php";
			break;
		case "deposit_sub":
			$DEPOSITED = make_deposit();
			$LOG = mysql_query("SELECT * FROM pos_cash_log WHERE org_entities__id = {$USER['org_entities__id']} ORDER BY ts");
			include "views/deposit.php";
			break;
		case "edit_fields":
			include "views/edit_fields.php";
			break;
		case "edit_user":
			if (isset($_POST['username'])) {
				$RESPONSE = edit_user();
				$_GET["edited"] = 1;
				$result = mysql_query("SELECT * FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". intval($_GET["id"]));
				if (!mysql_num_rows($result)) {
					$ERROR = "User not found.";
					include "views/index.php";
				} else {
					$THIS_USER = mysql_fetch_assoc($result);
					include "views/edit_user.php";
				}
			} else {
				$result = mysql_query("SELECT * FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". intval($_GET["id"]));
				if (!mysql_num_rows($result)) {
					$ERROR = "User not found.";
					include "views/index.php";
				} else {
					$THIS_USER = mysql_fetch_assoc($result);
					include "views/edit_user.php";
				}
			}
			break;
		case "list_users":
			$USERS = mysql_query("
SELECT
  u.*
FROM
  users u,
  org_entities oe,
  org_entity_types oet
WHERE
  u.org_entities__id = oe.id
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
ORDER BY
  u.id
");
			include "views/list_users.php";
			break;
		case "delete_user":
			delete_user(intval($_GET["id"]));
      		$USERS = mysql_query("SELECT * FROM users WHERE org_entities__id = {$USER['org_entities__id']} ORDER BY id");
			include "views/list_users.php";
      		exit;
			break;
		case "locations":
			include "views/locations.php";
			break;
		case "add_location":
			add_location();
			include "views/locations.php";
			break;
		case "delete_location":
			delete_location();
			include "views/locations.php";
			break;
		default:
			include "views/index.php";
			break;
	}
} else {
	include "views/index.php";
}

display_footer();


function user_rpt_delete() {
	$sql = "DELETE FROM user_rpt_submissions WHERE template_id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);
	$sql = "DELETE FROM user_rpt_templates WHERE template_id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function user_rpt_new() {
	global $USER;

	$rpt_name = $_POST["name"];
	$nFields = intval($_POST["field_count"]);

	$FIELDS = array();
	$FIELD_STR = array();
	for ($i = 1; $i <= $nFields; $i++) {
		if (!isset($_POST["f{$i}_question"])) continue;
		$FIELDS[$i] = array();
		$FIELDS[$i]["order"] = intval($_POST["f{$i}_order"]);
		$FIELDS[$i]["question"] = str_replace("|","-",$_POST["f{$i}_question"]);
		$FIELDS[$i]["rtype"] = intval($_POST["f{$i}_type"]);
		switch ($FIELDS[$i]["rtype"]) {
			case 1: // text
				$FIELDS[$i]["range"] = intval($_POST["f{$i}_res"]);
				break;
			case 2: // integer
				$FIELDS[$i]["range"] = intval($_POST["f{$i}_res_1"]) .",". intval($_POST["f{$i}_res_2"]);
				if ($FIELDS[$i]["range"] == "0,0") $FIELDS[$i]["range"] = 0;
				break;
			case 3: // decimal
				$FIELDS[$i]["range"] = floatval($_POST["f{$i}_res_1"]) .",". floatval($_POST["f{$i}_res_2"]);
				if ($FIELDS[$i]["range"] == "0,0") $FIELDS[$i]["range"] = 0;
				break;
			case 4: // multiple choice
				$opts = array();
				$nOpts = intval($_POST["f{$i}_opts_count"]);
				for ($j = 1; $j <= $nOpts; $j++) {
					if (!isset($_POST["f{$i}_opt_{$j}"])) continue;
					$opts[] = str_replace(",","",$_POST["f{$i}_opt_{$j}"]);
				}
				if (count($opts) == 0) {
					unset($FIELDS[$i]);
					continue;
				}
				$FIELDS[$i]["range"] = str_replace("|","-",join(",",$opts));
				break;
			case 5: // checkbox
				$FIELDS[$i]["range"] = 0;
				break;
			default:
				unset($FIELDS[$i]);
			continue;
		}
	}

	// Sort by "order" value, then disregard it and re-number
	$sortArray = array();
	foreach($FIELDS as $field){
		foreach($field as $key=>$value){
			if(!isset($sortArray[$key])){
				$sortArray[$key] = array();
			}
			$sortArray[$key][] = $value;
		}
	}
	array_multisort($sortArray["order"],SORT_ASC,$FIELDS);

	foreach($FIELDS as $order => $field) {
		$FIELD_STR[] = str_replace("~","-",($order + 1)."|". $field["question"] ."|". $field["rtype"] ."|". $field["range"]);
	}

	$columnData = join("~",$FIELD_STR);

	$sql = "INSERT INTO user_rpt_templates (template_name,created_by,column_data) VALUES (";
	$sql .= "'". mysql_real_escape_string($rpt_name) ."',";
	$sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string($columnData) ."')";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return mysql_insert_id();
}

function new_rec_task() {
  GLOBAL $USER;
	$sql = "INSERT INTO recurring_tasks (reset_date,created_date,descr,done_by,points,report_id,org_entities__id) VALUES (";
	$sql .= "'". date("Y-m-d") ."',";
	$sql .= "'". date("Y-m-d") ."',";
	$sql .= "'". mysql_real_escape_string($_POST["task"]) ."',";
	$sql .= "'',";
	$sql .= "'". floatval($_POST["points"]) ."',";
	if (isset($_POST["report"]) && intval($_POST["report"]) > 0) {
		$sql .= intval($_POST["report"]);
	} else {
		$sql .= "NULL";
	}
	$sql .= ",{$USER['org_entities__id']})";
	$ok = @mysql_query($sql);
	if (!$ok) return "Error: ". mysql_error() ."::". $sql;
	return "Task added.";
}

function rec_task_delete() {
	$sql = "DELETE FROM recurring_tasks WHERE task_id = ". intval($_GET["id"]);
	$ok = mysql_query($sql);
	if (!$ok) return "Error: ". mysql_error() ."::". $sql;
	return "Task deleted.";
}

function task_delete() {
  GLOBAL $USER;
	if (isset($_GET["location"])) {

		//$result = mysql_query("SELECT * FROM locations WHERE store_number = '". mysql_real_escape_string($_GET["location"]) ."'");

    $result = mysql_query("SELECT * FROM org_entities WHERE id = '". mysql_real_escape_string($_GET["location"]) ."'");

		if (!mysql_num_rows($result)) return "ERROR: Invalid store.";
		$data = mysql_fetch_assoc($result);
// 		if (!$data["is_here"]) {
// 			$FOREIGN = 1;
// 			$DB2 = mysql_connect($data["db_host"],$data["db_user"],$data["db_pass"]);
// 			mysql_select_db($data["db_db"],$DB2);
// 		}
	}
  $eid = isset($data) ? $data['id'] : $USER['org_entities__id'];
	mysql_query("DELETE FROM tasks WHERE org_entities__id = {$eid} AND id = ".intval($_GET["id"]));
	return "Task deleted.";
}

function task_assign() {
	global $USER,$DB,$db_host,$db_user,$db_pass,$db_database;

	if (isset($_POST["location"])) {

    //$result = mysql_query("SELECT * FROM locations WHERE store_number = '". mysql_real_escape_string($_GET["location"]) ."'");

    $result = mysql_query("SELECT * FROM org_entities WHERE id = '". mysql_real_escape_string($_POST["location"]) ."'");

		if (!mysql_num_rows($result)) return "ERROR: Invalid store.";
		$data = mysql_fetch_assoc($result);
// 		if (!$data["is_here"]) {
// 			$FOREIGN = 1;
// 			$DB2 = mysql_connect($data["db_host"],$data["db_user"],$data["db_pass"]);
// 			mysql_select_db($data["db_db"],$DB2);
// 		}
	}
  $eid = isset($data) ? $data['id'] : $USER['org_entities__id'];
	$sql = "INSERT INTO tasks (users__id__assigned_to,users__id__assigned_by,task,due,is_completed,toc,points,org_entities__id) VALUES (";
	$sql .= intval($_POST["user"]) .",";
	if (isset($FOREIGN)) $sql .= "NULL,";
	else $sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string($_POST["task"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["due"]) ." ". mysql_real_escape_string($_POST["due_hour"]) .":". mysql_real_escape_string($_POST["due_minute"]) .":00',";
	$sql .= "0,";
	$sql .= "0,";
	$sql .= "'". floatval($_POST["points"]) ."',{$eid})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$message = "You have been assigned a new <a href=\"?module=core&do=tasks\">task</a> which is due on {$_POST["due"]} by {$_POST["due_hour"]}:{$_POST["due_minute"]}.";
	$sql = "INSERT INTO messages (users__id__1,users__id__2,box,subject,message,is_read) VALUES (";
	$sql .= intval($_POST["user"]) .",";
	if (isset($FOREIGN)) $sql .= "NULL,";
	else $sql .= $USER["id"] .",";
	$sql .= "1,'You Have Been Assigned A Task',";
	$sql .= "'". mysql_real_escape_string($message) ."',0)";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return "Task assigned.";
}

function delete_user($id) {
	$setNull = array(

// 		"bugs" => "user_id",
// 		"bugs_notes" => "user_id",
// 		"calendar" => "user_id",
// 		"-calendar" => "updated_by",
// 		"cash_log" => "user_id",
// 		"deposits" => "user_id",
// 		"invoices" => "salesman",
// 		"issue_changes" => "user_id",
// 		"issues" => "intake_tech",
// 		"-issues" => "assigned_to",
// 		"labor" => "tech",
// 		"messages" => "user2",
// 		"newsletters" => "created_by",
// 		"notes" => "user_id",
// 		"transactions" => "salesman",
// 		"-transactions" => "refunded_by",
// 		"tasks" => "assigned_by",

    "bugs" => "users__id",
    "bugs_notes" => "users__id",
    "calendar" => "users__id__target",
    "-calendar" => "users__id__updated",
    "pos_cash_log" => "users__id",
    "pos_deposits" => "users__id",
    "invoices" => "users__id__sale",
    "issue_changes" => "users__id",
    "issues" => "users__id__intake",
    "-issues" => "users__id__assigned",
    "issue_labor" => "users__id",
    "messages" => "users__id__2",
    "newsletters" => "users__id__created_by",
    "user_notes" => "users__id",
    "pos_transactions" => "users__id__sale",
    "-pos_transactions" => "users__id__refund",
    "tasks" => "users__id__assigned_by"
	);

	$deleteRow = array(

// 		"calendar_views" => "user_id",
// 		"cart" => "salesman",
// 		"messages" => "user1",
// 		"punchcards" => "user_id",
// 		"reports_config" => "user_id",
// 		"sessions" => "user_id",
// 		"settings" => "user_id",
// 		"tasks" => "assigned_to",

    "calendar_views" => "users__id",
    "pos_cart_items" => "users__id__sale",
    "messages" => "users__id__1",
    "payroll_timecards" => "users__id",
    "reports_config" => "users__id",
    "sessions" => "users__id",
    "user_settings" => "users__id",
    "tasks" => "users__id__assigned_to"
	);
	foreach ($setNull as $table => $row) {
		mysql_query("UPDATE ".str_replace("-","",$table)." SET $row = NULL WHERE $row = $id");
	}
	foreach ($deleteRow as $table => $row) {
		mysql_query("DELETE FROM $table WHERE $row = $id");
	}
	mysql_query("DELETE FROM users WHERE id = $id");
}

function edit_newsletter() {
	global $USER;

	$subj = (isset($_POST["subj"]) ? $_POST["subj"] : "");
	$msg = (isset($_POST["msg"]) ? $_POST["msg"] : "");
	$html = (isset($_POST["html"]) ? $_POST["html"] : "");

	$sql = "UPDATE newsletters SET ";
	$sql .= "subj = '". mysql_real_escape_string($subj) ."',";
	$sql .= "msg = '". mysql_real_escape_string($msg) ."',";
	$sql .= "html = '". mysql_real_escape_string($html) ."',";
	$sql .= "is_attachment = ". (isset($_POST["is_attachment"]) ? "1" : "0") .",";
	$sql .= "last_emailed = NULL,";
	$sql .= "emailed_to = 0,";
	$sql .= "users__id__created_by = ".$USER["id"].",";
	$sql .= "created = NOW() ";
	$sql .= "WHERE id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return "Changes saved.";
}

function send_newsletter() {
	global $NEWSLETTER, $USER;

	$FROM = "newsletter@computer-answers.com";
	$SUBJECT = $NEWSLETTER["subj"];
	if (!$NEWSLETTER["is_attachment"]) {
		$MESSAGE = $NEWSLETTER["html"];
		$HEADERS = "From: $FROM\r\n";
		$HEADERS .= "Content-type: text/html\r\n";
	} else {
		$semi_rand = md5(time());
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
		$MESSAGE = $NEWSLETTER["msg"];
		$MESSAGE = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $MESSAGE . "\n\n";
		$HEADERS = "From: ".$FROM."\n";
		$HEADERS .= "MIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";
		$MESSAGE .= "--{$mime_boundary}\n";
		$msgdata = chunk_split(base64_encode($NEWSLETTER["html"]));
		$MESSAGE .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"Newsletter.html\"\n" .
				"Content-Disposition: attachment;\n" . " filename=\"Newsletter.html\"\n" .
				"Content-Transfer-Encoding: base64\n\n" . $msgdata . "\n\n";
		$MESSAGE .= "--{$mime_boundary}\n";
	}

	$sent = 0;
	$failed = 0;
	$skipped = 0;
	if (isset($_GET["test"])) {
		$result = mysql_query("SELECT email FROM users WHERE id = ".$USER["id"]);
	} else {
		$result = mysql_query("SELECT DISTINCT email FROM customers WHERE email LIKE '%@%.%' AND is_subscribed = 1");
	}
	while ($row = mysql_fetch_assoc($result)) {
		$emails = explode(",",$row["email"]);
		foreach($emails as $email) {
			if (!check_email_address(trim($email))) {
				$skipped++;
				continue 2;
			}
		}
		$success = mail($row["email"],$SUBJECT,$MESSAGE,$HEADERS);
		if ($success) {
			$sent++;
		} else {
			$failed++;
		}
	}

	if (!isset($_GET["test"])) {
		mysql_query("UPDATE newsletters SET last_emailed = NOW(), emailed_to = ".$sent." WHERE id = ".$NEWSLETTER["id"]);
		$NEWSLETTER = mysql_fetch_assoc(mysql_query("SELECT * FROM newsletters WHERE id = ".$NEWSLETTER["id"]));
	}

	return "Emailed to $sent recipients. $skipped skipped, $failed failed.";
}

function create_newsletter() {
	global $USER;

	$subj = (isset($_POST["subj"]) ? $_POST["subj"] : "");
	$msg = (isset($_POST["msg"]) ? $_POST["msg"] : "");
	$html = (isset($_POST["html"]) ? $_POST["html"] : "");

	$sql = "INSERT INTO newsletters (users__id__created_by,subj,msg,html,is_attachment) VALUES (";
	$sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string($subj) ."',";
	$sql .= "'". mysql_real_escape_string($msg) ."',";
	$sql .= "'". mysql_real_escape_string($html) ."',";
	$sql .= (isset($_POST["is_attachment"]) ? "1" : "0") .")";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return mysql_insert_id();
}

function make_deposit() {
	global $USER;

	$drops = array();
	$total = 0;
	$result = mysql_query("SELECT * FROM pos_cash_log WHERE org_entities__id = {$USER['org_entities__id']} AND is_drop = 1 AND is_deposited = 0 ORDER BY ts");
	while ($drop = mysql_fetch_assoc($result)) {
		if (!isset($_POST["drop".$drop["id"]])) continue;
		mysql_query("UPDATE pos_cash_log SET is_deposited = 1 WHERE id = ". $drop["id"]);
		$drops[] = $drop["id"];
		$total += $drop["amt"];
	}

	if ($total == 0) return 0;

	$sql = "INSERT INTO pos_deposits (users__id,amt,drops,org_entities__id) VALUES (";
	$sql .= $USER["id"] .",";
	$sql .= "'". $total ."',";
	$sql .= "':". implode(":",$drops) .":',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return $total;
}

function add_location() {

// 	$sql = "INSERT INTO locations (store_number,name,db_host,db_user,db_pass,db_db,address,city,state,zip) VALUES (";
// 	$sql .= "'". mysql_real_escape_string($_POST["store_number"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["name"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["db_host"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["db_user"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["db_pass"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["db_db"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["address"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["city"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["state"]) ."',";
// 	$sql .= "'". mysql_real_escape_string($_POST["zip"]) ."')";

  $sql = "INSERT INTO org_entities (title,location_code,address,city,state,postcode,org_entity_types__id) VALUES (";
  $sql .= "'". mysql_real_escape_string($_POST["name"]) ."',";
  $sql .= "'". mysql_real_escape_string($_POST["store_number"]) ."',";
  $sql .= "'". mysql_real_escape_string($_POST["address"]) ."',";
  $sql .= "'". mysql_real_escape_string($_POST["city"]) ."',";
  $sql .= "'". mysql_real_escape_string($_POST["state"]) ."',";
  $sql .= "'". mysql_real_escape_string($_POST["zip"]) ."',";
  $sql .= "'". mysql_real_escape_string($_POST["entity_type"]) ."')";

	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function delete_location() {

	//mysql_query("DELETE FROM locations WHERE location_id = ". intval($_GET["id"]));

  mysql_query("DELETE FROM org_entities WHERE id = ". intval($_GET["id"]));
}

function new_user() {
	$password_plain = trim($_POST["password"]);
	$salt = new_salt(10);
	$password_md5 = md5($password_plain . $salt);

	$result = mysql_query("SELECT * FROM users WHERE username = '". mysql_real_escape_string($_POST["username"]) ."'");
	if (mysql_num_rows($result)) return false;

	$sql = "INSERT INTO users (org_entities__id,user_roles__id,username,password,salt,firstname,lastname,phone,email,is_onsite,hourlyrate,timeout,is_disabled)";
  $sql .= " VALUES (";
	$sql .= mysql_real_escape_string($_POST["location"]) .",";
  $sql .= intval($_POST["user_type"]) .",";
  $sql .= "'". mysql_real_escape_string($_POST["username"]) ."',";
	$sql .= "'". $password_md5 ."',";
	$sql .= "'". $salt ."',";
	$sql .= "'". mysql_real_escape_string($_POST["firstname"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["lastname"]) ."',";
	$sql .= "'". mysql_real_escape_string(str_replace("-","",$_POST["phone"])) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["email"]) ."',";
	$sql .= (isset($_POST["onsite"]) ? "1":"0") .",";
	$sql .= "'". floatval($_POST["hourlyrate"]) ."',";
	$sql .= "1800,0)";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$uid = mysql_insert_id();

	$CREATED = mysql_fetch_assoc(mysql_query("SELECT * FROM users WHERE id = ". $uid));

	//add user_perms

	$MODULES = ARRAY();
	$result = mysql_query("SELECT * FROM modules WHERE 1");
	while(false!==($row=mysql_fetch_assoc($result)))$MODULES[]=$row;

	$add = ARRAY();
	FOREACH ( $MODULES as $module ) {
    $add[] = "({$CREATED['id']},'{$module['module']}',2147483647,0,NOW())";
	}
	$add = IMPLODE(',',$add);
	mysql_query($query="INSERT INTO user_perms (users__id,module,bitfield_n,bitfield_y,last_mod) VALUES $add;");
	if(-1==mysql_affected_rows())exit(mysql_error().'=='.$query);


	return $CREATED;
}

function edit_user() {
	$sql = "UPDATE users SET ";
	$sql .= "username = '". mysql_real_escape_string($_POST["username"]) ."',";
	$sql .= "org_entities__id = ". mysql_real_escape_string($_POST["location"]) .",";
	$password_plain = trim($_POST["password"]);
	if ($password_plain) {
		$salt = new_salt(10);
		$password_md5 = md5($password_plain . $salt);
		$sql .= "salt = '". $salt ."',";
		$sql .= "password = '". $password_md5 ."',";
	}
	$sql .= "firstname = '". mysql_real_escape_string($_POST["firstname"]) ."',";
	$sql .= "lastname = '". mysql_real_escape_string($_POST["lastname"]) ."',";
	preg_match_all('!\d+!', $_POST["phone"], $matches);
	$phone = implode('',$matches[0]);
	$sql .= "phone = '". $phone ."',";
	$sql .= "email = '". mysql_real_escape_string($_POST["email"]) ."',";
	$sql .= "is_onsite = ". (isset($_POST["onsite"]) ? "1":"0") .",";
	$sql .= "user_role__id = ". intval($_POST["user_type"]) .",";
	$sql .= "is_disabled = ". (isset($_POST["disabled"]) ? "1":"0") .",";
	$sql .= "hourlyrate = '". floatval($_POST["hourlyrate"]) ."' ";
	$sql .= "WHERE id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

}

?>
