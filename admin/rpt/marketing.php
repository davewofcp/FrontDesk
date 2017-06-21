<?php

// run only once, in case being included from a per-store loop

if (isset($run_once)) return true;
else $run_once = 1;

if (!isset($REPORT)) $REPORT = "";
$REPORT .= <<<EOF
<h3>Marketing Report</h3>

<font size="+2">Zipcodes with Most Customers</font>
<table border="0">
 <tr class="heading" align="center">
  <td>Zipcode</td>
  <td>Customers</td>
 </tr>
EOF;

$result = mysql_query("SELECT postcode,COUNT(*) AS count FROM customers GROUP BY postcode ORDER BY count DESC");
while ($row = mysql_fetch_assoc($result)) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>". $row["postcode"] ."</td>\n";
	$REPORT .= "  <td>". $row["count"] ."</td>\n";
	$REPORT .= " </tr>\n";
}

$REPORT .= <<<EOF
</table><br>

<font size="+2">Referrals</font>
<table border="0">
 <tr class="heading" align="center">
  <td>Referral</td>
  <td>Count</td>
 </tr>
EOF;

$result = mysql_query("SELECT referral,COUNT(*) AS count FROM customers WHERE referral != '' GROUP BY referral ORDER BY count DESC");
while ($row = mysql_fetch_assoc($result)) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>". $row["referral"] ."</td>\n";
	$REPORT .= "  <td>". $row["count"] ."</td>\n";
	$REPORT .= " </tr>\n";
}

$REPORT .= "</table><br>\n\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
?>
