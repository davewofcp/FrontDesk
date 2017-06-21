<?php

if ($DEVICE["customers__id"]) {
	$CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ".$DEVICE["customers__id"]));
} else {
	$CUSTOMER = array();
	$CUSTOMER["firstname"] = "Unknown";
	$CUSTOMER["lastname"] = "Customer";
	$CUSTOMER["id"] = "0";
}

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

$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NULL ORDER BY category_name");
$result2 = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NOT NULL ORDER BY category_name");
$CATS = array();
$CAT_SUBS = array();
while($row = mysql_fetch_assoc($result)) {
	$CATS[$row["id"]] = $row;
}
while ($row = mysql_fetch_assoc($result2)) {
	if (!isset($CAT_SUBS[$row["parent_id"]])) $CAT_SUBS[$row["parent_id"]] = array();
	$CAT_SUBS[$row["parent_id"]][] = $row;
}

?>
<h3>Edit Device</h3>

<b>Customer:</b> <?php echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; ?> (# <?php echo $CUSTOMER["id"]; ?>)
<form action="?module=cust&do=edit_dev&id=<?php echo $DEVICE["id"]; ?>" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Device Type</td>
  <td>
  <select name="device_type">
<?php

foreach ($CATS as $cat) {
	echo "  <option value=\"".$cat["id"]."\"".($DEVICE["item_type_lookup"] == $cat["id"] ? " SELECTED":"").">".$cat["category_name"]."</option>\n";
	if (isset($CAT_SUBS[$cat["id"]])) {
		foreach ($CAT_SUBS[$cat["id"]] as $sub) {
			echo "  <option value=\"".$sub["id"]."\"".($DEVICE["item_type_lookup"] == $sub["id"] ? " SELECTED":"").">- ".$sub["category_name"]."</option>\n";
		}
	}
}

?>
  </select>
  </td>
 </tr>
  <tr>
  <td class="heading" align="right">Manufacturer</td>
  <td>
  <select name="mfc">
<?php

foreach ($MANUFACTURER as $mfc) {
	echo "  <option value=\"".$mfc."\"".($DEVICE["manufacturer"] == $mfc ? " SELECTED":"").">".$mfc."</option>\n";
}

?>
  </select>
  </td>
 </tr>
  <tr>
  <td class="heading" align="right">Model</td>
  <td><input type="edit" name="model" size="20" value="<?php echo $DEVICE["model"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Serial Number</td>
  <td><input type="edit" name="sn" size="30" value="<?php echo $DEVICE["serial_number"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">OS</td>
  <td>
  <select name="os">
  <option value="None">None</option>
<?php

foreach ($OS as $id => $d_os) {
  if($d_os==$DEVICE["operating_system"]){
    $var = "SELECTED ";
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
  <td><input type="edit" name="user" size="15" value="<?php echo $DEVICE["username"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Password</td>
  <td><input type="edit" name="pass" size="15" value="<?php echo $DEVICE["password"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Location</td>
  <td>
  <select name="location">
<?php

foreach ($LOCATIONS as $id => $title) {
  if($id==$DEVICE["org_entitites__id"]){
    $var = "SELECTED";
  } else {
    $var = "";
  }
	echo "  <option value=\"".$id."\" ".$var.">".$title."</option>\n";
}

?>
  </select>
  </td>
 </tr>
  <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Update Device">
  </td>
 </tr>
</table>
</form>
