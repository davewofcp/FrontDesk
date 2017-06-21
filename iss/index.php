<?php
if (!isset($USER)) { header("Location: ../login.php"); exit; }

if (isset($_GET["ajax"])) {
	if (!isset($USER)) exit;

	switch ($_GET["ajax"]) {
		case "inv_search":
			$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
			//$result = mysql_query("SELECT * FROM inventory WHERE UPPER(item_number) LIKE '%". $search_string ."%' OR UPPER(descr) LIKE '%". $search_string ."%' LIMIT 10") or die("Error: ". mysql_error());
			$result = mysql_query("SELECT * FROM inventory WHERE UPPER(descr) LIKE '%". $search_string ."%' LIMIT 10") or die("Error: ". mysql_error());
			if (mysql_num_rows($result) < 1) {
				echo "No matches.";
				exit;
			}
			echo "<table border=\"0\" width=\"100%\">";
			echo " <tr><td><b>#</b></td><td><b>Description</b></td><td><b>Cost</b></td><td><b>QTY</b></td><td><b>Add</b></td></tr>\n";
			while ($row = mysql_fetch_assoc($result)) {
				echo " <tr>\n";
//				echo "  <td>". $row["item_number"] ."</a></td>\n";
        echo "  <td>". $row["id"] ."</a></td>\n";
				echo "  <td>". $row["descr"] ."</td>\n";
				echo "  <td>$". number_format(floatval($row["cost"]),2) ."</td>\n";
				echo "  <td>". $row["qty"] ."</td>\n";
				echo "  <td><a href=\"?module=iss&do=add_item&id=". $_GET["id"] ."&inv_id=". $row["id"] ."\">Add</a></td>\n";
				echo " </tr>\n";
			}
			echo "</table>\n";
			break;
		default:
			break;
	}
	exit;
}

if (isset($_GET["do"])) {
	switch ($_GET["do"]) {
		case "transfer":
			include "views/transfer.php";
			break;
		case "transfer_sub":
			transfer_issue();
			break;
		case "cust_ack":
			$result = mysql_query("SELECT *,i.id as id,i.customers__id as customers__id FROM issues i LEFT JOIN customers c ON i.customers__id = c.id LEFT JOIN inventory_type_devices d ON i.device_id = d.id LEFT JOIN categories ca ON d.categories__id = ca.id WHERE i.id = ".intval($_GET["id"]));
			if (!mysql_num_rows($result)) {
				$RESPONSE = "Issue #{$_GET["id"]} not found.";
				include "views/index.php";
			} else {
				$ISSUE = mysql_fetch_assoc($result);
				include "views/cust_ack.php";
			}
			break;
		case "add_note":
			$result = mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"]));
			$ISSUE = mysql_fetch_assoc($result);
			$RESPONSE = add_note();
			include "views/view.php";
			break;
		case "add_item":
			$result = mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"]));
			$ISSUE = mysql_fetch_assoc($result);
			if (!$ISSUE) {
				$RESPONSE = "Issue not found.";
				include "views/index.php";
			} else {
				$RESPONSE = add_item();
				include "views/view.php";
			}
			break;
		case "add_inv":
			$result = mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"]));
			$ISSUE = mysql_fetch_assoc($result);
			if (!$ISSUE) {
				$RESPONSE = "Issue not found.";
				include "views/index.php";
			} else {
				$RESPONSE = add_inv(); //Returns mysql_insert_id for `issue_inv`
			  header("Location: ?module=iss&do=view&id=". intval($_GET["id"]));
				//include "views/view.php";
			}
			break;
		case "delete_inv":
				$RESPONSE = delete_inv(); //Returns mysql_insert_id for `issue_inv`
			  header("Location: ?module=iss&do=view&id=". intval($RESPONSE));
				//include "views/view.php";
			break;
		case "remove_item":
			$result = mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"]));
			$ISSUE = mysql_fetch_assoc($result);
			if (!$ISSUE) {
				$RESPONSE = "Issue not found.";
				include "views/index.php";
			} else {
				$RESPONSE = remove_item();
				include "views/view.php";
			}
			break;
		case "resolve":
			$result = mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"]));
			$ISSUE = mysql_fetch_assoc($result);
			if (!$ISSUE) {
				$RESPONSE = "Issue not found.";
				include "views/index.php";
			} else {
				$RESPONSE = resolve($ISSUE["id"]);
				$result = mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"]));
				$ISSUE = mysql_fetch_assoc($result);
				include "views/view.php";
			}
			break;
		case "add_to_cart":
			$result = mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"]));
			$ISSUE = mysql_fetch_assoc($result);
			if ($ISSUE == null) {
				$RESPONSE = "Issue not found.";
				include "views/index.php";
			} else {
				include dirname(__FILE__) ."/../pos/cart.php";
				$grp_id = new_salt(10);
				$CART_ITEM_ID = add_to_cart("issues","id",$_GET["id"],"resolved",$ISSUE["do_price"],"1",$ISSUE["troubledesc"],"1",$grp_id,"1",$ISSUE["customers__id"]);
				//$items = mysql_query("SELECT * FROM issue_items WHERE issue_id = ". intval($_GET["id"]));
				$invs = mysql_query("SELECT * FROM issue_inv WHERE issues__id = ". intval($_GET["id"]) ." AND `add` = 1");
				//$it = 0;
				//while ($item = mysql_fetch_assoc($items)) {
				//	$it++;
				//	$CART_ITEM_ID = add_to_cart("issue_items","issue_item_id",$item["issue_item_id"],"tid",$item["amt"],"1",$item["descr"],$item["taxable"],$grp_id,"0",$ISSUE["customer_id"]);
				//}
				$in = 0;
				while ($inv = mysql_fetch_assoc($invs)) {
					$in++;
					$inventory_item = mysql_fetch_assoc(mysql_query("SELECT * FROM inventory WHERE id = ". $inv["inventory__id"]));
					if (!$inventory_item) continue;
					$CART_ITEM_ID = add_to_cart("issue_inv","id",$inv["id"],"no",$inventory_item["cost"],$inv["qty"],$inventory_item["descr"],$inventory_item["is_taxable"],$grp_id,"0",$ISSUE["customers__id"]);
				}
				$RESPONSE = "Issue ". intval($_GET["id"]) ." added to cart.";

				include "views/view.php";
			}
			break;
		case "new":
			if (isset($_POST["troubledesc"])) {
				$ISSUE = new_issue();
				$RESPONSE = "Issue ". $ISSUE["id"] ." has been created.";
        		header("Location: ?module=iss&do=view&id=". $ISSUE["id"]);
        		exit;
				include "views/view.php";
			} else {
				include "views/new.php";
			}
			break;
		case "view":
			$result = mysql_query("SELECT * FROM issues WHERE id = ". intval($_GET["id"]));
			if (mysql_num_rows($result)) {
				$ISSUE = mysql_fetch_assoc($result);
				set_customer($ISSUE["customers__id"]);
				if ($ISSUE["device_id"]) mysql_query("UPDATE sessions SET inventory_items__id = {$ISSUE["device_id"]} WHERE id = '{$SESSION["id"]}'");
				mysql_query("UPDATE sessions SET issues__id = {$ISSUE["id"]} WHERE id = '{$SESSION["id"]}'");
				include "views/view.php";
			} else {
				$RESPONSE = "Issue #{$_GET["id"]} not found.";
				include "views/index.php";
			}
			break;
		case "update_status":
			$ISSUE = update_status();
			header("Location: ?module=iss&do=view&id=". intval($_GET["id"]));
			//include "views/view.php";
			break;
		case "delete":
			$RESPONSE = delete_issue();
			include "views/index.php";
			break;
		case "hdelete":
			$RESPONSE = hard_delete_issue();
			include "views/index.php";
		case "receipt":
			include "views/receipt.php";
			break;
		case "invoice":
			include "views/invoice.php";
			break;
		case "barcode":
			header("Location: core/barcodelabel.php?id=".$_GET["id"]);
			break;
		default:
			include "views/index.php";
			break;
	}
} else {
	include "views/index.php";
}

function transfer_issue() {
	/*
	$result = mysql_query("SELECT * FROM issues WHERE issue_id = ".intval($_GET["id"]));
	if (!mysql_num_rows($result)) {
		echo "Invalid issue ID.";
		exit;
	}
	$ISSUE = mysql_fetch_assoc($result);
	if ($ISSUE["device_id"]) {
		$result = mysql_query("SELECT * FROM devices d JOIN categories cg ON d.device_type = cg.category_id WHERE device_id = ".intval($ISSUE["device_id"]));
		if (mysql_num_rows($result)) {
			$DEVICE = mysql_fetch_assoc($result);
		}
	}

	$NOTES = array();
	$result = mysql_query("SELECT * FROM notes WHERE for_table = 'issues' AND for_key = ".intval($_GET["id"]));
	while ($row = mysql_fetch_assoc($result)) {
		$NOTES[$row["note_id"]] = $row;
	}

	$result = mysql_query("SELECT * FROM locations WHERE store_number = '".mysql_real_escape_string($_POST["to_store"])."'");
	if (!mysql_num_rows($result)) die("Remote store not found.");
	$STORE = mysql_fetch_assoc($result);
	mysql_close($DB);
	$DB = mysql_connect($STORE["db_host"],$STORE["db_user"],$STORE["db_pass"]) or die("Unable to connect to remote database.");
	mysql_select_db($STORE["db_db"],$DB) or die("Unable to select remote database.");
	*/

	echo "Function not implemented yet.";
	exit;
}

function add_inv() {
  $data = mysql_fetch_assoc(mysql_query("SELECT * FROM inventory WHERE id = ". intval($_GET["inventory_id"])));
  $var="";
  if(!$data){
    die("Cant find inventory item");
  } else {
    $amt = intval($data["qty"]);
    mysql_query("UPDATE inventory SET qty=". $amt ." WHERE id = ". intval($_GET["inventory_id"]));
    mysql_query("INSERT INTO issue_inv (issues__id,inventory__id,qty) VALUES (". intval($_GET["id"]) .",". intval($_GET["inventory_id"]) .",1)");

    return intval(mysql_insert_id());
  }
}

function delete_inv() {
  $data = mysql_fetch_assoc(mysql_query("SELECT * FROM issue_inv WHERE id = ". intval($_GET["issue_inv_id"])));
  $data1 = mysql_fetch_assoc(mysql_query("SELECT * FROM inventory WHERE id = ". intval($data["inventory__id"])));
  $var="";
  if(!$data){
    die("Cant find `issue_inv`.`id` item");
  } else {
    $amt = intval($data1["qty"]) + 1;
    mysql_query("UPDATE inventory SET qty=". $amt ." WHERE id = ". intval($data1["id"]));
    mysql_query("DELETE FROM issue_inv WHERE id = ". intval($_GET["issue_inv_id"]));

    return $data["issues__id"];
  }
}

function add_labor($id,$cust,$amount) {
	global $USER;
	$result = mysql_query("SELECT id FROM customer_accounts WHERE customers__id = ".$cust);
	if (mysql_num_rows($result) == 0) {
		$ACCOUNT = "NULL";
	} else {
		$data = mysql_fetch_assoc($result);
		$ACCOUNT = $data["id"];
	}

	$sql = "INSERT INTO issue_labor (customer_accounts__id,issues__id,amount,users__id,ts,org_entities__id) VALUES (";
	$sql .= $ACCOUNT .",";
	$sql .= intval($id) .",";
	$sql .= "'". floatval($amount) ."',";
	$sql .= $USER["id"] .",";
	$sql .= "NOW(),{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	return "Labor added.";
}

function resolve($id) {
	global $USER;

  //flip is_resolved
	mysql_query("UPDATE issues SET is_resolved = !is_resolved WHERE id = ".intval($id));

	$data = mysql_fetch_assoc(mysql_query("SELECT varref_status,is_resolved FROM issues WHERE id = ".intval($id)));

	$sql = "INSERT INTO issue_changes (issues__id,description,varref_status,tou,users__id,org_entities__id) VALUES (";
	$sql .= intval($id) .",";
	if ($data["is_resolved"]) {
		$sql .= "'Resolved',";
	} else {
		$sql .= "'Reopened',";
	}
	$sql .= $data["varref_status"] .",";
	$sql .= "NOW(),";
	$sql .= $USER["id"] .",{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	if ($data["is_resolved"]) {
		return "Issue Checked Out/Resolved";
	}

	return "Issue Reopened";
}

function remove_labor($id) {
	mysql_query("DELETE FROM issue_labor WHERE id = ". intval($_GET["labor_id"]));

	return "Labor removed.";
}

function set_customer($id) {
	global $SESSION;
	$id = intval($id);
	mysql_query("UPDATE sessions SET customers__id='". $id ."', customer_ts = NOW() WHERE id='". $SESSION["id"] ."'");
}

function add_item() {
  global $USER;
	//$sql = "INSERT INTO issue_items (issues__id,descr,amt,qty,is_taxable,tid) VALUES (";
  $sql = "INSERT INTO issue_items (issues__id,descr,amt,qty,is_taxable,org_entities__id) VALUES (";
	$sql .= intval($_GET["id"]) .",";
	$sql .= "'". mysql_real_escape_string($_POST["descr"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["amt"]) ."',";
	if (intval($_POST["qty"]) == 0)
		$sql .= "1,";
	else
		$sql .= intval($_POST["qty"]) .",";
	$sql .= (isset($_POST["is_taxable"]) ? "1" : "0") .",{$USER['org_entities__id']})";
	//$sql .= "NULL)";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$iit = mysql_fetch_assoc(mysql_query("SELECT SUM(amt * qty) AS total FROM issue_items WHERE issues__id = ".intval($_GET["id"])));
	$ivt = mysql_fetch_assoc(mysql_query("SELECT SUM(inventory.cost * issue_inv.qty) AS total FROM issue_inv JOIN inventory ON issue_inv.inventory__id = inventory.id WHERE issues__id = ".intval($_GET["id"])));
	$subtotal = floatval($iit["total"]) + floatval($ivt["total"]);
	mysql_query("UPDATE issues SET subtotal = '".$subtotal."' WHERE id = ".intval($_GET["id"]));

	return "Sale item added.";
}

function remove_item() {
	$data = mysql_fetch_assoc(mysql_query("SELECT issues__id FROM issue_items WHERE id = ". intval($_GET["item_id"])));

	if(!$data) return "No issue to remove";

	mysql_query("DELETE FROM issue_items WHERE id = ". intval($_GET["item_id"]));

	$iit = mysql_fetch_assoc(mysql_query("SELECT SUM(amt * qty) AS total FROM issue_items WHERE issues__id = ".$data["issues__id"]));
	$ivt = mysql_fetch_assoc(mysql_query("SELECT SUM(inventory.cost * issue_inv.qty) AS total FROM issue_inv JOIN inventory ON issue_inv.inventory__id = inventory.id WHERE issues__id = ".$data["issues__id"]));
	$subtotal = floatval($iit["total"]) + floatval($ivt["total"]);
	mysql_query("UPDATE issues SET subtotal = '".$subtotal."' WHERE id = ".$data["issues__id"]);

	return "Sale item removed.";
}

function new_issue() {
	global $USER;

	$result = mysql_query("SELECT id FROM customer_accounts WHERE customers__id = ".intval($_POST["customer_id"]));
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
		$account = $data["id"];
	}

	$sql = "INSERT INTO issues (customers__id,varref_status,device_id,varref_issue_type,savedfiles,troubledesc,intake_ts,users__id__intake,"
		  ."users__id__assigned,final_summary,invoices__id,is_deleted,customer_accounts__id,last_status_chg,has_charger,org_entities__id) VALUES (";
	$sql .= intval($_POST["customer_id"]) .",";
	$sql .= "6,";
	if (intval($_POST["device_id"]) > 0) {
		$sql .= intval($_POST["device_id"]) .",";
	} else {
		$sql .= "NULL,";
	}
	$sql .= intval($_POST["issue_type"]) .",";
	$sql .= "'". mysql_real_escape_string($_POST["savedfiles"]) ."',";
	$sql .= "'". mysql_real_escape_string($_POST["troubledesc"]) ."',";
	$sql .= "CURRENT_TIMESTAMP,";
	$sql .= $USER["id"] .",";
	if (intval($_POST["assigned_to"]) == 0)
		$sql .= "NULL,";
	else
		$sql .= intval($_POST["assigned_to"]) .",";
	$sql .= "'',";
	$sql .= "NULL,";
	$sql .= "0,";
	if (!isset($account)) {
		$sql .= "NULL,";
	} else {
		$sql .= intval($account) .",";
	}
	$sql .= "NOW(),";
	$sql .= (isset($_POST["with_charger"]) && $_POST["with_charger"] == "1" ? "1":"0");
	$sql .= ",{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	$issue_id = mysql_insert_id();

	mysql_query("INSERT INTO issue_changes (issues__id,description,varref_status,tou,users__id,org_entities__id) VALUES (".$issue_id.",'Issue opened',6,NOW(),".$USER["id"].",{$USER['org_entities__id']})");

	if ($_POST["issue_type"] > 1) {
		$result = mysql_query("SELECT title FROM org_entities WHERE id={$USER['org_entities__id']} AND id>0");
		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			$store_name = " at the ".$data["title"]." location";
		} else {
			$store_name = "";
		}

		$result = mysql_query("SELECT email FROM users WHERE is_disabled = 0 AND id = ".intval($_POST["assigned_to"]));
		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			$TO = $data["email"];
			$FROM = "frontdesk@computer-answers.com";
			$SUBJECT = "You have been assigned ".($_POST["issue_type"] == 2 ? "an onsite":"a remote support")." issue (# $issue_id )";
			$MESSAGE = "You have been assigned ".($_POST["issue_type"] == 2 ? "an onsite":"a remote support")." issue$store_name. The issue ID is $issue_id.<br><br>\n\nPlease login and change this issue's varref_status to 'Do It' (or 'Warranty' when applicable) to acknowledge that you received this notice.";
			$HEADERS = "From: $FROM\r\n";
			$HEADERS .= "Content-type: text/html\r\n";
			$OK = mail($TO, $SUBJECT, $MESSAGE, $HEADERS);
		}
	}

	$ISSUE = mysql_fetch_assoc(mysql_query("SELECT * FROM issues WHERE id = ". $issue_id));

	// Update issue_id in inventory_items table if applicable
	if (intval($_POST["device_id"]) > 0) {
		$result = mysql_query("SELECT inventory_item_number FROM inventory_type_devices WHERE id = ".intval($_POST["device_id"]));
		if (mysql_num_rows($result)) {
			$dev = mysql_fetch_assoc($result);
			if ($dev["inventory_item_number"]) {
				mysql_query("UPDATE inventory_items SET issues__id = ".intval($issue_id)." WHERE id = ".intval($dev["inventory_item_number"]));
			}
		}
	}

	return $ISSUE;
}

function delete_issue() {
	mysql_query("UPDATE issues SET is_deleted = 1 WHERE id = ". intval($_GET["id"]));

	return "Issue ". intval($_GET["id"]) ." deleted.";
}

function hard_delete_issue() {
	mysql_query("DELETE FROM issues WHERE id = ". intval($_GET["id"]));

	return "Issue ". intval($_GET["id"]) ." deleted permanently.";
}

function add_note() {
	global $USER;

	if(!isset($_POST["note"])) return "No note variable sent to function. Please reload page.";

	mysql_query("INSERT INTO user_notes (for_table,for_key,note,users__id,note_ts) VALUES ('issues',".intval($_GET["id"]).",'".mysql_real_escape_string($_POST["note"])."',".$USER["id"].",NOW())");
	return "Note added to issue.";
}

?>
