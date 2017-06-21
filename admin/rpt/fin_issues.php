<?php

// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}

if (!isset($REPORT)) $REPORT = "";

$REPORT .= <<<END
<h3>Outstanding Issue Invoices</h3>

<table border="0">
 <tr align="center" class="heading">
  <td>Customer</td>
  <td>Phone (H)</td>
  <td>Phone (C)</td>
  <td>Issue</td>
  <td>Invoice</td>
  <td>Amount</td>
  <td>Days Old</td>
 </tr>
END;

$TOTAL = 0;
$result = mysql_query("SELECT c.id as customer_id,c.firstname,c.lastname,c.phone_home,c.phone_cell,i.id AS issue_id,iv.id AS invoice_id,iv.amt,TIMESTAMPDIFF(DAY,i.last_status_chg,NOW()) AS tlsc FROM issues i LEFT JOIN customers c ON i.customers__id = c.id LEFT JOIN invoices iv ON i.invoices__id = iv.id WHERE i.org_entities__id = {$store_id} AND i.varref_status = 9 AND i.invoices__id IS NOT NULL AND i.is_resolved = 0 ORDER BY tlsc DESC");
while ($row = mysql_fetch_assoc($result)) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>".$row["firstname"]." ".$row["lastname"]." (#{$row["customer_id"]})</td>\n";
	$REPORT .= "  <td>".display_phone($row["phone_home"])."</td>\n";
	$REPORT .= "  <td>".display_phone($row["phone_cell"])."</td>\n";
	$REPORT .= "  <td>".alink($row["issue_id"],"?module=iss&do=view&id={$row["issue_id"]}")."</td>\n";
	$REPORT .= "  <td>".alink($row["invoice_id"],"?module=invoice&do=view&id={$row["invoice_id"]}")."</td>\n";
	$REPORT .= "  <td>$".number_format($row["amt"],2)."</td>\n";
	$REPORT .= "  <td>".$row["tlsc"]."</td>\n";
	$REPORT .= " </tr>\n";
	$TOTAL += $row["amt"];
}

$REPORT .= "</table><br>\n";
$REPORT .= "<font size=\"+1\"><b>Total:</b> $".number_format($TOTAL,2)."</font><br>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}

?>