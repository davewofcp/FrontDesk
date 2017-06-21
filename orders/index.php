<?php

display_header();

?><h2>Orders</h2><?php

if (isset($_GET["do"])) {
	switch ($_GET["do"]) {
		case "new":
			if (isset($_POST["order_number"])) {
				$ORDER = new_order();
				include "views/view_order.php";
			} else {
				include "views/new_order.php";
			}
			break;
		case "view":
			$result = mysql_query("SELECT * FROM orders WHERE id = ". intval($_GET["id"]));
			$ORDER = mysql_fetch_assoc($result);
			include "views/view_order.php";
			break;
		case "delete":
			$RESPONSE = delete_order();
			include "views/index.php";
			break;
		case "receive":
			$RESPONSE = receive_order();
			include "views/index.php";
			break;
		case "edit":
			if (isset($_POST["order_number"])) {
				$RESPONSE = edit_order();
				include "views/index.php";
			} else {
				$result = mysql_query("SELECT * FROM orders WHERE id = ". intval($_GET["id"]));
				$ORDER = mysql_fetch_assoc($result);
				include "views/edit_order.php";
			}
			break;
		default:
			include "views/index.php";
			break;
	}
} else {
	include "views/index.php";
}

display_footer();

function new_order() {
  global $USER;
	if (isset($_POST["orderCount"])) {
		$totalItems = count($_POST["orderCount"]);
		$goodItems = array();

		// Name is required, only process items with a name
		foreach($_POST["orderName"] as $num => $var){
			if($var!="") $goodItems[] = $num;
		}
	}

	// Create Order Heading
	$sql = "INSERT INTO orders (purchased_from,order_number,shipping_type,tracking_number,"
	      ."subtotal,tax,carrier,varref_status,org_entities__id) VALUES (";
	$sql .= "'". mysql_real_escape_string($_POST["purchased_from"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["order_number"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["shipping_type"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["tracking_number"]) ."',";
	$sql .= "'". floatval($_POST["subtotal"]) ."',";
	$sql .= "'". floatval($_POST["tax"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["carrier"]) ."',";
	$sql .= "1,{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$ORDER_ID = mysql_insert_id();

	if (isset($_POST["orderCount"])) {
		foreach($goodItems as $var) {
			$inventory_id = "NULL";
			if (isset($_POST["invId"][$var]) && intval($_POST["invId"][$var]) > 0) {
				// Ordering an item from inventory
				$inventory_id = intval($_POST["invId"][$var]);
			} else {
				$result = mysql_query("SELECT id FROM inventory WHERE upc = '".mysql_real_escape_string($_POST["orderUpc"][$var])."'");
				if (mysql_num_rows($result)) {
					// They entered an existing item as a new item
					$data = mysql_fetch_assoc($result);
					$inventory_id = $data["id"];
				} else {
					// Ordering an item not in inventory - add a dummy entry
					$sql = "INSERT INTO inventory (upc,descr,purchase_price,cost,qty,is_taxable,item_type_lookup,name,is_qty,org_entities__id) VALUES (";
					$sql .= "'". mysql_real_escape_string($_POST["orderUpc"][$var]) ."',";
					$sql .= "'". mysql_real_escape_string($_POST["orderDescr"][$var]) ."',";
					$sql .= "'". floatval($_POST["orderPPrice"][$var]) ."',";
					$sql .= "'". floatval($_POST["orderCost"][$var]) ."',";
					$sql .= "0,";
					$sql .= ($_POST["orderTax"][$var] ? "1" : "0") .",";
					$sql .= intval($_POST["orderDevType"][$var]) .",";
					$sql .= "'". mysql_real_escape_string($_POST["orderName"][$var]) ."',";
					$sql .= "1,{$USER['org_entities__id']})";
					mysql_query($sql) or die(mysql_error() ."::". $sql);
					$inventory_id = mysql_insert_id();

					$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,in_store_location,descr,varref_status,reason,org_entities__id) VALUES (";
					$sql .= $inventory_id .",NULL,";
					$sql .= $USER["id"] .",";
					$sql .= "1,0,NULL,";
					$sql .= "'Added Product ''". mysql_real_escape_string($_POST["orderName"][$var]) ."''',";
					$sql .= "NULL,NULL,{$USER['org_entities__id']})";
					mysql_query($sql) or die(mysql_error() ."::". $sql);
				}
			}
			// Add item to order
			$sql = "INSERT INTO order_items (orders__id,inventory__id,issues__id,cost,varref_status,qty,org_entities__id) VALUES (";
			$sql .= $ORDER_ID .",";
			$sql .= $inventory_id .",";
			$sql .= (intval($_POST["orderIssue"][$var]) > 0 ? intval($_POST["orderIssue"][$var]) : "NULL") .",";
			$sql .= "'". floatval($_POST["orderPPrice"][$var]) ."',";
			$sql .= "1,";
			$sql .= intval($_POST["orderQty"][$var]) .",{$USER['org_entities__id']})";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}
	}

	$ORDER = mysql_fetch_assoc(mysql_query("SELECT * FROM orders WHERE id = ". $ORDER_ID));
	return $ORDER;
}

function edit_order() {
	$sql = "UPDATE orders SET ";
	$sql .= "purchased_from = '". mysql_real_escape_string($_POST["purchased_from"]) ."',";
	$sql .= "order_number = '". mysql_real_escape_string($_POST["order_number"]) ."',";
	$sql .= "shipping_type = ". intval($_POST["shipping_type"]) .",";
	$sql .= "carrier = ". intval($_POST["carrier"]) .",";
	$sql .= "tracking_number = '". mysql_real_escape_string($_POST["tracking_number"]) ."',";
	if (isset($_POST["receive_date"]) && $_POST["receive_date"] != "") {
		$sql .= "receive_date = '". mysql_real_escape_string($_POST["receive_date"]) ."',";
	} else {
		$sql .= "receive_date = NULL,";
	}
	$sql .= "subtotal = '". floatval($_POST["subtotal"]) ."',";
	$sql .= "tax = '". floatval($_POST["tax"]) ."' ";
	$sql .= "WHERE id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	if (isset($_POST["delete"])) {
		foreach($_POST["delete"] as $num => $var) {
			mysql_query("DELETE FROM order_items WHERE id = ".intval($var));
		}
	}

	return "Order ".intval($_GET["id"])." updated.";
}

function delete_order() {
	mysql_query("DELETE FROM order_items WHERE orders__id = ". intval($_GET["id"]));
	mysql_query("DELETE FROM orders WHERE id = ". intval($_GET["id"]));
	return "Order ". intval($_GET["id"]) ." deleted.";
}

function receive_order() {
	global $USER;

	// Mark order and items received
	$sql = "UPDATE orders SET ";
	$sql .= "varref_status = 2, receive_date = '". date("Y-m-d") ."' ";
	$sql .= "WHERE id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$sql = "UPDATE order_items SET varref_status = 2 ";
	$sql .= "WHERE orders__id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	// Update inventory quantities and attach items to issues if issue_id is set
	$inventory = array();
	$inv_names = array();
	$issue_inv = array();
	$itemCount = 0;
	$items = mysql_query("SELECT o.inventory__id,o.qty,o.issues__id,i.name FROM order_items o JOIN inventory i ON o.inventory__id = i.id WHERE orders__id = ".intval($_GET["id"]));
	while ($row = mysql_fetch_assoc($items)) {
		$itemCount += $row["qty"];
		$inv_names[$row["inventory__id"]] = $row["name"];
		if (!isset($inventory[$row["inventory__id"]])) $inventory[$row["inventory__id"]] = 0;
		$inventory[$row["inventory__id"]] += $row["qty"];

		if ($row["issues__id"] != null) {
			if (!isset($issue_inv[$row["issues__id"]])) $issue_inv[$row["issues__id"]] = array();
			if (!isset($issue_inv[$row["issues__id"]][$row["inventory__id"]])) $issue_inv[$row["issues__id"]][$row["inventory__id"]] = 0;
			$issue_inv[$row["issues__id"]][$row["inventory__id"]] += $row["qty"];
		}
	}
	foreach ($inventory as $id => $qty) {
		mysql_query("UPDATE inventory SET qty = qty + $qty WHERE id = $id");
	}
	// Attach items to issues and notify assigned techs that parts have arrived
	foreach ($issue_inv as $id => $inv_items) {
		$message = "Parts have been received for <a href=\"?module=iss&do=view&id=$id\">Issue $id</a>:<br><br>";
		foreach ($inv_items as $inv_id => $qty) {
			$sql = "INSERT INTO issue_inv (issues__id,inventory__id,qty) VALUES (";
			$sql .= $id .",";
			$sql .= $inv_id .",";
			$sql .= $qty .")";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
			$message .= "- ($qty) ".$inv_names[$inv_id] ."<br>";
		}
		$data = mysql_fetch_assoc(mysql_query("SELECT users__id__assigned FROM issues WHERE id = $id"));
		if ($data["users__id__assigned"] == null) continue;
		$sql = "INSERT INTO messages (users__id__1,users__id__2,box,subject,message,is_read) VALUES (";
		$sql .= $data["users__id__assigned"] .",";
		$sql .= $USER["id"] .",";
		$sql .= "1,'Parts Received For Issue $id',";
		$sql .= "'". mysql_real_escape_string($message) ."',0)";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}

	return "Order received ($itemCount items).";
}

?>
