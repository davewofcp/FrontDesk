<?php

$TODAY = new DateTime(date("Y-m-d"));
$time = date("H:i:s");

$time_parts = explode(":",$time);
$HOUR = $time_parts[0];
$MINUTE = $time_parts[1];
$SECOND = $time_parts[2];

if ($SECOND >= 30) $MINUTE++;

rollover2();

if ($MINUTE < 8) {
	$MINUTE = "00";
} else if ($MINUTE >= 8 && $MINUTE <= 22) {
	$MINUTE = 15;
} else if ($MINUTE >= 23 && $MINUTE <= 37) {
	$MINUTE = 30;
} else if ($MINUTE >= 38 && $MINUTE <= 52) {
	$MINUTE = 45;
} else {
	$MINUTE = "00";
	$HOUR++;
}

rollover2();

$result = mysql_query("SELECT * FROM payroll_timecards WHERE org_entities__id = {$USER['org_entities__id']} AND users__id = ".$USER["id"]." ORDER BY punch_date DESC LIMIT 1");
if (!mysql_num_rows($result)) {
	$NEW_PUNCH = 1;
} else {
	$PUNCH = mysql_fetch_assoc($result);
	$PUNCH_ID = $PUNCH["id"];

	if ($PUNCH["break_out"] == null) $BREAK_OUT = 1;
	else if ($PUNCH["break_in"] == null) $BREAK_IN = 1;

	if ($PUNCH["punch_out"] == null && !isset($BREAK_IN)) $PUNCH_OUT = 1;
	else if ($PUNCH["punch_out"] != null) {
		if ($PUNCH["punch_date"] == date("Y-m-d")) {
			$DISABLE_PUNCH = 1;
			$NOTICE = "You have already punched out for the day.";
		} else {
			$PUNCH_IN = 1;
			$NEW_PUNCH = 1;
		}
	}
}

if (isset($PUNCH) && $PUNCH["punch_out"] == null && $PUNCH["punch_date"] != $TODAY->format("Y-m-d")) {
	$NEW_PUNCH = 1;
	$NOTICE = "Looks like you forgot to punch out from your last shift.<br>\nYou'll need an administrator to manually enter your punch-out time.";
}

if (isset($NEW_PUNCH)) {
	$DOING = "In For The Day";
	$ACTION = 1;
}
else {
	if (isset($BREAK_IN)) { $DOING = "In From Break"; $ACTION = 3; }
	else if (isset($BREAK_OUT)) { $DOING = "Out For The Day / Take Break"; $ACTION = 2; }
	if (!isset($BREAK_OUT) && isset($PUNCH_OUT)) { $DOING = "Out For The Day"; $ACTION = 4; }
}

if (isset($DISABLE_PUNCH)) $DOING = "Disabled";

$ADDR = $_SERVER['REMOTE_ADDR'];

if (isset($_GET["sub"]) && !isset($DISABLE_PUNCH)) {
	switch ($ACTION) {
		case 1:
			$sql = "INSERT INTO payroll_timecards (users__id,punch_date,punch_in,punch_in_apt,addrs,org_entities__id) VALUES (";
			$sql .= $USER["id"] .",";
			$sql .= "'". $TODAY->format("Y-m-d") ."',";
			$sql .= "'". $HOUR .":". $MINUTE .":00',";
			$sql .= "NOW(),'$ADDR',{$USER['org_entities__id']})";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
			$PUNCH_RESPONSE = "You have punched in for the day.";
			break;
		case 2:
			$out_break = intval($_POST["out_break"]);
			if ($out_break == 1) {
				$sql = "UPDATE payroll_timecards SET ";
				$sql .= "break_out = '". $HOUR .":". $MINUTE .":00',";
				$sql .= "break_out_apt = NOW(),";
				$sql .= "addrs = CONCAT(addrs,',$ADDR') ";
				$sql .= "WHERE org_entities__id = {$USER['org_entities__id']} AND id = $PUNCH_ID";
				mysql_query($sql) or die(mysql_error() ."::". $sql);
				$PUNCH_RESPONSE = "You have punched out for break.";
			} else if ($out_break == 2) {
				if (!$PUNCH["break_out"] && !$PUNCH["break_in"]) $ADDR = ",,".$ADDR;
				$sql = "UPDATE payroll_timecards SET ";
				$sql .= "punch_out = '". $HOUR .":". $MINUTE .":00',";
				$sql .= "punch_out_apt = NOW(),";
				$sql .= "addrs = CONCAT(addrs,',$ADDR') ";
				$sql .= "WHERE org_entities__id = {$USER['org_entities__id']} AND id = $PUNCH_ID";
				mysql_query($sql) or die(mysql_error() ."::". $sql);
				$hours_worked = calculate_hours();
				$PUNCH_RESPONSE = "You have punched out for the day ($hours_worked hours worked).";
			}
			break;
		case 3:
			$sql = "UPDATE payroll_timecards SET ";
			$sql .= "break_in = '". $HOUR .":". $MINUTE .":00',";
			$sql .= "break_in_apt = NOW(),";
			$sql .= "addrs = CONCAT(addrs,',$ADDR') ";
			$sql .= "WHERE org_entities__id = {$USER['org_entities__id']} AND id = $PUNCH_ID";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
			$PUNCH_RESPONSE = "You have punched in from break.";
			break;
		case 4:
			if (!$PUNCH["break_out"] && !$PUNCH["break_in"]) $ADDR = ",,".$ADDR;
			$sql = "UPDATE payroll_timecards SET ";
			$sql .= "punch_out = '". $HOUR .":". $MINUTE .":00',";
			$sql .= "punch_out_apt = NOW(),";
			$sql .= "addrs = CONCAT(addrs,',$ADDR') ";
			$sql .= "WHERE org_entities__id = {$USER['org_entities__id']} AND id = $PUNCH_ID";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
			$hours_worked = calculate_hours();
			$PUNCH_RESPONSE = "You have punched out for the day ($hours_worked hours worked).";
			break;
		default:
			break;
	}
	if (!isset($PUNCH_RESPONSE)) $PUNCH_RESPONSE = "Error: Invalid program state. Unable to punch.";
	echo "<h3>$PUNCH_RESPONSE</h3>";
} else {

	if (isset($NOTICE)) { echo "<font color=\"red\">$NOTICE</font><br><br>\n\n"; }

?>
<h3>Punch <?php echo $DOING; ?></h3>
<?php if (!isset($DISABLE_PUNCH)) { ?>
<form action="?module=core&do=punch&sub=1" method="post">
<?php if ($ACTION == 2) { ?>
<input type="radio" name="out_break" value="1"> Taking Break<br>
<input type="radio" name="out_break" value="2" CHECKED> Out For The Day<br>
<?php } ?>
<input type="submit" value="Punch (<?php echo $HOUR.":".$MINUTE; ?>)">
</form>
<?php
	}

}

?>

<h3>Your Punch Card For This Week</h3>

<table border="1">
 <tr class="heading" align="center">
  <td>Day</td>
  <td>Time In</td>
  <td>Break Start</td>
  <td>Break End</td>
  <td>Time Out</td>
  <td>Hours</td>
 </tr>
<?php

function findMonday($d="",$format="Y-m-d") {
	if($d=="") $d=date("Y-m-d");
	$dparts = explode("-",$d);
	$mydate = mktime(12,0,0,$dparts[1],$dparts[2],$dparts[0]);
	$weekday = ((int)date( 'w', $mydate ) + 6 ) % 7;
	$prevmonday = $mydate - $weekday * 24 * 3600;
	return date($format,$prevmonday);
}

$week_begin = new DateTime(findMonday(date("Y-m-d")));
$week_end = clone $week_begin;
$week_end->add(new DateInterval("P6D"));
$day = $week_begin;

$CARD = array();
$result = mysql_query("SELECT * FROM payroll_timecards WHERE org_entities__id = {$USER['org_entities__id']} AND users__id = ".$USER["id"]." AND punch_date >= '". $week_begin->format("Y-m-d") ."' AND punch_date <= '".$week_end->format("Y-m-d")."' ORDER BY punch_date");
while ($row = mysql_fetch_assoc($result)) {
	$CARD[$row["punch_date"]] = $row;
}
$count = 0;
$hours_worked = 0;
while ($count < 7) {
	unset($line);
	unset($punch_in);
	unset($break_out);
	unset($break_in);
	unset($punch_out);
	if (isset($CARD[$day->format("Y-m-d")])) {
		$line = $CARD[$day->format("Y-m-d")];
		if ($line["punch_in"] != null) $punch_in = explode(":",$line["punch_in"]);
		if ($line["break_out"] != null) $break_out = explode(":",$line["break_out"]);
		if ($line["break_in"] != null) $break_in = explode(":",$line["break_in"]);
		if ($line["punch_out"] != null) $punch_out = explode(":",$line["punch_out"]);
	}
	echo " <tr align=\"center\">\n";
	echo "  <td class=\"heading\">".$day->format("l jS")."</td>\n";
	if (!isset($line)) { echo "  <td colspan=\"4\"></td><td>0</td>\n"; }
	else {
		echo "  <td>".(isset($punch_in) ? $punch_in[0].":".$punch_in[1] : "")."</td>\n";
		echo "  <td>".(isset($break_out) ? $break_out[0].":".$break_out[1] : "")."</td>\n";
		echo "  <td>".(isset($break_in) ? $break_in[0].":".$break_in[1] : "")."</td>\n";
		echo "  <td>".(isset($punch_out) ? $punch_out[0].":".$punch_out[1] : "")."</td>\n";
		echo "  <td>".$line["hours_worked"]."</td>\n";
		$hours_worked += $line["hours_worked"];
	}
	echo " </tr>\n";
	$day->add(new DateInterval("P1D"));
	$count++;
}

?>
 <tr align="center">
  <td class="major_heading">TOTAL</td>
  <td colspan="4"></td>
  <td><?php echo $hours_worked; ?></td>
 </tr>
</table>

<?php

function rollover2() {
	global $TODAY,$HOUR,$MINUTE,$SECOND;
	if ($MINUTE == 60) {
		$HOUR++;
		$MINUTE = "00";
	}
	if ($HOUR == 24) {
		$HOUR = "00";
		$TODAY->add(new DateInterval('P1D'));
	}
}

function calculate_hours() {
	global $USER,$PUNCH_ID;

	$result = mysql_query("SELECT TIMEDIFF(TIMEDIFF(punch_out,punch_in),TIMEDIFF(IFNULL(break_in,'00:00:00'),IFNULL(break_out,'00:00:00'))) AS hours FROM payroll_timecards WHERE org_entities__id = {$USER['org_entities__id']} AND id = $PUNCH_ID");
	$data = mysql_fetch_assoc($result);

	$hour_string = $data["hours"];
	$time_parts = explode(":",$hour_string);
	$hours_worked = $time_parts[0];
	$hours_worked += ($time_parts[1] / 60);

	mysql_query("UPDATE payroll_timecards SET hours_worked = '$hours_worked' WHERE org_entities__id = {$USER['org_entities__id']} AND id = $PUNCH_ID");

	return $hours_worked;
}

?>