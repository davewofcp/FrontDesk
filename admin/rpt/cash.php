<?php

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

//$result = mysql_query("SELECT value FROM config WHERE setting = 'tax_rate'");

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

// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}
// try to get store-specific tax rate
$result = mysql_query("SELECT tax_rate FROM org_entities WHERE id={$store_id} AND tax_rate IS NOT NULL LIMIT 1");
if (mysql_num_rows($result)) {
  $data = mysql_fetch_assoc($result);
  $tax_rate = floatval($data["tax_rate"]);
}

// hack fallback for now
if (!isset($tax_rate)) $tax_rate = floatval("0.08");

$result = mysql_fetch_assoc(mysql_query("SELECT SUM(paid_cash) AS cash,SUM(paid_check) AS checks,COUNT(*) AS count FROM pos_transactions WHERE org_entities__id = {$store_id} AND line_number = 0 AND (paid_cash != 0 OR paid_check != 0) AND CAST(tos AS date) >= '".$START."' AND CAST(tos AS date) < '".$END."'"));
$cash_total = number_format(floatval($result["cash"]),2);
$checks_total = number_format(floatval($result["checks"]),2);
$transactions = intval($result["count"]);

if (!isset($REPORT)) $REPORT = "";
$REPORT .= "<h3>Cash Report</h3>\n\nFrom ".$START." until ".$END."<br><br>\n\n";
$REPORT .= <<<EOF
<table border="0">
 <tr class="heading" align="center">
  <td>Total Cash</td>
  <td>Total Checks</td>
  <td>Transactions</td>
 </tr>
 <tr align="center">
  <td>$$cash_total</td>
  <td>$$checks_total</td>
  <td>$transactions</td>
 </tr>
</table>

<hr>
EOF;

$T_HEADERS = mysql_query("SELECT * FROM pos_transactions WHERE org_entities__id = {$store_id} AND line_number = 0 AND (paid_cash > 0 OR paid_check > 0) AND CAST(tos AS date) >= '".$START."' AND CAST(tos AS date) < '".$END."'");

while ($T_HEADER = mysql_fetch_assoc($T_HEADERS)) {
	$TID = $T_HEADER["id"];
	if ($T_HEADER["customers__id"]) {
		$result = mysql_query("SELECT * FROM customers WHERE id = ". $T_HEADER["customers__id"]);
		if (mysql_num_rows($result)) $CUSTOMER = mysql_fetch_assoc($result);
	}
	$customer_name = (isset($CUSTOMER) ? $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"] : "(No Customer)");
	$customer_home_phone = (isset($CUSTOMER) && $CUSTOMER["phone_home"] ? display_phone($CUSTOMER["phone_home"]) : "");
	$customer_cell_phone = (isset($CUSTOMER) && $CUSTOMER["phone_cell"] ? display_phone($CUSTOMER["phone_cell"]) : "");
	$customer_address = (isset($CUSTOMER) ? $CUSTOMER["address"] .", ". $CUSTOMER["city"] .", ". $CUSTOMER["state"] ." ". $CUSTOMER["postcode"] : "");

$REPORT .= <<<EOF
<h3>Transaction $TID</h3>
<table border="0" width="780">
 <tr>
  <td width="75%" valign="top">

	<table border="0" width="100%">
	 <tr>
	  <td class="heading" align="right">Customer</td>
	  <td>$customer_name</td>
	  <td class="heading" align="right">Home Phone</td>
	  <td>$customer_home_phone</td>
	  <td class="heading" align="right">Cell Phone</td>
	  <td>$customer_cell_phone</td>
	 </tr>
	 <tr>
	  <td class="heading" align="right">Address</td>
	  <td colspan="5">$customer_address</td>
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
EOF;

	$ROWS = $T_HEADER["qty"];
	$i = 0;
	$SUBTOTAL = 0;
	$TAXABLE = 0;
	while ($i < $ROWS) {
		$i++;
		$T_ITEM = mysql_fetch_assoc(mysql_query("SELECT * FROM pos_transactions WHERE id = ". $TID ." AND line_number = ". $i));
		if (!$T_ITEM) continue;

		$REPORT .= " <tr>\n";
		$REPORT .= "  <td>".$i."</td>\n";
		$REPORT .= "  <td>".$T_ITEM["descr"]."</td>\n";
		if ($T_ITEM["is_heading"]) {
			if ($T_ITEM["from_table"] == "issues") {
				$REPORT .= "  <td colspan=\"5\" align=\"center\">". alink_pop("Print Issue Receipt","?module=iss&do=receipt&id=". $T_ITEM["from_key"]) ." - ". alink("View Issue","?module=iss&do=view&id=". $T_ITEM["from_key"]) ."</td>\n";
			} else {
				$REPORT .= "  <td colspan=\"5\"></td>\n";
			}
		} else {
			$REPORT .= "  <td align=\"center\">".number_format($T_ITEM["amt"],2)."</td>\n";
			$REPORT .= "  <td align=\"center\">".$T_ITEM["qty"]."</td>\n";
			$REPORT .= "  <td align=\"center\">".($T_ITEM["is_taxable"] ? "Yes":"No")."</td>\n";
			$REPORT .= "  <td align=\"center\">".number_format($T_ITEM["amt"] * $T_ITEM["qty"],2)."</td>\n";
			$SUBTOTAL += ($T_ITEM["amt"] * $T_ITEM["qty"]);
			if ($T_ITEM["is_taxable"]) {
				$TAXABLE += ($T_ITEM["amt"] * $T_ITEM["qty"]);
			}
			$REPORT .= "  <td align=\"center\"><strike>Refund</strike></td>\n";
		}
		$REPORT .= " </tr>\n";
	}

	$TAX = round($TAXABLE * $tax_rate,2);
	$TOS = $T_HEADER["tos"];

	$TOTAL = $SUBTOTAL + $TAX;
	$SUBTOTAL = number_format($SUBTOTAL,2);
	$TAX = number_format($TAX,2);
	$TOTAL = number_format($TOTAL,2);

	$paid_cash = number_format($T_HEADER["paid_cash"],2);
	$paid_credit = number_format($T_HEADER["paid_credit"],2);
	$paid_check = number_format($T_HEADER["paid_check"],2);

$REPORT .= <<<EOF
	</table>

	<table border="0" width="100%">
	 <tr>
	  <td class="heading" align="right">Time of Sale</td>
	  <td>$TOS</td>
	 </tr>
	</table>

  </td><td width="25%" valign="top">

	<table border="1" width="100%">
	 <tr>
	  <td class="heading" align="right">Subtotal</td>
	  <td align="left">$$SUBTOTAL</td>
	 </tr>
	 <tr>
	  <td class="heading" align="right">Tax</td>
	  <td align="left">$$TAX</td>
	 </tr>
	 <tr>
	  <td class="heading" align="right">Total</td>
	  <td align="left">$$TOTAL</td>
	 </tr>
	 <tr bgcolor="#AAFFAA">
	  <td align="right"><b>Cash Amount</b></td>
	  <td align="left">$$paid_cash</td>
	 </tr>
	 <tr bgcolor="#AAFFAA">
	  <td align="right"><b>Credit Amount</b></td>
	  <td align="left">$$paid_credit</td>
	 </tr>
	 <tr bgcolor="#AAFFAA">
	  <td align="right"><b>Check Amount</b></td>
	  <td align="left">$$paid_check</td>
	 </tr>
	</table>

  </td>
 </tr>
</table>
EOF;

}

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
?>
