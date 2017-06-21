<?php

if (!isset($USER)) {
	header("Location: login.php"); exit;
}

// get default org tax rate
$result = mysql_query("
SELECT
  oe.tax_rate
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Organization'
  AND tax_rate IS NOT NULL
LIMIT 1
");
if (mysql_num_rows($result)) {
  $data = mysql_fetch_assoc($result);
  $tax_rate = floatval($data["tax_rate"]);
}
// try to get store-specific tax rate
$result = mysql_query("SELECT tax_rate FROM org_entities WHERE id={$USER['org_entities__id']} AND tax_rate IS NOT NULL LIMIT 1");
if (mysql_num_rows($result)) {
  $data = mysql_fetch_assoc($result);
  $tax_rate = floatval($data["tax_rate"]);
}
// hack fallback for now
if (!isset($tax_rate)) $tax_rate = floatval("0.08");

if (isset($_GET["do"])) {
	switch ($_GET["do"]) {
		case "scan":
			$IID = intval($_POST["id"]);
			$result = mysql_query("SELECT * FROM invoices WHERE id = $IID");
			if (!mysql_num_rows($result)) {
				$product = decodeUpc($iid);
				if (!$product) {
					$RESPONSE = "Invalid invoice ID or barcode.";
					include "views/index.php";
				} else {
					if ($product->type != "invoice") {
						$RESPONSE = "That barcode is not an in-store invoice.";
						include "views/index.php";
					} else {
						$IID = $product->id;
						include "views/view.php";
					}
				}
			} else {
				include "views/view.php";
			}
			break;
		case "merge":
			$IID = intval($_GET["id"]);
			merge_invoices();
			include "views/view.php";
			break;
		case "edit_sub":
			$IID = intval($_GET["id"]);
			if (!TFD_HAS_PERMS('admin','use')) {
				include "views/view.php";
			} else {
				edit_invoice();
				$RESPONSE = "Invoice updated.";
				include "views/edit.php";
			}
			break;
		case "edit":
			$IID = intval($_GET["id"]);
			if (!TFD_HAS_PERMS('admin','use')) {
				include "views/view.php";
			} else {
				include "views/edit.php";
			}
			break;
		case "add_note":
			$IID = intval($_GET["id"]);
			add_note();
			include "views/view.php";
			break;
		case "discount":
			$IID = intval($_GET["id"]);
			apply_discount($IID,floatval($_GET["amt"]));
			include "views/view.php";
			break;
		case "remove_discount":
			$IID = intval($_GET["id"]);
			$DID = intval($_GET["did"]);
			remove_discount($IID,$DID);
			include "views/view.php";
			break;
		case "checkout_partial":
			$IID = intval($_GET["id"]);
			load_invoice($IID,floatval($_GET["amt"]));
			header("Location: ?module=pos");
			break;
		case "checkout":
			$IID = intval($_GET["id"]);
			$result = mysql_query("SELECT * FROM invoices WHERE id = $IID");
			$INV = mysql_fetch_assoc($result);
			load_invoice($IID,$INV["amt"] - $INV["amt_paid"]);
			header("Location: ?module=pos");
			break;
		case "create":
			if($_POST){
				$IID = make_invoice();
				header("Location: ?module=invoice&do=view&id=".$IID);
			} else {
				include "views/create.php";
			}
			break;
		case "create_from_issue":
			$IID = make_invoice_from_issue();
			header("Location: ?module=invoice&do=view&id=".$IID);
			//include "views/view.php";
			break;
		case "delete":
			$IID = intval($_GET["id"]);
			delete_invoice($IID);
			header("Location: ?module=invoice");
			//include "views/index.php";
			break;
		case "delete_item":
			$IID = intval($_GET["id"]);
			delete_item();
			include "views/view.php";
			break;
		case "add_items":
			$IID = intval($_GET["id"]);
			add_items();
			include "views/view.php";
			break;
		case "print":
			$IID = intval($_GET["id"]);
			include "views/print.php";
			break;
		case "view":
			$IID = intval($_GET["id"]);
			include "views/view.php";
			break;
		default:
			include "views/index.php";
			break;
	}
} else {
	include "views/index.php";
}

function add_items() {
	global $IID, $USER, $SESSION, $tax_rate;

	$result = mysql_query("SELECT amt FROM invoices WHERE id = $IID");
	$data = mysql_fetch_assoc($result);
	$OLD_AMOUNT = floatval($data["amt"]);

	$totalItems = count($_POST["invCount"]);
	$goodItems = array();

	// Name is required, only process items with a name
	foreach($_POST["invName"] as $num => $var){
		if($var!="")$goodItems[] = $num;
	}

	// Generate subtotal, tax, and total
	$subtotal = 0;
	$taxable = 0;

	$result = mysql_query("SELECT cost,qty,is_taxable FROM invoice_items WHERE invoices__id = $IID");
	if (mysql_num_rows($result)) {
		while ($row = mysql_fetch_assoc($result)) {
			$subtotal += round($row["cost"] * $row["qty"],2);
			if ($row["is_taxable"]) $taxable += round($row["cost"] * $row["qty"],2);
		}
	}

	foreach($goodItems as $var){
		$subtotal += round(floatval($_POST["invCost"][$var]),2) * intval($_POST["invQty"][$var]);
		if($_POST["invTax"][$var]) $taxable += round(floatval($_POST["invCost"][$var]),2) * intval($_POST["invQty"][$var]);
	}
	$tax = round($taxable * $tax_rate,2);
	$total = round($subtotal + $tax,2);

	mysql_query("UPDATE invoices SET amt = '$total', tax = '$tax' WHERE id = $IID");

	$ITEM_DESCR = "Items";

	// Add Invoice Items
	foreach($goodItems as $var){
		$sql = "INSERT INTO invoice_items (invoices__id,name,descr,cost,qty,is_taxable,from_table,from_key_name,";
		$sql .= "from_key,writeback,is_heading,grp,org_entitites__id) VALUES (";
		$sql .= $IID .",";
		$sql .= "'". mysql_real_escape_string($_POST["invName"][$var]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["invDescr"][$var]) ."',";
		$sql .= "'". round(floatval($_POST["invCost"][$var]),2) ."',";
		$sql .= intval($_POST["invQty"][$var]) .",";
		$sql .= ($_POST["invTax"][$var] ? 1 : 0) .",";
		if (isset($_POST["invId"][$var]) && intval($_POST["invId"][$var]) != 0) {
			mysql_query("UPDATE inventory SET qty = qty - ".intval($_POST["invQty"][$var])." WHERE id = ".intval($_POST["invId"][$var]));
			$sql .= "'inventory','id',".intval($_POST["invId"][$var]).",'no',";
			$ITEM_DESCR .= " #".intval($_POST["invId"][$var])." in inventory (".intval($_POST["invQty"][$var])." @ $".number_format(floatval($_POST["invCost"][$var]),2).")";
		} else {
			$sql .= "NULL,NULL,NULL,'no',";
			$ITEM_DESCR .= " #MANUAL_ITEM (".intval($_POST["invQty"][$var])." @ $".number_format(floatval($_POST["invCost"][$var]),2).")";
		}
		$sql .= "0,'". new_salt(10) ."',{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}

	$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
	$sql .= $IID .",";
	$sql .= $USER["id"] .",";
	$sql .= "'$ITEM_DESCR added',";
	$sql .= "'$OLD_AMOUNT',";
	$sql .= "'$total',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function delete_item() {
	global $USER,$IID,$CONFIG;

	$result = mysql_query("SELECT amt FROM invoices WHERE id = $IID");
	$data = mysql_fetch_assoc($result);
	$OLD_AMOUNT = floatval($data["amt"]);

	$ITEM_DESCR = "Item ".intval($_GET["iid"]);
	$result = mysql_query("SELECT * FROM invoice_items WHERE id = ".intval($_GET["iid"]));
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
		if ($data["from_table"] == "issues") {
			mysql_query("UPDATE issues SET invoices__id = NULL WHERE id = {$data["from_key"]}");
		}
		if ($data["from_table"]) {
			$ITEM_DESCR .= ": #{$data["from_key"]} in {$data["from_table"]} ({$data["qty"]} @ $".number_format($data["cost"],2).")";
		} else {
			$ITEM_DESCR .= ": #MANUAL_ITEM ({$data["qty"]} @ $".number_format($data["cost"],2).")";
		}
	}

	$sql = "DELETE FROM invoice_items WHERE id = ".intval($_GET["iid"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$result = mysql_query("SELECT cost,qty,is_taxable FROM invoice_items WHERE invoices__id = $IID");
	if (!mysql_num_rows($result)) {
		mysql_query("UPDATE invoices SET amt = '0', tax = '0' WHERE id = $IID");

		$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
		$sql .= $IID .",";
		$sql .= $USER["id"] .",";
		$sql .= "'$ITEM_DESCR deleted',";
		$sql .= "'$OLD_AMOUNT',";
		$sql .= "'0',{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		return;
	}
	$TAXABLE = 0;
	$TAX = 0;
	$TOTAL = 0;
	while ($row = mysql_fetch_assoc($result)) {
		$TOTAL += round($row["cost"] * $row["qty"],2);
		if ($row["is_taxable"]) $TAXABLE += round($row["cost"] * $row["qty"],2);
	}
	if (isset($CONFIG["tax_rate"])) {
		$TAX = round($TAXABLE * floatval($CONFIG["tax_rate"]),2);
	}
	$TOTAL += $TAX;
	mysql_query("UPDATE invoices SET amt = '$TOTAL', tax = '$TAX' WHERE id = $IID");

	$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
	$sql .= $IID .",";
	$sql .= $USER["id"] .",";
	$sql .= "'$ITEM_DESCR deleted',";
	$sql .= "'$OLD_AMOUNT',";
	$sql .= "'$TOTAL',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function merge_invoices() {
	global $USER,$IID,$CONFIG;
	$sql = "UPDATE invoice_items SET invoices__id = $IID WHERE invoices__id = ".intval($_POST["id2"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);
	$ROWS = mysql_affected_rows();

	$result = mysql_query("SELECT amt FROM invoices WHERE id = $IID");
	$data = mysql_fetch_assoc($result);
	$OLD_AMOUNT = floatval($data["amt"]);

	$result = mysql_query("SELECT amt_paid,amt FROM invoices WHERE id = ".intval($_POST["id2"]));
	$data = mysql_fetch_assoc($result);
	$OLD_AMOUNT_2 = floatval($data["amt"]);
	$PAID_AMOUNT_2 = floatval($data["amt_paid"]);

	$result = mysql_query("SELECT cost,qty,is_taxable FROM invoice_items WHERE invoices__id = $IID");
	if (!mysql_num_rows($result)) return;
	$TAXABLE = 0;
	$TAX = 0;
	$TOTAL = 0;
	while ($row = mysql_fetch_assoc($result)) {
		$TOTAL += round($row["cost"] * $row["qty"],2);
		if ($row["is_taxable"]) $TAXABLE += round($row["cost"] * $row["qty"],2);
	}
	if (isset($CONFIG["tax_rate"])) {
		$TAX = round($TAXABLE * floatval($CONFIG["tax_rate"]),2);
	}
	$TOTAL += $TAX;
	mysql_query("UPDATE invoices SET amt = '$TOTAL', tax = '$TAX', amt_paid = amt_paid + '$PAID_AMOUNT_2' WHERE id = $IID");

	$sql = "UPDATE issues SET invoices__id = $IID WHERE invoices__id = ".intval($_POST["id2"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$sql = "UPDATE notes SET for_key = $IID WHERE for_table = 'invoices' AND for_key = '".intval($_POST["id2"])."'";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	mysql_query("DELETE FROM invoices WHERE id = ".intval($_POST["id2"]));

	$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
	$sql .= intval($_POST["id2"]) .",";
	$sql .= $USER["id"] .",";
	$sql .= "'Merged into invoice $IID',";
	$sql .= "'$OLD_AMOUNT_2',";
	$sql .= "'0',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
	$sql .= $IID .",";
	$sql .= $USER["id"] .",";
	$sql .= "'Absorbed $ROWS items from invoice ".intval($_POST["id2"])."',";
	$sql .= "'$OLD_AMOUNT',";
	$sql .= "'$TOTAL',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function edit_invoice() {
	global $USER;

	if (isset($_POST["invoice_date"]) && strtotime($_POST["invoice_date"])) {
		$result = mysql_query("SELECT toi FROM invoices WHERE id = ".intval($_GET["id"]));
		$data = mysql_fetch_assoc($result);
		$ORIG_TIME = $data["toi"];

		$datestamp = strtotime($_POST["invoice_date"]);
		$date = date("Y-m-d",$datestamp);
		$hr = str_pad("".intval($_POST["inv_hr"]),2,"0",STR_PAD_LEFT);
		$min = str_pad("".intval($_POST["inv_min"]),2,"0",STR_PAD_LEFT);
		$ts = $date ." ". $hr .":". $min .":00";
		mysql_query("UPDATE invoices SET toi = '$ts' WHERE id = ".intval($_GET["id"]));

		$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
		$sql .= intval($_GET["id"]) .",";
		$sql .= $USER["id"] .",";
		$sql .= "'Timestamp changed from \"$ORIG_TIME\" to \"$ts\"',";
		$sql .= "NULL,";
		$sql .= "NULL,{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}
}

function add_note() {
	global $USER, $IID;
	$sql = "INSERT INTO user_notes (for_table,for_key,note,users__id,note_ts) VALUES (";
	$sql .= "'invoices',";
	$sql .= $IID .",";
	$sql .= "'". mysql_real_escape_string($_POST["note"]) ."',";
	$sql .= $USER["id"] .",";
	$sql .= "NOW())";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function delete_invoice($iid) {
	global $USER;

	$result = mysql_query("SELECT amt FROM invoices WHERE id = $iid");
	$data = mysql_fetch_assoc($result);
	$OLD_AMOUNT = floatval($data["amt"]);

	$sql = "INSERT INTO invoice_changes (invoices__id,users__id__change,change_summary,old_amt,new_amt,org_entitites__id) VALUES (";
	$sql .= $iid .",";
	$sql .= $USER["id"] .",";
	$sql .= "'Invoice deleted',";
	$sql .= "'$OLD_AMOUNT',";
	$sql .= "'0',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	mysql_query("DELETE FROM invoice_items WHERE invoices__id = ". intval($iid));
  mysql_query("UPDATE issues SET invoices__id = NULL WHERE invoices__id = ". intval($iid));
	mysql_query("DELETE FROM invoices WHERE id = ". intval($iid));
	mysql_query("DELETE FROM user_notes WHERE for_table = 'invoices' AND for_key = ". intval($iid));
}


function make_invoice_from_issue() {
	global $USER, $tax_rate;

	$ISSUE = mysql_fetch_assoc(mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"])));
	$items = mysql_query("SELECT * FROM issue_items WHERE issues__id = ". intval($_GET["id"]));
	$invs = mysql_query("SELECT * FROM issue_inv WHERE issues__id = ". intval($_GET["id"]));

	$grp_id = new_salt(10);

	$subtotal = round(floatval($ISSUE["do_price"]),2);
	$taxable = $subtotal;
	$total = round($subtotal + ($taxable * $tax_rate),2);

	// Create Invoice Header
	$sql = "INSERT INTO invoices (customers__id,amt,toi,users__id__sale) VALUES (";
	$sql .= $ISSUE["customers__id"] .",";
	$sql .= "'". $total ."',";
	$sql .= "NOW(),";
	$sql .= $USER["id"] .")";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$IID = mysql_insert_id();

	// Add Issue Heading to Invoice Items
	$sql = "INSERT INTO invoice_items (invoices__id,name,descr,cost,qty,is_taxable,from_table,from_key_name,";
	$sql .= "from_key,writeback,is_heading,grp,org_entities__id) VALUES (";
	$sql .= $IID .",";
	$sql .= "'Issue #". intval($_GET["id"]) ."',";
	$sql .= "'Issue #". intval($_GET["id"]) ."',";
	$sql .= "'". round(floatval($ISSUE["do_price"]),2) ."',";
	$sql .= "1,1,'issues','id',".intval($_GET["id"]).",'resolved',1,";
	$sql .= "'". $grp_id ."',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	// Add Issue Items as Invoice Items
	while ($item = mysql_fetch_assoc($items)) {
		$sql = "INSERT INTO invoice_items (invoices__id,name,descr,cost,qty,is_taxable,from_table,from_key_name,";
		$sql .= "from_key,writeback,is_heading,grp,org_entities__id) VALUES (";
		$sql .= $IID .",";
		$sql .= "'Issue Item #". $item["id"] ."',";
		$sql .= "'". mysql_real_escape_string($item["descr"]) ."',";
		$sql .= "'". round(floatval($item["amt"]),2) ."',";
		$sql .= "'". intval($item["qty"]) ."',";
		$sql .= intval($item["is_taxable"]) .",";
		$sql .= "NULL,NULL,NULL,'no',0,";
		$sql .= "'". $grp_id ."',{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
		$subtotal += (intval($item["qty"]) * round(floatval($item["amt"]),2));
		if (intval($item["is_taxable"])) $taxable += (intval($item["qty"]) * round(floatval($item["amt"]),2));
	}

	// Add Issue Inventory as Invoice Items
	while ($inv = mysql_fetch_assoc($invs)) {
		if (intval($inv["add"]) == 0) continue;

		$inv_item = mysql_fetch_assoc(mysql_query("SELECT * FROM inventory WHERE id = ". $inv["id"]));

		$sql = "INSERT INTO invoice_items (invoices__id,name,descr,cost,qty,is_taxable,from_table,from_key_name,";
		$sql .= "from_key,writeback,is_heading,grp,org_entities__id) VALUES (";
		$sql .= $IID .",";
		$sql .= "'". $inv_item["name"] ."',";
		$sql .= "'". mysql_real_escape_string($inv_item["descr"]) ."',";
		$sql .= "'". round(floatval($inv_item["cost"]),2) ."',";
		$sql .= "'". intval($inv["qty"]) ."',";
		$sql .= intval($inv_item["is_taxable"]) .",";
		if ($inv["inventory_items__id"]) {
			$sql .= "'inventory_items','id','{$inv["inventory_items__id"]}','item_status',0,";
		} else {
			$sql .= "'inventory','id','{$inv["inventory__id"]}','no',0,";
		}
		$sql .= "'". $grp_id ."',{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
		$subtotal += (intval($inv["qty"]) * round(floatval($inv_item["cost"]),2));
		if (intval($inv_item["is_taxable"])) $taxable += (intval($inv["qty"]) * round(floatval($inv_item["cost"]),2));
	}

	$total = $subtotal + round($taxable * $tax_rate,2);

	mysql_query("UPDATE issues SET invoices__id = $IID WHERE id = ".intval($_GET["id"]));
	mysql_query("UPDATE invoices SET amt = '$total', toi = toi WHERE id = $IID");

	$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
	$sql .= $IID .",";
	$sql .= $USER["id"] .",";
	$sql .= "'Invoice created from issue ".intval($_GET["id"])."',";
	$sql .= "'0',";
	$sql .= "'$total',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return $IID;
}

function make_invoice() {
	global $USER, $SESSION, $tax_rate;

	$totalItems = count($_POST["invCount"]);
  	$goodItems = array();

	// Name is required, only process items with a name
	foreach($_POST["invName"] as $num => $var){if($var!="")$goodItems[] = $num;}

	// Generate subtotal, tax, and total
	$subtotal = 0;
	$taxable = 0;
	foreach($goodItems as $var){
		$subtotal += round(floatval($_POST["invCost"][$var]),2) * intval($_POST["invQty"][$var]);
		if($_POST["invTax"][$var]) $taxable += round(floatval($_POST["invCost"][$var]),2) * intval($_POST["invQty"][$var]);
	}
	$tax = round($taxable * $tax_rate,2);
	$total = round($subtotal + $tax,2);

	// Create Invoice Header
	$sql = "INSERT INTO invoices (customers__id,amt,toi,users__id__sale,org_entities__id) VALUES (";
	if(isset($_POST["customer_id"])){
		$sql .= intval($_POST["customer_id"]) .",";
	} else {
		$sql .= "NULL,";
	}
    $sql .= "'". $total ."',";
    $sql .= "NOW(),";
    $sql .= $USER["id"] .",{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$IID = mysql_insert_id();

	// Add Invoice Items
	foreach($goodItems as $var){
		$sql = "INSERT INTO invoice_items (invoices__id,name,descr,cost,qty,is_taxable,from_table,from_key_name,";
		$sql .= "from_key,writeback,is_heading,grp,org_entities__id) VALUES (";
		$sql .= $IID .",";
		$sql .= "'". mysql_real_escape_string($_POST["invName"][$var]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["invDescr"][$var]) ."',";
		$sql .= "'". round(floatval($_POST["invCost"][$var]),2) ."',";
		$sql .= intval($_POST["invQty"][$var]) .",";
		$sql .= ($_POST["invTax"][$var] ? 1 : 0) .",";
		if (isset($_POST["invId"][$var]) && intval($_POST["invId"][$var]) != 0) {
			if (isset($_POST["invItemId"][$var]) && intval($_POST["invItemId"][$var]) != 0) {
				mysql_query("UPDATE inventory_items SET varref_status = 8 WHERE id = ".intval($_POST["invItemId"][$var]));
				$sql .= "'inventory_items','id',".intval($_POST["invItemId"][$var]).",'item_status',";
			} else {
				mysql_query("UPDATE inventory SET qty = qty - ".intval($_POST["invQty"][$var])." WHERE id = ".intval($_POST["invId"][$var]));
				$sql .= "'inventory','id',".intval($_POST["invId"][$var]).",'no',";
			}
		} else {
			$sql .= "NULL,NULL,NULL,'no',";
		}
		$sql .= "0,'". new_salt(10) ."',{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}

	$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
	$sql .= $IID .",";
	$sql .= $USER["id"] .",";
	$sql .= "'Invoice created - ".count($goodItems)." items',";
	$sql .= "'0',";
	$sql .= "'$total',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return $IID;
}

function apply_discount($iid,$amt) {
	global $USER;
	if (isset($_GET["rsn"])) {
		$reason = " : ".mysql_real_escape_string($_GET["rsn"]);
	} else {
		$reason = "";
	}
	$amt = floatval($amt);

	$result = mysql_query("SELECT amt FROM invoices WHERE id = ".intval($iid));
	$data = mysql_fetch_assoc($result);
	$OLD_AMOUNT = floatval($data["amt"]);

	mysql_query("UPDATE invoices SET amt = amt - '$amt', toi = toi WHERE id = ".intval($iid));
	$sql = "INSERT INTO invoice_items (invoices__id,name,descr,cost,qty,is_taxable,from_table,from_key_name,from_key,writeback,is_heading,grp,org_entities__id) VALUES (";
	$sql .= intval($iid) .",";
	$sql .= "'Discount','Discount by ".mysql_real_escape_string($USER["username"])." @ ".date("Y-m-d H:i:s").$reason."',";
	$sql .= "'-". $amt ."',";
	$sql .= "1,";
	$sql .= "0,";
	$sql .= "NULL,NULL,NULL,'no',0,";
	$sql .= "'". new_salt(10) ."',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
	$sql .= $iid .",";
	$sql .= $USER["id"] .",";
	$sql .= "'Discount applied - $".number_format($amt,2)." off - ".mysql_real_escape_string($_GET["rsn"])."',";
	$sql .= "'$OLD_AMOUNT',";
	$sql .= "'".floatval($OLD_AMOUNT - $amt)."',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function remove_discount($iid,$did) {
	global $USER;

	$result = mysql_query("SELECT amt FROM invoices WHERE id = ".intval($iid));
	$data = mysql_fetch_assoc($result);
	$OLD_AMOUNT = floatval($data["amt"]);

	$result = mysql_query("SELECT cost FROM invoice_items WHERE invoices__id = ".intval($iid)." AND id = ".intval($did));
	if (!mysql_num_rows($result)) return;
	$data = mysql_fetch_assoc($result);
	mysql_query("DELETE FROM invoice_items WHERE id = ".intval($did));
	mysql_query("UPDATE invoices SET amt = amt - '".floatval($data["cost"])."', toi = toi WHERE id = ".intval($iid));

	$ac = abs(floatval($data["cost"]));
	$sql = "INSERT INTO invoice_changes (invoice_id,changed_by,change_summary,old_amt,new_amt,org_entities__id) VALUES (";
	$sql .= intval($iid) .",";
	$sql .= $USER["id"] .",";
	$sql .= "'Discount removed - $".number_format($ac,2)."',";
	$sql .= "'$OLD_AMOUNT',";
	$sql .= "'".floatval($OLD_AMOUNT + $ac)."',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function load_invoice($iid,$amt) {
	global $USER, $SESSION;

	mysql_query("DELETE FROM pos_cart_items WHERE users__id__sale = ". $USER["id"]);

	$data = mysql_fetch_assoc(mysql_query("SELECT * FROM invoices WHERE id = ". intval($iid)));
	$CUSTOMER = $data["customers__id"];
	$total_amt = $data["amt"];
	$paid_amt = $data["amt_paid"];

	if ($amt > 0) { // Partial payment
		$sql = "INSERT INTO pos_cart_items (from_table,from_key_name,from_key,writeback,amt,";
		$sql .= "qty,descr,users__id__sale,is_taxable,grp,is_heading,org_entities__id) VALUES (";
		$sql .= "'invoices',";
		$sql .= "'id',";
		$sql .= $iid .",";
		$sql .= "'amt_paid',";
		$sql .= "'". floatval($amt) ."',";
		$sql .= "1,";
		$sql .= "'Partial Payment on Invoice #$iid',";
		$sql .= $USER["id"] .",";
		$sql .= "0,";
		$sql .= "'". new_salt(10) ."',";
		$sql .= "1,{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		if ($paid_amt + $amt >= $total_amt) { // Payment complete, add items
			$result = mysql_query("SELECT * FROM invoice_items WHERE invoices__id = ". intval($iid));
			while ($row = mysql_fetch_assoc($result)) {
				$sql = "INSERT INTO pos_cart_items (from_table,from_key_name,from_key,writeback,amt,";
				$sql .= "qty,descr,users__id__sale,is_taxable,grp,is_heading,org_entities__id) VALUES (";
				$sql .= "'". $row["from_table"] ."',";
				$sql .= "'". $row["from_key_name"] ."',";
				$sql .= intval($row["from_key"]) .",";
				$sql .= "'". $row["writeback"] ."',";
				$sql .= "0,";
				$sql .= $row["qty"] .",";
				$sql .= "'". mysql_real_escape_string($row["descr"]) ."',";
				$sql .= $USER["id"] .",";
				$sql .= $row["is_taxable"] .",";
				$sql .= "'". $row["grp"] ."',";
				$sql .= intval($row["is_heading"]) .",{$USER['org_entities__id']})";
				mysql_query($sql) or die(mysql_error() ."::". $sql);
			}
		}
	} else { // Full payment
		// Heading for Invoice (for marking it paid when payment is complete)
		$sql = "INSERT INTO pos_cart_items (from_table,from_key_name,from_key,writeback,amt,";
		$sql .= "qty,descr,users__id__sale,is_taxable,grp,is_heading,org_entities__id) VALUES (";
		$sql .= "'invoices',";
		$sql .= "'id',";
		$sql .= $iid .",";
		$sql .= "'amt_paid',";
		$sql .= "0,0,'Invoice #". $iid ."',";
		$sql .= $USER["id"] .",";
		$sql .= "0,";
		$sql .= "'". new_salt(10) ."',";
		$sql .= "1,{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		$result = mysql_query("SELECT * FROM invoice_items WHERE invoices__id = ". intval($iid));
		while ($row = mysql_fetch_assoc($result)) {
			$sql = "INSERT INTO pos_cart_items (from_table,from_key_name,from_key,writeback,amt,";
			$sql .= "qty,descr,users__id__sale,is_taxable,grp,is_heading,org_entities__id) VALUES (";
			$sql .= "'". $row["from_table"] ."',";
			$sql .= "'". $row["from_key_name"] ."',";
			$sql .= intval($row["from_key"]) .",";
			$sql .= "'". $row["writeback"] ."',";
			$sql .= "'". $row["cost"] ."',";
			$sql .= $row["qty"] .",";
			$sql .= "'". mysql_real_escape_string($row["descr"]) ."',";
			$sql .= $USER["id"] .",";
			$sql .= $row["is_taxable"] .",";
			$sql .= "'". $row["grp"] ."',";
			$sql .= intval($row["is_heading"]) .",{$USER['org_entities__id']})";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}
	}

	mysql_query("UPDATE sessions SET customers__id = '". $CUSTOMER ."', customer_ts = NOW() WHERE id = '". $SESSION["id"] ."'");
}

function width($var){
 echo "width: ".$var."px;";
}

?>
