<?php

// run only once, in case being included from a per-store loop

if (isset($run_once)) return true;
else $run_once = 1;

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

$STORES = array();
//$result = mysql_query("SELECT * FROM locations ORDER BY store_number");
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
ORDER BY
  oe.location_code
");
while ($row = mysql_fetch_assoc($result)) {
		$STORES[] = $row;
}

if (!isset($REPORT)) $REPORT = "";

$REPORT .= "<h3>Punch Cards Report</h3>\n";
$REPORT .= "<table border=\"0\">\n";
$REPORT .= "<tr align=\"center\" style=\"font-weight:bold;\"><td>User</td><td>Date</td><td>Hours</td><td>Store</td></tr>\n";

foreach($STORES as $store) {
// 	mysql_close();
// 	$DB = mysql_connect($store["db_host"],$store["db_user"],$store["db_pass"],true);
// 	mysql_select_db($store["db_db"],$DB);

	$result = mysql_query("SELECT *,p.users__id FROM payroll_timecards p JOIN users u ON p.users__id = u.id WHERE p.org_entities__id = {$store['id']} AND p.punch_date >= '$START' AND p.punch_date < '$END' ORDER BY u.lastname");
	while ($row = mysql_fetch_assoc($result)) {
		$REPORT .= "<tr align=\"center\"><td>{$row["firstname"]} {$row["lastname"]}</td>";
		$REPORT .= "<td>{$row["punch_date"]}</td>";
		$REPORT .= "<td>".floatval($row["hours_worked"])."</td>";
		$REPORT .= "<td>{$store["title"]}</td></tr>\n";
	}
}

$REPORT .= "</table>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
//else {
	// Reset the database connection
//	mysql_close($DB);
//	$DB = mysql_connect($db_host, $db_user, $db_pass) or die("Couldn't connect to database.");
//	mysql_select_db($db_database) or die("Couldn't select database.");
//}

?>
