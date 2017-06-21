<?php

$T_HEADER = mysql_fetch_assoc(mysql_query("SELECT * FROM pos_transactions WHERE id = ". $TID ." AND line_number = 0"));
if (!$T_HEADER) {
	echo "Transaction ".$TID." not found.";
} else {
	if ($T_HEADER["customers__id"])
		$CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ". $T_HEADER["customers__id"]));

	$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
		$address = $data["address"];
		$csz = $data["city"] ." ". $data["state"] ." ". $data["postcode"];
	} else {
		$address = "617 Central Ave";
		$csz = "Albany NY 12206";
	}

	$tos = strtotime($T_HEADER["tos"]);
?>
<html>
<head>
<style type="text/css">
.receipt_top td {
	text-align: center;
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 10px;
}
.receipt {
	text-align: center;
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 6px;

}
.receipt td{
	text-align: left;
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 10px;
}
.receipt_top img{ BORDER: 0; vertical-align:top;text-align:center; }
</style>
</head>
<body onload="window.print();">
<table width='350px'>
 <tr>
  <td colspan='2' align='center'>
  <img src='./images/logoreceipt.gif' width = '150' height = '84'>
  </td>
 </tr>
 <tr>
  <td colspan='2' align='center' class='receipt_top'><?php echo $address; ?></td>
 </tr>
 <tr>
  <td colspan='2' align='center' class='receipt_top'><?php echo $csz; ?></td>
 </tr>
 <tr>
  <td><font size='-2' face='Verdana'> </font></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Date</strong></td>
  <td><?php echo date("Y.m.d",$tos); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Time</strong></td>
  <td><?php echo date("H:i:s",$tos); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Customer ID</strong></td>
  <td><?php echo ($T_HEADER["customers__id"] ? $T_HEADER["customers__id"] : "N/A"); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Transaction Number</strong></td>
  <td><?php echo $TID; ?></td>
 </tr>
 <tr class='receipt'>
  <td colspan='2'>

  <hr>
  <table width = '100%' class='receipt'>
   <tr>
    <td><strong>Units</strong></td>
    <td><strong>Description</strong></td>
    <td><strong>Price</strong></td>
   </tr>
<?php

$ISSUES = array();
$ROWS = $T_HEADER["qty"];
$i = 0;
$SUBTOTAL = 0;
$TAXABLE = 0;
while ($i < $ROWS) {
	$i++;
	$T_ITEM = mysql_fetch_assoc(mysql_query("SELECT * FROM pos_transactions WHERE id = ". $TID ." AND line_number = ". $i));
	if (!$T_ITEM) continue;

	if ($T_ITEM["from_table"] == "issues") {
		//$ISSUES[] = $T_ITEM["from_key"];
	}

	echo " <tr>\n";
	if (!$T_ITEM["is_heading"]) echo "  <td>".$T_ITEM["qty"]."</td>\n";
	else echo "  <td></td>\n";
	echo "  <td>".$T_ITEM["descr"]."</td>\n";
	//if (!$T_ITEM["is_heading"])
	echo "  <td>".number_format($T_ITEM["amt"],2)."</td>\n";
	//else echo "  <td></td>\n";
	echo " </tr>";

	$SUBTOTAL += ($T_ITEM["amt"] * $T_ITEM["qty"]);
	if ($T_ITEM["is_taxable"]) {
		$TAXABLE += ($T_ITEM["amt"] * $T_ITEM["qty"]);
	}
}

// get default org tax rate
$result = mysql_query("
SELECT
  oe.tax_rate
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Organization'
  AND tax_rate IS NOT NULL
LIMIT 1
");
if (mysql_num_rows($result)) {
  $data = mysql_fetch_assoc($result);
  $tax_rate = floatval($data["tax_rate"]);
}
// try to get store-specific tax rate
$result = mysql_query("SELECT tax_rate FROM org_entities WHERE id={$USER['org_entities__id']} AND tax_rate IS NOT NULL LIMIT 1");
if (mysql_num_rows($result)) {
  $data = mysql_fetch_assoc($result);
  $tax_rate = floatval($data["tax_rate"]);
}
// hack fallback for now
if (!isset($tax_rate)) $tax_rate = floatval("0.08");

$TAX = round($TAXABLE * $tax_rate,2);

?>
  </table>
  <hr>

  </td>
 </tr>
 <tr class='receipt'>
  <td><strong>Sub Total</strong></td>
  <td><?php echo number_format($SUBTOTAL,2); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Tax Amount</strong></td>
  <td><?php echo number_format($TAX,2); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Total</strong></td>
  <td><?php echo number_format($SUBTOTAL + $TAX,2); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Payment Method</strong></td>
  <td><?php

$payment = array();
if ($T_HEADER["paid_cash"] > 0) $payment[] = "cash";
if ($T_HEADER["paid_credit"] > 0) $payment[] = "credit";
if ($T_HEADER["paid_check"] > 0) $payment[] = "check";
echo implode("+",$payment);

?></td>
 </tr>
 <tr>
  <td><br></td>
 </tr>
</table>

<?php

	if (count($ISSUES)) {
		$SERVICES = array();
		$result = mysql_query("SELECT id,name FROM services WHERE 1");
		while ($row = mysql_fetch_assoc($result)) {
			$SERVICES[$row["id"]] = $row["name"];
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
	}

	foreach ($ISSUES as $iss) {
		$result = mysql_query("SELECT * FROM issues i LEFT JOIN customers c ON i.customers__id = c.id LEFT JOIN inventory_type_devices d ON i.device_id = d.id LEFT JOIN categories ca ON d.categories__id = ca.id WHERE i.id = $iss");
		if (!mysql_num_rows($result)) {
			continue;
		}
		$ISSUE = mysql_fetch_assoc($result);

		$result = mysql_query("SELECT username FROM users WHERE id = ".$ISSUE["users__id__assigned"]);
		if (!mysql_num_rows($result)) {
			$assigned_to = "Nobody";
		} else {
			$data = mysql_fetch_assoc($result);
			$assigned_to = $data["username"];
		}

?>

<div style='page-break-before:always;'></div>
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

$svc = explode(":",$ISSUE["services__id"]);
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

<?php

	}

?>

</body>
</html>
<?php

}

?>
