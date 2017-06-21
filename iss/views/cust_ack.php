<?php

if (!isset($ISSUE) || !$ISSUE) die("Issue not found.");

$result = mysql_query("SELECT username FROM users WHERE id = ".intval($ISSUE["users__id__assigned"]));
if (!mysql_num_rows($result)) {
	$assigned_to = "Nobody";
} else {
	$data = mysql_fetch_assoc($result);
	$assigned_to = $data["username"];
}

$result = mysql_query("
SELECT
  oe.phone
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
if (!mysql_num_rows($result)) {
	$store_phone = "";
} else {
	$data = mysql_fetch_assoc($result);
	$store_phone = $data["phone"];
}

?>
<div style="float:left;width:50%;" align="center">
<img src="images/logo.gif" width="280" height="130"><br>
<b>Customer Acknowledgement<br>
<?php echo date("F d Y"); ?></b><br>
Tech Support<br>
<?php echo $store_phone; ?>
</div>

<div style="float:left;width:50%;" align="center">
Customer ID<br>
<IMG SRC='core/barcode.php?barcode=<?php echo $ISSUE["customers__id"]; ?>&width=280&height=80'><br>
Issue ID<br>
<IMG SRC='core/barcode.php?barcode=<?php echo $ISSUE["id"]; ?>&width=280&height=80'><br>
</div>

<hr>

<table border="0" width="100%" cellpadding="5">
	<tr>
		<td align="right" width="25%"><b>Customer Name</b></td>
		<td width="25%"><?php echo $ISSUE["firstname"] ." ". $ISSUE["lastname"]; ?></td>
		<td align="right" width="25%"><b>Device Type</b></td>
		<td width="25%"><?php echo $ISSUE["category_name"]; ?></td>
	</tr>
	<tr>
		<td align="right"><b>Street Address</b></td>
		<td><?php echo $ISSUE["address"]; ?></td>
		<td align="right"><b>Device Manufacturer & Model</b></td>
		<td><?php echo $ISSUE["manufacturer"] ." ". $ISSUE["model"]; ?></td>
	</tr>
	<tr>
		<td align="right"><b>City</b></td>
		<td><?php echo $ISSUE["city"]; ?></td>
		<td align="right"><b>Device Serial Number</b></td>
		<td><?php echo $ISSUE["serial_number"]; ?></td>
	</tr>
	<tr>
		<td align="right"><b>Zip Code</b></td>
		<td><?php echo $ISSUE["postcode"]; ?></td>
		<td align="right"><b>Services</b></td>
		<td><?php

$svc = explode(":",$ISSUE["services"]);
foreach ($svc as $s) {
	if (!isset($SERVICES[$s])) continue;
	echo $SERVICES[$s] ."<br>";
}

?></td>
	</tr>
	<tr>
		<td align="right"><b>Home Phone</b></td>
		<td><?php echo display_phone($ISSUE["phone_home"]); ?></td>
		<td align="right"><b>Issue Status</b></td>
		<td><?php echo $STATUS[$ISSUE["varref_status"]]; ?></td>
	</tr>
	<tr>
		<td align="right"><b>Cell Phone</b></td>
		<td><?php echo display_phone($ISSUE["phone_cell"]); ?></td>
		<td align="right"><b>Original Service Quote</b></td>
		<td>$<?php echo number_format($ISSUE["quote_price"],2); ?></td>
	</tr>
	<tr>
		<td align="right"><b>Email Address</b></td>
		<td><?php echo $ISSUE["email"]; ?></td>
		<td align="right"><b>Technician</b></td>
		<td><?php echo $assigned_to; ?></td>
	</tr>
	<tr>
		<td colspan="2"></td>
		<td align="right"><b>With Charger?</b></td>
		<td><?php echo ($ISSUE["has_charger"] ? "Yes":"No"); ?></td>
	</tr>
</table><br>

<b>Issue Description</b><br>
<?php echo $ISSUE["troubledesc"]; ?>
<br><br>

<b>Technician Summary and Work Performed</b><br>
<?php echo $ISSUE["final_summary"]; ?>
<br><br>

<div align="center">
Above-named customer acknowledges that they have picked up the device.<br><br><br>

<b>Customer Signature ______________________________________________</b>
</div>
