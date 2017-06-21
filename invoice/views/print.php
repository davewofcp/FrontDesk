<?php

$IID = intval($_GET["id"]);

$data = mysql_fetch_assoc(mysql_query("SELECT IFNULL(customers__id,0) AS customers__id FROM invoices WHERE id = ". $IID ." LIMIT 1"));
if($data){
  $CUSTOMER_ID = $data["customers__id"];
  $CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ". $CUSTOMER_ID));
}

$INVOICE = mysql_fetch_assoc(mysql_query("SELECT * FROM invoices WHERE id = ". $IID));

$SERVICES = array();
$result = mysql_query("SELECT * FROM services WHERE 1");
while ($row = mysql_fetch_assoc($result)) {
	$SERVICES[$row["id"]] = $row["name"];
}

$subtotal = 0;
$taxable = 0;

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

?>
<html>
<head>
<title>Invoice</title>

<link rel="stylesheet" type="text/css" href="default.css">
<style type="text/css">
.invoicehead {
  background: none;
  color: #000;
  text-shadow: none;
  border-color: #000;
}
</style>
</head>
<body onload="window.print();">
<table border="0" width="780">
 <tr align="center">
  <td width="33%"><img src="images/logo.gif" width="200" height="120"></td>
  <td width="34%"><h2>Invoice</h2></td>
  <td width="33%"><IMG SRC="core/barcode.php?barcode=<?php echo $IID; ?>&width=200&height=50"></td>
 </tr>
</table>

<br>

<div class="invoicemain center" style="width:99%;background:none;">

  <div class="invoicerow">
    <div class="invoicehead">Name</div>
    <div class="invoicecontent"><?php echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; ?></div>
  </div>
  <div class="invoicerow clearL">
    <div class="invoicehead">Address</div>
    <div class="invoicecontent" style="min-width:120px;">
      <?php echo $CUSTOMER["address"] .($CUSTOMER["apt"] ? " #".$CUSTOMER["apt"] : ""); ?>
      <br>
      <?php echo $CUSTOMER["city"]; ?>, <?php echo $CUSTOMER["state"]; ?> <?php echo $CUSTOMER["postcode"]; ?>
    </div>
  </div>
  <div class="relative floatR" style="margin:-40 0 0 40;">
    <div class="invoicerow">
      <div class="invoicehead">Invoice #</div>
      <div class="invoicecontent"><?php echo $INVOICE["id"]; ?></div>
    </div>
    <div class="invoicerow clearL">
      <div class="invoicehead">Date</div>
      <div class="invoicecontent">
        <?php
          $date = strtotime($INVOICE["toi"]);
          echo date('n/d/Y',$date);
          echo '
                <br>';
          echo "(".date('l g:i A',$date).")";
        ?>
      </div>
    </div>
  </div>
  <div class="clear"><br></div>
  <table class="invoiceTable" style="margin-left:15px;padding:5px;">
    <tr class="invoiceTableHeader">
      <td style="width:120px">Item</td>
      <td style="width:30px">Qty</td>
      <td>Description</td>
      <td style="width:70px">Amount</td>
      <td style="width:35px">Tax</td>
    </tr>
<?php

$ISSUES = array();
$result = mysql_query("SELECT * FROM invoice_items WHERE invoices__id = $IID");
while ($row = mysql_fetch_assoc($result)) {
	echo "	<tr class=\"invoiceTableContent\">\n";
	echo "		<td align=\"center\">".$row["name"]."</td>\n";
	echo "		<td align=\"center\">".$row["qty"]."</td>\n";
	echo "		<td>".$row["descr"];
	if ($row["from_table"] == "issues") {
		$ISSUES[] = $row["from_key"];
		$result2 = mysql_query("SELECT services__id FROM issues WHERE id = {$row["from_key"]}");
		if (mysql_num_rows($result2)) {
			$data = mysql_fetch_assoc($result2);
			$s = explode(":",$data["services__id"]);
			$sn = array();
			foreach ($s as $svc) {
					if ($svc == "") continue;
					if (isset($SERVICES[$svc])) $sn[] = $SERVICES[$svc];
			}
			if (count($sn) > 0) {
				foreach ($sn as $svc) {
					echo "<br>\n- $svc";
				}
			}
		}
	}
	echo "</td>\n";
	echo "		<td align=\"center\">$".number_format($row["cost"],2)."</td>\n";
	echo "		<td align=\"center\">".($row["is_taxable"] ? "$".number_format($row["cost"] * $row["qty"] * $tax_rate,2):"No")."</td>\n";
	echo "	</tr>\n";
	$subtotal += $row["cost"] * $row["qty"];
	if ($row["is_taxable"]) $taxable += $row["cost"] * $row["qty"];
}

?>

  </table>
  <div class="clear"></div>

  <div class="invoicerow relative floatR">

    <div class="invoicerow floatR" style="margin-right:27px;margin-bottom:0px;">
      <div class="invoicehead absolute" style="left:-87;">Subtotal</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($subtotal,2); ?></div>
    </div>
    <div class="invoicerow floatR clearR" style="margin-right:27px;margin-top:0px;">
      <div class="invoicehead absolute" style="left:-52;">Tax</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($taxable * $tax_rate,2); ?></div>
    </div>
    <div class="invoicerow floatR clearR" style="margin-right:27px;">
      <div class="invoicehead absolute" style="left:-62;">Total</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($subtotal + ($taxable * $tax_rate),2); ?></div>
    </div>

    <div class="clear"><br></div>

    <div class="invoicerow floatR" style="margin-right:27px;">
      <div class="invoicehead absolute" style="left:-62;">Paid</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($INVOICE["amt_paid"],2); ?></div>
    </div>
    <div class="invoicerow floatR clearR" style="margin-right:27px;">
      <div class="invoicehead absolute" style="left:-62;">Due</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($subtotal + ($taxable * $tax_rate) - $INVOICE["amt_paid"],2); ?></div>
    </div>

  </div>

</div>

<?php

if ($INVOICE["amt_paid"] >= $INVOICE["amt"] && count($ISSUES)) {
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

	foreach ($ISSUES as $iss) {
		$result = mysql_query("SELECT * FROM issues i LEFT JOIN customers c ON i.customers__id = c.id LEFT JOIN inventory_type_devices d ON i.device_id = d.id LEFT JOIN categories ca ON d.categories__id = ca.id WHERE i.id = $iss");
		if (!mysql_num_rows($result)) {
			continue;
		}
		$ISSUE = mysql_fetch_assoc($result);

		$result = mysql_query("SELECT username FROM users WHERE id = ".intval($ISSUE["users__id__assigned"]));
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
}

?>

</body>
</html>
