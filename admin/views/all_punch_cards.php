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

echo "<h2>All Punch Cards</h2>";
echo "From ".$BEGIN->format("Y-m-d")." to ".$END->format("Y-m-d")."<br><br>\n\n";
display_date_selector();

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

//mysql_close($DB);

$CARDS = array();
$names = array();

foreach ($LOCATIONS as $id => $loc) {
// 	$link = @mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"],true);
// 	if (!$link) {
// 		$ERRORS .= "<font color=\"#FF0000\"><b>Failed to connect to {$loc["name"]} - connection error.</b></font><hr>";
// 		continue;
// 	}
// 	$success = @mysql_select_db($loc["db_db"],$link);
// 	if (!$success) {
// 		$REPORT .= "<font color=\"#FF0000\"><b>Failed to connect to {$loc["name"]} - database error.</b></font><hr>";
// 		continue;
// 	}

	$CARDS[$id] = array();
	$names[$id] = array();

	$result = mysql_query("SELECT * FROM payroll_timecards p JOIN users u ON p.users__id = u.id WHERE p.org_entities__id = {$loc['id']} AND p.punch_date >= '". $BEGIN->format("Y-m-d") ."' AND p.punch_date <= '". $END->format("Y-m-d") ."' ORDER BY u.lastname,p.punch_date");//,$link);
	while ($row = mysql_fetch_assoc($result)) {
		if (!isset($CARDS[$id][$row["users__id"]])) $CARDS[$id][$row["users__id"]] = array();
		$CARDS[$id][$row["users__id"]][$row["punch_date"]] = $row;
		$names[$id][$row["users__id"]] = $row["firstname"] ." ". $row["lastname"];
	}
}

echo "<table border=\"1\" cellspacing=\"0\">\n";
foreach ($CARDS as $id => $crds) {
	$store_name = $LOCATIONS[$id]["title"];
	foreach ($crds as $uid => $timecard) {
		display_timecard($BEGIN,$END,$names[$id][$uid],$store_name,$timecard,0);
	}
}
echo "</table>";

function display_date_selector() {
	global $BEGIN;
	$begin_str = $BEGIN->format("Y-m-d");
	$id = "";
	if (isset($_GET["id"])) $id = "&id=".intval($_GET["id"]);
	echo <<<EOD
	<form action="?module=admin&do=all_punch$id" method="post">
	<b>Select Date:</b> <input type="text" name="begin" id="begin" size="10" value="$begin_str">
	<input type="submit" value="Adjust Range"></form>
	<script type="text/javascript">
	calendar.set("begin");
	</script>
EOD;
}

function display_timecard($week_begin,$week_end,$name,$store,$card,$display_range = 0) {
	$BEGIN_STR = $week_begin->format("Y-m-d");
	$END_STR = $week_end->format("Y-m-d");
	if ($display_range) echo "From $BEGIN_STR to $END_STR<br><br>\n\n";
	echo <<<EOD
	<tr class="heading" align="center">
	<td colspan="7">$name : $store</td>
	</tr>
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
			if ($line["addrs"] != null) {
				$addrs = explode(",",$line["addrs"]);
				while (count($addrs) < 4) $addrs[] = "NULL";
			} else {
				$addrs = array("NULL","NULL","NULL","NULL");
			}
		}
		echo " <tr align=\"center\">\n";
		echo "  <td class=\"heading\">".$day->format("l jS")."</td>\n";
		if (!isset($line)) {
			echo "  <td colspan=\"4\"></td><td>0</td>\n";
		}
		else {
			echo "  <td title=\"{$addrs[0]}\">".(isset($punch_in) ? $punch_in[0].":".$punch_in[1] : "").($line["is_m_in"] ? "*":"")."</td>\n";
			echo "  <td title=\"{$addrs[1]}\">".(isset($break_out) ? $break_out[0].":".$break_out[1] : "").($line["is_m_b_out"] ? "*":"")."</td>\n";
			echo "  <td title=\"{$addrs[2]}\">".(isset($break_in) ? $break_in[0].":".$break_in[1] : "").($line["is_m_b_in"] ? "*":"")."</td>\n";
			echo "  <td title=\"{$addrs[3]}\">".(isset($punch_out) ? $punch_out[0].":".$punch_out[1] : "").($line["is_m_out"] ? "*":"")."</td>\n";
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
EOD;
}

?>
