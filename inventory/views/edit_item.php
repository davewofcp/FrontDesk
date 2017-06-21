<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<?php
// in-store locations
$LOCATIONS = array();
$result = mysql_query("SELECT * FROM inventory_locations WHERE 1");
while(false!==($row=mysql_fetch_assoc($result)))$LOCATIONS[$row['id']]=$row['title'];
asort($LOCATIONS);

$MANUFACTURER = array();
$OS = array();
$result = mysql_query("SELECT * FROM option_values WHERE category IN ('manufacturer','os')");
while ($row = mysql_fetch_assoc($result)) {
  if ($row["category"] == "manufacturer") {
    $MANUFACTURER[$row["id"]] = $row["value"];
  }
  if ($row["category"] == "os") {
    $OS[$row["id"]] = $row["value"];
  }
}
asort($MANUFACTURER);
arsort($OS);

?>
<h3>'<?php echo $ITEM["name"]; ?>' - Editing Item <?php echo $ITEM["id"]; ?></h3>

<?php if (isset($RESPONSE)) { ?><font color="#CC0000"><b><?php echo $RESPONSE; ?></b></font><br><br><?php } ?>

<?php echo alink_pop("Print Barcode","inventory/label.php?iid={$ITEM["id"]}"); ?> &nbsp;&nbsp;&nbsp;
<?php echo alink("Add to Cart","?module=inventory&do=add_to_cart&iid={$ITEM["id"]}"); ?><br><br>

<?php echo alink("View Product","?module=inventory&do=view&id=".$ITEM["id"]); ?> &nbsp;&nbsp;&nbsp;
<?php echo alink("Edit Product","?module=inventory&do=edit&id=".$ITEM["id"]); ?><br><br>

<?php if ($ITEM["is_in_transit"]) { ?><font color="red" size="+1"><b>This item is being transferred to another store.</b></font><br><br>
<?php
} else {
	$result = mysql_query("SELECT id,location_code,title
SELECT
  oe.id,
  oe.location_code,
  oe.title
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
	if (mysql_num_rows($result)) {

?>

<form action="?module=inventory&do=transfer&id=<?php echo $ITEM["id"]; ?>&iid=<?php echo $ITEM["id"]; ?>" method="post">
<b>Inter-Store Transfer:</b> <select name="location_id">
<?php

		while ($row = mysql_fetch_assoc($result)) {
			echo "<option value=\"{$row["id"]}\">#{$row["location_code"]} - {$row["title"]}</option>\n";
		}

?>
</select> <input type="submit" value="Go">
</form>
<?php } } ?>

<form action="?module=inventory&do=edit_item_sub&id=<?php echo $ITEM["id"]; ?>" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Reason for Edit</td>
  <td><input type="edit" id="reason" name="reason" size="40"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Product ID</td>
  <td><?php echo $ITEM["id"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Serial Number</td>
  <td><input type="edit" name="sn" size="16" value="<?php echo $ITEM["sn"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Notes</td>
  <td><textarea name="notes" rows="5" cols="60"><?php echo $ITEM["notes"]; ?></textarea></td>
 </tr>
 <tr>
  <td class="heading" align="right">Item Status</td>
  <td>
   <select name="status">
<?php

foreach ($INVENTORY_STATUS as $id => $status) {
	if ($id == 0) continue;
	echo "   <option value=\"$id\"".($id == $ITEM["varref_status"] ? " SELECTED":"").">$status</option>\n";
}

?>
   </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Location</td>
  <td>
  <select name="location">
<?php

foreach ($LOCATIONS as $id => $loc) {
	$var = "";
	if ($id == $ITEM["in_store_location"]) $var = " SELECTED";
	echo "  <option value=\"".$id."\" ".$var.">".$loc."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Device</td>
  <td>
<?php
if ($ITEM["item_table_lookup"]) {
	echo alink("Edit Device Info","?module=cust&do=edit_dev&id=".$ITEM["item_table_lookup"]);
?>
<table border="0">
 <tr>
  <td class="heading" align="right">Manufacturer</td>
  <td><?php echo $ITEM["manufacturer"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Model</td>
  <td><?php echo $ITEM["model"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">OS</td>
  <td><?php echo $ITEM["operating_system"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Username</td>
  <td><?php echo $ITEM["username"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Password</td>
  <td><?php echo $ITEM["password"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">With Charger?</td>
  <td><?php echo ($ITEM["has_charger"] ? "Yes":"No"); ?></td>
 </tr>
</table>
<?php
} else {
?>
   <input type="checkbox" name="device_info" id="device_info" value="1" onClick="deviceChange();"> Add Device Info<br>
   <div id="device_info_box" style="display:none;">
<!-- # START DEVICE INFO # -->
<table border="0">
 <tr>
  <td class="heading" align="right">Manufacturer</td>
  <td>
  <select name="dev_mfc">
<?php

foreach ($MANUFACTURER as $mfc) {
	echo "  <option value=\"".$mfc."\">".$mfc."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Model</td>
  <td><input type="edit" name="dev_model" size="20"></td>
 </tr>
 <tr>
  <td class="heading" align="right">OS</td>
  <td>
  <select name="dev_os">
<?php

foreach ($OS as $id => $d_os) {
	if($d_os=="Windows 7"){
		$var = " SELECTED";
	} else {
		$var = " ";
	}
	echo "  <option value=\"".$d_os."\"".$var.">".$d_os."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Username</td>
  <td><input type="edit" name="dev_uname" size="15"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Password</td>
  <td><input type="edit" name="dev_passw" size="15"></td>
 </tr>
 <tr>
  <td class="heading" align="right">With Charger?</td>
  <td><input type="checkbox" name="dev_charger" value="1" CHECKED></td>
 </tr>
</table>
<!-- # END DEVICE INFO # -->
   </div>
<?php } ?>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Issue</td>
  <td>
<?php
if ($ITEM["issues__id"]) {
	echo alink("View Issue ".$ITEM["issues__id"],"?module=iss&do=view&id=".$ITEM["issues__id"]);
} else {
	if ($ITEM["item_table_lookup"]) {
    $result = mysql_query("SELECT inventory_locations__id AS default FROM xref__org_entities__inventory_locations WHERE org_entities__id = {$USER['org_entities__id']} AND is_default = 1");
    if (false !==($row = mysql_fetch_assoc($result)) $inv_loc = $row;
		echo alink("New Issue","?module=iss&do=new&cid=".(isset($inv_loc) ? $inv_loc["default"] : "0")."&dev={$ITEM["item_table_lookup"]}");
	} else {
		echo "<i>Add device info to create an issue.</i>";
	}
}

?>
  </td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Save Changes" onClick="return checkReason();">
  </td>
 </tr>
</table>
</form>

<h3>Item Change Log</h3>
<?php if (TFD_HAS_PERMS('admin','use')) { ?>
<form action="?module=inventory&do=clear_log&iid=<?php echo $ITEM["id"]; ?>" method="post">
Clear history before <input type="edit" id="clear_dt" name="clear_dt" size="10" value="<?php echo date("Y-m-d"); ?>"><br>
<b>Reason:</b> <input type="edit" name="reason" size="40"> <input type="submit" value="Go" onClick="return confirm('Are you sure you want to clear the history before '+document.getElementById('clear_dt').value+'?\n\nThis action will be recorded.');">
</form>
<?php } ?>
<table border="0">
 <tr class="heading" align="center">
  <td>User</td>
  <td>Time</td>
  <td>Change</td>
  <td>Description</td>
  <td>New Status</td>
  <td>Reason</td>
 </tr>
<?php

$result = mysql_query("SELECT u.username,ic.varref_change_code,ic.qty,ic.ts,ic.descr,ic.varref_status,ic.reason FROM inventory_changes ic LEFT JOIN users u ON ic.users__id = u.id WHERE ic.org_entities__id = {$USER['org_entities__id']} AND ic.inventory__id = {$ITEM["id"]} AND ic.inventory_item_number = {$ITEM["id"]} ORDER BY ic.ts DESC");
if (!mysql_num_rows($result)) echo " <tr><td colspan=\"4\" align=\"center\"><i>No Changes to Display</i></td></tr>\n";
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($INVENTORY_CHANGE_CODE[$row["varref_change_code"]])) $row["varref_change_code"] = 0;
	if (!isset($INVENTORY_STATUS[$row["varref_status"]])) $row["varref_status"] = 0;
	echo " <tr align=\"center\">\n";
	echo "  <td>{$row["username"]}</td>\n";
	echo "  <td>".date("Y-m-d H:i",strtotime($row["ts"]))."</td>\n";
	echo "  <td>".$INVENTORY_CHANGE_CODE[$row["varref_change_code"]]."</td>\n";
	if ($row["change_code"] == 3) {
		$moved = false;
		if (stristr($row["descr"],"LOC")) {
			$changes = explode("|",$row["descr"]);
			foreach ($changes as $change) {
				$c = explode(":",$change);
				if ($c[0] != "LOC") continue;
				echo "  <td><b>Moved</b> to '{$LOCATIONS[$c[1]]}'<br><i>Edits Recorded</i></td>\n";
				$moved = true;
				break;
			}
		}
		if (!$moved) {
			echo "  <td><i>Details In Database</i></td>\n";
		}
	}
	else echo "  <td>".$row["descr"]."</td>\n";
	echo "  <td>".($row["varref_status"] ? $INVENTORY_STATUS[$row["varref_status"]] : "")."</td>\n";
	echo "  <td>".$row["reason"]."</td>\n";
	echo " </tr>\n";
}

?>
</table>

<script type="text/javascript">
calendar.set("clear_dt");
function deviceChange() {
	var checked = html('device_info').checked;
	if (checked) {
		html('device_info_box').style.display = '';
	} else {
		html('device_info_box').style.display = 'none';
	}
}
function checkReason() {
	if (html('reason').value == '') {
		alert('Please enter a reason for the edit.');
		html('reason').focus();
		return false;
	}
	return true;
}
</script>
