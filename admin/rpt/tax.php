<?php

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}

if (!isset($REPORT)) $REPORT = "";

$result = mysql_query("SELECT IFNULL(SUM(amt),0) AS subtotal FROM pos_transactions WHERE org_entities__id = {$store_id} AND line_number > 0 AND CAST(tos AS date) >= '$START' AND CAST(tos AS date) < '$END'");
$data = mysql_fetch_assoc($result);
$SUBTOTAL = floatval($data["subtotal"]);

$result = mysql_query("SELECT IFNULL(SUM(paid_cash + paid_credit + paid_check),0) AS total FROM pos_transactions WHERE org_entities__id = {$store_id} AND line_number = 0 AND CAST(tos AS date) >= '$START' AND CAST(tos AS date) < '$END'");
$data = mysql_fetch_assoc($result);
$TOTAL = floatval($data["total"]);

$TAX = $TOTAL - $SUBTOTAL;

$TAX = number_format($TAX,2);
$TOTAL = number_format($TOTAL,2);
$SUBTOTAL = number_format($SUBTOTAL,2);

$REPORT .= "<h3>Sales Tax Report</h3>\n";
$REPORT .= "From $START until $END<br><br>\n";
$REPORT .= "<b>Total Tax Collected:</b> $".$TAX."<br>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}

?>
