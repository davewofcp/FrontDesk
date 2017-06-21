<?php

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}

$LOG = mysql_query("SELECT * FROM pos_cash_log WHERE org_entities__id = {$store_id} AND ts < '". mysql_real_escape_string($START) ."'");
$TOTAL = 0;
while ($entry = mysql_fetch_assoc($LOG)) {
	if ($entry["is_reset"]) {
		$TOTAL = $entry["amt"];
	} else if ($entry["is_drop"]) {
		$TOTAL -= $entry["amt"];
	} else {
		$TOTAL += $entry["amt"];
	}
}

$TOTAL_DISPLAY = number_format($TOTAL,2);

if (!isset($REPORT)) $REPORT = "";
$REPORT .= <<<EOF
<h3>Cash Log Report</h3>
From $START until $END<br>
Drawer total before this time period was $$TOTAL_DISPLAY<br><br>

<table border="0" width="700">
 <tr class="heading" align="center">
  <td>Date / Time</td>
  <td>User</td>
  <td>Amount</td>
  <td>Type</td>
  <td>Cash / Check</td>
  <td width="200">Reason</td>
  <td>Transaction</td>
  <td>Total</td>
 </tr>
EOF;

$LOG = mysql_query("SELECT * FROM pos_cash_log JOIN users ON pos_cash_log.users__id = users.id WHERE pos_cash_log.org_entities__id = {$store_id} AND CAST(ts AS date) >= '". mysql_real_escape_string($START) ."' AND CAST(ts AS date) < '". mysql_real_escape_string($END) ."' ORDER BY ts");
while ($entry = mysql_fetch_assoc($LOG)) {
	if ($entry["is_reset"]) {
		$TOTAL = $entry["amt"];
	} else if ($entry["is_drop"]) {
		$TOTAL -= $entry["amt"];
	} else {
		$TOTAL += $entry["amt"];
	}

  $style="";
	if ($entry["is_reset"]) {
		$type = "Total";
		$bgcolor = "#FFFFFF";
	} elseif ($entry["amt"] < 0) {
		$type = "Removed";
		$bgcolor = "#FFCCCC";
	} elseif ($entry["is_drop"]){
		$type = "Drop";
		$bgcolor = "#CCCCFF";
		$style = "border:1px solid red;";
  } else {
		$type = "Added";
		$bgcolor = "#CCFFCC";
	}

	$REPORT .= " <tr align=\"center\" bgcolor=\"".$bgcolor."\">\n";
	$REPORT .= "  <td>". $entry["ts"] ."</td>\n";
	$REPORT .= "  <td>". $entry["username"] ."</td>\n";
	$REPORT .= "  <td>$". number_format($entry["amt"],2) ."</td>\n";
	$REPORT .= "  <td>". $type ."</td>\n";
	$REPORT .= "  <td>". ($entry["is_checks"] ? "Checks":"Cash") ."</td>\n";
	$REPORT .= "  <td>". $entry["reason"] ."</td>\n";
	$REPORT .= "  <td>". ($entry["pos_transactions__id"] == null ? "<i>N/A</i>" : alink("# ". $entry["pos_transactions__id"],"?module=pos&do=view_trans&tid=". $entry["pos_transactions__id"])) ."</td>\n";
	$REPORT .= "  <td>$". number_format($TOTAL,2) ."</td>\n";
	$REPORT .= " </tr>\n";
}

$REPORT .= "</table><br>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
?>
