<?php

if (!isset($USERS)) $USERS = array();
if (isset($_POST["all"])) $ALL_USERS = 1;
else if (!isset($USERS) && !isset($ALL_USERS)) {
	$USER_MAX = intval($_POST["users"]);
	for ($i = 0; $i <= $USER_MAX; $i++) {
		if (isset($_POST["user".$i])) $USERS[] = $i;
	}
}
if (count($USERS) > 0) {
	$USTR = "IN (".join(",",$USERS).")";
} else {
	$USTR = "IS NOT NULL";
}

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

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

if (!isset($REPORT)) $REPORT = "";
$REPORT .= <<<EOF
<h3>User Report</h3>
$START until $END<br><br>
EOF;

$SERVICES = array();
$result = mysql_query("SELECT id,name FROM services WHERE 1");
while ($row = mysql_fetch_assoc($result)) {
	$SERVICES[$row["id"]] = $row["name"];
}

$INTAKE_ISSUES = array();
$result = mysql_query("
SELECT *
FROM issues i
LEFT JOIN inventory_type_devices d
ON i.device_id = d.id
LEFT JOIN customers c
ON i.customers__id = c.id
WHERE i.org_entities__id = {$store_id}
AND i.varref_issue_type = 1
AND CAST(i.intake_ts AS date) >= '$START'
AND CAST(i.intake_ts AS date) < '$END'
AND i.users__id__intake $USTR
ORDER BY i.users__id__intake,i.intake_ts
");
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($INTAKE_ISSUES[$row["users__id__intake"]])) $INTAKE_ISSUES[$row["users__id__intake"]] = array();
	$INTAKE_ISSUES[$row["users__id__intake"]][] = $row;
}

$RESOLVED_COUNT = array();
$RESOLVED_TOTAL = array();
$result = mysql_query("SELECT ic.users__id,IFNULL(COUNT(*),0) AS count,IFNULL(SUM(i.do_price),0) AS total FROM issue_changes ic LEFT JOIN issues i ON ic.issues__id = i.id WHERE ic.org_entities__id = {$store_id} AND ic.description = 'Resolved' AND CAST(ic.tou AS date) >= '$START' AND CAST(ic.tou AS date) < '$END' GROUP BY ic.users__id");
while ($row = mysql_fetch_assoc($result)) {
	$RESOLVED_COUNT[$row["users__id"]] = $row["count"];
	$RESOLVED_TOTAL[$row["users__id"]] = $row["total"];
}

$CHECKEDOUT_COUNT = array();
$CHECKEDOUT_TOTAL = array();
$result = mysql_query("SELECT users__id__sale,COUNT(*) AS count,SUM(amt * qty + (is_taxable * (amt * qty * $tax_rate))) AS total FROM pos_transactions WHERE org_entities__id = {$store_id} AND from_table = 'issues' AND CAST(tos AS date) >= '$START' AND CAST(tos AS date) < '$END' GROUP BY users__id__sale");
while ($row = mysql_fetch_assoc($result)) {
	$CHECKEDOUT_COUNT[$row["users__id__sale"]] = $row["count"];
	$CHECKEDOUT_TOTAL[$row["users__id__sale"]] = $row["total"];
}

$HOURS = array();
$result = mysql_query("SELECT users__id,SUM(hours_worked) AS hours FROM payroll_timecards WHERE org_entities__id = {$store_id} AND punch_date >= '$START' AND punch_date < '$END'");
WHILE ($row = mysql_fetch_assoc($result)) {
	$HOURS[$row["users__id"]] = $row["hours"];
}

$MONEY_TOTALS = array();
$result = mysql_query("SELECT users__id__sale,SUM(paid_cash + paid_credit + paid_check) AS total FROM pos_transactions WHERE org_entities__id = {$store_id} AND line_number = 0 AND CAST(tos AS date) >= '$START' AND CAST(tos AS date) < '$END' GROUP BY users__id__sale");
while ($row = mysql_fetch_assoc($result)) {
	$MONEY_TOTALS[$row["users__id__sale"]] = $row["total"];
}

$ISSUES = array();
$NOGO_ISSUES = array();
$result = mysql_query("
SELECT
  i.id,
  i.intake_ts,
  c.firstname,
  c.lastname,
  ic.tou,
  cg.category_name,
  d.model,
  i.services,
  i.varref_status,
  ic.varref_status AS ic_status,
  ic.users__id
FROM issue_changes ic
JOIN issues i
ON ic.issues__id = i.id
LEFT JOIN inventory_type_devices d
ON i.device_id = d.id
LEFT JOIN categories cg
ON d.categories__id = cg.id
LEFT JOIN customers c
ON i.customers__id = c.id
WHERE ic.org_entities__id = {$store_id}
AND ic.id
IN (
  SELECT MAX(id)
  FROM issue_changes
  GROUP BY users__id,varref_status,issues__id
)
AND CAST(ic.tou AS date) >= '$START'
AND CAST(ic.tou AS date) < '$END'
AND ic.varref_status != 0
ORDER BY ic.users__id,ic.varref_status
");

while ($row = mysql_fetch_assoc($result)) {
	if ($row["ic_status"] == 10) {
		if (!isset($NOGO_ISSUES[$row["users__id"]])) $NOGO_ISSUES[$row["users__id"]] = array();
		$NOGO_ISSUES[$row["users__id"]][] = $row;
	} else {
		if (!isset($ISSUES[$row["users__id"]])) $ISSUES[$row["users__id"]] = array();
		$ISSUES[$row["users__id"]][] = $row;
	}
}

$NOTES = array();
$result = mysql_query("SELECT user_notes.* FROM user_notes, users WHERE user_notes.users__id = users.id AND users.org_entities__id = {$store_id} AND CAST(note_ts AS date) >= '$START' AND CAST(note_ts AS date) < '$END' ORDER BY note_ts");
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($NOTES[$row["users__id"]])) $NOTES[$row["users__id"]] = array();
	$NOTES[$row["users__id"]][] = $row;
}

$users = mysql_query("SELECT * FROM users WHERE org_entities__id = {$store_id} AND is_disabled = 0 AND id $USTR");
while ($user = mysql_fetch_assoc($users)) {
	$REPORT .= "<div style=\"width:750px;border:1px solid #000;border-radius:5px;\" align=\"center\">\n";
	$REPORT .= "<font size=\"+1\" cellpadding=\"5\">{$user["firstname"]} {$user["lastname"]} ({$user["username"]})</font><br>\n";
	$REPORT .= "<table border=\"0\">\n";
	$REPORT .= "<tr><td align=\"right\" width=\"50%\">In-House Intake Issues</td><td width=\"50%\"><b>".(isset($INTAKE_ISSUES[$user["id"]]) ? count($INTAKE_ISSUES[$user["id"]]) : "0")."</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">In-House Issues Resolved</td><td><b>".(isset($RESOLVED_COUNT[$user["id"]]) ? $RESOLVED_COUNT[$user["id"]] : "0")."</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">In-House Issues Checked Out</td><td><b>".(isset($CHECKEDOUT_COUNT[$user["id"]]) ? $CHECKEDOUT_COUNT[$user["id"]] : "0")."</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">No Gos</td><td><b>".(isset($NOGO_ISSUES[$user["id"]]) ? count($NOGO_ISSUES[$user["id"]]) : "0")."</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">$ Resolved</td><td><b>$".(isset($RESOLVED_TOTAL[$user["id"]]) ? number_format($RESOLVED_TOTAL[$user["id"]],2) : "0.00")."</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">$ Checked Out</td><td><b>$".(isset($CHECKEDOUT_TOTAL[$user["id"]]) ? number_format($CHECKEDOUT_TOTAL[$user["id"]],2) : "0.00")."</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">Avg $ Per Issue</td><td><b>".(isset($CHECKEDOUT_COUNT[$user["id"]]) ? "$". number_format($CHECKEDOUT_TOTAL[$user["id"]] / $CHECKEDOUT_COUNT[$user["id"]],2) : "<i>N/A</i>")."</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">Hours Worked</td><td><b>".(isset($HOURS[$user["id"]]) ? $HOURS[$user["id"]] : "0")."</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">Avg $ Per Hour</td><td><b>";
	if (isset($HOURS[$user["id"]])) {
		if (isset($MONEY_TOTALS[$user["id"]])) {
		  // no divide by 0
		  IF ($HOURS[$user["id"]]==0) $REPORT .= "$".number_format($MONEY_TOTALS[$user["id"]] / 1,2);
			ELSE $REPORT .= "$".number_format($MONEY_TOTALS[$user["id"]] / $HOURS[$user["id"]],2);
		} else {
			$REPORT .= "$0.00";
		}
	} else {
		$REPORT .= "<i>N/A</i>";
	}
	$REPORT .= "</b></td></tr>\n";
	$REPORT .= "<tr><td align=\"right\">Total Sales</td><td><b>$".(isset($MONEY_TOTALS[$user["id"]]) ? number_format($MONEY_TOTALS[$user["id"]],2) : "0.00")."</b></td></tr>\n";
	$REPORT .= "</table><br>\n";

	if (!isset($NOGO_ISSUES[$user["id"]]) && !isset($ISSUES[$user["id"]])) {
		$noi = "No ";
	} else {
		$noi = "";
	}

	$REPORT .= "<b>{$noi}Issues</b><br>\n";
	$issue_header = "	<tr class=\"heading\" align=\"center\"><td>ID</td><td>Customer</td><td>Intake Date</td><td>Device Type</td><td>Model</td><td>Services</td><td>Status</td></tr>\n";
	$REPORT .= "<table border=\"0\">\n";
	if (isset($NOGO_ISSUES[$user["id"]])) {
		$REPORT .= "	<tr><td colspan=\"7\" align=\"center\"><b>No Go Issues</b></td></tr>\n";
		$REPORT .= $issue_header;

		foreach ($NOGO_ISSUES[$user["id"]] as $issue) {
			$REPORT .= "	<tr align=\"center\" style=\"background:{$ST_COLORS[$issue["varref_status"]]};\">\n";
			$REPORT .= "		<td>".$issue["id"]."</td>\n";
			$REPORT .= "		<td>".$issue["firstname"]." ".$issue["lastname"]."</td>\n";
			$REPORT .= "		<td>".$issue["intake_ts"]."</td>\n";
			$REPORT .= "		<td>".$issue["category_name"]."</td>\n";
			$REPORT .= "		<td>".$issue["model"]."</td>\n";
			$REPORT .= "		<td>";
			$did = false;
			$svc = explode(":",$issue["services"]);
			foreach ($svc as $s) {
				if (!$s) continue;
				if (!isset($SERVICES[$s])) continue;
				$REPORT .= "- ".$SERVICES[$s]."<br>\n";
				$did = true;
			}
			if (!$did) $REPORT .= "<i>None</i>";
			$REPORT .= "</td>\n";
			$REPORT .= "		<td>".$STATUS[$issue["varref_status"]]."</td>\n";
			$REPORT .= "	</tr>\n";
		}
	}

	if (isset($ISSUES[$user["id"]])) {
		$last_status = 0;
		foreach ($ISSUES[$user["id"]] as $issue) {
			if ($issue["ic_status"] != $last_status) {
				$REPORT .= "	<tr><td colspan=\"7\" align=\"center\"><b>".$STATUS[$issue["ic_status"]]." Issues</b></td></tr>\n";
				$REPORT .= $issue_header;
				$last_status = $issue["ic_status"];
			}

			$REPORT .= "	<tr align=\"center\" style=\"background:{$ST_COLORS[$issue["varref_status"]]};\">\n";
			$REPORT .= "		<td>".$issue["id"]."</td>\n";
			$REPORT .= "		<td>".$issue["firstname"]." ".$issue["lastname"]."</td>\n";
			$REPORT .= "		<td>".$issue["intake_ts"]."</td>\n";
			$REPORT .= "		<td>".$issue["category_name"]."</td>\n";
			$REPORT .= "		<td>".$issue["model"]."</td>\n";
			$REPORT .= "		<td>";
			$svc = explode(":",$issue["services"]);
			foreach ($svc as $s) {
				if (!$s) continue;
				if (!isset($SERVICES[$s])) continue;
				$REPORT .= "- ".$SERVICES[$s]."<br>\n";
			}
			$REPORT .= "</td>\n";
			$REPORT .= "		<td>".$STATUS[$issue["varref_status"]]."</td>\n";
			$REPORT .= "	</tr>\n";
		}
	}

	$REPORT .= "</table><br>\n";

	if (isset($NOTES[$user["id"]])) {
		$REPORT .= "<font size=\"+1\">Notes</font><br>\n";
		$REPORT .= "<table border=\"1\">\n";
		$REPORT .= "	<tr align=\"center\" style=\"font-weight:bold;\"><td>Type</td><td>ID</td><td>Note</td><td>Date/Time</td></tr>\n";
		foreach ($NOTES[$user["id"]] as $note) {
			$REPORT .= "	<tr>\n";
			$REPORT .= "		<td>";
			switch ($note["for_table"]) {
				case "invoices":
					$REPORT .= "Invoice";
					break;
				case "issues":
					$REPORT .= "Issue";
					break;
				default:
					$REPORT .= "Other";
					break;
			}
			$REPORT .= "</td>\n		<td>{$note["for_key"]}</td>\n";
			$REPORT .= "		<td>{$note["note"]}</td>\n";
			$REPORT .= "		<td>{$note["note_ts"]}</td>\n";
			$REPORT .= "	</tr>\n";
		}
		$REPORT .= "</table>\n";
	}
	$REPORT .= "</div><br>\n";
}

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}

?>
