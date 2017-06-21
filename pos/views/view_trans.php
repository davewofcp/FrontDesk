<?php

display_header();

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

$T_HEADER = mysql_fetch_assoc(mysql_query("SELECT * FROM pos_transactions WHERE id = ". $TID ." AND line_number = 0"));
if (!$T_HEADER) {
	echo "<h3>Transaction ".$TID." not found.</h3>";
} else {
	if ($T_HEADER["customers__id"])
		$CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ". $T_HEADER["customers__id"]));
?>
<h3>Transaction <?php echo $TID; ?>
<?php echo alink_pop("Print Receipt","?module=pos&do=receipt&tid=".$TID); ?></h3>
<table border="0" width="780">
 <tr>
  <td width="75%" valign="top">

 <table border="0" width="100%">
  <tr>
   <td class="heading" align="right">Customer</td>
   <td><?php if (isset($CUSTOMER)) { echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; } else { echo "(Not Found)"; } ?></td>
   <td class="heading" align="right">Home Phone</td>
   <td><?php if (isset($CUSTOMER) && $CUSTOMER["phone_home"]) echo display_phone($CUSTOMER["phone_home"]); ?></td>
   <td class="heading" align="right">Cell Phone</td>
   <td><?php if (isset($CUSTOMER) && $CUSTOMER["phone_cell"]) echo display_phone($CUSTOMER["phone_cell"]); ?></td>
  </tr>
  <tr>
   <td class="heading" align="right">Address</td>
   <td colspan="5"><?php if (isset($CUSTOMER)) echo $CUSTOMER["address"] .", ". $CUSTOMER["city"] .", ". $CUSTOMER["state"] ." ". $CUSTOMER["postcode"]; ?></td>
  </tr>
 </table>

 <table border="0" width="100%">
 <tr class="major_heading" align="center">
  <td>.</td>
  <td>Description</td>
  <td>Price</td>
  <td>Units</td>
  <td>Taxable</td>
  <td>Subtotal</td>
  <td>Refund</td>
 </tr>
<?php

$issue_id = 0;
$ROWS = $T_HEADER["qty"];
$i = 0;
$SUBTOTAL = 0;
$TAXABLE = 0;
while ($i < $ROWS) {
	$i++;
	$T_ITEM = mysql_fetch_assoc(mysql_query("SELECT * FROM pos_transactions WHERE id = ". $TID ." AND line_number = ". $i));
	if (!$T_ITEM) continue;

	echo " <tr>\n";
	echo "  <td>".$i."</td>\n";
	echo "  <td>".$T_ITEM["descr"]."</td>\n";
	if ($T_ITEM["is_heading"]) {
		echo "  <td align=\"center\">$".number_format($T_ITEM["amt"],2)."</td>\n";
		if ($T_ITEM["from_table"] == "issues") {
			if ($issue_id == 0) $issue_id = $T_ITEM["from_key"];
			echo "  <td colspan=\"3\" align=\"center\">". alink_pop("Issue Receipt","?module=iss&do=receipt&id=". $T_ITEM["from_key"]) ." - ". alink("View Issue","?module=iss&do=view&id=". $T_ITEM["from_key"]) ." - ". alink_pop("Acknowledgement","?module=iss&do=cust_ack&id=". $T_ITEM["from_key"]) ."</td>\n";
		} else if($T_ITEM["from_table"] == "invoices") {
			echo "  <td colspan=\"3\" align=\"center\">". alink_pop("Print Invoice","?module=invoice&do=print&id=". $T_ITEM["from_key"]) ." - ". alink("View Invoice","?module=invoice&do=view&id=". $T_ITEM["from_key"]) ."</td>\n";
		} else {
			echo "  <td colspan=\"3\"></td>\n";
		}
		if ($T_ITEM["is_refunded"]) {
			echo "  <td align=\"center\"><strike>Refund</strike></td>\n";
		} else {
			echo "  <td align=\"center\">".alink("Refund","?module=pos&do=refund&tid={$T_ITEM["id"]}&line={$T_ITEM["line_number"]}")."</td>\n";
		}
		$SUBTOTAL += ($T_ITEM["amt"] * $T_ITEM["qty"]);
		if ($T_ITEM["is_taxable"]) {
			$TAXABLE += ($T_ITEM["amt"] * $T_ITEM["qty"]);
		}
	} else {
		echo "  <td align=\"center\">".number_format($T_ITEM["amt"],2)."</td>\n";
		echo "  <td align=\"center\">".$T_ITEM["qty"]."</td>\n";
		echo "  <td align=\"center\">".($T_ITEM["is_taxable"] ? "Yes":"No")."</td>\n";
		echo "  <td align=\"center\">".number_format($T_ITEM["amt"] * $T_ITEM["qty"],2)."</td>\n";
		$SUBTOTAL += ($T_ITEM["amt"] * $T_ITEM["qty"]);
		if ($T_ITEM["is_taxable"]) {
			$TAXABLE += ($T_ITEM["amt"] * $T_ITEM["qty"]);
		}
		if ($T_ITEM["is_refunded"]) {
			echo "  <td align=\"center\"><strike>Refund</strike></td>\n";
		} else {
			echo "  <td align=\"center\">".alink("Refund","?module=pos&do=refund&tid={$T_ITEM["id"]}&line={$T_ITEM["line_number"]}")."</td>\n";
		}
	}
	echo " </tr>\n";
}

$TAX = round($TAXABLE * $tax_rate,2);

?>
 </table>

 <table border="0" width="100%">
  <tr>
   <td class="heading" align="right">Time of Sale</td>
   <td><?php echo $T_HEADER["tos"]; ?></td>
  </tr>
 </table>

  </td><td width="25%" valign="top">

  <table border="1" width="100%">
   <tr>
    <td class="heading" align="right">Subtotal</td>
    <td align="left"><?php echo number_format($SUBTOTAL,2); ?></td>
   </tr>
   <tr>
    <td class="heading" align="right">Tax</td>
    <td align="left"><?php echo number_format($TAX,2); ?></td>
   </tr>
   <tr>
    <td class="heading" align="right">Total</td>
    <td align="left"><?php echo number_format($SUBTOTAL + $TAX,2); ?></td>
   </tr>
   <tr bgcolor="#AAFFAA">
    <td align="right"><b>Cash Amount</b></td>
    <td align="left"><?php echo number_format($T_HEADER["paid_cash"],2); ?></td>
   </tr>
   <tr bgcolor="#AAFFAA">
    <td align="right"><b>Credit Amount</b></td>
    <td align="left"><?php echo number_format($T_HEADER["paid_credit"],2); ?></td>
   </tr>
   <tr bgcolor="#AAFFAA">
    <td align="right"><b>Check Amount</b></td>
    <td align="left"><?php echo number_format($T_HEADER["paid_check"],2); ?></td>
   </tr>
<?php

if ($T_HEADER["descr"]) {
	$p = explode("#",$T_HEADER["descr"]);
	$check_no = $p[1];
	echo <<<EOD
   <tr bgcolor="#FFFFAA">
    <td align="right"><b>Check Number</b></td>
    <td align="left">$check_no</td>
   </tr>
EOD;
}

?>
  </table>

  </td>
 </tr>
</table>

<?php

	if ($issue_id != 0 && isset($CUSTOMER)) {
		echo alink("Leave Feedback","?module=cust&do=feedback&id={$CUSTOMER["id"]}&issue_id=$issue_id");
	}

}

display_footer();

?>
