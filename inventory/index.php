<?php

if(!isset($USER)) {
	header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php");
	exit;
}

display_header();

$SQL_VIEW_PRODUCT = "SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.category_name,i.name,i.qty,i.is_qty,i.do_notify_low_qty,i.low_qty,(SELECT COUNT(*) FROM inventory_items ii WHERE ii.inventory__id = i.id) AS iqty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND i.id = ";
$SQL_EDIT_ITEM = "SELECT i.name,ii.id as inv_item_id,i.id,ii.sn,ii.notes,ii.varref_status,ii.in_store_location,ii.item_type_lookup,d.manufacturer,d.model,d.operating_system,d.username,d.password,d.has_charger,ii.issues__id,ii.is_in_transit FROM inventory_items ii JOIN inventory i ON ii.inventory__id = i.id LEFT JOIN inventory_type_devices d ON ii.item_type_lookup = d.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND ii.id = ";

if (isset($_GET["do"])) {
	switch ($_GET["do"]) {
		case "scan":
			scan();
			break;
		case "add":
			include "views/add.php";
			break;
		case "add_sub":
			add_inv();
			break;
		case "add_item":
			$result = mysql_query("SELECT name,id FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($_GET["id"]));
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Product not found.";
				include "views/index.php";
				break;
			} else {
				$ITEM = mysql_fetch_assoc($result);
				include "views/add_item.php";
			}
			break;
		case "add_item_sub":
			add_item_inv();
			break;
		case "transfer":
			if (isset($_POST["to_id"])) {
				$RESPONSE = transfer();
				$result = mysql_query($SQL_VIEW_PRODUCT . intval($_GET["id"]));
				if (!mysql_num_rows($result)) {
					$RESPONSE = "Item not found.";
					include "views/index.php";
				} else {
					$ITEM = mysql_fetch_assoc($result);
					include "views/view.php";
				}
			} else {
				$result = mysql_query($SQL_VIEW_PRODUCT . intval($_GET["id"]));
				if (!mysql_num_rows($result)) {
					$RESPONSE = "Item not found.";
					include "views/index.php";
				} else {
					$ITEM = mysql_fetch_assoc($result);
					include "views/transfer.php";
				}
			}
			break;
		case "view":
			$result = mysql_query($SQL_VIEW_PRODUCT . intval($_GET["id"]));
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Product not found.";
				include "views/index.php";
				break;
			} else {
				$ITEM = mysql_fetch_assoc($result);
				include "views/view.php";
			}
			break;
		case "edit":
			$result = mysql_query("SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.id as category__id,i.name,i.qty,i.is_qty,i.do_notify_low_qty,i.low_qty,(SELECT COUNT(*) FROM inventory_items ii WHERE ii.inventory__id = i.id) AS iqty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND i.id = ".intval($_GET["id"]));
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Product not found.";
				include "views/index.php";
				break;
			} else {
				$ITEM = mysql_fetch_assoc($result);
				include "views/edit.php";
			}
			break;
		case "edit_sub":
			edit_inv();
			break;
		case "edit_item":
			$result = mysql_query($SQL_EDIT_ITEM . intval($_GET["id"]));
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Item not found.";
				include "views/index.php";
				break;
			} else {
				$ITEM = mysql_fetch_assoc($result);
				include "views/edit_item.php";
			}
			break;
		case "edit_item_sub":
			edit_item_inv();
			break;
		case "delete":
			delete_inv();
			break;
		case "delete_item":
			delete_item_inv();
			break;
		case "request":
			include "views/request.php";
			break;
		case "clear_log":
			if (!TFD_HAS_PERMS('admin','use')) {
				$RESPONSE = "Only administrators may clear the inventory history.";
				include "views/index.php";
				break;
			}
			clear_log();
			break;
		case "xfers":
			if (!TFD_HAS_PERMS('admin','use')) {
				$RESPONSE = "Only administrators may access this utility.";
				include "views/index.php";
				break;
			}
			include "views/xfers.php";
			break;
		case "add_to_cart":
			add_inv_to_cart();
			break;
		default:
			include "views/index.php";
			break;
	}
} else {
	include "views/index.php";
}

display_footer();

function transfer() {
	global $DB, $USER, $db_host, $db_user, $db_pass, $db_database;
	//$result = mysql_query("SELECT * FROM locations WHERE is_here = 1 OR location_id = ".intval($_GET["to_loc"]));
  $result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  ( oe.id = {$USER['org_entities__id']} OR oe.id = ".intval($_GET["to_loc"])." )
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
// 	if (!mysql_num_rows($result)) {
// 		return "Local store does not have the 'is_here' flag set. Please contact an administrator.";
//	}
	$this_store = 0;
	$remote_store = 0;
	$loc = null;
	while ($row = mysql_fetch_assoc($result)) {
		if ($row["id"] == $USER['org_entities__id']) {
			$this_store = $row["id"];
		} else {
			$remote_store = $row["id"];
			$loc = $row;
		}
	}

	if (!$loc) return "Invalid remote location id.";

	$sql = "INSERT INTO inventory_transfers (inventory__id__orig,inventory_item_number_orig,users__id__orig,org_entities__id__dest,inventory__id__dest,";
	$sql .= "inventory_item_number_dest,users__id__dest,inventory_name_dest,is_incoming,qty,varref_status,payload,ts_created,ts_updated,org_entities__id__orig) VALUES (";
	$sql .= intval($_GET["id"]) .",";
	if (isset($_GET["iid"])) $sql .= intval($_GET["iid"]) .",";
	else $sql .= "NULL,";
	$sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string($loc["id"]) ."',";
	$sql .= intval($_POST["to_id"]) .",";
	$sql .= "NULL,NULL,NULL,0,";
	if (isset($_POST["qty"])) $sql .= intval($_POST["qty"]) .",";
	else $sql .= "1,";
	$sql .= "1,NULL,NOW(),NOW(),{$this_store})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
	$local_transfer_id = mysql_insert_id();

	if (isset($_POST["qty"])) {
		$sql = "UPDATE inventory SET qty = qty - ".intval($_POST["qty"]) ." WHERE org_entities__id = {$this_store} AND id = ". intval($_GET["id"]);
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	} else {
		$sql = "UPDATE inventory_items SET is_in_transit = 1 WHERE org_entities__id = {$this_store} AND id = ". intval($_GET["iid"]);
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}

	$result = mysql_query("SELECT * FROM inventory i JOIN categories c ON i.item_type_lookup = c.id WHERE org_entities__id = {$this_store} AND i.id = ". intval($_GET["id"]));
	$ITEM = mysql_fetch_assoc($result);

	//mysql_close($DB);
// 	$DB2 = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"],true) or die("ERROR: Couldn't connect to remote database.");
// 	mysql_select_db($loc["db_db"],$DB2) or die("ERROR: Couldn't select remote database.");

	$remote_id = intval($_POST["to_id"]);
	$remote_cat = "NULL";

	if ($remote_id == 0) {
		$result = mysql_query("SELECT id FROM categories WHERE category_name = '".mysql_real_escape_string($ITEM["category_name"])."'",$DB2);
		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			$remote_cat = $data["id"];
		} else {
			$result = mysql_query("SELECT id FROM categories WHERE category_name = 'Other'",$DB2);
			$data = mysql_fetch_assoc($result);
			$remote_cat = $data["id"];
		}
		$sql = "INSERT INTO inventory (upc,descr,purchase_price,cost,qty,is_taxable,item_type_lookup,name,is_qty,org_entities__id) VALUES (";
		if ($ITEM["upc"]) {
			$sql .= "'". mysql_real_escape_string($ITEM["upc"]) ."',";
		} else {
			$sql .= "NULL,";
		}
		$sql .= "'". mysql_real_escape_string($ITEM["descr"]) ."',";
		$sql .= "'". floatval($ITEM["purchase_price"]) ."',";
		$sql .= "'". floatval($ITEM["cost"]) ."',";
		$sql .= "0,";
		$sql .= intval($ITEM["is_taxable"]) .",";
		$sql .= intval($remote_cat) .",";
		$sql .= "'". mysql_real_escape_string($ITEM["name"]) ."',";
		$sql .= ($ITEM["is_qty"] ? "1":"0") .",$remote_store)";
		//mysql_query($sql,$DB2) or die(mysql_error($DB2) ."::". $sql);
    mysql_query($sql) or die(mysql_error() ."::". $sql);

		//$remote_id = mysql_insert_id($DB2);
    $remote_id = mysql_insert_id();

		mysql_query("UPDATE inventory_transfers SET inventory__id__dest = $remote_id WHERE org_entities__id__orig = {$this_store} AND id = $local_transfer_id");//,$DB);
	}

	$sql = "INSERT INTO inventory_transfers (inventory__id__orig,inventory_item_number_orig,users__id__orig,org_entities__id__dest,inventory__id__dest,";
	$sql .= "inventory_item_number_orig,users__id__dest,inventory_name_dest,is_incoming,qty,varref_status,payload,ts_created,ts_updated,inventory_transfers__id__dest,org_entities__id__orig) VALUES (";
	$sql .= $remote_id .",";
	$sql .= "0,";
	$sql .= "NULL,";
	$sql .= "'". mysql_real_escape_string($this_store) ."',";
	$sql .= intval($_GET["id"]) .",";
	if (isset($_GET["iid"])) $sql .= intval($_GET["iid"]) .",";
	else $sql .= "NULL,";
	$sql .= $USER["id"] .",";
	$sql .= "'". mysql_real_escape_string($USER["username"]) ."',";
	$sql .= "1,";
	if (isset($_POST["qty"])) $sql .= intval($_POST["qty"]) .",";
	else $sql .= "1,";
	$sql .= "1,NULL,NOW(),NOW(),";
	$sql .= $local_transfer_id .",{$remote_store})";
	//mysql_query($sql,$DB2) or die(mysql_error($DB2) ."::". $sql);
  mysql_query($sql) or die(mysql_error() ."::". $sql);
  //$remote_transfer_id = mysql_insert_id($DB2);
	$remote_transfer_id = mysql_insert_id();

	// STRICKEN CODE:
	// Quantity should only be incremented when the transfer is complete.
	// The products/items "live" in the inventory_transfers table.
	//
	//if (isset($_POST["qty"])) {
	//	$sql = "UPDATE inventory SET qty = qty + ".intval($_POST["qty"]) ." WHERE inventory_id = ". $remote_id;
	//	mysql_query($sql,$DB2) or die(mysql_error() ."::". $sql);
	//}

	mysql_query("UPDATE inventory_transfers SET inventory_transfers__id__dest = $remote_transfer_id WHERE org_entities__id__orig = {$this_store} AND id = $local_transfer_id");//,$DB);

	//mysql_close($DB2);
	//mysql_close($DB);
	//$DB = mysql_connect($db_host,$db_user,$db_pass,true);
	//mysql_select_db($db_database);

	$qty = 1;
	if (isset($_POST["qty"])) $qty = intval($_POST["qty"]);

	return $qty ." units of inventory ID ". intval($_GET["id"]) ." being transferred to store #". $remote_store .".";
}

function add_inv_to_cart() {
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
	include dirname(__FILE__) ."/../pos/cart.php";
	$from_table = "";
	$from_key = "";
	$writeback = "";
	$INV = array();
	if (isset($_GET["iid"])) {
		$from_table = "inventory_items";
		$from_key_name = "id";
		$from_key = intval($_GET["iid"]);
		$result = mysql_query("SELECT * FROM inventory i LEFT JOIN inventory_items ii ON i.id = ii.inventory__id WHERE i.org_entities__id = {$USER['org_entities__id']} AND ii.id = $from_key");
		if (!mysql_num_rows($result)) {
			$RESPONSE = "Item not found.";
			include "views/index.php";
			return;
		}
		$INV = mysql_fetch_assoc($result);
		$writeback = "item_status";
	} else {
		$from_table = "inventory";
		$from_key_name = "id";
		$from_key = intval($_GET["id"]);
		$result = mysql_query("SELECT * FROM inventory i WHERE org_entities__id = {$USER['org_entities__id']} AND i.id = $from_key");
		if (!mysql_num_rows($result)) {
			$RESPONSE = "Item not found.";
			include "views/index.php";
			return;
		}
		$INV = mysql_fetch_assoc($result);
		$writeback = "qty";
	}
	add_to_cart($from_table,$from_key_name,$from_key,$writeback,$INV["cost"],1,$INV["name"],$INV["is_taxable"] ? "1":"0",new_salt(10),0,0);
	$RESPONSE = "Item added to cart.";
	include "views/index.php";
}

function clear_log() {
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	if (isset($_GET["iid"])) {
		$sql = "DELETE FROM inventory_changes WHERE org_entities__id = {$USER['org_entities__id']} AND CAST(ts AS date) < '". mysql_real_escape_string($_POST["clear_dt"]) ."' AND inventory_item_number = ".intval($_GET["iid"]);
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		$result = mysql_query("SELECT id FROM inventory_items WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($_GET["iid"]));
		if (!mysql_num_rows($result)) {
			$RESPONSE = "Item not found.";
			include "views/index.php";
			return;
		}
		$INV = mysql_fetch_assoc($result);
		inv_change_log($INV["id"],intval($_GET["iid"]),10,false,false,$_POST["reason"],false);
	} else {
		$sql = "DELETE FROM inventory_changes WHERE org_entities__id = {$USER['org_entities__id']} AND CAST(ts AS date) < '". mysql_real_escape_string($_POST["clear_dt"]) ."' AND inventory__id = ".intval($_GET["id"])." AND inventory_item_number IS NULL";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}

	$RESPONSE = "Inventory change log has been cleared before ".$_POST["clear_dt"].".";
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
				include "views/view.php";
			}
			break;
		case "inventory_item":
			if (TFD_HAS_PERMS('admin','use')) { // Take them to the edit item page
				$result = mysql_query($SQL_EDIT_ITEM . $product->id);
				if (!mysql_num_rows($result)) {
					$RESPONSE = "Item not found.";
					include "views/index.php";
					break;
				} else {
					$ITEM = mysql_fetch_assoc($result);
					include "views/edit_item.php";
				}
			} else { // Take them to the product header view
				$result = mysql_query("SELECT inventory__id FROM inventory_items WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".$product->id);
				if (!mysql_num_rows($result)) {
					$RESPONSE = "Product not found.";
					include "views/index.php";
					break;
				} else {
					$inv = mysql_fetch_assoc($result);
					$result = mysql_query($SQL_VIEW_PRODUCT . $inv["inventory__id"]);
					if (!mysql_num_rows($result)) {
						$RESPONSE = "Product not found.";
						include "views/index.php";
						return;
					} else {
						$ITEM = mysql_fetch_assoc($result);
						include "views/view.php";
					}
				}
			}
			break;
		case "upc":
			$result = mysql_query("SELECT id FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND upc = '". mysql_real_escape_string($product->id) ."'");
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
				include "views/view.php";
			}
			break;
		default:
			$RESPONSE = "That UPC is not an inventory item.";
			include "views/index.php";
			break;
	}
}

function add_inv() {
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	if (strlen($_POST["f_upc"]) > 0) {
		$result = mysql_query("SELECT 1 FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND upc = '".mysql_real_escape_string($_POST["f_upc"])."' LIMIT 1");
		if (mysql_num_rows($result)) {
			$RESPONSE = "That UPC is already in the database.";
			include "views/add.php";
			return;
		}
		$upc = "'".mysql_real_escape_string($_POST["f_upc"])."'";
	} else {
		$upc = "NULL";
	}

	$qty = ($_POST["f_tracking"] == "1" ? intval($_POST["f_qty"]) : "0");

	$sql = "INSERT INTO inventory (upc,descr,purchase_price,cost,is_taxable,item_type_lookup,name,qty,is_qty,do_notify_low_qty,low_qty,org_entities__id) VALUES (";
	$sql .= $upc .",";
	$sql .= "'". mysql_real_escape_string($_POST["f_descr"]) ."',";
	$sql .= "'". floatval($_POST["f_cost"]) ."',";
	$sql .= "'". floatval($_POST["f_retail"]) ."',";
	$sql .= intval($_POST["f_taxable"]) .",";
	$sql .= intval($_POST["f_category"]) .",";
	$sql .= "'". mysql_real_escape_string($_POST["f_name"]) ."',";
	$sql .= $qty .",";
	$sql .= ($_POST["f_tracking"] == "1" ? "1" : "0") .",";
	$sql .= ($_POST["f_is_lowqty"] == "1" ? "1" : "0") .",";
	$sql .= ($_POST["f_is_lowqty"] == "1" ? intval($_POST["f_lowqty"]) : "0") .",{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$inv_id = mysql_insert_id();

	inv_change_log($inv_id,false,1,($qty > 0 ? $qty : 1),false,"Added Product '{$_POST["f_name"]}'",false);

	if ($_POST["f_tracking"] == "2") {
		if ($_POST["f_dev"] == "1") {
			$sql = "INSERT INTO inventory_type_devices (categories__id,manufacturer,model,serial_number,operating_system,has_charger,username,password,in_store_location,customers__id,inventory_item_number,org_entities__id) VALUES (";
			$sql .= intval($_POST["f_category"]) .",";
			$sql .= "'". mysql_real_escape_string($_POST["f_dev_mfc"]) ."',";
			$sql .= "'". mysql_real_escape_string($_POST["f_dev_model"]) ."',";
			$sql .= "'". mysql_real_escape_string($_POST["f_sn"]) ."',";
			$sql .= "'". mysql_real_escape_string($_POST["f_dev_os"]) ."',";
			$sql .= intval($_POST["f_dev_charger"]) .",";
			$sql .= "'". mysql_real_escape_string($_POST["f_dev_uname"]) ."',";
			$sql .= "'". mysql_real_escape_string($_POST["f_dev_passw"]) ."',";
			$sql .= intval($_POST["f_location"]) .",";
// 			if (isset($CONFIG["magicno_cust_store"])) {
// 				$sql .= intval($CONFIG["magicno_cust_store"]) .",";
// 			} else {
// 				$sql .= "NULL,";
// 			}
			$sql .= '1,'; // customer number 1 is self (the organization)
			$sql .= $inv_id .",{$USER['org_entities__id']})";
			mysql_query($sql) or die(mysql_error() ."::". $sql);

			$dev_id = mysql_insert_id();
		}


		$sql = "INSERT INTO inventory_items (inventory__id,notes,sn,issues__id,varref_status,in_store_location,item_type_lookup,org_entities__id) VALUES (";
		$sql .= $inv_id .",";
		$sql .= "'". mysql_real_escape_string($_POST["f_notes"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["f_sn"]) ."',";
		$sql .= "NULL,";
		$sql .= intval($_POST["f_status"]) .",";
		$sql .= intval($_POST["f_location"]) .",";
		if (isset($dev_id)) {
			$sql .= $dev_id ;
		} else {
			$sql .= "NULL";
		}
		$sql .= $USER['org_entities__id'].')';
		mysql_query($sql) or die(mysql_error() ."::". $sql);
		$ii_id = mysql_insert_id();

		inv_change_log($inv_id,$ii_id,2,false,intval($_POST["f_location"]),"Added First Item, S/N: '{$_POST["f_sn"]}'",intval($_POST["f_status"]));

		if (isset($dev_id)) {
			mysql_query("UPDATE inventory_type_devices SET inventory_item_number = $ii_id WHERE org_entities__id = {$USER['org_entities__id']} AND id = $dev_id");

			inv_change_log($inv_id,$ii_id,7,false,false,false,false);
		}
	}

	$RESPONSE = "Product added. ".alink("View","?module=inventory&do=view&id=$inv_id");
	include "views/index.php";
}

function add_item_inv() {
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	$result = mysql_query("SELECT id,item_type_lookup FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($_GET["id"]));
	if (!mysql_num_rows($result)) {
		$RESPONSE = "Item not found.";
		include "views/index.php";
		return;
	}
	$INV = mysql_fetch_assoc($result);

	$sql = "INSERT INTO inventory_items (inventory__id,notes,sn,issues__id,varref_status,in_store_location,item_type_lookup,org_entities__id) VALUES (";
	$sql .= $INV["inventory__id"] .",";
	$sql .= "'". mysql_real_escape_string($_POST["notes"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["sn"]) ."',";
	$sql .= "NULL,";
	$sql .= intval($_POST["status"]) .",";
	$sql .= intval($_POST["location"]) .",";
	$sql .= "NULL,{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$ii_id = mysql_insert_id();

	inv_change_log($INV["inventory__id"],$ii_id,2,false,intval($_POST["location"]),"Added Item, S/N: '{$_POST["sn"]}'",intval($_POST["status"]));

	unset($dev_id);

	if (isset($_POST["device_info"])) {
		$sql = "INSERT INTO inventory_type_devices (categories__id,manufacturer,model,serial_number,operating_system,has_charger,username,password,in_store_location,customers__id,inventory_item_number,org_entities__id) VALUES (";
		$sql .= intval($INV["item_type_lookup"]) .",";
		$sql .= "'". mysql_real_escape_string($_POST["dev_mfc"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["dev_model"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["sn"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["dev_os"]) ."',";
		$sql .= (isset($_POST["dev_charger"]) ? "1":"0") .",";
		$sql .= "'". mysql_real_escape_string($_POST["dev_uname"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["dev_passw"]) ."',";
		$sql .= intval($_POST["location"]) .",";
// 		if (isset($CONFIG["magicno_cust_store"])) {
// 			$sql .= intval($CONFIG["magicno_cust_store"]) .",";
// 		} else {
// 			$sql .= "NULL,";
// 		}
		$sql .= '1,'; // customer number 1 is self (the organization)
		$sql .= $ii_id .",{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		$dev_id = mysql_insert_id();
		mysql_query("UPDATE inventory_items SET item_type_lookup = $dev_id WHERE org_entities__id = {$USER['org_entities__id']} AND id = $ii_id");

		inv_change_log($INV["id"],$ii_id,7,false,false,false,false);
	}

	$RESPONSE = "Item added.";
	$result = mysql_query($SQL_VIEW_PRODUCT . intval($_GET["id"]));
	if (!mysql_num_rows($result)) {
		$RESPONSE = "Product not found.";
		include "views/index.php";
		break;
	} else {
		$ITEM = mysql_fetch_assoc($result);
		include "views/view.php";
	}
}

function edit_inv() {
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	if (!isset($_POST["upc"])) $_POST["upc"] = "";

	if (strlen($_POST["upc"]) > 0) {
		$result = mysql_query("SELECT 1 FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND upc = '".mysql_real_escape_string($_POST["upc"])."' AND id != ".intval($_GET["id"])." LIMIT 1");
		if (mysql_num_rows($result)) {
			$RESPONSE = "That UPC is already in the database.";
			$result = mysql_query("SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.id as category__id,i.name,i.qty,i.is_qty,i.do_notify_low_qty,i.low_qty,(SELECT COUNT(*) FROM inventory_items ii WHERE ii.org_entities__id = {$USER['org_entities__id']} AND ii.inventory__id = i.id) AS iqty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND i.id = ".intval($_GET["id"]));
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Product not found.";
				include "views/index.php";
				return;
			} else {
				$ITEM = mysql_fetch_assoc($result);
				include "views/edit.php";
			}
			return;
		}
		$upc = "'".mysql_real_escape_string($_POST["upc"])."'";
	} else {
		$upc = "NULL";
	}

	$result = mysql_query("SELECT * FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". intval($_GET["id"]));
	$INV = mysql_fetch_assoc($result);

	$sql = "UPDATE inventory SET ";
	$sql .= "upc = $upc,";
	$sql .= "descr = '". mysql_real_escape_string($_POST["descr"]) ."',";
	$sql .= "purchase_price = '". floatval($_POST["cost"]) ."',";
	$sql .= "cost = '". floatval($_POST["retail"]) ."',";
	$sql .= "is_taxable = ". (isset($_POST["taxable"]) ? "1":"0") .",";
	$sql .= "item_type_lookup = ". intval($_POST["category"]) .",";
	if (isset($_POST["qty"])) {
		$sql .= "qty = ". intval($_POST["qty"]) .",";
	}
	$sql .= "name = '". mysql_real_escape_string($_POST["name"]) ."',";
	$sql .= "do_notify_low_qty = ". (isset($_POST["is_lowqty"]) ? "1":"0") .",";
	$sql .= "low_qty = ". (isset($_POST["is_lowqty"]) ? intval($_POST["lowqty"]) : "0");
	$sql .= " WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	// Scan for data changes and enter them in the log
	$changes = array();
	$c_qty = false;
	if ($INV["upc"]) {
		if ($upc == "NULL") $changes[] = "UPC:NULL";
		else if ($_POST["upc"] != $INV["upc"]) $changes[] = "UPC:".str_replace(":",";",str_replace("|","!",$_POST["upc"]));
	} else {
		if ($upc != "NULL") $changes[] = "UPC:".str_replace(":",";",str_replace("|","!",$_POST["upc"]));
	}
	if (strlen($_POST["descr"]) != strlen($INV["descr"])) $changes[] = "DSC:". (strlen($_POST["descr"]) - strlen($INV["descr"]));
	if (floatval($_POST["cost"]) != floatval($INV["purchase_price"])) $changes[] = "CST:".floatval($_POST["cost"]);
	if (floatval($_POST["retail"]) != floatval($INV["cost"])) $changes[] = "RTL:".floatval($_POST["retail"]);
	if ((isset($_POST["taxable"]) && !$INV["is_taxable"]) || (!isset($_POST["taxable"]) && $INV["taxable"])) $changes[] = "TX:".(isset($_POST["taxable"]) ? "1":"0");
	if (intval($_POST["category"]) != intval($INV["item_type_lookup"])) $changes[] = "CAT:".intval($_POST["category"]);
	if (isset($_POST["qty"]) && $INV["qty"] != intval($_POST["qty"])) {
		$c_qty = intval($_POST["qty"]);
		$changes[] = "QTY:".intval($_POST["qty"]);
	}
	if ($_POST["name"] != $INV["name"]) $changes[] = "N:".str_replace(":",";",str_replace("|","!",$_POST["name"]));
	if ((isset($_POST["is_lowqty"]) && !$INV["do_notify_low_qty"]) || (!isset($_POST["is_lowqty"]) && $INV["do_notify_low_qty"])) $changes[] = "LQ:".(isset($_POST["is_lowqty"]) ? intval($_POST["lowqty"]):"D");

	$changeStr = join("|",$changes);
	inv_change_log(intval($_GET["id"]),false,3,$c_qty,false,$changeStr,false,$_POST["reason"]);

	$RESPONSE = "Product Updated.";
	$result = mysql_query($SQL_VIEW_PRODUCT . intval($_GET["id"]));
	if (!mysql_num_rows($result)) {
		$RESPONSE = "Product not found.";
		include "views/index.php";
		return;
	} else {
		$ITEM = mysql_fetch_assoc($result);
		include "views/view.php";
	}
}

function edit_item_inv() {
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	unset($dev_id);

	$result = mysql_query("SELECT i.id,i.item_type_lookup FROM inventory i JOIN inventory_items ii ON i.id = ii.inventory__id WHERE i.org_entities__id = {$USER['org_entities__id']} AND ii.id = ".intval($_GET["id"]));
	if (!mysql_num_rows($result)) {
		$RESPONSE = "Item not found.";
		include "views/index.php";
		return;
	}
	$DEVICE = mysql_fetch_assoc($result);

	// Optionally create entry in devices table for issue linking
	if (isset($_POST["device_info"])) {
		$sql = "INSERT INTO inventory_type_devices (categories__id,manufacturer,model,serial_number,operating_system,has_charger,username,password,in_store_location,customers__id,inventory_item_number,org_entities__id) VALUES (";
		$sql .= $DEVICE["item_type_lookup"] .",";
		$sql .= "'". mysql_real_escape_string($_POST["dev_mfc"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["dev_model"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["sn"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["dev_os"]) ."',";
		$sql .= (isset($_POST["dev_charger"]) ? "1":"0") .",";
		$sql .= "'". mysql_real_escape_string($_POST["dev_uname"]) ."',";
		$sql .= "'". mysql_real_escape_string($_POST["dev_passw"]) ."',";
		$sql .= intval($_POST["location"]) .",";
// 		if (isset($CONFIG["magicno_cust_store"])) {
// 			$sql .= intval($CONFIG["magicno_cust_store"]) .",";
// 		} else {
// 			$sql .= "NULL,";
// 		}
		$sql .= '1,'; // customer number 1 is self (the organization)
		$sql .= intval($_GET["id"]) .",{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		$dev_id = mysql_insert_id();

		inv_change_log($DEVICE["id"],intval($_GET["id"]),7,false,false,false,false);
	}

	$result = mysql_query("SELECT * FROM inventory_items WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". intval($_GET["id"]));
	$II = mysql_fetch_assoc($result);

	// Update inventory_items table
	$sql = "UPDATE inventory_items SET ";
	$sql .= "sn = '". mysql_real_escape_string($_POST["sn"]) ."',";
	$sql .= "notes = '". mysql_real_escape_string($_POST["notes"]) ."',";
	$sql .= "varref_status = ". intval($_POST["status"]) .",";
	if (isset($dev_id)) {
		$sql .= "item_type_lookup = ". $dev_id .",";
	}
	$sql .= "in_store_location = ". intval($_POST["location"]);
	$sql .= " WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	// Scan for changes and add to change log
	$changes = array();
	$new_status = false;
	if ($II["sn"] != $_POST["sn"]) $changes[] = "SN:".str_replace(":",";",str_replace("|","!",$_POST["sn"]));
	if (strlen($II["notes"]) != strlen($_POST["notes"])) $changes[] = "NT:".(strlen($_POST["notes"]) - strlen($II["notes"]));
	if (intval($_POST["status"]) != intval($II["varref_status"])) {
		$changes[] = "ST:".intval($_POST["status"]);
		$new_status = intval($_POST["status"]);
	}
	if (isset($dev_id)) $changes[] = "DVC:".$dev_id;
	if (intval($_POST["location"]) != intval($II["in_store_location"])) $changes[] = "LOC:".intval($_POST["location"]);

	$changeStr = join("|",$changes);
	inv_change_log($DEVICE["id"],intval($_GET["id"]),3,false,false,$changeStr,$new_status,$_POST["reason"]);

	// Update location in devices table if device_id is present
	$result = mysql_query("SELECT item_type_lookup FROM inventory_items WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". intval($_GET["id"]));
	$iid = mysql_fetch_assoc($result);
	if ($iid) {
		mysql_query("UPDATE inventory_type_devices SET in_store_location = ". intval($_POST["location"]) ." WHERE org_entities__id = {$USER['org_entities__id']} AND id = ". $iid["item_type_lookup"]);
	}

	// Redirect back to the form
	$RESPONSE = "Item Updated.";
	$result = mysql_query($SQL_EDIT_ITEM . intval($_GET["id"]));
	if (!mysql_num_rows($result)) {
		$RESPONSE = "Item not found.";
		include "views/index.php";
		return;
	} else {
		$ITEM = mysql_fetch_assoc($result);
		include "views/edit_item.php";
	}
}

/*
 * These may end up being janitorial functions but they are not the preferred procedure for removing inventory.
 * The "Junk" status will be used, and once in that status a device may be "Recycled" which deletes it and its
 * device info from the database but preserves any attached issues.
 *
function delete_inv() {
	//TODO
}

function delete_item_inv() {
	extract($GLOBALS, EXTR_REFS | EXTR_SKIP);

	$result = mysql_query("SELECT inventory_id,device_id FROM inventory_items WHERE inv_item_id = ". intval($_GET["id"]));
	if (!mysql_num_rows($result)) {
		$RESPONSE = "Item not found.";
		include "views/index.php";
		return;
	}
	$II = mysql_fetch_assoc($result);
	if ($II["device_id"]) {
		$sql = "DELETE FROM devices WHERE device_id = ". $II["device_id"];
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}

	$sql = "DELETE FROM inventory_items WHERE inv_item_id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$RESPONSE = "Item deleted.";
	$result = mysql_query($SQL_VIEW_PRODUCT . $II["inventory_id"]);
	if (!mysql_num_rows($result)) {
		$RESPONSE = "Product not found.";
		include "views/index.php";
		return;
	} else {
		$ITEM = mysql_fetch_assoc($result);
		include "views/view.php";
	}
}
*/

function inv_change_log($inv_id,$inv_item_id,$code,$qty,$location,$descr,$status,$reason = null) {
	global $USER;
	$sql = "INSERT INTO inventory_changes (inventory__id,inventory_item_number,users__id,varref_change_code,qty,in_store_location,descr,varref_status,reason,org_entities__id) VALUES (";
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
	if ($reason) $sql .= "'". mysql_real_escape_string($reason) ."'";
	else $sql .= "NULL";
	$sql .= ",{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
	return mysql_insert_id();
}

?>
