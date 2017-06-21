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
<h3>'<?php echo $ITEM["name"]; ?>' - Add Item</h3>

<form action="?module=inventory&do=add_item_sub&id=<?php echo $ITEM["id"]; ?>" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Item Notes</td>
  <td><textarea name="notes" id="notes" rows="5" cols="40"></textarea></td>
 </tr>
 <tr>
  <td class="heading" align="right">Serial Number</td>
  <td><input type="edit" name="sn" id="sn" size="20"></td>
 </tr>
  <tr>
  <td class="heading" align="right">Location</td>
  <td>
  <select name="location">
<?php

$result = mysql_query("SELECT inventory_locations__id AS default FROM xref__org_entities__inventory_locations WHERE org_entities__id = {$USER['org_entities__id']} AND is_default = 1");
if (false !==($row = mysql_fetch_assoc($result)) $inv_loc = $row;

foreach ($LOCATIONS as $id => $loc) {
  if(isset($inv_loc) && $id==$inv_loc["default"]){
		$var = " SELECTED";
	} else {
		$var = "";
	}
	echo "  <option value=\"".$id."\" ".$var.">".$loc."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Info</td>
  <td>
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
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Item Status</td>
  <td>
   <select name="status">
<?php

foreach ($INVENTORY_STATUS as $id => $status) {
	if ($id == 0) continue;
	echo "   <option value=\"$id\">$status</option>\n";
}

?>
   </select>
  </td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Add Item"> <?php echo alink("Cancel","?module=inventory&do=view&id=".$ITEM["inventory_id"]); ?>
  </td>
 </tr>
</table>
</form>

<script type="text/javascript">
function deviceChange() {
	var checked = html('device_info').checked;
	if (checked) {
		html('device_info_box').style.display = '';
	} else {
		html('device_info_box').style.display = 'none';
	}
}
</script>
