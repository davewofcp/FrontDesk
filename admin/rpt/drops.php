<?php

$LOCATIONS = array();
//$result = mysql_query("SELECT * FROM locations");
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
while ($row = mysql_fetch_assoc($result)) {
	$LOCATIONS[$row["id"]] = $row;
}

$DROPS = array();

if (!isset($REPORT)) $REPORT = "";

$REPORT .= "<h3>Drop Box Report</h3>\n\n";

$REPORT .= "<table border=\"0\">\n<tr align=\"center\" style=\"font-weight:bold;\">\n<td>Location</td><td>Drop Box Total</td>\n</tr>\n";

foreach ($LOCATIONS as $loc) {
// 	$link = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"],true);
// 	if (!$link) {
// 		$REPORT .= "<tr><td align=\"right\">{$loc["name"]}</td><td><font color=\"#FF0000\"><b>ERROR: Connect</b></font></td></tr>\n";
// 		continue;
// 	}
// 	$success = mysql_select_db($loc["db_db"],$link);
// 	if (!$success) {
// 		$REPORT .= "<tr><td align=\"right\">{$loc["name"]}</td><td><font color=\"#FF0000\"><b>ERROR: Select</b></font></td></tr>\n";
// 		continue;
// 	}

	$result = mysql_query("SELECT IFNULL(SUM(amt),0) AS drops FROM pos_cash_log WHERE org_entities__id = {$loc['id']} AND is_drop = 1 AND is_deposited = 0");
	$data = mysql_fetch_assoc($result);
	$REPORT .= "<tr><td align=\"right\">{$loc["title"]}</td><td align=\"center\">$".number_format($data["drops"],2)."</td></tr>\n";

	//mysql_close($link);
}

$REPORT .= "</table>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
//else {
	// Reset the database connection
// 	mysql_close($DB);
// 	$DB = mysql_connect($db_host, $db_user, $db_pass) or die("Couldn't connect to database.");
// 	mysql_select_db($db_database) or die("Couldn't select database.");
// }

?>
