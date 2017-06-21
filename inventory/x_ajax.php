<?php

if (!isset($_POST["cmd"])) exit;

require_once("../init.php");
require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) { exit; }

function ds_escape($str) {
	return str_replace('"','\"',$str);
}
function san_sn($str) {
	$str = str_replace('"','',$str);
	$str = str_replace("\n","",$str);
	$str = str_replace("\r","",$str);
	return $str;
}
function s_escape($str) {
	return str_replace("'","\\'",$str);
}
function sanitize($str) {
	//$str = str_replace("'","\\'",$str);
	$str = str_replace("\n","\\n",$str);
	$str = str_replace("\r","",$str);
	return $str;
}

$LOCATIONS = array();
// $result = mysql_query("SELECT * FROM locations");
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
while ($row = mysql_fetch_assoc($result)) {
	$LOCATIONS[$row["id"]] = $row;
}

switch ($_POST["cmd"]) {
	case "unlock":
		unlock();
		break;
	case "change":
		change();
		break;
	case "deny":
		deny();
		break;
	case "approve":
		approve();
		break;
	case "transfer":
		transfer();
		break;
	case "status":
		status();
		break;
	default:
		alert_die("ERROR: Invalid request: '".$_POST["cmd"]."'");
		break;
}

function transfer() {
	global $DB,$LOCATIONS;
	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	mysql_close($DB);
// 	$DB = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"]);
// 	mysql_select_db($loc["db_db"],$DB);

	$result = mysql_query("SELECT * FROM inventory_requests WHERE id = ".intval($_POST["id"]));
	IF (!mysql_num_rows($result)) alert_die("Request not found.");
	$request = mysql_fetch_assoc($result);


}

function change() {
	//global $DB,$LOCATIONS;
  global $$LOCATIONS;
	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	mysql_close($DB);
// 	$DB = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"]);
// 	mysql_select_db($loc["db_db"],$DB);

	mysql_query("UPDATE inventory_requests SET qty = ".intval($_POST["qty"])." WHERE id = ".intval($_POST["id"]));

	echo '{"action":"changed","sn":"'.san_sn($_POST["store"]).'","id":"'.intval($_POST["id"]).'","qty":"'.intval($_POST["qty"]).'"}';
}

function unlock() {
	$result = mysql_query("SELECT password FROM inventory_passwords WHERE org_entities__id = {$USER['org_entities__id']} AND password IS NOT NULL");
	if (!mysql_num_rows($result)) alert_die("Inventory password is not set. Please contact the administrator.");
	$data = mysql_fetch_assoc($result);
	if ($_POST["p"] != $data["password"]) {
		die('{"action":"pass_wrong"}');
	}
	die('{"action":"pass_ok"}');
}

function approve() {
	//global $DB,$LOCATIONS;
  global $LOCATIONS;
	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	mysql_close($DB);
// 	$DB = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"]);
// 	mysql_select_db($loc["db_db"],$DB);

	mysql_query("UPDATE inventory_requests SET varref_status = 2 WHERE id = ".intval($_POST["id"]));

	$link = alink_onclick("Transfer","#","do_transfer('".san_sn($_POST["store"])."','".intval($_POST["id"])."');");

	echo '{"action":"approved","sn":"'.san_sn($_POST["store"]).'","id":"'.intval($_POST["id"]).'","link":"'.ds_escape($link).'"}';
}

function status() {
	//global $USER,$DB,$LOCATIONS;
  global $USER,$LOCATIONS;
	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	mysql_close($DB);
// 	$DB = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"]) or alert_die("Unable to reach sending store database.");
// 	mysql_select_db($loc["db_db"],$DB) or alert_die("Unable to select sending store database.");

	mysql_query("UPDATE inventory_transfers SET varref_status = ".intval($_POST["status"]).",ts_updated = NOW() WHERE id = ".intval($_POST["id"]));

	$result = mysql_query("SELECT * FROM inventory_transfers WHERE id = ".intval($_POST["id"]);//,$DB);
	$data = mysql_fetch_assoc($result);

	if (!isset($LOCATIONS[$data["org_entities__id__dest"]])) alert_die("Receiving store number is invalid.");
	$rcv_loc = $LOCATIONS[$data["org_entities__id__dest"]];
// 	$DB2 = mysql_connect($rcv_loc["db_host"],$rcv_loc["db_user"],$rcv_loc["db_pass"],true) or alert_die("Unable to reach receiving store database.");
// 	mysql_select_db($rcv_loc["db_db"],$DB2) or alert_die("Unable to select receiving store database.");

	mysql_query("UPDATE inventory_transfers SET varref_status = ".intval($_POST["status"]).",ts_updated = NOW() WHERE id = ".$data["inventory_transfers__id__dest"]);//,$DB2);

	// _POST["store"] is the SENDING store, therefore remote_store is RECEIVING
	if (intval($_POST["status"]) == 5) {

		if (intval($data["inventory_item_number_orig"]) > 0) { // Item Transfer, need to create in remote system
			$result = mysql_query("SELECT * FROM inventory_items WHERE id = ". $data["inventory_item_number_orig"]);//,$DB);
			$item = mysql_fetch_assoc($result);
			$sql = "INSERT INTO inventory_items (inventory__id,notes,sn,varref_status,org_entities__id) VALUES (";
			$sql .= $data["inventory__id__dest"] .",";
			$sql .= "'". mysql_real_escape_string($item["notes"]) ."',";
			$sql .= "'". mysql_real_escape_string($item["sn"]) ."',";
			$sql .= intval($item["varref_status"]) .",{$rcv_loc['id']})";
			//mysql_query($sql,$DB2) or alert_die(mysql_error($DB2) ."::". $sql);
      mysql_query($sql) or alert_die(mysql_error() ."::". $sql);
      //$remote_inv_id = mysql_insert_id($DB2);
			$remote_inv_id = mysql_insert_id();

			$sql = "DELETE FROM inventory_items WHERE id = ".$data["inventory_item_number_orig"];
			//mysql_query($sql,$DB) or alert_die(mysql_error($DB) ."::". $sql);
      mysql_query($sql) or alert_die(mysql_error() ."::". $sql);

			// SENDER change log
			$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,descr,varref_status,org_entities__id) VALUES (";
			$sql .= $data["inventory_item_number_orig"] .",";
			$sql .= "NULL,";
			$sql .= $USER["id"] .",";
			$sql .= "4,1,";
			$sql .= "'Item ".$data["inventory_item_number_orig"]." sent to store #".mysql_real_escape_string($rcv_loc["location_code"])."',";
			$sql .= intval($item["varref_status"]) .",{$loc['id']})";
			//mysql_query($sql,$DB) or alert_die(mysql_error($DB) ."::". $sql);
      mysql_query($sql) or alert_die(mysql_error() ."::". $sql);

			// RECEIVER change log
			$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,descr,varref_status,org_entities__id) VALUES (";
			$sql .= $data["inventory__id__dest"] .",";
			$sql .= $remote_inv_id .",";
			$sql .= "NULL,";
			$sql .= "5,1,";
			$sql .= "'Arrived from store #".mysql_real_escape_string($loc["location_code"])."',";
			$sql .= intval($item["varref_status"]) .",{$rcv_loc['id']})";
			//mysql_query($sql,$DB2) or alert_die(mysql_error($DB2) ."::". $sql);
      mysql_query($sql) or alert_die(mysql_error() ."::". $sql);

		} else { // Quantity shift
			$sql = "UPDATE inventory SET qty = qty + ".intval($data["qty"])." WHERE id = ".$data["inventory__id__dest"];
			//mysql_query($sql,$DB2) or alert_die(mysql_error($DB2) ."::". $sql);
      mysql_query($sql) or alert_die(mysql_error() ."::". $sql);

			// SENDER change log
			$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,descr,varref_status,org_entities__id) VALUES (";
			$sql .= $data["inventory_item_number_orig"] .",";
			$sql .= "NULL,";
			$sql .= $USER["id"] .",";
			$sql .= "11,1,";
			$sql .= "'".intval($data["qty"])." units sent to store #".mysql_real_escape_string($rcv_loc["location_code"])."',";
			$sql .= "NULL,{$loc['id']})";
			//mysql_query($sql,$DB) or alert_die(mysql_error($DB) ."::". $sql);
      mysql_query($sql) or alert_die(mysql_error() ."::". $sql);

			// RECEIVER change log
			$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,descr,varref_status,org_entities__id) VALUES (";
			$sql .= $data["inventory__id__dest"] .",";
			$sql .= "NULL,";
			$sql .= "NULL,";
			$sql .= "12,1,";
			$sql .= "'".intval($data["qty"])." units arrived from store #".mysql_real_escape_string($loc["location_code"])."',";
			$sql .= "NULL,{$rcv_loc['id']})";
			//mysql_query($sql,$DB2) or alert_die(mysql_error($DB2) ."::". $sql);
      mysql_query($sql) or alert_die(mysql_error() ."::". $sql);
		}

		// Set transfer completion timestamp in both locations
		mysql_query("UPDATE inventory_transfers SET ts_completed = NOW() WHERE id = ".intval($_POST["id"]);//,$DB);
		mysql_query("UPDATE inventory_transfers SET ts_completed = NOW() WHERE id = ".$data["inventory_transfers__id__dest"]);//,$DB2);

		echo '{"action":"transfer","sn":"'.san_sn($_POST["store"]).'","id":"'.intval($_POST["id"]).'","updated":"'.ds_escape($data["ts_updated"]).'"}';
		exit;
	}

	echo '{"action":"status","sn":"'.san_sn($_POST["store"]).'","id":"'.intval($_POST["id"]).'","updated":"'.ds_escape($data["ts_updated"]).'"}';
}

function deny() {
	global $DB,$LOCATIONS;
	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	mysql_close($DB);
// 	$DB = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"]);
// 	mysql_select_db($loc["db_db"],$DB);

	mysql_query("UPDATE inventory_requests SET varref_status = 3 WHERE id = ".intval($_POST["id"]));

	echo '{"action":"denied","sn":"'.san_sn($_POST["store"]).'","id":"'.intval($_POST["id"]).'"}';
}

function alert_die($err) {
	die('{"action":"alert","alrt":"'.ds_escape($err).'"}');
}
