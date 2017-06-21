<?php

if (!isset($USER)) {
	header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit;
}

if (isset($_GET["do"])) {
	switch ($_GET["do"]) {
		case "add_note":
			add_note();
			$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
			$CUSTOMER = mysql_fetch_assoc($result);
			set_customer(intval($_GET["id"]));
			display_header();
			include "views/view.php";
			display_footer();
			break;
		case "cpp":
			$salt = new_salt(10);
			$C_PASS = strtoupper(new_salt(8));
			$phash = md5($C_PASS.$salt);
			mysql_query("UPDATE customers SET user_salt = '$salt', user_pass = '$phash' WHERE id = ".intval($_GET["id"]));
			$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Customer ID not found.";
				display_header();
				include "views/index.php";
				display_footer();
			} else {
				$CUSTOMER = mysql_fetch_assoc($result);
				include "views/cpp.php";
			}
			break;
		case "purchase":
			if (isset($_POST["f_category"]) && isset($_GET["id"]) && intval($_GET["id"]) != 0) {
				$RESPONSE = purchase();
				$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
				$CUSTOMER = mysql_fetch_assoc($result);
				display_header();
				include "views/view.php";
				display_footer();
			} else {
				display_header();
				include "views/purchase_inv.php";
				display_footer();
			}
			break;
		case "edit_dev":
			mysql_query("UPDATE sessions SET inventory_items__id = ".intval($_GET["id"])." WHERE id = '{$SESSION["id"]}'");
			if (isset($_POST["device_type"])) {
				$RESPONSE = edit_device();
				$result = mysql_query("SELECT * FROM customers c JOIN inventory_type_devices d ON d.customers__id = c.id WHERE d.id = ".intval($_GET["id"]));
				$CUSTOMER = mysql_fetch_assoc($result);
				display_header();
				include "views/view.php";
				display_footer();
			} else {
				$result = mysql_query("SELECT * FROM inventory_type_devices WHERE id = ".intval($_GET["id"]));
				if (!mysql_num_rows($result)) {
					$RESPONSE = "Device not found.";
					display_header();
					include "views/index.php";
					display_footer();
				} else {
					$DEVICE = mysql_fetch_assoc($result);
					display_header();
					include "views/edit_device.php";
					display_footer();
				}
			}
			break;
		case "add_dev":
			$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
			$CUSTOMER = mysql_fetch_assoc($result);
			display_header();
			include "views/add_device.php";
			display_footer();
			break;
		case "add_dev_sub":
			$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
			$CUSTOMER = mysql_fetch_assoc($result);
			$device_id = add_device();
			mysql_query("UPDATE sessions SET inventory_items__id = {$device_id} WHERE id = '{$SESSION["id"]}'");
			display_header();
			include "views/view.php";
			display_footer();
			break;
		case "feedback":
			$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
			$CUSTOMER = mysql_fetch_assoc($result);
			display_header();
			include "views/feedback.php";
			display_footer();
			break;
		case "feedback_sub":
			$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
			$CUSTOMER = mysql_fetch_assoc($result);
			add_feedback();
			display_header();
			include "views/view.php";
			display_footer();
			break;
		case "new":
			if (isset($_POST["lastname"])) {
				$CUSTOMER = new_customer();
				set_customer($CUSTOMER["id"]);
				display_header();
				include "views/view.php";
				display_footer();
			} else {
				display_header();
				include "views/new_customer.php";
				display_footer();
			}
			break;
		case "edit":
			$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
			$CUSTOMER = mysql_fetch_assoc($result);
			display_header();
			include "views/edit.php";
			display_footer();
			break;
		case "edit_sub":
			$CUSTOMER = edit_customer();
			display_header();
			include "views/view.php";
			display_footer();
			break;
		case "list":
			$sortby = "lastname";
			if (isset($_GET["sort"])) {
				$sortby = mysql_real_escape_string($_GET["sort"]);
			}
			$CUSTOMERS = mysql_query("SELECT * FROM customers ORDER BY ".$sortby);
			display_header();
			include "views/list.php";
			display_footer();
			break;
		case "view":
			$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
			$CUSTOMER = mysql_fetch_assoc($result);
			set_customer(intval($_GET["id"]));
			display_header();
			include "views/view.php";
			display_footer();
			break;
		case "search":
			display_header();
			include "views/search.php";
			display_footer();
			break;
		default:
			display_header();
			include "views/index.php";
			display_footer();
			break;
	}
} else {
	display_header();
	include "views/index.php";
	display_footer();
}

function add_note() {
	global $USER;
	$sql = "INSERT INTO user_notes (for_table,for_key,note,users__id,note_ts) VALUES (";
	$sql .= "'customers',";
	$sql .= intval($_GET["id"]) .",";
	$sql .= "'". mysql_real_escape_string($_POST["note"]) ."',";
	$sql .= $USER["id"] .",";
	$sql .= "NOW())";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
}

function purchase() {
	global $USER,$CONFIG;
	$INVENTORY_ID = 0;
	if (isset($_POST["epf_prod_id"]) && intval($_POST["epf_prod_id"]) != 0) {
		$INVENTORY_ID = intval($_POST["epf_prod_id"]);

		// Possibly add new item to existing product line
		if ($_POST["epf_indiv"] == "1") { // Add item
			if ($_POST["nif_dev"] == "1") {
				$result = mysql_query("SELECT item_type_lookup FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".$INVENTORY_ID);
				$data = mysql_fetch_assoc($result);

				$sql = "INSERT INTO inventory_type_devices (categories__id,manufacturer,model,device_serial_number,operating_system,has_charger,username,password,org_entities__id,customers__id,inventory_item_number) VALUES (";
				$sql .= $data["item_type_lookup"] .",";
				$sql .= "'". mysql_real_escape_string($_POST["nif_dev_mfc"]) ."',";
				$sql .= "'". mysql_real_escape_string($_POST["nif_dev_model"]) ."',";
				$sql .= "'". mysql_real_escape_string($_POST["nif_sn"]) ."',";
				$sql .= "'". mysql_real_escape_string($_POST["nif_dev_os"]) ."',";
				$sql .= intval($_POST["nif_dev_charger"]) .",";
				$sql .= "'". mysql_real_escape_string($_POST["nif_dev_uname"]) ."',";
				$sql .= "'". mysql_real_escape_string($_POST["nif_dev_passw"]) ."',";
				$sql .= intval($_POST["nif_location"]) .",";
// 				if (isset($CONFIG["magicno_cust_store"])) {
// 					$sql .= intval($CONFIG["magicno_cust_store"]) .",";
// 				} else {
// 					$sql .= "NULL,";
// 				}
				$sql .= '1,'; // customer number 1 is self (the organization)
				$sql .= $INVENTORY_ID .")";
				mysql_query($sql) or die(mysql_error() ."::". $sql);

				$dev_id = mysql_insert_id();
			}

			$sql = "INSERT INTO inventory_items (inventory__id,notes,sn,issues__id,varref_status,org_entities__id,item_table_lookup) VALUES (";
			$sql .= $INVENTORY_ID .",";
			$sql .= "'". mysql_real_escape_string($_POST["nif_notes"]) ."',";
			$sql .= "'". mysql_real_escape_string($_POST["nif_sn"]) ."',";
			$sql .= "NULL,";
			$sql .= intval($_POST["nif_status"]) .",";
			$sql .= intval($_POST["nif_location"]) .",";
			if (isset($dev_id)) {
				$sql .= $dev_id .")";
			} else {
				$sql .= "NULL)";
			}
			mysql_query($sql) or die(mysql_error() ."::". $sql);
			$ii_id = mysql_insert_id();

			inv_change_log($INVENTORY_ID,$ii_id,2,false,intval($_POST["f_location"]),"Added First Item, S/N: '{$_POST["f_sn"]}'",intval($_POST["f_status"]));

			if (isset($dev_id)) {
				mysql_query("UPDATE inventory_type_devices SET inventory_item_number = $ii_id WHERE id = $dev_id");

				inv_change_log($INVENTORY_ID,$ii_id,7,false,false,false,false);
			}
		}

	} else { // Add New Product
		$upc = "";

		if (strlen($_POST["f_upc"]) > 0) {
			$result = mysql_query("SELECT 1 FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND upc = '".mysql_real_escape_string($_POST["f_upc"])."' LIMIT 1");
			if (mysql_num_rows($result)) {
				$RESPONSE = "That UPC is already in the database.";
				extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
				display_header();
				include "views/purchase_inv.php";
				display_footer();
				exit;
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
		$sql .= "0,";
		$sql .= ($_POST["f_tracking"] == "1" ? "1" : "0") .",";
		$sql .= ($_POST["f_is_lowqty"] == "1" ? "1" : "0") .",";
		$sql .= ($_POST["f_is_lowqty"] == "1" ? intval($_POST["f_lowqty"]) : "0") .",{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);

		$INVENTORY_ID = mysql_insert_id();

		inv_change_log($INVENTORY_ID,false,1,($qty > 0 ? $qty : 1),false,"Added Product '{$_POST["f_name"]}'",false);

		if ($_POST["f_tracking"] == "2") { // Individually tracked, add item
			if ($_POST["f_dev"] == "1") {
				$sql = "INSERT INTO inventory_type_devices (categories__id,manufacturer,model,device_serial_number,operating_system,has_charger,username,password,org_entities__id,customers__id,inventory_item_number) VALUES (";
				$sql .= intval($_POST["f_category"]) .",";
				$sql .= "'". mysql_real_escape_string($_POST["f_dev_mfc"]) ."',";
				$sql .= "'". mysql_real_escape_string($_POST["f_dev_model"]) ."',";
				$sql .= "'". mysql_real_escape_string($_POST["f_sn"]) ."',";
				$sql .= "'". mysql_real_escape_string($_POST["f_dev_os"]) ."',";
				$sql .= intval($_POST["f_dev_charger"]) .",";
				$sql .= "'". mysql_real_escape_string($_POST["f_dev_uname"]) ."',";
				$sql .= "'". mysql_real_escape_string($_POST["f_dev_passw"]) ."',";
				$sql .= intval($_POST["f_location"]) .",";
// 				if (isset($CONFIG["magicno_cust_store"])) {
// 					$sql .= intval($CONFIG["magicno_cust_store"]) .",";
// 				} else {
// 					$sql .= "NULL,";
// 				}
				$sql .= '1,'; // customer number 1 is self (the organization)
				$sql .= $INVENTORY_ID .")";
				mysql_query($sql) or die(mysql_error() ."::". $sql);

				$dev_id = mysql_insert_id();
			}

			$sql = "INSERT INTO inventory_items (inventory__id,notes,sn,issues__id,varref_status,org_entities__id,item_table_lookup) VALUES (";
			$sql .= $INVENTORY_ID .",";
			$sql .= "'". mysql_real_escape_string($_POST["f_notes"]) ."',";
			$sql .= "'". mysql_real_escape_string($_POST["f_sn"]) ."',";
			$sql .= "NULL,";
			$sql .= intval($_POST["f_status"]) .",";
			$sql .= intval($_POST["f_location"]) .",";
			if (isset($dev_id)) {
				$sql .= $dev_id .")";
			} else {
				$sql .= "NULL)";
			}
			mysql_query($sql) or die(mysql_error() ."::". $sql);
			$ii_id = mysql_insert_id();

			inv_change_log($INVENTORY_ID,$ii_id,2,false,intval($_POST["f_location"]),"Added First Item, S/N: '{$_POST["f_sn"]}'",intval($_POST["f_status"]));

			if (isset($dev_id)) {
				mysql_query("UPDATE inventory_type_devices SET inventory_item_number = $ii_id WHERE id = $dev_id");

				inv_change_log($INVENTORY_ID,$ii_id,7,false,false,false,false);
			}
		}
	}

	$INVENTORY_ITEM = mysql_fetch_assoc(mysql_query("SELECT * FROM inventory WHERE id = ".$INVENTORY_ID));
	$UNIT_COST = (floatval($_POST["epf_price"]) > 0 ? round(floatval($_POST["epf_price"]),2) : round(floatval($_POST["f_cost"]),2));
	if ($_POST["f_tracking"] == "1") {
		$TOTAL = $UNIT_COST * intval($_POST["f_qty"]);
	} else {
		$TOTAL = $UNIT_COST;
	}
	$sql = "INSERT INTO inventory_items_customer (customers__id,inventory__id,inventory_item_number,qty,unit_cost,total_cost,serial_numbers) VALUES (";
	$sql .= intval($_GET["id"]) .",";
	$sql .= $INVENTORY_ID .",";
	$sql .= (isset($ii_id) ? $ii_id : "NULL") .",";
	if ($_POST["f_tracking"] == "1") {
		$sql .= intval($_POST["f_qty"]) .",";
	} else {
		$sql .= "0,";
	}
	$sql .= "'". $UNIT_COST ."',";
	$sql .= "'". $TOTAL ."',";
	if (isset($_POST["f_sn"]) && $_POST["f_sn"] != "") {
		$sql .= "'". mysql_real_escape_string($_POST["f_sn"]) ."')";
	} else {
		$sql .= "'". mysql_real_escape_string($_POST["nif_sn"]) ."')";
	}
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$CID = mysql_insert_id();

	if ($_POST["f_tracking"] == "1") {
		mysql_query("UPDATE inventory SET qty = qty + ".intval($_POST["f_qty"])." WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".$INVENTORY_ID);
	}

	$sql = "INSERT INTO pos_cash_log (users__id,amt,reason,ts,is_reset,pos_transactions__id,is_checks,is_drop,is_deposited,org_entities__id) VALUES (";
	$sql .= $USER["id"] .",";
	$sql .= "'-".floatval($TOTAL)."',";
	$sql .= "'Inventory Purchase',NOW(),0,NULL,0,0,0,{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$qty = ($_POST["f_tracking"] == "1" ? $_POST["f_qty"] : "1");

	return "Purchased ".$qty." units for a total of $".number_format($TOTAL,2).". ". alink_pop("Receipt","cust/preceipt.php?id=$CID")." ". alink_pop("Print Label","inventory/label.php?id=$INVENTORY_ID");
}

function add_feedback() {
	$id = intval($_GET["id"]);
	$SCORE = intval($_POST["score"]);
	$FEEDBACK = mysql_real_escape_string($_POST["feedback"]);
	$ISSUE_ID = intval($_POST["issue_id"]) > 0 ? intval($_POST["issue_id"]) : "NULL";
	mysql_query("INSERT INTO feedback (customers__id,score,feedback,issues__id,ts) VALUES (".$id.",".$SCORE.",'".$FEEDBACK."',$ISSUE_ID,NOW())");
}

function set_customer($id) {
	global $SESSION;
	$id = intval($id);
	mysql_query("UPDATE sessions SET customer='". $id ."', customer_ts = NOW() WHERE id='". $SESSION["id"] ."'");
}

function new_customer() {
	global $USER;

	if (isset($_POST["referral"])) {
		$result = mysql_query("SELECT * FROM option_values WHERE category='referall_location' AND id = ".intval($_POST["referral"]));
		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			$referral = $data["value"];
		} else {
			$referral = "None";
		}
	} else {
		$referral = "None";
	}

	$matches = array();
	preg_match_all('!\d+!', $_POST["phone_home"], $matches);
	$phone_home = implode('',$matches[0]);
	preg_match_all('!\d+!', $_POST["phone_cell"], $matches);
	$phone_cell = implode('',$matches[0]);

	$sql = "INSERT INTO customers (firstname,lastname,is_male,dob,company,address,city,state,postcode,email,phone_home,"
		  ."phone_cell,referral,v_address,apt,email_add_date,email_added_by) VALUES (";
	$sql .= "'". mysql_real_escape_string($_POST["firstname"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["lastname"]) ."',";
	$sql .= "'". (isset($_POST["is_male"]) ? "1" : "0") ."',";
	$sql .= "'". mysql_real_escape_string("". $_POST["dob_y"] ."-". $_POST["dob_m"] ."-". $_POST["dob_d"] ." ") ."',";
	$sql .= "'". mysql_real_escape_string($_POST["company"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["address"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["city"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["state"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["zip"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["email"]) ."',";
	$sql .= "'". $phone_home ."',";
	$sql .= "'". $phone_cell ."',";
	$sql .= "'". mysql_real_escape_string($referral) ."',";
	if (isset($_POST["v_address"]) && $_POST["v_address"] != "") {
		$sql .= "'". mysql_real_escape_string($_POST["v_address"]) ."',";
	} else {
		$sql .= "NULL,";
	}
	$sql .= "'". mysql_real_escape_string($_POST["apt"]) ."',";
	if (strlen($_POST["email"]) >= 5) {
		$sql .= "'". date("Y-m-d") ."',";
		$sql .= intval($USER["id"]) .")";
	} else {
		$sql .= "NULL,NULL)";
	}
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$id = mysql_insert_id();

	$result = mysql_query("SELECT * FROM customers WHERE id = ".$id);
	$CUSTOMER = mysql_fetch_assoc($result);
	return $CUSTOMER;
}

function edit_customer() {
	global $USER;

	$result = mysql_query("SELECT email FROM customers WHERE id = ". intval($_GET["id"]));
	$data = mysql_fetch_assoc($result);

	$sql = "UPDATE customers SET ";
	$sql .= "firstname = '". mysql_real_escape_string($_POST["firstname"]) ."',";
	$sql .= "lastname = '". mysql_real_escape_string($_POST["lastname"]) ."',";
	$sql .= "is_male = '". (isset($_POST["is_male"]) && $_POST["is_male"] ? "1":"0") ."',";
	$sql .= "dob = '". mysql_real_escape_string($_POST["dob"]) ."',";
	$sql .= "company = '". mysql_real_escape_string($_POST["company"]) ."',";
	$sql .= "address = '". mysql_real_escape_string($_POST["address"]) ."',";
	$sql .= "city = '". mysql_real_escape_string($_POST["city"]) ."',";
	$sql .= "state = '". mysql_real_escape_string($_POST["state"]) ."',";
	$sql .= "postcode = '". mysql_real_escape_string($_POST["zip"]) ."',";
	$sql .= "email = '". mysql_real_escape_string($_POST["email"]) ."',";
	$sql .= "phone_home = '". mysql_real_escape_string(str_replace("-","",trim($_POST["phone_home"]))) ."',";
	$sql .= "phone_cell = '". mysql_real_escape_string(str_replace("-","",trim($_POST["phone_cell"]))) ."',";
	$sql .= "referral = '". mysql_real_escape_string($_POST["referral"]) ."',";
	$sql .= "apt = '". mysql_real_escape_string($_POST["apt"]) ."',";
	if ($data["email"] != $_POST["email"]) {
		$sql .= "email_add_date = '".date("Y-m-d")."',";
		$sql .= "email_added_by = ". $USER["id"] .",";
	}
	if (isset($_POST["v_address"]) && $_POST["v_address"] != '') {
		$sql .= "v_address = '". mysql_real_escape_string($_POST["v_address"]) ."' ";
	} else {
		$sql .= "v_address = NULL ";
	}
	$sql .= "WHERE id = ". intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($_GET["id"]));
	$CUSTOMER = mysql_fetch_assoc($result);

	return $CUSTOMER;
}

function add_device() {
	global $USER,$CUSTOMER;
	$sql = "INSERT INTO inventory_type_devices (categories__id,manufacturer,model,serial_number,operating_system,username,"
		  ."password,in_store_location,customers__id,org_entities__id) VALUES (";
	$sql .= intval($_POST["device_type"]) .",";
	$sql .= "'". mysql_real_escape_string($_POST["device_mfc"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["device_model"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["device_sn"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["device_os"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["device_user"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["device_pass"]) ."',";
	$sql .= intval($_POST["location"]) .",";
	$sql .= intval($CUSTOMER["id"]) .",{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
	return mysql_insert_id();
}

function edit_device() {
	$sql = "UPDATE inventory_type_devices SET ";
	$sql .= "categories__id = ".intval($_POST["device_type"]).",";
	$sql .= "manufacturer = '".mysql_real_escape_string($_POST["mfc"])."',";
	$sql .= "model = '".mysql_real_escape_string($_POST["model"])."',";
	$sql .= "serial_number = '".mysql_real_escape_string($_POST["sn"])."',";
	$sql .= "operating_system = '".mysql_real_escape_string($_POST["os"])."',";
	$sql .= "username = '".mysql_real_escape_string($_POST["user"])."',";
	$sql .= "password = '".mysql_real_escape_string($_POST["pass"])."',";
	$sql .= "in_store_location = ".intval($_POST["location"])." ";
	$sql .= "WHERE id = ".intval($_GET["id"]);
	mysql_query($sql) or die(mysql_error() ."::". $sql);
	return "Device Updated.";
}

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
	if ($reason) $sql .= "'". mysql_real_escape_string($reason) ."')";
	else $sql .= "NULL,{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);
	return mysql_insert_id();
}

?>