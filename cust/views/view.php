<?php

$SERVICES = array();
$result = mysql_query("SELECT * FROM services WHERE 1");
while ($row = mysql_fetch_assoc($result)) {
	$SERVICES[$row["id"]] = $row["name"];
}

if (!$CUSTOMER) {
	echo "Customer ID ". intval($_GET["id"]) ." not found.";
} else {

	$ISSUES = mysql_query("SELECT * FROM issues WHERE org_entities__id = {$USER['org_entities__id']} AND customers__id = ". $CUSTOMER["id"] ." AND is_deleted = 0 ORDER BY id,varref_status DESC");

  $LOCATIONS = array();
  $result = mysql_query("SELECT * FROM inventory_locations WHERE 1");
  while(false!==($row=mysql_fetch_assoc($result)))$LOCATIONS[$row['id']]=$row['title'];

  $SERVICE_TYPES = array();
	$result = mysql_query("SELECT * FROM option_values WHERE category='service_type'");
	while ($row = mysql_fetch_assoc($result)) {
    $SERVICE_TYPES[$row["id"]] = $row["value"];
	}

  $DEVICE_TYPES = array();
	$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory'");
	while ($row = mysql_fetch_assoc($result)) {
		$DEVICE_TYPES[$row["id"]] = $row["category_name"];
	}

  $USERS = array(0 => "Nobody");
	$result = mysql_query("
SELECT
  u.id,
  u.username
FROM
  users u,
  user_roles ur,
  org_entities oe,
  org_entity_types oet,
  app_levels al
WHERE
  u.org_entities__id = {$USER['org_entities__id']}
  AND u.org_entities__id = oe.id
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
  AND u.user_roles__id = ur.id
  AND ur.app_levels__id = al.id
  AND al.title = 'Entity'
");
	while ($row = mysql_fetch_assoc($result)) {
		$USERS[$row["id"]] = $row["username"];
	}

	$result = mysql_query("SELECT * FROM customer_accounts WHERE customers__id = ". $CUSTOMER["id"] ." LIMIT 1");
	if (mysql_num_rows($result)) $ACCOUNT = mysql_fetch_assoc($result);

	$TRANS = mysql_query("SELECT * FROM pos_transactions WHERE org_entities__id = {$USER['org_entities__id']} AND customers__id = ". $CUSTOMER["id"] ." AND line_number = 0 ORDER BY tos DESC");

  $INVOICE = mysql_query("SELECT * FROM invoices WHERE org_entities__id = {$USER['org_entities__id']} AND customers__id = ". $CUSTOMER["id"] ." ORDER BY toi DESC");

	$INVENTORY = mysql_query("
	SELECT iic.id,iic.qty,unit_cost,total_cost,i.name,ts,i.item_type_lookup FROM inventory_items_customer iic JOIN inventory i ON iic.inventory__id = i.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND iic.customers__id = {$CUSTOMER["id"]} ORDER BY ts DESC");
  ?>
<h3><?php echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; ?></h3>

<?php if (isset($RESPONSE)) { echo "<font size=\"+1\">$RESPONSE</font><br><br>\n\n"; } ?>

  <div class="relative center">
    <div class="inline padding5"><?php echo alink("Edit","?module=cust&do=edit&id=". $CUSTOMER["id"]); ?></div>
    <div class="inline padding5"><?php echo alink("Create Invoice","?module=invoice&do=create&customer_id=". $CUSTOMER["id"]); ?></div>
<?php if (isset($ACCOUNT)) { ?>
	<div class="inline padding5"><?php echo alink("Go To Account","?module=acct&do=view&id=". $ACCOUNT["id"]); ?></div>
<?php } else { ?>
	<div class="inline padding5"><?php echo alink("Create Account","?module=acct&do=new_account&customer=". $CUSTOMER["id"]); ?></div>
<?php } ?>
	<div class="inline padding5"><?php echo alink("Purchase Inventory","?module=cust&do=purchase&id=". $CUSTOMER["id"]); ?></div>
</div>
<div class="relative center">
<?php echo alink("Reset Customer Portal Password","?module=cust&do=cpp&id={$CUSTOMER["id"]}"); ?>
</div>
  <div class="clear"><br></div>

  <div class="itemrow relative center" style="margin-left:90px;">

    <div class="itemhead clearL">Address</div>
    <div class="itemcontent" style="padding:5px 0px 5px 7px;"><?php
      echo $CUSTOMER["address"];
      if ($CUSTOMER["apt"] != '') echo " #".$CUSTOMER["apt"];
      echo "<br>";
      echo $CUSTOMER["city"].", ".$CUSTOMER["state"]." ".$CUSTOMER["postcode"];
      if ($CUSTOMER["v_address"] && $CUSTOMER["v_address"] != "INVALID") {
      	echo "<br><font color=\"green\">Validated</font>\n";
      } else if ($CUSTOMER["v_address"] == "INVALID") {
      	echo "<br><font color=\"red\">Invalid</font>\n";
      } else {
      	echo "<br><font color=\"orange\">Not Validated</font>\n";
      }
    ?></div>

      <div class="itemhead" style="margin-left:7px;">Home Phone</div>
      <div class="itemcontent" style="padding:5px 0px 5px 7px;"><?php echo ($CUSTOMER["phone_home"]==""||!$CUSTOMER["phone_home"] ? "None" : display_phone($CUSTOMER["phone_home"])); ?></div>

      <div class="itemhead" style="margin-left:7px;">Cell Phone</div>
      <div class="itemcontent" style="padding:5px 0px 5px 7px;"><?php echo ($CUSTOMER["phone_cell"]==""||!$CUSTOMER["phone_cell"] ? "None" : display_phone($CUSTOMER["phone_cell"])); ?></div>

      <div class="itemhead clearL">Gender</div>
      <div class="itemcontent" style="padding:5px 0px 5px 7px;"><?php echo ($CUSTOMER["is_male"]==1 ? "Male" : "Female"); ?></div>

      <div class="itemhead" style="margin-left:7px;">DOB</div>
      <div class="itemcontent" style="padding:5px 0px 5px 7px;"><?php echo ($CUSTOMER["dob"]=="0000-00-00" ? "N/a" : $CUSTOMER["dob"]); ?></div>

      <div class="itemhead" style="margin-left:7px;">Company</div>
      <div class="itemcontent" style="padding:5px 0px 5px 7px;"><?php echo (!$CUSTOMER["company"]||$CUSTOMER["company"]=="" ? "N/a" : $CUSTOMER["company"]); ?></div>

      <div class="itemhead" style="margin-left:7px;">Referall</div>
      <div class="itemcontent" style="padding:5px 0px 5px 7px;"><?php echo (!$CUSTOMER["referral"]||$CUSTOMER["referral"]=="" ? "N/a" : $CUSTOMER["referral"]); ?></div>

  </div>

<div class="clear"><br></div>

<h3>Devices for this Customer</h3>
<table border="0">
 <tr class="heading" align="center">
  <td>#</td>
  <td>Device Type</td>
  <td>Manufacturer</td>
  <td>Model</td>
  <td>OS</td>
  <td>Location</td>
  <td>Edit</td>
  <td>New Issue</td>
 </tr>
<?php

	$DEVICES = mysql_query("SELECT * FROM inventory_type_devices WHERE customers__id = ". $CUSTOMER["id"]);

	$DEVICE_TYPE = array();
	$DEVICE_LOC = array();

	while ($dev = mysql_fetch_assoc($DEVICES)) {
		$DEVICE_TYPE[$dev["id"]] = $dev["categories__id"];
		$DEVICE_LOC[$dev["id"]] = $dev["in_store_location"];
		echo " <tr align=\"center\">\n";
		echo "  <td>".$dev["id"]."</td>\n";
		echo "  <td>".$DEVICE_TYPES[$dev["categories__id"]]."</td>\n";
		echo "  <td>".$dev["manufacturer"]."</td>\n";
		echo "  <td>".$dev["model"]."</td>\n";
		echo "  <td>".$dev["operating_system"]."</td>\n";
		if (isset($LOCATIONS[$dev["in_store_location"]])) {
			echo "  <td>".$LOCATIONS[$dev["in_store_location"]]."</td>\n";
		} else {
			echo "  <td><i>Nowhere</i></td>\n";
		}
		echo "  <td>".alink("Edit","?module=cust&do=edit_dev&id=".$dev["id"])."</td>\n";
		echo "  <td>".alink("New Issue","?module=iss&do=new&dev=".$dev["id"])."</td>\n";
		echo " </tr>\n";
	}

?>
</table>

<?php echo alink("+ Add New Device","?module=cust&do=add_dev&id=".$CUSTOMER["id"]); ?><br>

<br>

<h3>Issues for this Customer</h3>
<table cellspacing="1" cellpadding="1" bgcolor="#000" width="780">
 <tr class="heading" align="center" style="font-size:10pt;">
  <td>#</td>
  <td>Intake Date</td>
  <td>Device Type</td>
  <td>Assigned To</td>
  <td>Location</td>
  <td>Services</td>
  <td>Issue Status</td>
  <td>View Issue</td>
  <td>Add to Cart</td>
 </tr>
<?php

	while ($issue = mysql_fetch_assoc($ISSUES)) {
	 if($issue["users__id__assigned"] === NULL){
    $issue["users__id__assigned"]=0;
   }
		if (isset($ST_COLORS[$issue["varref_status"]])) {
			echo " <tr align=\"center\" bgcolor=\"". $ST_COLORS[$issue["varref_status"]] ."\" style=\"font-size:10pt;\">\n";
		} else {
			echo " <tr align=\"center\" style=\"font-size:10pt;\">\n";
		}
		echo "  <td>". $issue["id"] ."</td>\n";
		echo "  <td>". $issue["intake_ts"] ."</td>\n";
		if ($issue["device_id"] == null) {
			echo "  <td><i>On-Site Device</i></td>\n";
		} else {
			echo "  <td>". $DEVICE_TYPES[$DEVICE_TYPE[$issue["device_id"]]] ."</td>\n";
		}
		echo "  <td>". $USERS[$issue["users__id__assigned"]] ."</td>\n";
		if ($issue["device_id"] == null || !isset($DEVICE_LOC[$issue["device_id"]]) || !isset($LOCATIONS[$DEVICE_LOC[$issue["device_id"]]])) {
			echo "  <td><i>N/A</i></td>\n";
		} else {
			echo "  <td>". $LOCATIONS[$DEVICE_LOC[$issue["device_id"]]] ."</td>\n";
		}
		$s = explode(":",$issue["services"]);
		$sn = array();
		foreach ($s as $svc) {
			if ($svc == '' || !isset($SERVICES[$svc])) continue;
			$sn[] = $SERVICES[$svc];
		}

		echo "<td>";
		if(count($sn) > 0){
			echo "- ".join("<br>- ",$sn);
		} else {
			echo "<i>None</i>";
		}
    	echo "</td>\n";

		echo "  <td>". $STATUS[$issue["varref_status"]] ."</td>\n";
		echo "  <td>".alink("View Issue","?module=iss&do=view&id=". $issue["id"])."</td>\n";
		if ($issue["invoices__id"] == null) {
			echo "  <td>".alink("Create Invoice","?module=invoice&do=create_from_issue&id=". $issue["id"])."</td>\n";
		} else {
			echo "  <td><i>Paid</i></td>\n";
		}
		echo " </tr>\n";
	}

?>
</table>

<br>

<h3>Notes</h3>
<?php

$NOTES = mysql_query("SELECT * FROM user_notes LEFT JOIN users ON user_notes.users__id = users.id WHERE org_entities__id = {$USER['org_entities__id']} AND for_table = 'customers' AND for_key = ".intval($_GET["id"])." ORDER BY note_ts DESC");

if (mysql_num_rows($NOTES)) {
	while ($note = mysql_fetch_assoc($NOTES)) {
		echo "Added by <b>{$note["firstname"]} {$note["lastname"]}</b> on <b>".date("D, j F Y </\\b>\\a\\t<\\b> h:iA",strtotime($note["note_ts"]))."</b><br>";
		echo $note["note"] ."\n<hr>\n";
	}
} else {
	echo "<i>No Notes</i><br><br>";
}

?>
<font size="+2">Add Note</font><br>
<form action="?module=cust&do=add_note&id=<?php echo intval($_GET["id"]); ?>" method="post">
<textarea name="note" rows="10" cols="50"></textarea><br>
<input type="submit" value="Add Note">
</form>

<br>

<h3>Transaction History</h3>
<table border="0" width="400">
<?php
  if(mysql_num_rows($TRANS)){
?>
 <tr class="heading" align="center">
  <td>ID</td>
  <td>Time of Sale</td>
  <td>Amount</td>
  <td>Items</td>
  <td>View</td>
 </tr>
<?php
} else {
  echo "<div style=\"border-top:4px double #000;border-bottom:4px double #000;\">None!</div>";
}
	while ($trans = mysql_fetch_assoc($TRANS)) {
		echo " <tr align=\"center\">\n";
		echo "  <td>". $trans["id"] ."</td>\n";
		echo "  <td>". $trans["tos"] ."</td>\n";
		echo "  <td>$". number_format(floatval($trans["amt"]),2) ."</td>\n";
		echo "  <td>". $trans["qty"] ."</td>\n";
		echo "  <td>". alink("View","?module=pos&do=view_trans&tid=". $trans["id"]) ."</td>\n";
		echo " </tr>\n";
	}

?>
</table>

<br>

<h3>Invoices</h3>
<table border="0" width="400">
<?php
  if(mysql_num_rows($INVOICE)){
?>
 <tr class="heading" align="center">
  <td>ID</td>
  <td>Creation Date</td>
  <td>Amount</td>
  <td>View</td>
 </tr>
<?php
} else {
  echo "<div style=\"border-top:4px double #000;border-bottom:4px double #000;\">None!</div>";
}

	while ($invoice = mysql_fetch_assoc($INVOICE)) {
		echo " <tr align=\"center\">\n";
		echo "  <td>". $invoice["id"] ."</td>\n";
		echo "  <td>". $invoice["toi"] ."</td>\n";
		echo "  <td>$". number_format(floatval($invoice["amt"]),2) ."</td>\n";
		echo "  <td>". alink("View","?module=invoice&do=view&id=". $invoice["id"]) ."</td>\n";
		echo " </tr>\n";
	}

?>
</table>

<br>

<h3>Inventory Purchased</h3>
<table border="0">
<?php
  if(mysql_num_rows($INVENTORY)){
?>
 <tr class="heading" align="center">
  <td>Date</td>
  <td>Name</td>
  <td>Type</td>
  <td>Quantity</td>
  <td>Unit Cost</td>
  <td>Total Cost</td>
  <td>Receipt</td>
 </tr>
<?php
} else {
  echo "<div style=\"border-top:4px double #000;border-bottom:4px double #000;\">None!</div>";
}

	while ($inv = mysql_fetch_assoc($INVENTORY)) {
		echo " <tr align=\"center\">\n";
		echo "  <td>". $inv["ts"] ."</td>\n";
		echo "  <td>". $inv["name"] ."</td>\n";
		echo "  <td>". $DEVICE_TYPES[$inv["item_type_lookup"]] ."</td>\n";
		echo "  <td>". $inv["qty"] ."</td>\n";
		echo "  <td>$". number_format(floatval($inv["unit_cost"]),2) ."</td>\n";
		echo "  <td>$". number_format(floatval($inv["total_cost"]),2) ."</td>\n";
		echo "  <td>".alink("Receipt","cust/preceipt.php?id=".$inv["id"])."</td>\n";
		echo " </tr>\n";
	}

?>
</table>

<br>

<h3>Feedback from this Customer</h3>
<table border="0" width="750">
<?php
$result = mysql_query("SELECT * FROM feedback WHERE customers__id = ".$CUSTOMER["id"]);
if(mysql_num_rows($result)){
?>
 <tr class="heading" align="center">
  <td width="150">Date / Time</td>
  <td>Score</td>
  <td>Feedback</td>
 </tr>
<?php
} else {
  echo "<div style=\"border-top:4px double #000;border-bottom:4px double #000;\">None!</div>";
}
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\">\n";
	echo "  <td>". $row["ts"] ."</td>\n";
	echo "  <td>". $row["score"] ."</td>\n";
	echo "  <td>". $row["feedback"] ."</td>\n";
	echo " </tr>\n";
}

?>
</table>
<?php echo alink("+ Add Feedback","?module=cust&do=feedback&id=".$CUSTOMER["id"]); ?>

<?php

} // end else

?>
