<?php

// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}

if (!isset($REPORT)) $REPORT = "";

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

function microtime_float() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

$week_begin = new DateTime($START);
$week_begin_ts = $week_begin->getTimestamp();
$week_end = new DateTime($END);
$week_end_ts = $week_end->getTimestamp();
$interval = $week_begin->diff($week_end);
$interval_days = intval($interval->format('%a'));

$time_start = microtime(true);

// Get user list
$TOTAL_POINTS = array();
$ADJ_POINTS = array();
$ST_CHANGES = array();
$ST_CHG_POINTS = array();
$USERS = array();
$result = mysql_query("SELECT id,username FROM users WHERE org_entities__id = {$store_id} AND is_disabled = 0");
while ($row = mysql_fetch_assoc($result)) {
	$USERS[$row["id"]] = $row["username"];
	$ST_CHANGES[$row["id"]] = array();
	$ST_CHG_POINTS[$row["id"]] = 0;
	$TOTAL_POINTS[$row["id"]] = 0;
	$ADJ_POINTS[$row["id"]] = 0;
}

// Count all issue changes for each user
for ($k = 1; $k < count($STATUS); $k++) {
	$and = " AND (issue_changes.description LIKE 'Status%' OR issue_changes.description = 'Issue opened') ";
	$result = mysql_query("SELECT users__id,COUNT(*) AS count FROM issue_changes WHERE org_entities__id = {$store_id} AND users__id IS NOT NULL AND varref_status = ". $k ."".$and."AND CAST(tou AS date) >= '".$START."' AND CAST(tou AS date) < '".$END."' GROUP BY users__id");
	while ($row = mysql_fetch_assoc($result)) {
		if (!isset($ST_CHANGES[$row["users__id"]])) continue;
		$ST_CHANGES[$row["users__id"]][$k] = intval($row["count"]);

		if ($k == 10) {
			$ST_CHG_POINTS[$row["users__id"]] -= 0.005;
		} else if ($k == 5) {
			$ST_CHG_POINTS[$row["users__id"]] += 0.333;
		} else {
			$ST_CHG_POINTS[$row["users__id"]] += 0.25;
		}
	}
}

// Intake issues for return customers
$INTAKES = array();
$INTAKE_CUSTOMERS = array(0);
$result = mysql_query("SELECT i.customers__id,i.users__id__intake FROM issue_changes ic JOIN issues i ON ic.issues__id = i.id WHERE ic.org_entities__id = {$store_id} AND ic.varref_status = 6 AND description = 'Issue opened' AND CAST(ic.tou AS date) >= '$START' AND CAST(ic.tou AS date) < '$END'");
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($INTAKES[$row["users__id__intake"]])) $INTAKES[$row["users__id__intake"]] = array();
	$INTAKES[$row["users__id__intake"]][] = $row["customers__id"];
	$INTAKE_CUSTOMERS[] = $row["customers__id"];
}

// Get list of intake_customers that have had 2 or more issues (return customers)
$RETURN_CUSTOMERS = array();
$result = mysql_query("SELECT customers__id, COUNT(*) AS count FROM issues WHERE org_entities__id = {$store_id} AND customers__id IN (".join(",",$INTAKE_CUSTOMERS).") GROUP BY customers__id HAVING COUNT(*) > 1");
while ($row = mysql_fetch_assoc($result)) {
	$RETURN_CUSTOMERS[$row["customers__id"]] = $row["count"];
}

// Loop through intakes, check for return customers
$INTAKE_RETURNS = array();
foreach ($INTAKES as $uid => $customers) {
	foreach ($customers as $cust) {
		if (!isset($RETURN_CUSTOMERS[$cust])) continue;
		if (!isset($INTAKE_RETURNS[$uid])) $INTAKE_RETURNS[$uid] = 0;
		$INTAKE_RETURNS[$uid]++;
	}
}

$sql = "SELECT i.id,i.users__id__sale,i.amt,ii.id FROM invoices i JOIN (SELECT DISTINCT ";
$sql .= "invoices__id,id FROM issues WHERE org_entities__id = {$store_id} AND id IN (SELECT DISTINCT issues__id FROM issue_changes WHERE ";
$sql .= "varref_status = 10)) ii ON i.id = ii.invoices__id WHERE i.amt_paid >= i.amt AND i.amt != 0";
$sql .= " AND CAST(i.ts_paid AS date) >= '$START' AND CAST(i.ts_paid AS date) < '$END' AND i.users__id__sale IS NOT NULL";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
$PAID_NOGOS = array();
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($PAID_NOGOS[$row["users__id__sale"]])) $PAID_NOGOS[$row["users__id__sale"]] = array();
	$PAID_NOGOS[$row["users__id__sale"]][] = $row;
}

$sql = "SELECT COUNT(*) AS count,users__id FROM issue_changes WHERE org_entities__id = {$store_id} AND description = 'Resolved' AND users__id IS NOT NULL AND CAST(tou AS date) >= '$START' AND CAST(tou AS date) < '$END' GROUP BY users__id";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
$RESOLVED = array();
while ($row = mysql_fetch_assoc($result)) {
	$RESOLVED[$row["users__id"]] = $row["count"];
}

$sql = "SELECT id,service_steps FROM issues WHERE org_entities__id = {$store_id} AND CAST(last_step_ts AS date) >= '$START' AND service_steps IS NOT NULL AND service_steps != ''";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
$STEP_POINTS = array(); // Master Points
$SVCS = array(); // SVCS[user] = #, total # of services a step was completed on
$ISS = array(); // ISS[user][issue_id] = 1, so count(ISS[user]) will be # of issues a step was done on
while ($row = mysql_fetch_assoc($result)) {
	$ssteps = explode("|",$row["service_steps"]);
	$this_svc = "0";
	$count_done = array(); // count_done[svc][user] = # steps
	$total_steps = array(); // total_steps[svc] = # steps
	foreach ($ssteps as $step) {
		$sp = explode(":",$step);

		if ($this_svc != $sp[0]) { // First time and between services
			$this_svc = $sp[0];
			$count_done[$this_svc] = array();
			$total_steps[$this_svc] = 0;
		}

		$total_steps[$this_svc] += 1;

		if ($sp[4] < $week_begin_ts || $sp[4] > $week_end_ts) continue; // step done out of range

		if ($sp[2] == "1") {
			if (!isset($count_done[$this_svc][$sp[3]]))	$count_done[$this_svc][$sp[3]] = 0;
			$count_done[$this_svc][$sp[3]] += 1;
		}
	}

	// For each service, add x/n points to STEP_POINTS[user]
	foreach($total_steps as $svc => $count) {
		if (count($count_done[$svc]) == 0) continue; // none done, no points awarded
		foreach($count_done[$svc] as $user => $dcount) {
			if (!isset($STEP_POINTS[$user])) $STEP_POINTS[$user] = 0;
			$points = round($dcount / $count,3);
			$STEP_POINTS[$user] += $points;

			if (!isset($SVCS[$user])) $SVCS[$user] = 0;
			$SVCS[$user] += 1;

			if (!isset($ISS[$user])) $ISS[$user] = array();
			$ISS[$user][$row["id"]] = "1";
		}
	}
}

$sql = "SELECT COUNT(*) AS count,user_notes.users__id FROM user_notes JOIN users ON user_notes.users__id = users.id AND users.org_entities__id = {$store_id} WHERE user_notes.for_table = 'issues' AND CAST(user_notes.note_ts AS date) >= '$START' AND CAST(user_notes.note_ts AS date) < '$END' GROUP BY user_notes.users__id";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
$NOTES = array();
while ($row = mysql_fetch_assoc($result)) {
	$NOTES[$row["users__id"]] = $row["count"];
}

$sql = "SELECT hours_worked,users__id,punch_date FROM payroll_timecards WHERE org_entities__id = {$store_id} AND punch_date >= '$START' AND punch_date < '$END' GROUP BY users__id";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
if(!isset($WORKED_DAY))$WORKED_DAY = ARRAY();
$HOURS = array();
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($HOURS[$row["users__id"]])) $HOURS[$row["users__id"]] = 0;
	if (!isset($WORKED_DAY[$row["punch_date"]])) $WORKED_DAY[$row["punch_date"]] = array();
	$HOURS[$row["users__id"]] += $row["hours_worked"];
	$WORKED_DAY[$row["punch_date"]][] = $row["users__id"];
}

$DOLLARS = array();
$D_P_ISSUE = array();
$sql = "SELECT i.id,i.do_price,t.users__id__sale FROM issues i JOIN pos_transactions t ON i.id = t.from_key WHERE i.org_entities__id = {$store_id} AND t.from_table = 'issues' AND CAST(t.tos AS date) >= '$START' AND CAST(t.tos AS date) < '$END'";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($DOLLARS[$row["users__id__sale"]])) $DOLLARS[$row["users__id__sale"]] = 0;
	if (!isset($D_P_ISSUE[$row["users__id__sale"]])) $D_P_ISSUE[$row["users__id__sale"]] = 0;
	$D_P_ISSUE[$row["users__id__sale"]]++;
	$DOLLARS[$row["users__id__sale"]] += $row["do_price"];
}

$TOTAL_SALES = array();
$sql = "SELECT SUM(paid_cash + paid_credit + paid_check) AS total,users__id__sale FROM pos_transactions WHERE org_entities__id = {$store_id} AND line_number = 0 AND CAST(tos AS date) >= '$START' AND CAST(tos AS date) < '$END' GROUP BY users__id__sale";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
	$TOTAL_SALES[$row["users__id__sale"]] = $row["total"];
}

$TASK_SCORES = array();
$TASK_COUNT = array();
$TASKS_ASSIGNED = array();
$DTASKS_DONE = array();
$sql = "SELECT tasks__id,user_ids,points,date_done FROM tasks_completed WHERE org_entities__id = {$store_id} AND date_done >= '$START' AND date_done < '$END'";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
	$users = explode(",",$row["user_ids"]);
	if (!isset($DTASKS_DONE[$row["date_done"]])) $DTASKS_DONE[$row["date_done"]] = array();
	if (!isset($DTASKS_DONE[$row["date_done"]][$row["tasks__id"]])) $DTASKS_DONE[$row["date_done"]][$row["tasks__id"]] = array();
	foreach ($users as $uid) {
		if ($uid == "") continue;
		if (!isset($TASK_SCORES[$uid])) $TASK_SCORES[$uid] = 0;
		if (!isset($TASK_COUNT[$uid])) $TASK_COUNT[$uid] = 0;
		$TASK_SCORES[$uid] += $row["points"];
		$TASK_COUNT[$uid]++;
		$DTASKS_DONE[$row["date_done"]][$row["tasks__id"]][] = $uid;
	}
}

$DTASKS = array();
$result = mysql_query("SELECT task_id,created_date FROM recurring_tasks WHERE org_entities__id = {$store_id}");
while ($row = mysql_fetch_assoc($result)) {
	$DTASKS[$row["task_id"]] = $row["created_date"];
}

$DTASKS_NOTDONE = array();
foreach ($WORKED_DAY as $date => $users) {
	$d1 = new DateTime($date);
	foreach ($users as $uid) {
		foreach ($DTASKS as $dtid => $cdate) {
			$d2 = new DateTime($cdate);
			if ($d2 > $d1) continue; // task created after this worked day
			if (!isset($DTASKS_DONE[$date]) || !isset($DTASKS_DONE[$date][$dtid]) || !in_array($uid,$DTASKS_DONE[$date][$dtid])) {
				if (!isset($DTASKS_NOTDONE[$uid])) $DTASKS_NOTDONE[$uid] = 0;
				$DTASKS_NOTDONE[$uid]++;
				continue;
			}
		}
	}
}

$TASKS_NOTDONE = array();
$sql = "SELECT is_completed,users__id__assigned_to,points FROM tasks WHERE org_entities__id = {$store_id} AND CAST(toc AS date) >= '$START' AND CAST(toc AS date) < '$END'";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($TASK_SCORES[$row["users__id__assigned_to"]])) $TASK_SCORES[$row["users__id__assigned_to"]] = 0;
	if (!isset($TASK_COUNT[$row["users__id__assigned_to"]])) $TASK_COUNT[$row["users__id__assigned_to"]] = 0;
	if ($row["is_completed"]) {
		$TASK_SCORES[$row["users__id__assigned_to"]] += $row["points"];
		$TASK_COUNT[$row["users__id__assigned_to"]]++;
	} else {
		if (!isset($TASKS_NOTDONE[$row["users__id__assigned_to"]])) $TASKS_NOTDONE[$row["users__id__assigned_to"]] = 0;
		$TASKS_NOTDONE[$row["users__id__assigned_to"]]++;
	}
}

$INVOICES_CREATED = array(); // .075
$sql = "SELECT users__id__sale,COUNT(*) AS count FROM invoices WHERE org_entities__id = {$store_id} AND CAST(toi AS date) >= '$START' AND CAST(toi AS date) < '$END' GROUP BY users__id__sale";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
	$INVOICES_CREATED[$row["users__id__sale"]] = $row["count"];
}

$INVENTORY_ADDED = array(); // 0.125
$INVENTORY_QTY_MOD = array(); // 0.015
$INVENTORY_SOLD = array(); // 0.125
$sql = "SELECT id,users__id,varref_change_code,descr,qty FROM inventory_changes WHERE org_entities__id = {$store_id} AND varref_change_code IN (2,3,13) AND CAST(ts AS date) >= '$START' AND CAST(ts AS date) < '$END'";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
	switch ($row["varref_change_code"]) {
		case "2": // Item Added (.125 p.ea)
			if (!isset($INVENTORY_ADDED[$row["users__id"]])) $INVENTORY_ADDED[$row["users__id"]] = 0;
			$INVENTORY_ADDED[$row["users__id"]]++;
			break;
		case "3": // Edited (.015 p.ea for QTY changes)
			$f = explode("|",$row["descr"]);
			$qty = false;
			foreach ($f as $field) {
				$vals = explode(":",$field);
				if ($vals[0] == "QTY") {
					$qty = true;
					break;
				}
			}
			if (!$qty) continue;
			if (!isset($INVENTORY_QTY_MOD[$row["users__id"]])) $INVENTORY_QTY_MOD[$row["users__id"]] = 0;
			$INVENTORY_QTY_MOD[$row["users__id"]]++;
			break;
		case "13": // Sold (.125 ea)
			if (!isset($INVENTORY_SOLD[$row["users__id"]])) $INVENTORY_SOLD[$row["users__id"]] = 0;
			$INVENTORY_SOLD[$row["users__id"]] += $row["qty"];
			break;
	}
}

$time_end = microtime(true);
$time = round($time_end - $time_start,6);

$REPORT .= "<font size=\"+2\">User Scores</font><br><font size=\"+1\">From $START until $END</font><br>\n";
$REPORT .= "Report data gathered in ".$time." seconds.<br>Point values in parentheses.<br><br>";

$REPORT .= "<font size=\"+1\">Issue Status Changes</font><br>\n";
$REPORT .= "<table border=\"0\">\n";
$REPORT .= " <tr class=\"heading\" style=\"font-size:10pt;\" align=\"center\">\n";
$REPORT .= "  <td>Username</td>\n";
for ($k = 1; $k < count($STATUS); $k++) {
	$REPORT .= "  <td>". $STATUS[$k] ."</td>\n";
}
$REPORT .= "  <td>Total Points</td>\n";
$REPORT .= " </tr>\n";

foreach ($USERS as $user_id => $username) {
	$total = 0;
	$var=0;
	for ($k = 1; $k < count($STATUS); $k++) {
		if (!isset($ST_CHANGES[$user_id][$k])) $ST_CHANGES[$user_id][$k] = 0;
		if(intval($ST_CHANGES[$user_id][$k]) > 0){
			$var++;
		}
	}
	if($var > 0){
		$REPORT .= " <tr align=\"center\" style=\"font-size:10pt;\">\n";
		$REPORT .= "  <td>". $username ."</td>\n";
		for ($k = 1; $k < count($STATUS); $k++) {
			if (!isset($ST_CHANGES[$user_id][$k])) $ST_CHANGES[$user_id][$k] = 0;
			$REPORT .= "  <td>". intval($ST_CHANGES[$user_id][$k]);
			if ($k == 10) {
				$REPORT .= " (". ((intval($ST_CHANGES[$user_id][$k])) * (-0.2)) .")";
				$total += ((intval($ST_CHANGES[$user_id][$k])) * (-0.2));
			} else if ($k == 5) {
				$REPORT .= " (". ((intval($ST_CHANGES[$user_id][$k])) * (0.333)) .")";
				$total += ((intval($ST_CHANGES[$user_id][$k])) * (0.333));
			} else {
				$REPORT .= " (". ((intval($ST_CHANGES[$user_id][$k])) * (0.25)) .")";
				$total += ((intval($ST_CHANGES[$user_id][$k])) * (0.25));
			}
			$REPORT .= "</td>\n";
		}
		$REPORT .= "  <td>". $total ."</td>\n";
		$TOTAL_POINTS[$user_id] += $total;
		$REPORT .= " </tr>\n";
	}
}

$REPORT .= "</table><br>\n";

$REPORT .= "<font size=\"+1\">Issues</font><br>\n";
$REPORT .= "<table border=\"0\">\n";
$REPORT .= " <tr align=\"center\" class=\"heading\" style=\"font-size:10pt;\">\n";
$REPORT .= "  <td>Username</td>\n";
$REPORT .= "  <td>Sold After No-Go</td>\n";
$REPORT .= "  <td>Resolved</td>\n";
$REPORT .= "  <td>Notes</td>\n";
$REPORT .= "  <td>Return Customers</td>\n";
$REPORT .= "  <td>Total Points</td>\n";
$REPORT .= " </tr>\n";
foreach ($USERS as $id => $uname) {
	$total = 0;
	$REPORT .= " <tr align=\"center\" style=\"font-size:10pt;\">\n";
	$REPORT .= "  <td>". $uname ."</td>\n";
	if (isset($PAID_NOGOS[$id])) {
		$REPORT .= "  <td>". count($PAID_NOGOS[$id]) ." (".count($PAID_NOGOS[$id]).")</td>\n";
		$total += count($PAID_NOGOS[$id]);
	} else {
		$REPORT .= "  <td>0 (0)</td>\n";
	}
	if (isset($RESOLVED[$id])) {
		$REPORT .= "  <td>".$RESOLVED[$id]." (".round($RESOLVED[$id] / 3,2).")</td>\n";
		$total += ($RESOLVED[$id] / 3);
	} else {
		$REPORT .= "  <td>0 (0)</td>\n";
	}
	if (isset($NOTES[$id])) {
		$REPORT .= "  <td>".$NOTES[$id]." (".($NOTES[$id] * 0.005).")</td>\n";
		$total += ($NOTES[$id] * 0.005);
	} else {
		$REPORT .= "  <td>0 (0)</td>\n";
	}
	if (isset($INTAKE_RETURNS[$id])) {
		$REPORT .= "  <td>".$INTAKE_RETURNS[$id]." (".($INTAKE_RETURNS[$id] * 0.005).")</td>\n";
		$total += ($INTAKE_RETURNS[$id] * 0.005);
	} else {
			$REPORT .= "  <td>0 (0)</td>\n";
	}
	$REPORT .= "  <td>".round($total,3)."</td>\n";
	$TOTAL_POINTS[$id] += $total;
	$REPORT .= " </tr>\n";
}
$REPORT .= "</table><br>\n";

$REPORT .= "<font size=\"+1\">Inventory & Invoices</font><br>\n";
$REPORT .= "<table border=\"0\">\n";
$REPORT .= " <tr class=\"heading\" align=\"center\" style=\"font-size:10pt;\">\n";
$REPORT .= "  <td>Username</td>\n";
$REPORT .= "  <td>Invoices Created</td>\n";
$REPORT .= "  <td>Inventory Added</td>\n";
$REPORT .= "  <td>Quantities Changed</td>\n";
$REPORT .= "  <td>Inventory Sold</td>\n";
$REPORT .= "  <td>Total Points</td>\n";
$REPORT .= " </tr>\n";
foreach ($USERS as $id => $uname) {
	$total = 0;
	$REPORT .= " <tr align=\"center\" style=\"font-size:10pt;\">\n";
	$REPORT .= "  <td>$uname</td>\n";
	if (isset($INVOICES_CREATED[$id])) {
		$REPORT .= "  <td>".$INVOICES_CREATED[$id]." (".($INVOICES_CREATED[$id] * 0.075).")</td>\n";
		$total += ($INVOICES_CREATED[$id] * 0.075);
	} else {
		$REPORT .= "  <td>0 (0)</td>\n";
	}
	if (isset($INVENTORY_ADDED[$id])) {
		$REPORT .= "  <td>".$INVENTORY_ADDED[$id]." (".($INVENTORY_ADDED[$id] * 0.125).")</td>\n";
		$total += ($INVENTORY_ADDED[$id] * 0.125);
	} else {
		$REPORT .= "  <td>0 (0)</td>\n";
	}
	if (isset($INVENTORY_QTY_MOD[$id])) {
		$REPORT .= "  <td>".$INVENTORY_QTY_MOD[$id]." (".($INVENTORY_QTY_MOD[$id] * 0.015).")</td>\n";
		$total += ($INVENTORY_QTY_MOD[$id] * 0.015);
	} else {
		$REPORT .= "  <td>0 (0)</td>\n";
	}
	if (isset($INVENTORY_SOLD[$id])) {
		$REPORT .= "  <td>".$INVENTORY_SOLD[$id]." (".($INVENTORY_SOLD[$id] * 0.125).")</td>\n";
		$total += ($INVENTORY_SOLD[$id] * 0.125);
	} else {
		$REPORT .= "  <td>0 (0)</td>\n";
	}
	$REPORT .= "  <td>".round($total,3)."</td>\n";
	$REPORT .= " </tr>\n";
	$TOTAL_POINTS[$id] += $total;
}
$REPORT .= "</table><br>\n";

$REPORT .= "<font size=\"+1\">Service Steps</font><br>\n";
$REPORT .= "<table border=\"0\">\n";
$REPORT .= " <tr class=\"heading\" align=\"center\" style=\"font-size:10pt;\">\n";
$REPORT .= "  <td>Username</td>\n";
$REPORT .= "  <td>Issues Touched</td>\n";
$REPORT .= "  <td>Services Touched</td>\n";
$REPORT .= "  <td>Total Points</td>\n";
$REPORT .= " </tr>\n";
foreach ($USERS as $id => $uname) {
	$REPORT .= " <tr align=\"center\" style=\"font-size:10pt;\">\n";
	$REPORT .= "  <td>$uname</td>\n";
	if (isset($ISS[$id])) {
		$REPORT .= "  <td>". count($ISS[$id]) ."</td>\n";
	} else {
		$REPORT .= "  <td>0</td>\n";
	}
	if (isset($SVCS[$id])) {
		$REPORT .= "  <td>". $SVCS[$id] ."</td>\n";
	} else {
		$REPORT .= "  <td>0</td>\n";
	}
	if (isset($STEP_POINTS[$id])) {
		$TOTAL_POINTS[$id] += $STEP_POINTS[$id];
		$REPORT .= "  <td>". $STEP_POINTS[$id] ."</td>\n";
	} else {
		$REPORT .= "  <td>0</td>\n";
	}
	$REPORT .= " </tr>\n";
}
$REPORT .= "</table><br>\n";

$REPORT .= "<font size=\"+1\">Assigned Tasks</font><br>\n";
$REPORT .= "<table border=\"0\">\n";
$REPORT .= " <tr class=\"heading\" align=\"center\" style=\"font-size:10pt;\">\n";
$REPORT .= "  <td>Username</td>\n";
$REPORT .= "  <td>Tasks Completed</td>\n";
$REPORT .= "  <td>Daily Tasks Not Done</td>\n";
$REPORT .= "  <td>Indiv. Tasks Not Done</td>\n";
$REPORT .= "  <td>Total Points</td>\n";
$REPORT .= " </tr>\n";
foreach ($USERS as $uid => $username) {
	$REPORT .= " <tr align=\"center\" style=\"font-size:10pt;\">\n";
	$REPORT .= "  <td>".$username."</td>\n";
	if (isset($TASK_COUNT[$uid])) {
		$REPORT .= "  <td>".$TASK_COUNT[$uid]."</td>\n";
	} else {
		$REPORT .= "  <td><i>N/A</i></td>\n";
	}
	if (isset($DTASKS_NOTDONE[$uid])) {
		$REPORT .= "  <td>".$DTASKS_NOTDONE[$uid]." (-".($DTASKS_NOTDONE[$uid] * 0.005).")</td>\n";
		if (isset($TASK_SCORES[$uid])) $TASK_SCORES[$uid] -= ($DTASKS_NOTDONE[$uid] * 0.005);
	} else {
		$REPORT .= "  <td><i>N/A</i></td>\n";
	}
	if (isset($TASKS_NOTDONE[$uid])) {
		$REPORT .= "  <td>".$TASKS_NOTDONE[$uid]." (-".($TASKS_NOTDONE[$uid] * 0.005).")</td>\n";
		if (isset($TASK_SCORES[$uid])) $TASK_SCORES[$uid] -= ($TASKS_NOTDONE[$uid] * 0.005);
	} else {
		$REPORT .= "  <td><i>N/A</i></td>\n";
	}
	if (isset($TASK_SCORES[$uid])) {
		$REPORT .= "  <td>".$TASK_SCORES[$uid]."</td>\n";
	} else {
		$REPORT .= "  <td><i>N/A</i></td>\n";
	}
	$REPORT .= " </tr>\n";
	if (!isset($TOTAL_POINTS[$uid])) $TOTAL_POINTS[$uid] = 0;
	if (isset($TASK_SCORES[$uid])) $TOTAL_POINTS[$uid] += $TASK_SCORES[$uid];
}
$REPORT .= "</table><br>\n";

foreach ($USERS as $id => $uname) {
	if (isset($HOURS[$id]) && $HOURS[$id] > 0) {
		$ADJ_POINTS[$id] = ($TOTAL_POINTS[$id] / $HOURS[$id]) * 100;
	}
}

arsort($ADJ_POINTS);

$REPORT .= "<font size=\"+2\">User Scores</font><br>\n";
$REPORT .= "<table border=\"0\">\n";
$REPORT .= " <tr align=\"center\" class=\"heading\" style=\"font-size:10pt;\">\n";
$REPORT .= "  <td>Username</td>\n";
$REPORT .= "  <td>Total Points</td>\n";
$REPORT .= "  <td>Hours Worked</td>\n";
$REPORT .= "  <td>Score</td>\n";
$REPORT .= "  <td>$ / Issue</td>\n";
$REPORT .= "  <td>Total $</td>\n";
$REPORT .= " </tr>\n";
foreach ($ADJ_POINTS as $id => $score) {
		$REPORT .= " <tr align=\"center\">\n";
		$REPORT .= "  <td>". $USERS[$id] ."</td>\n";
		$REPORT .= "  <td>". round($TOTAL_POINTS[$id],3) ."</td>\n";
		if (isset($HOURS[$id])) {
			$REPORT .= "  <td>". $HOURS[$id] ."</td>\n";
		} else {
			$REPORT .= "  <td>0</td>\n";
		}
		$REPORT .= "  <td>". round($ADJ_POINTS[$id],3) ."</td>\n";
		if (isset($DOLLARS[$id])) {
			$val = round($DOLLARS[$id] / $D_P_ISSUE[$id],2);
			$REPORT .= "  <td>$".number_format($val,2)."</td>\n";
		} else {
			$REPORT .= "  <td><i>N/A</i></td>\n";
		}
		if (isset($TOTAL_SALES[$id])) {
			$REPORT .= "  <td>$".number_format($TOTAL_SALES[$id])."</td>\n";
		} else {
			$REPORT .= "  <td><i>N/A</i></td>\n";
		}
		$REPORT .= " </tr>\n";
}
$REPORT .= "</table><br>\n";

if (!isset($EMAILING) && !isset($STORE_RPT)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}

?>
