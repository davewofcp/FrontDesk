<?php
if (isset($_GET["ajax"])) {
	if (!isset($USER)) exit;
	switch ($_GET["ajax"]) {
		default:
			break;
	}
	exit;
}

if (!isset($USER)) { header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit; }

$SQL_VIEW_PRODUCT = "SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.category_name,i.name,i.qty,i.is_qty,i.do_notify_low_qty,i.low_qty,(SELECT COUNT(*) FROM inventory_items ii WHERE ii.inventory__id = i.id) AS iqty FROM inventory i LEFT JOIN categories ca ON i.categories__id = ca.id WHERE i.id = ";
$SQL_EDIT_ITEM = "SELECT i.name,ii.id,i.id as inventory__id,ii.sn,ii.notes,ii.varref_status,ii.in_store_location,ii.item_table_lookup,d.manufacturer,d.model,d.operating_system,d.username,d.password,d.has_charger,ii.issues__id,ii.is_in_transit FROM inventory_items ii JOIN inventory i ON ii.inventory__id = i.id LEFT JOIN inventory_type_devices d ON ii.item_table_lookup = d.id WHERE ii.id = ";

if (isset($_GET["do"])) {
	switch ($_GET["do"]) {
		case "scan":
			scan();
			break;
		case "refund":
			add_refund_item();
			include "views/index.php";
			break;
		case "drop_receipt":
			include "views/drop_receipt.php";
			break;
		case "thist":
			include "views/trans_history.php";
			break;
		case "pop":
			system("C:/pop.exe");
			include "views/index.php";
			break;
		case "add_to_cart":
			if (is_numeric($_POST["barcode"])) {
				scan();
			} else {
				add_to_cart2();
			}
			include "views/index.php";
			break;
		case "cash_adjust":
			$LOG = mysql_query("SELECT * FROM pos_cash_log WHERE 1 ORDER BY ts");
			include "views/cash_adjust.php";
			break;
		case "cash_adjust_sub":
			cash_adjust();
      header("Location: ?module=pos&do=cash_adjust");
			//include "views/index.php";
			break;
		case "complete":
			$TID = intval(complete_transaction());
      		header("Location: ?module=pos&do=view_trans&tid=".$TID);
      		exit;
			//include "views/view_trans.php";
			break;
		case "view_trans":
			$TID = intval($_GET["tid"]);
			include "views/view_trans.php";
			break;
		case "delete":
			delete(intval($_GET["id"]));
			include "views/index.php";
			break;
		case "delete_group":
			delete_group($_GET["grp"]);
			include "views/index.php";
			break;
		case "drop":
			$LOG = mysql_query("SELECT * FROM pos_cash_log WHERE 1 ORDER BY ts");
			include "views/drop.php";
			break;
		case "make_drop":
			$drop_ids = make_drop();
      		header("Location: ?module=pos&do=drop&make_drop=".join(",",$drop_ids));
      		exit;
			break;
		case "set_customer":
			set_customer(intval($_GET["id"]));
			$SESSION["customers__id"] = intval($_GET["id"]);
			include "views/index.php";
			break;
		case "remove_customer":
			remove_customer();
			$SESSION["customers__id"] = 0;
			include "views/index.php";
			break;
		case "receipt":
			$TID = intval($_GET["tid"]);
			include "views/receipt.php";
			break;
		default:
			if ($SESSION["customer_age"] && $SESSION["customer_age"] > 600) {
				remove_customer();
				$SESSION["customers__id"] = 0;
			} else {
				set_customer($SESSION["customers__id"]);
			}
			include "views/index.php";
			break;
	}
} else {
	if ($SESSION["customer_age"] && $SESSION["customer_age"] > 600) {
		remove_customer();
		$SESSION["customers__id"] = 0;
	} else {
		set_customer($SESSION["customers__id"]);
	}
	include "views/index.php";
}

function scan() {
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	$product = decodeUpc($_POST["barcode"]);

	if (!$product) {
		$RESPONSE = "Invalid store barcode.";
		include "views/index.php";
		return;
	}

	switch ($product->type) {
		case "inventory":
			$result = mysql_query($SQL_VIEW_PRODUCT . $product->id);
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Product not found.";
				include "views/index.php";
				return;
			} else {
				$ITEM = mysql_fetch_assoc($result);
				include "cart.php";
				add_to_cart("inventory","id",$ITEM["inventory__id"],"no",floatval($ITEM["cost"]),"1",$ITEM["name"],($ITEM["is_taxable"] ? "1" : "0"),"".new_salt(10),0,0);
				include "views/index.php";
			}
			break;
		case "inventory_item":
			$result = mysql_query($SQL_EDIT_ITEM . $product->id);
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Item not found.";
				include "views/index.php";
				break;
			} else {
				$ITEM = mysql_fetch_assoc($result);
				include "cart.php";
				add_to_cart("inventory_items","id",$ITEM["id"],"no",floatval($ITEM["cost"]),"1",$ITEM["name"],($ITEM["is_taxable"] ? "1" : "0"),"".new_salt(10),0,0);
				include "views/index.php";
			}
			break;
		case "upc":
			$result = mysql_query("SELECT id FROM inventory WHERE upc = '". mysql_real_escape_string($product->id) ."'");
			if (!mysql_num_rows($result)) {
				$RESPONSE = "UPC not found in inventory.";
				include "views/index.php";
				break;
			}
			$inv = mysql_fetch_assoc($result);
			$result = mysql_query($SQL_VIEW_PRODUCT . $inv["id"]);
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Product not found.";
				include "views/index.php";
				return;
			} else {
				$ITEM = mysql_fetch_assoc($result);
				include "cart.php";
				add_to_cart("inventory","id",$ITEM["inventory__id"],"no",floatval($ITEM["cost"]),"1",$ITEM["name"],($ITEM["is_taxable"] ? "1" : "0"),"".new_salt(10),0,0);
				include "views/index.php";
			}
			break;
		default:
			$RESPONSE = "That UPC is not an inventory item.";
		include "views/index.php";
		break;
	}
}

function add_refund_item() {
	global $USER;

	$result = mysql_query("SELECT * FROM pos_transactions WHERE id = ".intval($_GET["tid"])." AND line_number = ".intval($_GET["line"]));
	if (!mysql_num_rows($result)) {
		return "Transaction item not found.";
	}
	$data = mysql_fetch_assoc($result);

	$sql = "INSERT INTO pos_cart_items (from_table,from_key_name,from_key,writeback,".
		   "amt,qty,descr,is_taxable,users__id__sale,grp,is_heading,org_entities__id) VALUES (";
	$sql .= "NULL,";
	$sql .= "'".intval($_GET["tid"]).":".intval($_GET["line"])."',";
	$sql .= "NULL,";
	$sql .= "'refund',";
	$sql .= "'-". abs(floatval($data["amt"])) ."',";
	$sql .= "'". intval($data["qty"]) ."',";
	$sql .= "'REFUND: ". mysql_real_escape_string($data["descr"]) ."',";
	$sql .= intval($data["is_taxable"]) .",";
	$sql .= $USER["id"] .",";
	$sql .= "'". new_salt(10) ."',";
	$sql .= "0,{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return "Refund added to cart.";
}

function add_to_cart2() {
	global $USER;

	$sql = "INSERT INTO pos_cart_items (from_table,from_key_name,from_key,writeback,".
	       "amt,qty,descr,is_taxable,users__id__sale,grp,is_heading,org_entities__id) VALUES (";
	$sql .= "NULL,";
	$sql .= "NULL,";
	$sql .= "NULL,";
	$sql .= "'no',";
	$sql .= "'". floatval($_POST["price"]) ."',";
	$sql .= intval($_POST["units"]) .",";
	$sql .= "'". mysql_real_escape_string($_POST["barcode"]) ."',";
	$sql .= "'". (isset($_POST["taxable"]) ? "1":"0") ."',";
	$sql .= $USER["id"] .",";
	$sql .= "'". new_salt(10) ."',";
	$sql .= "0,{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function cash_adjust() {
	global $USER;
	$amount = floatval($_POST["amt"]);
	$action = intval($_POST["action"]);
	if ($action == 3) {
		$is_reset = "1";
	} else {
		$is_reset = "0";
	}

	if ($action == 2) {
		$amount = 0 - $amount;
	}

	$sql = "INSERT INTO pos_cash_log (users__id,amt,reason,ts,is_reset,is_checks,pos_transactions__id,is_drop,org_entities__id) VALUES (";
	$sql .= $USER["id"] .",";
	$sql .= "'". $amount ."',";
	$sql .= "'". mysql_real_escape_string($_POST["reason"]) ."',";
	$sql .= "NOW(),";
	$sql .= $is_reset .",";
	$sql .= intval($is_drop) .",";
	$sql .= "NULL,";
	$sql .= intval($is_drop) .",{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function set_customer($id) {
	global $USER, $SESSION;
	$id = intval($id);
	mysql_query("UPDATE sessions SET customers__id='". intval($id) ."', customer_ts = NOW() WHERE id='". $SESSION["id"] ."'");
}

function remove_customer() {
	global $USER, $SESSION;
	mysql_query("UPDATE sessions SET customer_ts=NULL WHERE id='". $SESSION["id"] ."'");
}

function delete($id) {
	global $USER;
	$id = intval($id);
	$result = mysql_query("SELECT * FROM pos_cart_items WHERE id = ". $id ." AND users__id__sale = '". $USER["id"] ."'");
	if (mysql_num_rows($result) < 1) return;
	$item = mysql_fetch_assoc($result);
	if ($item["is_heading"]) {
		delete_group($item["grp"]);
	} else {
		mysql_query("DELETE FROM pos_cart_items WHERE id = ". $id ." AND users__id__sale = '". $USER["id"] ."'");
	}
}

function delete_group($grp) {
	global $USER;
	mysql_query("DELETE FROM pos_cart_items WHERE grp = '". mysql_real_escape_string($grp) ."' AND users__id__sale = '". $USER["id"] ."'");
}

function complete_transaction() {
	global $USER;

	$data = mysql_fetch_assoc(mysql_query("SELECT IFNULL(MAX(id),0) AS max FROM pos_transactions"));
	$TID = $data["max"] + 1;

	$cash_total = (floatval($_POST["paid_cash"]) - floatval($_POST["change"]));
	$check_total = floatval($_POST["paid_check"]);

	$total_amt = $cash_total + floatval($_POST["paid_credit"]) + floatval($_POST["paid_check"]);

	$line = 1;
	$data = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM pos_cart_items WHERE users__id__sale = {$USER["id"]}"));

	$sql = "INSERT INTO pos_transactions (id,line_number,customers__id,qty,amt,tos,paid_cash,paid_credit,paid_check,users__id__sale,users__id__refund,is_heading,descr,paid_tax,org_entities__id) VALUES (";
	$sql .= $TID .",";
	$sql .= "0,";
	if (intval($_POST["customer_id"]) == 0) {
		$sql .= "NULL,";
	} else {
		$sql .= intval($_POST["customer_id"]) .",";
	}
	$sql .= $data["count"] .",";
	$sql .= "'". $total_amt ."',";
	$sql .= "NOW(),";
	$sql .= "'". $cash_total ."',";
	$sql .= "'". floatval($_POST["paid_credit"]) ."',";
	$sql .= "'". floatval($_POST["paid_check"]) ."',";
	$sql .= $USER["id"] .",";
	$sql .= "NULL,1,";
	if (isset($_POST["check_no"]) && intval($_POST["check_no"]) > 0) {
		$sql .= "'Check#". intval($_POST["check_no"]) ."',";
	} else {
		$sql .= "NULL,";
	}
	$sql .= "'". floatval($_POST["tax"]) ."',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$result = mysql_query("SELECT * FROM pos_cart_items WHERE users__id__sale = {$USER["id"]}");
	while ($row = mysql_fetch_assoc($result)) {
		$sql = "INSERT INTO pos_transactions (id,line_number,from_table,from_key_name,from_key,writeback,"
			  ."amt,descr,qty,users__id__sale,customers__id,is_taxable,is_refunded,users__id__refund,tos,tor,paid_cash,paid_credit,paid_check,"
			  ."grp,is_heading,org_entities__id) VALUES (";
		$sql .= $TID .",";
		$sql .= $line .",";
        $sql .= "'". $row["from_table"] ."',";
        $sql .= "'". $row["from_key_name"] ."',";
        if ($row["from_key"]) {
       		$sql .= "'". $row["from_key"] ."',";
        } else {
        	$sql .= "NULL,";
        }
        $sql .= "'". $row["writeback"] ."',";
       	$sql .= "'". $row["amt"] ."',";
       	$sql .= "'". mysql_real_escape_string($row["descr"]) ."',";
        $sql .= intval($row["qty"]) .",";
		$sql .= $USER["id"] .",";
		if (intval($_POST["customer_id"]) == 0) {
			$sql .= "NULL,";
		} else {
			$sql .= intval($_POST["customer_id"]) .",";
		}
		$sql .= intval($row["is_taxable"]) .",";
		$sql .= "0,NULL,NOW(),0,";
		$sql .= "'". $cash_total ."',";
		$sql .= "'". floatval($_POST["paid_credit"]) ."',";
		$sql .= "'". floatval($_POST["paid_check"]) ."',";
		$sql .= "'". $row["grp"] ."',";
		$sql .= intval($row["is_heading"]) .",{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		switch ($row["writeback"]) {
			case "qty":
				mysql_query("UPDATE ".$row["from_table"]." SET {$row["writeback"]} = {$row["writeback"]} - ".intval($row["qty"])." WHERE {$row["from_key_name"]} = '{$row["from_key"]}'");
				if ($row["from_table"] == "inventory") {
					$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,descr,varref_status,reason,org_entities__id) VALUES (";
					$sql .= $row["from_key"] .",";
					$sql .= "NULL,";
					$sql .= $USER["id"] .",";
					$sql .= "13,";
					$sql .= intval($row["qty"]) .",";
					$sql .= "'".mysql_real_escape_string(alink("Transaction $TID","?module=pos&do=view_trans&tid=$TID"))."',";
					$sql .= "7,";
					$sql .= "NULL,{$USER['org_entities__id']})";
					mysql_query($sql) or die(mysql_error() ."::". $sql);
				}
				break;
			case "tid":
				mysql_query("UPDATE ".$row["from_table"]." SET ".$row["writeback"]." = '".$TID."' WHERE ".$row["from_key_name"]." = '".$row["from_key"]."'");
				break;
			case "amt_paid":
				$result2 = mysql_fetch_assoc(mysql_query("SELECT amt_paid,amt FROM ".$row["from_table"]." WHERE ".$row["from_key_name"]." = ".$row["from_key"]));
				$amt = $result2["amt"];
				$new_amt = $result2["amt_paid"] + $total_amt;
				if ($new_amt > $amt) $new_amt = $amt;
				if ($row["from_table"] == "invoices" && $new_amt >= $amt) {
					$tsp = ", ts_paid = NOW(), users__id__sale = {$USER["id"]}";
				} else {
					$tsp = "";
				}
				mysql_query("UPDATE ".$row["from_table"]." SET ".$row["writeback"]." = '".$new_amt."'$tsp WHERE ".$row["from_key_name"]." = ".$row["from_key"]);
				break;
			case "resolved":
				mysql_query("UPDATE {$row["from_table"]} SET {$row["writeback"]} = 1 WHERE {$row["from_key_name"]} = {$row["from_key"]}");

				if ($row["from_table"] == "issues") {
					$data = mysql_fetch_assoc(mysql_query("SELECT varref_status FROM issues WHERE id = ".$row["from_key"]));

					$sql = "INSERT INTO issue_changes (issues__id,description,varref_status,tou,users__id,org_entities__id) VALUES (";
					$sql .= $row["from_key"] .",";
					$sql .= "'Resolved',";
					$sql .= (ARRAY_KEY_EXISTS('varref_status',$data) ? $data['varref_status'] : $data["varref_status"]) .",";
					$sql .= "NOW(),";
					$sql .= $USER["id"] .",{$USER['org_entities__id']})";
					mysql_query($sql);
				}
				break;
			case "refund":
				$tparts = explode(":",$row["from_key_name"]);
				mysql_query("UPDATE pos_transactions SET is_refunded = 1, refunded_by = {$USER["id"]} WHERE id = {$tparts[0]} AND line_number = {$tparts[1]}");
				break;
			case "item_status":
				mysql_query("UPDATE {$row["from_table"]} SET varref_status = 7 WHERE {$row["from_key_name"]} = {$row["from_key"]}");
				$result = mysql_query("SELECT inventory__id FROM inventory_items WHERE id = {$row["from_key"]}");
				$data = mysql_fetch_assoc($result);
				$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,descr,varref_status,reason,org_entities__id) VALUES (";
				$sql .= $data["inventory__id"] .",";
				$sql .= $row["from_key"] .",";
				$sql .= $USER["id"] .",";
				$sql .= "13,";
				$sql .= "1,";
				$sql .= "'".mysql_real_escape_string(alink("Transaction $TID","?module=pos&do=view_trans&tid=$TID"))."',";
				$sql .= "7,";
				$sql .= "NULL,{$USER['org_entities__id']})";
				mysql_query($sql) or die(mysql_error() ."::". $sql);
				break;
			case "no":
			default:
				break;
		}

		if ($row["from_table"] == "issues") {
			$sql = "INSERT INTO issue_changes (issues__id,description,varref_status,tou,users__id,org_entities__id) VALUES (";
			$sql .= $row["from_key"] .",'Issue checked out. TID is ".$TID."',0,NOW(),". $USER["id"] .",{$USER['org_entities__id']})";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}

		$line++;
	}

	mysql_query("DELETE FROM pos_cart_items WHERE users__id__sale = {$USER["id"]}");

	if ($cash_total != 0) {
		$sql = "INSERT INTO pos_cash_log (users__id,amt,reason,ts,is_reset,pos_transactions__id,org_entities__id) VALUES (";
		$sql .= $USER["id"] .",";
		$sql .= "'". $cash_total ."',";
		$sql .= "'Transaction ".$TID."',NOW(),0,";
		$sql .= $TID .",{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}

	if ($check_total != 0) {
		$sql = "INSERT INTO pos_cash_log (users__id,amt,reason,ts,is_reset,pos_transactions__id,is_checks,org_entities__id) VALUES (";
		$sql .= $USER["id"] .",";
		$sql .= "'". $check_total ."',";
		$sql .= "'Transaction ".$TID." :: Checks',NOW(),0,";
		$sql .= $TID .",";
		$sql .= "1,{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		if (isset($_POST["check_drop"]) && (TFD_HAS_PERMS('admin','use'))) {
			$sql = "INSERT INTO pos_cash_log (users__id,amt,reason,ts,is_reset,pos_transactions__id,is_checks,is_drop,org_entities__id) VALUES (";
			$sql .= $USER["id"] .",";
			$sql .= "'". $check_total ."',";
			$sql .= "'Transaction ".$TID." :: Auto-Drop Checks',NOW(),0,";
			$sql .= $TID .",";
			$sql .= "1,1,{$USER['org_entities__id']})";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}
	}

	return $TID;
}

function make_drop(){
	global $USER, $SESSION;

	$drop_ids = array();

  foreach($_POST["currency_type"] as $num => $var){
    if($var=="")continue;

    $reason = "Drop";
    if($var=="is_checks")$reason .= "::Check #".intval($_POST["check_num"][$num]."::");

		$sql = "INSERT INTO pos_cash_log (users__id,amt,reason,ts,is_checks,is_drop,org_entities__id) VALUES (";
		$sql .= $USER["id"] .",";
		$sql .= "'". $_POST["amt"][$num] ."',";
		$sql .= "'".$reason."',NOW(),";
		$sql .= "'". ($var=="is_checks" ? 1 : 0) ."',";
		$sql .= "1,{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		$drop_ids[] = mysql_insert_id();

  }

  return $drop_ids;

}

?>
