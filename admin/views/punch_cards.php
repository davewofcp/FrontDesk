<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<?php

if (isset($_POST["begin"])) $entered_begin = $_POST["begin"];
if (isset($_GET["begin"])) $entered_begin = $_GET["begin"];

if (isset($entered_begin) && preg_match('/\d{4}\-\d{2}\-\d{2}/',$entered_begin)) {
	$BEGIN = new DateTime(findMonday($entered_begin));
} else {
	$BEGIN = new DateTime(findMonday(date("Y-m-d")));
}
$END = clone $BEGIN;
$END->add(new DateInterval("P6D"));

function findMonday($d="",$format="Y-m-d") {
	if($d=="") $d=date("Y-m-d");
	$dparts = explode("-",$d);
	$mydate = mktime(12,0,0,$dparts[1],$dparts[2],$dparts[0]);
	$weekday = ((int)date( 'w', $mydate ) + 6 ) % 7;
	$prevmonday = $mydate - $weekday * 24 * 3600;
	return date($format,$prevmonday);
}

if (isset($_POST["punch_date"]) && preg_match('/\d{4}\-\d{2}\-\d{2}/',$_POST["punch_date"])) {
	$sql = "SELECT * FROM payroll_timecards WHERE org_entities__id = {$USER['org_entities__id']} AND users__id = ".intval($_GET["id"])." AND punch_date = '";
	$sql .= mysql_real_escape_string($_POST["punch_date"])."'";
	$result = mysql_query($sql);
	if (!mysql_num_rows($result)) {
		mysql_query("INSERT INTO payroll_timecards (users__id,punch_date,org_entities__id) VALUES (".intval($_GET["id"]).",'".$_POST["punch_date"]."',{$USER['org_entities__id']})");
		$PUNCH_ID = mysql_insert_id();
		$PUNCH = mysql_fetch_assoc(mysql_query("SELECT * FROM payroll_timecards WHERE org_entities__id = {$USER['org_entities__id']} AND id = $PUNCH_ID"));
	} else {
		$PUNCH = mysql_fetch_assoc($result);
		$PUNCH_ID = $PUNCH["id"];
	}

	switch (intval($_POST["punch_type"])) {
		case 1:
			$punch = "punch_in";
			$manual = "is_m_in";
			break;
		case 2:
			$punch = "break_out";
			$manual = "is_m_b_out";
			break;
		case 3:
			$punch = "break_in";
			$manual = "is_m_b_in";
			break;
		case 4:
			$punch = "punch_out";
			$manual = "is_m_out";
			break;
		default:
			break;
	}

	$HOUR = str_pad(intval($_POST["hour"]),2,"0",STR_PAD_LEFT);
	$MINUTE = str_pad(intval($_POST["minute"]),2,"0",STR_PAD_LEFT);

	mysql_query("UPDATE payroll_timecards SET $punch = '$HOUR:$MINUTE:00', ".$punch."_apt = NOW(), $manual = 1 WHERE org_entities__id = {$USER['org_entities__id']} AND id = $PUNCH_ID");

	// Update hours only if there are no open punches that day
	$PUNCH = mysql_fetch_assoc(mysql_query("SELECT * FROM payroll_timecards org_entities__id = {$USER['org_entities__id']} AND WHERE id = $PUNCH_ID"));
	if ($PUNCH["punch_in"] != null && $PUNCH["punch_out"] != null) {
		if ($PUNCH["break_out"] != null && $PUNCH["break_in"] != null) {
			calculate_hours($PUNCH_ID); // with break
		} else if ($PUNCH["break_out"] == null && $PUNCH["break_in"] == null) {
			calculate_hours($PUNCH_ID); // without break
		}
	}

	$RESPONSE = "Manual punch entered.";
} else if (isset($_POST["punch_date"]) && !preg_match('/\d{4}\-\d{2}\-\d{2}/',$_POST["punch_date"])) {
	$RESPONSE = "Invalid date entered.";
}

if (isset($_GET["id"])) {
	$data = mysql_query("SELECT * FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND id = ".intval($_GET["id"]));
	if (!mysql_num_rows($data)) {
		echo "<h3>User Not Found | ".alink("Back","?module=admin&do=punch")."</h3>";
	} else {
		echo "<h3>".alink("View All Punch Cards","?module=admin&do=punch")."</h3>\n";
		display_date_selector();
		$today = date("Y-m-d");
		echo <<<EOD
<form action="?module=admin&do=punch&id={$_GET["id"]}" method="post">
<table border="1">
 <tr>
  <td align="right" class="heading"><b>Date</b></td>
  <td><input type="text" name="punch_date" id="punch_date" size="10" value="$today"></td>
 </tr>
 <tr>
  <td align="right" class="heading"><b>Type of Punch</b></td>
  <td><select name="punch_type">
<option value="1">Punch In</option>
<option value="2">Break Start</option>
<option value="3">Break End</option>
<option value="4">Punch Out</option>
</select></td>
 </tr>
 <tr>
  <td align="right" class="heading"><b>Time</b></td>
  <td><select name="hour">
<option value="0">MN</option>
<option value="1">1am</option>
<option value="2">2am</option>
<option value="3">3am</option>
<option value="4">4am</option>
<option value="5">5am</option>
<option value="6">6am</option>
<option value="7">7am</option>
<option value="8">8am</option>
<option value="9">9am</option>
<option value="10">10am</option>
<option value="11">11am</option>
<option value="12" SELECTED>Noon</option>
<option value="13">1pm</option>
<option value="14">2pm</option>
<option value="15">3pm</option>
<option value="16">4pm</option>
<option value="17">5pm</option>
<option value="18">6pm</option>
<option value="19">7pm</option>
<option value="20">8pm</option>
<option value="21">9pm</option>
<option value="22">10pm</option>
<option value="23">11pm</option>
</select> <select name="minute">
<option value="0" SELECTED>:00</option>
<option value="15">:15</option>
<option value="30">:30</option>
<option value="45">:45</option>
</select></td>
 </tr>
 <tr>
  <td colspan="2" align="center"><input type="submit" value="Enter Manual Punch"></td>
 </tr>
</table>
</form>
<script type="text/javascript">
calendar.set("punch_date");
</script>
EOD;
		$_USER = mysql_fetch_assoc($data);
		$CARD = array();
		$result = mysql_query("SELECT * FROM payroll_timecards WHERE org_entities__id = {$USER['org_entities__id']} AND users__id = ".intval($_GET["id"])." AND punch_date >= '". $BEGIN->format("Y-m-d") ."' AND punch_date <= '". $END->format("Y-m-d") ."' ORDER BY punch_date");
		while ($row = mysql_fetch_assoc($result)) {
			$CARD[$row["punch_date"]] = $row;
		}
		display_timecard($BEGIN,$END,"Punch Card For ".$_USER["firstname"]." ".$_USER["lastname"],$CARD,1);
	}
} else {
	echo "<h2>Punch Cards</h2>";
	echo "From ".$BEGIN->format("Y-m-d")." to ".$END->format("Y-m-d")."<br><br>\n\n";
	display_date_selector();
	$CARDS = array();
	$names = array();
	$result = mysql_query("SELECT * FROM payroll_timecards p JOIN users u ON p.users__id = u.id WHERE p.org_entities__id = {$USER['org_entities__id']} AND punch_date >= '". $BEGIN->format("Y-m-d") ."' AND punch_date <= '". $END->format("Y-m-d") ."' ORDER BY u.lastname,p.punch_date");
	while ($row = mysql_fetch_assoc($result)) {
		if (!isset($CARDS[$row["users__id"]])) $CARDS[$row["users__id"]] = array();
		$CARDS[$row["users__id"]][$row["punch_date"]] = $row;
		$names[$row["users__id"]] = $row["firstname"] ." ". $row["lastname"];
	}
	foreach ($CARDS as $id => $card) {
		display_timecard($BEGIN,$END,$names[$id]." | ".alink("Edit","?module=admin&do=punch&id=$id&begin=".$BEGIN->format("Y-m-d")),$card,0);
		echo "<br>\n";
	}
}

function display_timecard($week_begin,$week_end,$heading,$card,$display_range = 0) {
	$BEGIN_STR = $week_begin->format("Y-m-d");
	$END_STR = $week_end->format("Y-m-d");
	echo "<h3>$heading</h3>\n";
	if ($display_range) echo "From $BEGIN_STR to $END_STR<br><br>\n\n";
	echo <<<EOD
<table border="1">
<tr class="heading" align="center">
<td>Day</td>
<td>Time In</td>
<td>Break Start</td>
<td>Break End</td>
<td>Time Out</td>
<td>Hours</td>
</tr>
EOD;
	$day = clone $week_begin;
	$count = 0;
	$hours_worked = 0;
	while ($count < 7) {
		unset($line);
		unset($punch_in);
		unset($break_out);
		unset($break_in);
		unset($punch_out);
		if (isset($card[$day->format("Y-m-d")])) {
			$line = $card[$day->format("Y-m-d")];
			if ($line["punch_in"] != null) $punch_in = explode(":",$line["punch_in"]);
			if ($line["break_out"] != null) $break_out = explode(":",$line["break_out"]);
			if ($line["break_in"] != null) $break_in = explode(":",$line["break_in"]);
			if ($line["punch_out"] != null) $punch_out = explode(":",$line["punch_out"]);
		}
		echo " <tr align=\"center\">\n";
		echo "  <td class=\"heading\">".$day->format("l jS")."</td>\n";
		if (!isset($line)) {
			echo "  <td colspan=\"4\"></td><td>0</td>\n";
		}
		else {
			echo "  <td>".(isset($punch_in) ? $punch_in[0].":".$punch_in[1] : "").($line["is_m_in"] ? "*":"")."</td>\n";
			echo "  <td>".(isset($break_out) ? $break_out[0].":".$break_out[1] : "").($line["is_m_b_out"] ? "*":"")."</td>\n";
			echo "  <td>".(isset($break_in) ? $break_in[0].":".$break_in[1] : "").($line["is_m_b_in"] ? "*":"")."</td>\n";
			echo "  <td>".(isset($punch_out) ? $punch_out[0].":".$punch_out[1] : "").($line["is_m_out"] ? "*":"")."</td>\n";
			echo "  <td>".$line["hours_worked"]."</td>\n";
			$hours_worked += $line["hours_worked"];
		}
		echo " </tr>\n";
		$day->add(new DateInterval("P1D"));
		$count++;
	}
	echo <<<EOD
 <tr align="center">
  <td class="major_heading">TOTAL</td>
  <td colspan="4"></td>
  <td>$hours_worked</td>
 </tr>
</table>
EOD;
}

function display_date_selector() {
	global $BEGIN;
	$begin_str = $BEGIN->format("Y-m-d");
	$id = "";
	if (isset($_GET["id"])) $id = "&id=".intval($_GET["id"]);
	echo <<<EOD
<form action="?module=admin&do=punch$id" method="post">
<b>Select Date:</b> <input type="text" name="begin" id="begin" size="10" value="$begin_str">
<input type="submit" value="Adjust Range"></form>
<script type="text/javascript">
calendar.set("begin");
</script>
EOD;
}

function calculate_hours($punch_id) {
  GLOBAL $USER;
	$result = mysql_query("SELECT TIMEDIFF(TIMEDIFF(punch_out,punch_in),TIMEDIFF(IFNULL(break_in,'00:00:00'),IFNULL(break_out,'00:00:00'))) AS hours FROM payroll_timecards WHERE org_entities__id = {$USER['org_entities__id']} AND id = $punch_id");
	$data = mysql_fetch_assoc($result);

	$hour_string = $data["hours"];
	$time_parts = explode(":",$hour_string);
	$hours_worked = $time_parts[0];
	$hours_worked += ($time_parts[1] / 60);

	mysql_query("UPDATE payroll_timecards SET hours_worked = '$hours_worked' WHERE org_entities__id = {$USER['org_entities__id']} AND id = $punch_id");

	return $hours_worked;
}

?>
