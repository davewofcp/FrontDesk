<?php

require_once("../mysql_connect.php");
require_once("../core/sessions.php");
require_once("../core/common.php");

if (!isset($USER)) exit;
if (!isset($_POST["action"])) exit;

//$_POST["action"] = "part_search";
//$_POST["category"] = "0";
//$_POST["str"] = "max";

switch ($_POST["action"]) {
	case "tr_cust_find":
		tr_cust_find();
		break;
	case "tr_device_list":
		tr_device_list();
		break;
	case "own_device":
		own_device();
		break;
	case "edit_note":
		edit_note();
		break;
	case "calendar":
		update_calendar();
		break;
	case "step":
		flip_step();
		break;
	case "charger":
		flip_charger();
		break;
	case "do_price":
		add_do_price();
		break;
	case "quote_price":
		edit_quote_price();
		break;
	case "note":
		add_note();
		break;
	case "part_search":
		part_search();
		break;
	case "part_item_list":
		part_item_list();
		break;
	case "part":
		add_part();
		break;
	case "type":
		change_type();
		break;
	case "remove_part":
		remove_part();
		break;
	case "services":
		set_services();
		break;
	case "index":
		index();
		break;
	case "status":
		update_status();
		break;
}

function escape($str) {
	$str = str_replace("'","\\'",$str);
	$str = str_replace("\n","\\n",$str);
	$str = str_replace("\r","",$str);
	return $str;
}

function change_type() {
	global $USER,$ISSUE_TYPE;
	if (intval($_POST["type"]) < 1 || intval($_POST["type"]) >= count($ISSUE_TYPE)) die("{action:['error'],error:'Invalid issue type.'}");
	mysql_query("UPDATE issues SET varref_issue_type = ".intval($_POST["type"])." WHERE id = ".intval($_POST["id"]));

	$sql = "INSERT INTO issue_changes (issues__id,description,varref_status,users__id,org_entities__id) VALUES (";
	$sql .= intval($_POST["id"]).",";
	$sql .= "'Issue Type changed to ".$ISSUE_TYPE[intval($_POST["type"])]."',";
	$sql .= "0,";
	$sql .= $USER["id"] .",{$USER['org_entities__id']})";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");

	echo "{action:['error'],error:'Issue type changed to ".$ISSUE_TYPE[intval($_POST["type"])].".'}";
	exit;
}

function tr_device_list() {
	//$result = mysql_query("SELECT * FROM locations WHERE store_number = '".mysql_real_escape_string($_POST["rstore"])."'");
  $result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = ".mysql_real_escape_string($_POST["rstore"])."
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
	if (!mysql_num_rows($result)) die("{action:['error'],error:'Store not found.'}");
	$STORE = mysql_fetch_assoc($result);
//	mysql_close($DB);
// 	$DB = mysql_connect($STORE["db_host"],$STORE["db_user"],$STORE["db_pass"]) or die("{action:['error'],error:'Unable to connect to remote database.'}");
// 	mysql_select_db($STORE["db_db"],$DB) or die("{action:['error'],error:'Unable to select remote database.'}");

	$result = mysql_query("SELECT * FROM inventory_type_devices d JOIN categories ca ON d.categories__id = ca.id WHERE d.org_entities__id = {$STORE['id']} AND customers__id = ".intval($_POST["c_id"]));

	echo "{action:['d_results'],results:[";
	while ($row = mysql_fetch_assoc($result)) {
		echo "{";
		echo "id:'".$row["id"]."',";
		echo "cat:'".escape($row["category_name"])."',";
		echo "mfc:'".escape($row["manufacturer"])."',";
		echo "model:'".escape($row["model"])."',";
		echo "os:'".escape($row["operating_system"])."'";
		echo "}";
	}
	echo "]}";
}

function tr_cust_find() {
	$search_string = strtoupper($_POST["c_str"]);
	$search_string = explode(" ", $search_string);
	if (count($search_string) == 2) {
		$result = mysql_query("SELECT * FROM customers WHERE (UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%' AND UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[1])."%') OR (UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[0])."%' OR UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%')") or die("{action:['error'],error:'MySQL error: ". escape(mysql_error())."'}");
	} else {
		$result = mysql_query("SELECT * FROM customers WHERE (UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%' OR UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[0])."%')") or die("{action:['error'],error:'MySQL error: ". escape(mysql_error())."'}");
	}

	echo "{action:['c_results'],results:[";
	while ($row = mysql_fetch_assoc($result)) {
		echo "{";
		echo "id:'".$row["id"]."',";
		echo "fname:'".escape($row["firstname"])."',";
		echo "lname:'".escape($row["lastname"])."',";
		echo "company:'".escape($row["company"])."',";
		echo "phome:'".escape(display_phone($row["phone_home"]))."',";
		echo "pcell:'".escape(display_phone($row["phone_cell"]))."'";
		echo "}";
	}
	echo "]}";
}

function own_device() {
	$result = mysql_query("SELECT i.device_id,d.inventory_item_number,d.categories__id,d.manufacturer,d.model,d.device_sn,d.in_store_location FROM issues i LEFT JOIN inventory_type_devices d ON i.device_id = d.id WHERE i.id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) die("{action:['error'],error:'Device not found.'}");
	$data = mysql_fetch_assoc($result);
	$did = $data["device_id"];
	if (!$did) die("{action:['error'],error:'Device not found.'}");
	$inventory_id = $data["inventory__id"];
	if ($inventory_id) die("{action:['error'],error:'Device is already in inventory.'}");

	$sql = "INSERT INTO inventory (upc,descr,purchase_price,cost,qty,is_taxable,item_type_lookup,name,is_qty) VALUES (";
	$sql .= "NULL,";
	$sql .= "'". mysql_real_escape_string($data["manufacturer"] ." ". $data["model"]) ."',";
	$sql .= "0,0,1,1,";
	if (intval($data["item_type_lookup"]) > 0) $sql .= intval($data["item_type_lookup"]) .",";
	else $sql .= "NULL,";
	$sql .= "'". mysql_real_escape_string($data["manufacturer"]." ".$data["model"]) ."',";
	$sql .= "0)";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	$iid = mysql_insert_id();

	inv_change_log($iid,false,1,1,false,"Added Product '".mysql_real_escape_string($data["manufacturer"] ." ". $data["model"])."'",false);

	$sql = "INSERT INTO inventory_items (inventory__id,notes,sn,in_store_location,item_table_lookup) VALUES (";
	$sql .= $iid .",";
	$sql .= "'',";
	if ($data["serial_number"]) $sql .= "'". mysql_real_escape_string($data["serial_number"]) ."',";
	else $sql .= "NULL,";
	$sql .= intval($data["in_store_location"]) .",";
	$sql .= $did .")";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	$iiid = mysql_insert_id();

	inv_change_log($iid,$iiid,2,false,intval($data["in_store_location"]),"Added First Item, S/N: '".(isset($data["serial_number"]) ? mysql_real_escape_string($data["serial_number"]) : "")."'",intval($_POST["f_status"]));

	mysql_query("UPDATE inventory_type_devices SET inventory_item_number = $iiid WHERE id = $did");
	echo "{action:['own_device'],id:'$iiid'}";
}

function edit_note() {
	global $USER;
	$result = mysql_query("SELECT users__id FROM user_notes WHERE id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) die("{action:['error'],error:'Note not found.'}");
	$data = mysql_fetch_assoc($result);
	if ($data["users__id"] != $USER["id"]) die("{action:['error'],error:'Cannot edit notes by other users.'}");
	$sql = "UPDATE user_notes SET note = '".mysql_real_escape_string($_POST["text"])."' WHERE id = ".intval($_POST["id"]);
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	echo "{action:['edit_note'],id:'".intval($_POST["id"])."',text:'".escape(str_replace("\n","\\n",$_POST["text"]))."'}";
}

function update_calendar() {
	$result = mysql_query("SELECT * FROM calendar WHERE id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) die("{action:['error'],error:'Calendar event not found.'}");
	if (isset($_POST["start"]) && preg_match('/\d{4}\-\d{2}\-\d{2}/',$_POST["start"])) {
		$start_date = $_POST["start"];
	} else {
		die("{action:['error'],error:'Invalid date entered.'}");
	}
	if (isset($_POST["start_time"]) && preg_match('/\d{2}\:\d{2}\:\d{2}/',$_POST["start_time"])) {
		$start_time = $_POST["start_time"];
	} else {
		die("{action:['error'],error:'Invalid start time entered.'}");
	}
	if (isset($_POST["end_time"]) && preg_match('/\d{2}\:\d{2}\:\d{2}/',$_POST["end_time"])) {
		$end_time = $_POST["end_time"];
	} else {
		die("{action:['error'],error:'Invalid end time entered.'}");
	}
	$cal_dt_p = explode("-",$start_date);
	$cal_dti = mktime(12,0,0,$cal_dt_p[1],$cal_dt_p[2],$cal_dt_p[0]);
	$cal_day = date("l j M Y",$cal_dti);

	$sql = "UPDATE calendar SET ";
	$sql .= "start = '$start_date',";
	$sql .= "start_time = '$start_time',";
	$sql .= "end_time = '$end_time',";
	$sql .= "users__id = ".intval($_POST["assigned_to"]);
	$sql .= " WHERE id = ".intval($_POST["id"]);
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	echo "{action:['calendar'],date:'$cal_day',start_time:'$start_time',end_time:'$end_time'}";
}

function flip_step() {
	global $USER;
	$result = mysql_query("SELECT service_steps FROM issues WHERE id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) die("{action:['error'],error:'Issue not found.'}");
	$data = mysql_fetch_assoc($result);
	if (!$data["service_steps"]) die("{action:['error'],error:'No steps on checklist.'}");
	$ss = explode("|",$data["service_steps"]);
	$new_steps = array();
	$json_steps = array();
	$and = "";
	foreach ($ss as $step) {
		if (!$step) continue;
		$kv = explode(":",$step);
		if (count($kv) < 3) continue;
		if ($kv[1] == $_POST["step"]) {
			if ($kv[2] == "1") $kv[2] = "0";
			else {
				$kv[2] = "1";
				$kv[3] = $USER["id"];
				$kv[4] = time();
				$and = ", last_step_ts = NOW()";
			}
		}
		if (!isset($kv[3]) || $kv[2] == "0") $kv[3] = "0";
		if (!isset($kv[4])) $kv[4] = "0";
		$new_steps[] = $kv[0].":".$kv[1].":".$kv[2].":".$kv[3].":".$kv[4];
		$json_steps[] = "{id:".$kv[1].",complete:".($kv[2] == "1" ? "true":"false")."}";
	}

	mysql_query("UPDATE issues SET service_steps = '".implode("|",$new_steps)."'$and WHERE id = ".intval($_POST["id"]));

	echo "{action:['steps'],steps:[".implode(",",$json_steps)."]}";
}

function flip_charger() {
	mysql_query("UPDATE issues SET has_charger = ! has_charger WHERE id = ".intval($_POST["id"]));
	$result = mysql_query("SELECT has_charger FROM issues WHERE id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) {
		die("{action:['error'],error:'Issue ID not found.'}");
	}
	$data = mysql_fetch_assoc($result);
	echo "{action:['charger'],charger:".intval($data["has_charger"])."}";
}

function add_do_price() {
	$do_price = number_format(floatval($_POST["dp"]),2);
	mysql_query("UPDATE issues SET do_price = '$do_price' WHERE id = ".intval($_POST["id"]));
	echo "{action:['do_price'],dp:'$do_price'}";
}

function edit_quote_price() {
	$quote_price = number_format(floatval($_POST["qp"]),2);
	mysql_query("UPDATE issues SET quote_price = '$quote_price' WHERE id = ".intval($_POST["id"]));
	echo "{action:['quote_price'],qp:'$quote_price'}";
}

function add_note() {
	global $USER;
	$sql = "INSERT INTO user_notes (for_table,for_key,note,users__id,note_ts) VALUES (";
	$sql .= "'issues',";
	$sql .= intval($_POST["id"]) .",";
	$sql .= "'". mysql_real_escape_string($_POST["text"]) ."',";
	$sql .= $USER["id"] .",NOW())";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	echo "{action:['note'],note:{user:'{$USER["firstname"]} {$USER["lastname"]}',date:'".date("D, j F Y")."',time:'".date("h:iA")."',text:'".escape(str_replace("\n","\\n",$_POST["text"]))."'}}";
}

function part_search() {
  global $USER;
	$str = mysql_real_escape_string($_POST["str"]);
	$cat = "";
	if (intval($_POST["category"]) > 0) $cat = " AND (item_type_lookup = ".intval($_POST["category"])." OR item_type_lookup IN (SELECT id FROM categories WHERE org_entities__id = {$USER['org_entities__id']} AND parent_id = ".intval($_POST["category"])."))";
	$sql = "SELECT COUNT(*) AS count FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND (upc LIKE '%$str%' OR descr LIKE '%$str%' OR name LIKE '%$str%')$cat";
	$data = mysql_fetch_assoc(mysql_query($sql));
	$sql = "SELECT *,(SELECT COUNT(*) FROM inventory_items ii WHERE ii.org_entities__id = {$USER['org_entities__id']} AND ii.inventory__id = i.id AND ii.varref_status < 6) AS iqty FROM inventory i JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND (i.upc LIKE '%$str%' OR i.descr LIKE '%$str%' OR i.name LIKE '%$str%')$cat LIMIT 10";
	$result = mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	if (!mysql_num_rows($result)) die("{action:['part_search'],results:'No matches.'}");
	echo "{action:['part_search'],results:'";
	$results = mysql_num_rows($result)." results";
	if ($data["count"] > mysql_num_rows($result)) $results .= " (". ($data["count"] - mysql_num_rows($result)) ." not shown)";
	$results .= "<br>";
	$results .= "<table width=\"100%\" border=\"0\" style=\"font-size:8pt;\">";
	while ($row = mysql_fetch_assoc($result)) {
		if ($row["is_qty"]) {
			$results .= "<tr><td>-</td><td width=\"250\" title=\"".str_replace('"',"\\\"",$row["descr"])."\">{$row["name"]}</td><td>{$row["category_name"]}</td><td>$".number_format($row["cost"],2)."</td><td width=\"50\"><input type=\"edit\" id=\"iss_part_qty_{$row["id"]}\" size=\"1\" style=\"width:30px;\" value=\"1\">/{$row["qty"]}</td><td width=\"80\"><a onClick=\"iss_add_part('{$row["id"]}');\" class=\"green ilink\">Add to Issue</a></td></tr>";
		} else {
			$results .= "<tr><td>-</td><td width=\"250\" title=\"".str_replace('"',"\\\"",$row["descr"])."\">{$row["name"]}</td><td>{$row["category_name"]}</td><td>$".number_format($row["cost"],2)."</td><td width=\"50\">{$row["iqty"]}</td><td width=\"80\"><a onClick=\"iss_list_items('{$row["id"]}');\" class=\"green ilink\">List Items</a></td></tr>";
		}
	}
	$results .= "</table>";
	echo escape($results) ."'}";
}

function part_item_list() {
	global $USER,$INVENTORY_STATUS;

	$id = intval($_POST["id"]);

	$sql = "SELECT i.id,i.name FROM inventory i WHERE i.id = $id";
	$result = mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	$PRODUCT = mysql_fetch_assoc($result);

	//$sql = "SELECT ii.id,ii.sn,ii.status,o.value FROM inventory_items ii LEFT JOIN optionvalues o ON ii.location = o.option_id WHERE ii.inventory_id = $id AND ii.status < 7 LIMIT 10";
	$sql = "SELECT ii.id,ii.sn,ii.varref_status FROM inventory_items ii LEFT JOIN inventory_locations il ON ii.in_store_location = il.id WHERE ii.inventory__id = $id AND ii.varref_status < 7 LIMIT 10";
	$result = mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	if (!mysql_num_rows($result)) die("{action:['part_search'],results:'No matches.'}");
	echo "{action:['part_search'],results:'";
	$results = mysql_num_rows($result)." results";
	$results .= "<br>";
	$results .= "<table width=\"100%\" border=\"0\" style=\"font-size:8pt;\">";
	$results .= "<tr><td colspan=\"5\" align=\"center\">Product {$PRODUCT["id"]}: {$PRODUCT["name"]}</td></tr>";
	while ($row = mysql_fetch_assoc($result)) {
		$results .= "<tr><td>{$row["id"]}</td><td width=\"60\">{$row["sn"]}</td><td>".$INVENTORY_STATUS[$row["varref_status"]]."</td><!-- td>".$row["value"]."</td --><td width=\"80\"><a onClick=\"iss_add_part('{$PRODUCT["id"]}','{$row["id"]}');\" class=\"green ilink\">Add to Issue</a></td></tr>";
	}
	$results .= "</table>";
	echo escape($results) ."'}";
}

function add_part() {
  global $USER;
	//$sql = "INSERT INTO issue_inv (issues__id,inventory__id,inventory_items__id,qty,`add`) VALUES (";
	$sql = "INSERT INTO issue_inv (issues__id,inventory__id,qty,`do_add`) VALUES (";
	$sql .= intval($_POST["id"]) .",";
	$sql .= intval($_POST["inv_id"]) .",";
	/* if (intval($_POST["inv_item_id"]) > 0) {
		$sql .= intval($_POST["inv_item_id"]) .",";
	} else {
		$sql .= "NULL,";
	} */
	$sql .= intval($_POST["qty"]) .",";
	$sql .= intval($_POST["add"]) .")";
	mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	$data = mysql_insert_id();
	$data2 = mysql_fetch_assoc(mysql_query("SELECT ii.id,ii.qty,ii.do_add,i.name,i.descr,i.cost FROM issue_inv ii JOIN inventory i ON ii.inventory__id = i.id WHERE ii.id = $data"));
	$data3 = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM issue_inv WHERE issue_inv.issues__id = ".intval($_POST["id"])));
	$data4 = mysql_fetch_assoc(mysql_query("SELECT IFNULL(SUM(ii.qty * i.cost),0) AS total FROM issue_inv ii JOIN inventory i ON ii.inventory__id = i.id WHERE ii.issues__id = ".intval($_POST["id"])." AND `do_add` = 1"));
	echo "{action:['part'],part_count:'{$data3["count"]}',part_total:'".(floatval($data4["total"]) > 0 ? "<b>$".number_format($data4["total"],2)."</b> Total" : "<i>All Parts Included</i>")."',part:{id:'{$data2["id"]}',name:'".escape($data2["name"])."',descr:'".escape($data2["descr"])."',cost:'".number_format($data2["cost"],2)."',qty:'".$data2["qty"]."',total:'";
	if ($data2["do_add"]) echo "$".number_format($data2["qty"] * $data2["cost"],2)."'}}";
	else echo "<i>Included</i>'}}";
}

function remove_part() {
	mysql_query("DELETE FROM issue_inv WHERE id = ".intval($_POST["part_id"])) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	$data = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM issue_inv WHERE issue_inv.issues__id = ".intval($_POST["id"])));
	$data2 = mysql_fetch_assoc(mysql_query("SELECT IFNULL(SUM(ii.qty * i.cost),0) AS total FROM issue_inv ii JOIN inventory i ON ii.inventory__id = i.id WHERE ii.issues__id = ".intval($_POST["id"])." AND `do_add` = 1"));
	echo "{action:['part_totals'],part_count:'{$data["count"]}',part_total:'".(floatval($data4["total"]) > 0 ? "<b>$".number_format($data4["total"],2)."</b> Total" : "<i>All Parts Included</i>")."'}";
}

function set_services() {
	$services = explode(",",$_POST["services"]);
	if ($_POST["services"] == '') $svc_string = "NULL";
	else {
		$svc_string = "':".implode(":",$services).":'";
	}
	mysql_query("UPDATE issues SET services = $svc_string WHERE id = ".intval($_POST["id"]));
	if ($_POST["services"] == '') $_POST["services"] = "0";

	$result = mysql_query("SELECT service_steps FROM issues WHERE id = ".intval($_POST["id"]));
	$data = mysql_fetch_assoc($result);
	if (!$data["service_steps"]) $data["service_steps"] = "";
	$ss = explode("|",$data["service_steps"]);
	$step_status = array();
	$step_by = array();
	$step_time = array();
	foreach ($ss as $step) {
		if (!$step) continue;
		$kv = explode(":",$step);
		if (count($kv) < 3) continue;
		$step_status[$kv[1]] = $kv[2];
		if (isset($kv[3]) && $kv[2] == "1") {
			$step_by[$kv[1]] = $kv[3];
			if (isset($kv[4])) $step_time[$kv[1]] = $kv[4];
		}
		else $step_by[$kv[1]] = "0";
	}

	$result = mysql_query("SELECT * FROM service_steps WHERE services__id IN (".mysql_real_escape_string($_POST["services"]).") ORDER BY services__id,`order`") or die("{action:['error'],error:'".escape(mysql_error()."::".$_POST["services"])."'}");
	$service_steps = array();
	$ss_save = array();
	while ($row = mysql_fetch_assoc($result)) {
		$service_steps[] = "{for_service:{$row["services__id"]},id:{$row["id"]},order:{$row["order"]},step:'".escape($row["step"])."',complete:".(isset($step_status[$row["id"]]) && $step_status[$row["id"]] == "1" ? "true":"false")."}";
		$ss_save[] = $row["services__id"] .":". $row["id"] .":". (isset($step_status[$row["id"]]) && $step_status[$row["id"]] == "1" ? "1":"0") .":". (isset($step_by[$row["id"]]) ? $step_by[$row["id"]] : "0") .":". (isset($step_time[$row["id"]]) ? $step_time[$row["id"]] : "0");
	}

	mysql_query("UPDATE issues SET service_steps = '".implode("|",$ss_save)."' WHERE id = ".intval($_POST["id"]));

	$result = mysql_query("SELECT * FROM services WHERE id IN (".mysql_real_escape_string($_POST["services"]).") ORDER BY name") or die("{action:['error'],error:'".escape(mysql_error()."::".$_POST["services"])."'}");
	$svc_total = 0;
	$services = array();
	echo "{action:['services'],services:[";
	while ($row = mysql_fetch_assoc($result)) {
		$svc_total += floatval($row["cost"]);
		$services[] = "{name:'".escape($row["name"])."',cost:'$".number_format(floatval($row["cost"]),2)."'}";
	}
	echo implode(",",$services);
	echo "],service_total:'$".number_format($svc_total,2)."',steps:[".implode(",",$service_steps)."]}";
}

function index() {
	global $USER,$SESSION;
	$opt = explode(",",$_POST["options"]);
	mysql_query("UPDATE sessions SET issue_filter = '".mysql_real_escape_string($opt[0].",".$opt[1].",".$opt[2].",".$opt[3].",".$opt[4].",".$opt[5].",".$opt[6].",".$opt[7].",".$opt[8])."' WHERE id = '".$SESSION["id"]."'");
	$where_clause = " WHERE i.org_entities__id = {$USER['org_entities__id']} AND i.is_deleted = 0";
	if (isset($opt[0]) && $opt[0] != "0") $where_clause .= " AND i.varref_issue_type = ".intval($opt[0]);
	if (isset($opt[1]) && $opt[1] != "0") $where_clause .= " AND i.varref_status = ".intval($opt[1]);
	if (isset($opt[2]) && $opt[2] != "0") $where_clause .= " AND i.users__id__assigned = ".intval($opt[2]);
	if (isset($opt[3]) && $opt[3] != "0") $where_clause .= " AND i.users__id__intake = ".intval($opt[3]);
	if (isset($opt[4]) && $opt[4] != "0") $where_clause .= " AND d.in_store_location = ".intval($opt[4]);
	if (isset($opt[5])) $where_clause .= " AND i.is_resolved = ".intval($opt[5]);
	$order_by = array();
	if (isset($opt[6])) switch ($opt[6]) {
		case "0":
			$order_by[] = "i.varref_status";
			break;
		case "1":
			$order_by[] = "i.intake_ts DESC";
			break;
		case "2":
			$order_by[] = "d.categories__id";
			break;
		case "3":
			$order_by[] = "i.users__id__assigned";
			break;
	}
	if (isset($opt[7])) switch ($opt[7]) {
		case "0":
			$order_by[] = "i.varref_status";
			break;
		case "1":
			$order_by[] = "i.intake_ts DESC";
			break;
		case "2":
			$order_by[] = "d.categories__id";
			break;
		case "3":
			$order_by[] = "i.users__id__assigned";
			break;
	}
	if (isset($opt[8]) && $opt[8] == "1") {
		$where_clause .= " AND i.varref_status NOT IN (9,10)";
	}
	if (count($order_by) > 0) $order_clause = " ORDER BY ".join(",",$order_by);
	else $order_clause = "";
	$sql = "SELECT COUNT(*) AS count FROM issues i LEFT JOIN inventory_type_devices d ON i.device_id = d.id".$where_clause;
	$result = mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	$data = mysql_fetch_assoc($result);
	$total_issues = $data["count"];
	$total_pages = ceil($total_issues / 25);
	if ($total_pages == 0) $total_pages = 1;

	$page = intval($opt[9]);
	if ($page < 1) $page = 1;
	if ($page > $total_pages) $page = $total_pages;

	$issue_changes = " LEFT JOIN (SELECT * FROM (SELECT TIMESTAMPDIFF(DAY,ic.tou,NOW()) AS ictou, ic.varref_status AS icstat, ic.issues__id AS icid FROM issue_changes ic WHERE ic.org_entities__id = {$USER['org_entities__id']} ORDER BY ic.tou DESC) AS t1 GROUP BY t1.icid) t2 ON i.id = t2.icid";
	$sql = "SELECT *,i.id as id FROM issues i LEFT JOIN customers c ON i.customers__id = c.id LEFT JOIN inventory_type_devices d ON i.device_id = d.id LEFT JOIN categories ca ON d.categories__id = ca.id LEFT JOIN inventory_locations il ON d.in_store_location = il.id".$issue_changes.$where_clause.$order_clause." LIMIT ".(($page - 1) * 25).",25";
	$result = mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	echo "{action:['index'],count:$total_issues,page:$page,pages:$total_pages,issues:[";
	$issues = array();
	while ($row = mysql_fetch_assoc($result)) {
			$issue = "{";
			$issue .= "id:'".$row["id"]."',";
			if ($row["customers__id"]) $issue .= "customer:{id:{$row["customers__id"]},name:'".escape($row["firstname"])." ".escape($row["lastname"])."'},";
			$issue .= "intake:'". date("D, j M Y",strtotime($row["intake_ts"])) ."',";
			$issue .= "device:'". ($row["category_name"] ? escape($row["category_name"]) : "<i>On-Site</i>") ."',";
			$issue .= "assigned_to:".intval($row["users__id__assigned"]).",";
			$issue .= "location:'". ($row["title"] ? escape($row["title"]) : "<i>On-Site</i>") ."',";
			$issue .= "services:'". $row["services"] ."',";
			$issue .= "status:{$row["varref_status"]},";
			if ($row["warranty_status"]) $issue .= "wstatus:{$row["warranty_status"]},";
			$issue .= "red:";
			if (isset($row["ictou"]) && $row["ictou"] > 30 && $row["icstat"] >= 9) $issue .= "1,";
			else $issue .= "0,";
			$issue .= "invoice_id:".intval($row["invoices__id"]).",";
			$issue .= "check_notes:". ($row["check_notes"] ? "'1'":"'0'");
			$issue .= "}";
			$issues[] = $issue;
	}
	echo join(",",$issues) ."]}";
}

function update_status() {
	global $USER,$STATUS,$ONSITE_STATUS_OPTIONS,$ONSITE_STATUS_CHG,$STATUS_CHG;
	$result = mysql_query("SELECT * FROM issues i LEFT JOIN inventory_type_devices d ON i.device_id = d.id WHERE i.id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) die("{action:['error'],error:'Issue does not exist.'}");
	$ISSUE = mysql_fetch_assoc($result);

	$issue_changes = array();

	if (intval($_POST["status"]) != intval($ISSUE["varref_status"])) {
		if (isset($STATUS[intval($_POST["status"])])) {
			$issue_changes[] = "Status changed to ".$STATUS[intval($_POST["status"])];
			$onsite = false;
			if ($ISSUE["varref_issue_type"] == 2 || $ISSUE["varref_issue_type"] == 3) {
				$onsite = true;
			}
			$statusId = array();
			$statusName = array();
			foreach ($STATUS as $id => $status) {
				if ($id == "0") continue;
				if ($onsite && !in_array($id,$ONSITE_STATUS_OPTIONS)) continue;
				if ($onsite && !in_array($id,$ONSITE_STATUS_CHG[intval($_POST["status"])])) continue;
				if (!$onsite && !in_array($id,$STATUS_CHG[intval($_POST["status"])])) continue;
				$statusId[] = $id;
				$statusName[] = $status;
			}
			if (intval($_POST["status"]) == 3) {
				$_POST["assigned_to"] = $USER["id"];
			}
		} else {
			$_POST["status"] = $ISSUE["varref_status"];
		}
	}
	if (isset($_POST["wstatus"]) && intval($_POST["wstatus"]) != intval($ISSUE["warranty_status"])) {
		if (isset($STATUS[intval($_POST["wstatus"])])) {
			$issue_changes[] = "Warranty Status changed to ".$STATUS[intval($_POST["wstatus"])];
		} else {
			$_POST["wstatus"] = $ISSUE["warranty_status"];
		}
	}
	if (intval($_POST["assigned_to"]) != $ISSUE["users__id__assigned"]) {
		$USERS = array();
		$result = mysql_query("SELECT id,firstname,lastname FROM users");
		while ($row = mysql_fetch_assoc($result)) {
			$USERS[$row["id"]] = $row["firstname"] ." ". $row["lastname"];
		}
		if (isset($USERS[intval($_POST["assigned_to"])])) {
			$issue_changes[] = "Assigned to ".$USERS[intval($_POST["assigned_to"])];
		} else {
			$issue_changes[] = "Assigned to Nobody";
		}
	}
	if (intval($_POST["location"]) > 0 && intval($_POST["location"]) != $ISSUE["in_store_location"]) {
		$result = mysql_query("SELECT title FROM inventory_locations WHERE id = ".intval($_POST["location"])." AND id>0");
		if (mysql_num_rows($result)) {
			$location = mysql_fetch_assoc($result);
			$issue_changes[] = "Moved to ".	$location["title"];
		} else {
			$_POST["location"] = $ISSUE["org_entities__id"];
		}
	}
	if (floatval($_POST["quote_price"]) > 0 && floatval($_POST["quote_price"]) != floatval($ISSUE["quote_price"])) {
		$issue_changes[] = "Quote Price: $". number_format(floatval($_POST["quote_price"]),2);
	}
	if (floatval($_POST["do_price"]) > 0 && floatval($_POST["do_price"]) != floatval($ISSUE["do_price"])) {
		$issue_changes[] = "Do It Price: $". number_format(floatval($_POST["do_price"]),2);
	}
	if ($_POST["diagnosis"] != "" && $_POST["diagnosis"] != $ISSUE["diagnosis"]) {
		$issue_changes[] = "Diagnosis: '".$_POST["diagnosis"]."'";
	}
	if ($_POST["finalsummary"] != "" && $_POST["finalsummary"] != $ISSUE["final_summary"]) {
		$issue_changes[] = "Final Summary: '".$_POST["finalsummary"]."'";
	}
	if (intval($_POST["check_notes"]) > 0 && !$ISSUE["check_notes"]) {
		$issue_changes[] = "Important notes flagged";
	}
	if (intval($_POST["check_notes"]) == 0 && $ISSUE["check_notes"]) {
		$issue_changes[] = "Important notes flag removed";
	}
	if (floatval($_POST["hours"]) > 0) {
		$issue_changes[] = "Hours Worked: ".floatval($_POST["hours"]);
		$result = mysql_query("SELECT id FROM customer_accounts a JOIN customers c ON c.id = a.customers__id JOIN issues i ON i.customers__id = c.id WHERE i.id = ".intval($_POST["id"]));
		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			$account_id = $data["id"];
		} else {
			$account_id = "NULL";
		}
		$sql = "INSERT INTO issue_labor (customer_accounts__id,issues__id,amount,users__id,ts) VALUES (";
		$sql .= $account_id .",";
		$sql .= intval($_POST["id"]) .",";
		$sql .= "'". floatval($_POST["hours"]) ."',";
		$sql .= $USER["id"] .",";
		$sql .= "NOW()";
		//$sql .= "'". floatval($_POST["rate"]) ."',";
		//$sql .= (isset($_POST["travel"]) && $_POST["travel"] == "1" ? "1":"0") .")";
		mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
		$labor_id = intval(mysql_insert_id());

		$result = mysql_query("SELECT * FROM issue_labor l LEFT JOIN users u ON l.users__id = u.id WHERE l.id = $labor_id");
		$LABOR = mysql_fetch_assoc($result);
	}

	if (count($issue_changes) > 0) {
		$sql = "UPDATE issues SET ";
		if (intval($_POST["assigned_to"]) > 0) $sql .= "users__id__assigned = ".intval($_POST["assigned_to"]).",";
		else $sql .= "users__id__assigned = NULL,";
		if (floatval($_POST["quote_price"]) > 0) $sql .= "quote_price = '".floatval($_POST["quote_price"])."',";
		if (floatval($_POST["do_price"]) > 0) $sql .= "do_price = '".floatval($_POST["do_price"])."',";
		if ($_POST["diagnosis"] != "") $sql .= "diagnosis = '".mysql_real_escape_string($_POST["diagnosis"])."',";
		if ($_POST["finalsummary"] != "") $sql .= "final_summary = '".mysql_real_escape_string($_POST["finalsummary"])."',";
		if (intval($_POST["status"]) != $ISSUE["varref_status"]) $sql .= "last_status_chg = NOW(),";
		$sql .= "varref_status = ".intval($_POST["status"]) .",";
		if (intval($_POST["check_notes"]) > 0) $sql .= "check_notes = 1";
		else $sql .= "check_notes = 0";
		if (isset($_POST["wstatus"]) && intval($_POST["wstatus"]) > 0) $sql .= ",warranty_status = ".intval($_POST["wstatus"]);
		$sql .= " WHERE id = ".intval($_POST["id"]);
		mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");

		if ($ISSUE["device_id"] && intval($_POST["location"]) > 0) {
				$sql = "UPDATE inventory_type_devices SET in_store_location = ".intval($_POST["location"])." WHERE id = ".intval($ISSUE["device_id"]);
				mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
		}

		$sql = "INSERT INTO issue_changes (issues__id,description,varref_status,users__id,org_entities__id) VALUES (";
		$sql .= intval($_POST["id"]).",";
		$sql .= "'". mysql_real_escape_string(implode(", ",$issue_changes)) ."',";
		$sql .= intval($_POST["status"]).",";
		$sql .= $USER["id"] .",{$USER['org_entities__id']})";
		mysql_query($sql) or die("{action:['error'],error:'".escape(mysql_error()."::".$sql)."'}");
	}

	echo "{action:['status'".(isset($statusId) ? ",'soptions'":"")."],status:".intval($_POST["status"]).",status_text:'".$STATUS[intval($_POST["status"])]."',assigned_to:".intval($_POST["assigned_to"]);
	if (isset($_POST["wstatus"]) && intval($_POST["wstatus"]) > 0) echo ",wstatus:".intval($_POST["wstatus"]).",wstatus_text:'".$STATUS[intval($_POST["wstatus"])]."'";
	if (floatval($_POST["quote_price"]) > 0) echo ",quote_price:'".number_format(floatval($_POST["quote_price"]),2)."'";
	if (floatval($_POST["do_price"]) > 0) echo ",do_price:'".number_format(floatval($_POST["do_price"]),2)."'";
	if ($_POST["diagnosis"] != "") echo ",diagnosis:'".escape($_POST["diagnosis"])."'";
	if ($_POST["finalsummary"] != "") echo ",final_summary:'".escape($_POST["finalsummary"])."'";
	if (isset($location)) echo ",location:'".$location["title"]."'";
	if (isset($statusId)) {
		echo ",soptions:{";
		$soptions = array();
		foreach ($statusId as $x => $id) {
			$soptions[] = "$id:'{$statusName[$x]}'";
		}
		echo join(",",$soptions);
		echo "}";
	}
	if (isset($LABOR)) {
		echo ",labor:{";
		echo "ts:'".$LABOR["ts"]."',";
		echo "hours:'".round($LABOR["amount"],2)."',";
//		echo "rate:'".round($LABOR["rate"],2)."',";
//		echo "travel:'".($LABOR["travel"] ? "Yes":"No")."',";
		echo "charge:'". round($LABOR["amount"],2);// * round($LABOR["rate"],2) + ($LABOR["travel"] ? 50:0))."',";
		echo "tech:'".$LABOR["username"]."'";
		echo "}";
	}
	echo ",check_notes:";
	if (intval($_POST["check_notes"]) > 0) echo "'1'";
	else echo "'0'";
	echo "}";
}

function inv_change_log($inv_id,$inv_item_id,$code,$qty,$location,$descr,$status,$reason = null) {
	global $USER;
	$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,org_entities__id,descr,varref_status,reason,org_entities__id) VALUES (";
	$sql .= intval($inv_id) .",";
	if ($inv_item_id) $sql .= intval($inv_item_id) .",";
	else $sql .= "NULL,";
	$sql .= $USER["id"] .",";
	$sql .= intval($code) .",";
	if ($qty) $sql .= intval($qty) .",";
	else $sql .= "NULL,";
	if ($location) $sql .= intval($location) .",";
	else $sql .= "NULL,";
	if ($descr) $sql .= "'". mysql_real_escape_string($descr) ."',";
	else $sql .= "NULL,";
	if ($status) $sql .= intval($status) .",";
	else $sql .= "NULL,";
	if ($reason) $sql .= "'". mysql_real_escape_string($reason) ."')";
	else $sql .= "NULL,{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
	return mysql_insert_id();
}

?>
