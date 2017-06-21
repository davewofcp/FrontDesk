<?php

display_header();

$result = mysql_query("SELECT * FROM users WHERE is_disabled = 0");
$USERS = array();
while ($row = mysql_fetch_assoc($result)) {
	$USERS[] = $row;
}
$LOCATIONS = array(); // in-store locations
$result = mysql_query("SELECT * FROM inventory_locations WHERE 1");
while(false!==($row=mysql_fetch_assoc($result)))$LOCATIONS[$row['id']]=$row['title'];
asort($LOCATIONS);

$SERVICE_TYPES = array();
$result = mysql_query("SELECT * FROM option_values WHERE category='service_type'");
while ($row = mysql_fetch_assoc($result)) $SERVICE_TYPES[$row["id"]] = $row["value"];
asort($SERVICE_TYPES);

$DEVICE_TYPES = array();
$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory'");
while ($row = mysql_fetch_assoc($result)) $DEVICE_TYPES[$row["id"]] = $row["category_name"];
asort($DEVICE_TYPES);

$customer = $SESSION["customers__id"];
if (isset($_GET["cid"]) && intval($_GET["cid"]) > 0) $customer = intval($_GET["cid"]);

if (isset($_GET["account"])) {
	$account = intval($_GET["account"]);
	if ($account > 0) {
		$result = mysql_fetch_assoc(mysql_query("SELECT customers__id FROM customer_accounts WHERE id = ". $account));
		$customer = $result["customers__id"];
	}
}

$result = mysql_query("SELECT firstname,lastname FROM customers WHERE id = ".$customer);
if (mysql_num_rows($result) < 1) {
	$customer_name = "";
} else {
	$data = mysql_fetch_assoc($result);
	$customer_name = $data["firstname"] ." ". $data["lastname"];
}

if (isset($_GET["dev"]) && intval($_GET["dev"]) > 0) {
	$DEVICE = mysql_fetch_assoc(mysql_query("SELECT * FROM inventory_type_devices WHERE id = ". intval($_GET["dev"])));
} else {
	$DEVICES = mysql_query("SELECT * FROM inventory_type_devices WHERE customers__id = ". $customer);
}
?><h3>Add New Issue</h3>
<form action="?module=iss&do=new" method="post" onSubmit="return lcount('troubledesc',20,1)">
<table border="0" cellspacing="3">
 <tr>
  <td bgcolor="#120A8F" colspan="2" align="right"><font color="#FFFFFF"><b>Customer</b></font></td>
  <input type="hidden" id="customer_id" name="customer_id" value="<?php echo $customer; ?>">
  <input type="hidden" id="account_id" name="account_id" value="<?php echo (isset($account) ? $account : 0); ?>">
  <td>
  # <?php echo $customer; ?> (<?php echo $customer_name; ?>)
  </td>
 </tr>
 <tr>
  <td bgcolor="#120A8F" colspan="2" align="right"><font color="#FFFFFF"><b>Device</b></font></td>
  <td>
  <?php if (isset($DEVICE)) { ?>
  <input type="hidden" name="device_id" value="<?php echo intval($_GET["dev"]); ?>">
  # <?php echo intval($_GET["dev"]); ?> (<?php echo $DEVICE["manufacturer"] ." ". $DEVICE["model"]; ?>)
  <?php } else { ?>
  <select name="device_id">
  <option value="0">On-Site Device</option>
  <?php
  while ($row = mysql_fetch_assoc($DEVICES)) {
  	echo "<option value=\"". $row["id"] ."\"># ". $row["id"] ." (". $row["manufacturer"] ." ". $row["model"] .")</option>\n";
  }
  ?>
  </select>
  <?php } ?>
  <br><input type="checkbox" name="with_charger" value="1"> With Charger
  </td>
 </tr>
 <tr>
  <td bgcolor="#120A8F" rowspan="5"><font color="#FFFFFF" size="+2"><b>Issue</b></font></td>
 </tr>
 <tr>
  <td bgcolor="#003399" align="right"><font color="#FFFFFF"><b>Issue Type</b></font></td>
  <td>
   <input type="radio" name="issue_type" value="1" checked> In-Store
   <input type="radio" name="issue_type" value="2"> On-Site
   <input type="radio" name="issue_type" value="3"> Remote Support
   <input type="radio" name="issue_type" value="4"> Internal
  </td>
 </tr>
 <tr>
  <td bgcolor="#003399" align="right"><font color="#FFFFFF"><b>Saved Files</b></font></td>
  <td><input type="edit" name="savedfiles" size="65"></td>
 </tr>
 <tr>
  <td bgcolor="#003399" align="right"><font color="#FFFFFF"><b>Description</b><br>(required)</font></td>
  <td><textarea name="troubledesc" id="troubledesc" rows="4" cols="50"></textarea></td>
 </tr>
 <tr>
  <td bgcolor="#003399" align="right"><font color="#FFFFFF"><b>Assigned To</b></font></td>
  <td>
    <select name="assigned_to">
      <option value="0">Nobody</option>
<?php

foreach ($USERS as $_user) {
	echo "
      <option value=\"". $_user["id"] ."\">". $_user["username"] ."</option>";
}

?>
    </select>
  </td>
 </tr>

 <tr>
  <td colspan="3" align="center">
  <input type="submit" value="Add New Issue">
  </td>
 </tr>
</table>
</form>
<?php display_footer(); ?>
