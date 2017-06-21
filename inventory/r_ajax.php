<?php

if (!isset($_POST["cmd"])) exit;

require_once("../init.php");
require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) { exit; }

function ds_escape($str) {
	return str_replace('"','\"',$str);
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
//$result = mysql_query("SELECT * FROM locations");
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
	case "cats":
		cats();
		break;
	case "search":
		search();
		break;
	case "list":
		list_items();
		break;
	case "request_prod":
		request_prod();
		break;
	case "request_item":
		request_item();
		break;
	case "list_matches":
		list_matches();
		break;
	case "do_request":
		do_request();
		break;
	default:
		alert_die("Invalid AJAX command.");
}

function do_request() {
	global $DB,$LOCATIONS,$USER;

	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	$DB2 = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"],true);
// 	mysql_select_db($loc["db_db"],$DB2);

	$result = mysql_query("SELECT * FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE org_entities__id = {$loc['id']} AND i.id = ".intval($_POST["id"]));//,$DB2);
	if (!mysql_num_rows($result)) alert_die("Product not found.");
	$PRODUCT = mysql_fetch_assoc($result);

	$cat = "NULL";
	$result = mysql_query("SELECT id FROM categories c WHERE c.category_name = '". mysql_real_escape_string($PRODUCT["category_name"]) ."' AND c.category_set = 'inventory' LIMIT 1");//,$DB);
	if (mysql_num_rows($result)) {
		$cinfo = mysql_fetch_assoc($result);
		$cat = $cinfo["id"];
	}

	if (intval($_POST["lid"]) == 0) {
		//TODO: Check for duplicate UPC

		$sql = "INSERT INTO inventory (upc,descr,purchase_price,cost,is_taxable,item_type_lookup,name,qty,is_qty,do_notify_low_qty,low_qty,org_entities__id) VALUES (";
		if ($PRODUCT["upc"]) {
			$sql .= "'". mysql_real_escape_string($PRODUCT["upc"]) ."',";
		} else {
			$sql .= "NULL,";
		}
		$sql .= "'". mysql_real_escape_string($PRODUCT["descr"]) ."',";
		$sql .= "'". floatval($PRODUCT["purchase_price"]) ."',";
		$sql .= "'". floatval($PRODUCT["cost"]) ."',";
		$sql .= ($PRODUCT["is_taxable"] ? "1":"0") .",";
		$sql .= intval($PRODUCT["item_type_lookup"]) .",";
		$sql .= "'". mysql_real_escape_string($PRODUCT["name"]) ."',";
		$sql .= "0,";
		if (intval($_POST["iid"]) > 0) {
			$sql .= "0,";
		} else {
			$sql .= "1,";
		}
		$sql .= "0,0,{$USER['org_entities__id']})";
    //mysql_query($sql,$DB) or alert_die(mysql_error($DB) ."::". $sql);
		mysql_query($sql) or alert_die(mysql_error() ."::". $sql);
		//$_POST["lid"] = mysql_insert_id($DB);
    $_POST["lid"] = mysql_insert_id();
	}

	if (intval($_POST["iid"]) > 0) {
		$sql = "INSERT INTO inventory_items (inventory__id,notes,sn,issues__id,varref_status,in_store_location,is_in_transit,org_entities__id) VALUES (";
		$sql .= intval($_POST["lid"]) .",";
		$sql .= "'',";
		$sql .= "'',";
		$sql .= "NULL,";
		$sql .= "1,";
		$sql .= "NULL,";
		$sql .= "1,{$USER['org_entities__id']})";
		//mysql_query($sql,$DB) or alert_die(mysql_error() ."::". $sql);
    mysql_query($sql) or alert_die(mysql_error() ."::". $sql);
    //$IID = mysql_insert_id($DB);
		$IID = mysql_insert_id();
	}

	$sql = "INSERT INTO inventory_requests (users__id,org_entities__id__orig,inventory__id__dest,inventory_item_number_dest,inventory__id__orig,inventory_item_number_orig,qty,varref_status,org_entities__id__dest) VALUES (";
	$sql .= $USER["users__id"] .",";
	$sql .= "'". mysql_real_escape_string($_POST["store"]) ."',";

	$sql .= intval($_POST["id"]) .",";
	if (intval($_POST["iid"]) > 0) {
		$sql .= intval($_POST["iid"]) .",";
	} else {
		$sql .= "NULL,";
	}

	$sql .= intval($_POST["lid"]) .",";
	if (isset($IID)) {
		$sql .= $IID .",";
	} else {
		$sql .= "NULL,";
	}

	$sql .= intval($_POST["qty"]) .",";
	$sql .= "1,{$USER['org_entities__id']})";
	mysql_query($sql,$DB) or alert_die(mysql_error($DB) ."::". $sql);

	echo '{"action":"request_done","message":"'. intval($_POST["qty"]) .' units of local product ID '. intval($_POST["lid"]) .' have been requested from '.$loc["name"].'."}';
}

function list_matches() {
	//global $DB,$LOCATIONS;
  global $LOCATIONS;

	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	$DB2 = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"],true);
// 	mysql_select_db($loc["db_db"],$DB2);

	// Get remote product info
	$result = mysql_query("SELECT * FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE org_entities__id = {$loc['id']} AND i.id = ".intval($_POST["id"]));//,$DB2);
	if (!mysql_num_rows($result)) alert_die("Product not found.");
	$PRODUCT = mysql_fetch_assoc($result);

	$where = "";
	if (intval($_POST["iid"]) > 0) {
		$where = "i.is_qty = 0";
	} else {
		$where = "i.is_qty = 1";
	}

	// List local inventory of that type(qty/itemized)
	$result = mysql_query("SELECT *,(SELECT IFNULL(COUNT(*),0) FROM inventory_items ii WHERE ii.org_entities__id = {$USER['org_entities__id']} AND ii.inventory__id = i.id) AS iqty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND $where");//,$DB);

	$z = "";
	echo '{"action":"list_matches","name":"'.ds_escape($loc["title"]).'","sn":"'.ds_escape($_POST["store"]).'","rqty":"'.intval($_POST["qty"]).'",';
	echo '"nil_button":"'. ds_escape(alink_onclick("Select","#","matchSelect('0');")) .'",';
	echo '"frn_item":{';
	echo '"id":"'. $PRODUCT["id"] .'",';
	echo '"name":"'. $PRODUCT["name"] .'",';
	echo '"upc":"'. $PRODUCT["upc"] .'",';
	echo '"descr":"'. $PRODUCT["descr"] .'",';
	echo '"cat":"'. $PRODUCT["category_name"] .'",';
	echo '"cost":"'. number_format($PRODUCT["purchase_price"],2) .'",';
	echo '"retail":"'. number_format($PRODUCT["cost"],2) .'",';
	echo '"taxable":"'. ($PRODUCT["is_taxable"] ? "Yes":"No") .'"';
	echo '},"content":"';
	while ($row = mysql_fetch_assoc($result)) {
		$z .= "<tr align=\"center\" style=\"font-size:12px;\" title=\"".ds_escape($row["descr"])."\">";
		$z .= '<td>'. $row["inventory__id"] .'</td>';
		$z .= '<td>'. $row["upc"] .'</td>';
		$z .= '<td align="left">'. $row["name"] .'</td>';
		$z .= '<td>'. ds_escape($row["category_name"]) .'</td>';
		if ($row["is_qty"]) {
			$z .= '<td>'. $row["qty"] .'</td>';
		} else {
			$z .= '<td>'. $row["iqty"] .'</td>';
		}
		$z .= '<td>'. number_format(floatval($row["purchase_price"]),2) .'</td>';
		$z .= '<td>'. number_format(floatval($row["cost"]),2)  .'</td>';
		$z .= '<td>'. ($row["is_taxable"] ? "Yes":"No") .'</td>';
		$z .= '<td>'. alink_onclick("Select","#","matchSelect({$row["inventory__id"]});").'</td>';
		$z .= "</tr>";
	}
	echo ds_escape($z);
	echo '"}';
}

function request_prod() {
	global $USER;
	$sql = "INSERT INTO inventory_requests (users__id,org_entities__id__orig,inventory__id__dest,inventory_item_number_dest,qty,varref_status,org_entities__id__dest) VALUES (";
	$sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string($_POST["store"]) ."',";
	$sql .= intval($_POST["id"]) .",";
	$sql .= "NULL,";
	$sql .= intval($_POST["qty"]) .",";
	$sql .= "1,{$USER['org_entities__id']})";
	mysql_query($sql) or alert_die(mysql_error() ."::". $sql);

	alert_die(intval($_POST["qty"])." units successfully requested.");
}

function request_item() {
	global $USER;
	$sql = "INSERT INTO inventory_requests (users__id,org_entities__id__orig,inventory__id__dest,inventory_item_number_dest,qty,varref_status,org_entities__id__dest) VALUES (";
	$sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string($_POST["store"]) ."',";
	$sql .= intval($_POST["id"]) .",";
	$sql .= intval($_POST["iid"]) .",";
	$sql .= "1,1,{$USER['org_entities__id']})";
	mysql_query($sql) or alert_die(mysql_error() ."::". $sql);

	alert_die("Item successfully requested.");
}

function cats() {
	global $DB,$LOCATIONS;
	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	mysql_close($DB);
// 	$DB = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"]);
// 	mysql_select_db($loc["db_db"],$DB);

	echo '{"action":"cats","name":"'.ds_escape($loc["title"]).'","id":"'.ds_escape($_POST["store"]).'","cats":[';

	$cats = array();
	$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NULL ORDER BY category_name");
	$result2 = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NOT NULL ORDER BY category_name");
	$SUBS = array();
	while ($row2 = mysql_fetch_assoc($result2)) {
		if (!isset($SUBS[$row2["parent_id"]])) $SUBS[$row2["parent_id"]] = array();
		$SUBS[$row2["parent_id"]][] = $row2;
	}
	while ($row = mysql_fetch_assoc($result)) {
		$cats[] = '{"id":"'.$row["id"].'","name":"'.ds_escape($row["category_name"]).'"}';
		if (isset($SUBS[$row["id"]])) {
			foreach($SUBS[$row["id"]] as $row2) {
				$cats[] = '{"id":"'.$row2["id"].'","name":"- '.ds_escape($row2["category_name"]).'"}';
			}
		}
	}

	echo join(",",$cats);

	echo ']}';
}

function search() {
	global $DB,$LOCATIONS;
	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

	// Tally existing requests
	$REQUESTS = array();
	$result = mysql_query("SELECT SUM(qty) AS reqd,inventory__id__dest FROM inventory_requests WHERE org_entities__id__dest = '".mysql_real_escape_string($loc["id"])."' GROUP BY inventory__id__dest");
	while ($row = mysql_fetch_assoc($result)) {
		if (!isset($REQUESTS[$row["inventory__id__dest"]])) $REQUESTS[$row["inventory__id__dest"]] = 0;
		$REQUESTS[$row["inventory__id__dest"]] += $row["reqd"];
	}

// 	mysql_close($DB);
// 	$DB = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"]);
// 	mysql_select_db($loc["db_db"],$DB);

	$cats = array(intval($_POST["cat"]));
	$catstring = "";
	if (intval($_POST["cat"]) != 0) {
		$result = mysql_query("SELECT id FROM categories WHERE parent_id = ".intval($_POST["cat"]));
		if (mysql_num_rows($result)) {
			while ($row = mysql_fetch_assoc($result)) {
				//$cats[] = $row["option_id"];
        $cats[] = $row["id"];
			}
		}
		$catstring = " AND i.item_type_lookup IN (".implode(",",$cats).")";
	}

	$sstr = mysql_real_escape_string($_POST["str"]);

	$result = mysql_query("SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.category_name,i.name,i.qty,i.is_qty,(SELECT IFNULL(COUNT(*),0) FROM inventory_items ii WHERE ii.org_entities__id = {$loc['id']} AND ii.inventory__id = i.id) AS iqty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$loc['id']} AND (i.name LIKE '%$sstr%' OR i.descr LIKE '%$sstr%')$catstring");
	if (!mysql_num_rows($result)) {
		alert_die("No products found.");
	}

	$items = array();

	$bgcolors = array("#FFFFFF","#AAAAAA");
	$c = 0;
	$content = "<div class=\"block bolder\" style=\"margin-top:-10px;font-size:18px;\">". mysql_num_rows($result) ." Results</div>\n";
	$content .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
	$content .= " <tr align=\"center\" class=\"heading\">\n";
	$content .= "  <td>ID</td><td>UPC</td><td>Name (hover for Description)</td><td>Type</td><td>QTY</td><td>Cost</td><td>Retail</td><td>Taxable</td><td>Requested</td>\n";
	$content .= " </tr>\n";

	while ($row = mysql_fetch_assoc($result)) {
		$qty = ($row["is_qty"] ? $row["qty"] : $row["iqty"]);
		if (!$row["is_qty"] && $qty < 1) continue;
		$content .= " <tr align=\"center\" style=\"font-size: 9pt;background-color:{$bgcolors[$c]};\" title=\"". ($row["descr"] ? ds_escape($row["descr"]) : "(No Description)") ."\">\n";
		$content .= "  <td>".$row["id"]."</td>\n";
		$content .= "  <td>".$row["upc"]."</td>\n";
		$content .= "  <td align=\"left\">". highlight($row["name"],$_POST["str"]) ."</td>\n";
		$content .= "  <td>".(isset($row["category_name"]) ? $row["category_name"] : "<i>None</i>")."</td>\n";
		$content .= "  <td>".($qty < 1 ? "<font color=red><b>SOLD OUT</b></font>" : $qty)."</td>\n";
		$content .= "  <td>$".number_format($row["purchase_price"],2)."</td>\n";
		$content .= "  <td>$".number_format($row["cost"],2)."</td>\n";
		$content .= "  <td>".($row["is_taxable"] ? "Yes" : "No")."</td>\n";
		$content .= "  <td>".(isset($REQUESTS[$row["id"]]) ? $REQUESTS[$row["id"]] : "0")."</td>\n";
		$content .= " </tr>\n";
		$content .= " <tr align=\"center\" style=\"background-color:{$bgcolors[$c]};\" title=\"". ($row["descr"] ? ds_escape($row["descr"]) : "(No Description)") ."\"><td colspan=\"9\">";

		if ($row["is_qty"] && $qty > 0) $content .= alink_onclick("Request","#","reqInv('{$row["id"]}','{$qty}','".s_escape($row["name"])."');");
		if (!$row["is_qty"] && $qty > 0) $content .= alink_onclick("List Items","#","listItems('{$row["id"]}');");

		$content .= "</td></tr>\n";

		$c++;
		if ($c == 2) $c = 0;
	}

	$content .= "</table>\n";

	echo '{"action":"results","results":'.json_encode($content).'}';
}

function list_items() {
	global $DB,$LOCATIONS,$INVENTORY_STATUS;
	if (!isset($LOCATIONS[$_POST["store"]])) alert_die("Invalid store selection: {$_POST["store"]} ".count($LOCATIONS));
	$loc = $LOCATIONS[$_POST["store"]];

// 	mysql_close($DB);
// 	$DB = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"]);
// 	mysql_select_db($loc["db_db"],$DB);

	$result = mysql_query("SELECT * FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$loc['id']} AND i.id = ".intval($_POST["id"]));
	if (!mysql_num_rows($result)) alert_die("Invalid product ID.");
	$INV = mysql_fetch_assoc($result);
	//$result = mysql_query("SELECT * FROM inventory_items ii LEFT JOIN optionvalues o ON ii.location = o.option_id WHERE ii.inventory_id = ".intval($_POST["id"]) ." AND ii.in_transit != 1");
  $result = mysql_query("SELECT ii.*,oe.title FROM inventory_items ii LEFT JOIN org_entities ON ii.org_entities__id = oe.id WHERE ii.org_entities__id = {$loc['id']} AND ii.inventory__id = ".intval($_POST["id"]) ." AND ii.is_in_transit != 1");
	if (!mysql_num_rows($result)) alert_die("That product is not in stock.");

	echo '{"action":"list","id":"'.$INV["id"].'","upc":"'.ds_escape($INV["upc"]).'","name":"'.ds_escape($INV["name"]).'",';
	echo '"descr":"'.ds_escape($INV["descr"]).'","category":"'.ds_escape($INV["category_name"]).'","qty":"'.mysql_num_rows($result).'",';
	echo '"cost":"$'.number_format($INV["purchase_price"],2).'","retail":"$'.number_format($INV["cost"],2).'","taxable":"'.($INV["is_taxable"] ? "Yes" : "No").'",';
	echo '"back":"'.ds_escape(alink_onclick("Back to Product List","#","goBack();")).'",';
	echo '"items":[';
	$items = array();
	while ($row = mysql_fetch_assoc($result)) {
		if (!isset($INVENTORY_STATUS[intval($row["varref_status"])])) $row["varref_status"] = 0;
		$item = "{";
		$item .= '"id":"'.$row["id"].'",';
		$item .= '"notes":"'.sanitize($row["notes"]).'",';
		$item .= '"sn":"'.sanitize($row["sn"]).'",';
		$item .= '"status":"'.sanitize($INVENTORY_STATUS[intval($row["varref_status"])]).'",';
		$item .= '"location":"'.sanitize($row["in_store_location"]).'",';
		$item .= '"link":"'.ds_escape(alink_onclick("Request","#","reqItem('{$INV["id"]}','{$row["id"]}');")).'"';
		$item .= "}";
		$items[] = $item;
	}
	echo join(",",$items);
	echo ']}';
}

function highlight($haystack, $needle) {
	if (strlen($haystack) < 1 || strlen($needle) < 1) {
		return $haystack;
	}
	if (strlen($haystack) == strlen($needle) && stristr($haystack,$needle)) {
		return "<font style=\"background-color:yellow\">". $haystack ."</font>";
	}
	preg_match_all("/$needle+/i", $haystack, $match);
	$exploded = preg_split("/$needle+/i",$haystack);
	$replaced = "";
	foreach($exploded as $e)
		foreach($match as $m)
		if($e!=$exploded[count($exploded)-1]) {
		$replaced .= $e . "<font style=\"background-color:yellow\">" . $m[0] . "</font>";
	} else {$replaced .= $e;
	}
	return $replaced;
}

function alert_die($err) {
	die('{"action":"alert","alrt":"'.ds_escape($err).'"}');
}
