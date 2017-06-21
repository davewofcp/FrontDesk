<?php

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
<h3>Add New Device</h3>
<b>Customer:</b> # <?php echo $CUSTOMER["id"]; ?> (<?php echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; ?>)
<form action="?module=cust&do=add_dev_sub&id=<?php echo $CUSTOMER["id"]; ?>" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Device Type</td>
  <td>
  <select name="device_type">
<?php

foreach ($CATS as $cat) {
	echo "  <option value=\"".$cat["id"]."\">".$cat["category_name"]."</option>\n";
	if (isset($CAT_SUBS[$cat["id"]])) {
		foreach ($CAT_SUBS[$cat["id"]] as $sub) {
			echo "  <option value=\"".$sub["id"]."\">- ".$sub["category_name"]."</option>\n";
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
  <select name="device_mfc">
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
  <td><input type="edit" name="device_model" size="20"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Serial Number</td>
  <td><input type="edit" name="device_sn" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">OS</td>
  <td>
  <select name="device_os">
<?php

foreach ($OS as $id => $d_os) {
  if($d_os=="Windows 7"){
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
  <td><input type="edit" name="device_user" size="15"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Password</td>
  <td><input type="edit" name="device_pass" size="15"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Location</td>
  <td>
  <select name="location">
<?php

foreach ($LOCATIONS as $id => $title) {
  if($id==169){
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
  <input type="submit" value="Add Device">
  </td>
 </tr>
</table>
