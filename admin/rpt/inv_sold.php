<?php

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

if (!isset($REPORT)) $REPORT = "";

$REPORT .= "<h3>Inventory Sold Report</h3>";
$REPORT .= "From $START until $END<br><br>\n\n";

$USERS = array();
$result = mysql_query("SELECT id,username FROM users");
while ($row = mysql_fetch_assoc($result)) {
	$USERS[$row["id"]] = $row["username"];
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

$COST = array();
$RETAIL = array();
$TAXABLE = array();
$ITEMS = array();

$sql = "SELECT t.amt,t.qty,t.users__id__sale,i.id,i.purchase_price,i.cost,i.is_taxable FROM pos_transactions t LEFT JOIN inventory i ON CAST(t.from_key AS unsigned) = i.id WHERE t.org_entities__id = {$store_id} AND t.line_number != 0 AND t.from_table = 'inventory' AND CAST(t.tos AS date) >= '$START' AND CAST(t.tos AS date) < '$END' ORDER BY t.id";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($COST[$row["users__id__sale"]])) $COST[$row["users__id__sale"]] = 0;
	if (!isset($RETAIL[$row["users__id__sale"]])) $RETAIL[$row["users__id__sale"]] = 0;
	if (!isset($TAXABLE[$row["users__id__sale"]])) $TAXABLE[$row["users__id__sale"]] = 0;
	if (!isset($ITEMS[$row["users__id__sale"]])) $ITEMS[$row["users__id__sale"]] = 0;

	$ITEMS[$row["users__id__sale"]] += 1;

	$item_cost = $row["cost"] * $row["qty"];
	if ($row["is_taxable"]) $TAXABLE[$row["users__id__sale"]] += $item_cost;
	$RETAIL[$row["users__id__sale"]] += $item_cost;
	$COST[$row["users__id__sale"]] += $row["purchase_price"];
}

$REPORT .= "<table border=\"0\">\n";
$REPORT .= " <tr class=\"heading\" align=\"center\">\n";
$REPORT .= "  <td>Username</td>\n";
$REPORT .= "  <td>Items Sold</td>\n";
$REPORT .= "  <td>Total Cost</td>\n";
$REPORT .= "  <td>Total Retail</td>\n";
$REPORT .= "  <td>Sales Tax</td>\n";
$REPORT .= "  <td>Total Profit</td>\n";
$REPORT .= " </tr>\n";
foreach ($COST as $uid => $c) {
	if (!isset($USERS[$uid])) $uname = "<i>Deleted User</i>";
	else $uname = $USERS[$uid];
	$tax_collected = round($TAXABLE[$uid] * $tax_rate,2);
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>$uname</td>\n";
	$REPORT .= "  <td>".$ITEMS[$uid]."</td>\n";
	$REPORT .= "  <td>$".number_format($c,2)."</td>\n";
	$REPORT .= "  <td>$".number_format($RETAIL[$uid],2)."</td>\n";
	$REPORT .= "  <td>$".number_format($tax_collected,2)."</td>\n";
	$REPORT .= "  <td>$".number_format($RETAIL[$uid] - $tax_collected - $c,2)."</td>\n";
	$REPORT .= " </tr>\n";
}
$REPORT .= "</table>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}

?>