<?php

$SORT = "count";
if (isset($_GET["sort"]) && intval($_GET["sort"]) == 1) $SORT = "total";
if (isset($_GET["sort"]) && intval($_GET["sort"]) == 3) $SORT = "fbs";


// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}

$result = mysql_query("
SELECT
  firstname,
  lastname,
  pos_transactions.customers__id,
  IFNULL(AVG(score),-1) AS fbs,
  SUM(paid_cash + paid_credit + paid_check) AS total,
  count(*) AS count
FROM pos_transactions
JOIN customers
ON pos_transactions.customers__id = customers.id
LEFT JOIN feedback
ON feedback.customers__id = customers.id
WHERE org_entities__id = {$store_id}
AND line_number = 0
GROUP BY customers__id
ORDER BY ".$SORT." DESC
");

if (!isset($REPORT)) $REPORT = "";
$REPORT .= "<h3>Customer Report</h3>\n\n";

if (!isset($EMAILING)) {
	$REPORT .= "Sort By:<br>\n";
	$REPORT .= alink("Total Spent","?module=admin&do=rpt_cust&sort=1") ." | \n";
	$REPORT .= alink("Transactions","?module=admin&do=rpt_cust&sort=2") ." | \n";
	$REPORT .= alink("Feedback","?module=admin&do=rpt_cust&sort=3") ."\n\n<br><br>\n\n";
}

$REPORT .= <<<EOF
<font size="+2">Frequent Customers</font>
<table border="0">
 <tr class="heading" align="center">
  <td>Customer</td>
  <td>View</td>
  <td>Total Spent</td>
  <td>Transactions</td>
  <td>Avg Feedback</td>
 </tr>
EOF;

while ($row = mysql_fetch_assoc($result)) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>". $row["firstname"] ." ". $row["lastname"] ."</td>\n";
	$REPORT .= "  <td>". alink("View","?module=cust&do=view&id=".$row["customers__id"]) ."</td>\n";
	$REPORT .= "  <td>$". number_format(floatval($row["total"]),2) ."</td>\n";
	$REPORT .= "  <td>". $row["count"] ."</td>\n";
	if ($row["fbs"] >= 0) {
		$REPORT .= "  <td>". number_format($row["fbs"],2) ."</td>\n";
	} else {
		$REPORT .= "  <td><i>N/A</i></td>\n";
	}
	$REPORT .= " </tr>\n";
}

$REPORT .= "</table><br>";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
?>
