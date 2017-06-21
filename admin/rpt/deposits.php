<?php

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}

$result = mysql_query("SELECT * FROM pos_deposits d LEFT JOIN users u ON d.users__id = u.id WHERE d.org_entities__id = {$store_id} AND CAST(d.tod AS date) >= '".mysql_real_escape_string($START)."' AND CAST(d.tod AS date) < '".mysql_real_escape_string($END)."' ORDER BY d.tod");
$DEPOSITS = array();
$DEPOSIT_TOTAL = 0;
while ($deposit = mysql_fetch_assoc($result)) {
	$DEPOSITS[] = $deposit;
	$DEPOSIT_TOTAL += $deposit["amt"];
}

$result = mysql_query("SELECT * FROM pos_cash_log cl LEFT JOIN users u ON cl.users__id = u.id WHERE cl.org_entities__id = {$store_id} AND cl.is_drop = 1 AND cl.is_deposited = 0 AND CAST(cl.ts AS date) >= '".mysql_real_escape_string($START)."' AND CAST(cl.ts AS date) < '".mysql_real_escape_string($END)."' ORDER BY cl.ts");
$UD_DROPS = array();
$UD_DROP_TOTAL = 0;
while ($drop = mysql_fetch_assoc($result)) {
	$UD_DROPS[] = $drop;
	$UD_DROP_TOTAL += $drop["amt"];
}

$result = mysql_query("SELECT * FROM pos_cash_log cl LEFT JOIN users u ON cl.users__id = u.id WHERE cl.org_entities__id = {$store_id} AND cl.is_drop = 1 AND cl.is_deposited = 1 AND CAST(cl.ts AS date) >= '".mysql_real_escape_string($START)."' AND CAST(cl.ts AS date) < '".mysql_real_escape_string($END)."' ORDER BY cl.ts");
$D_DROPS = array();
$D_DROP_TOTAL = 0;
while ($drop = mysql_fetch_assoc($result)) {
	$D_DROPS[] = $drop;
	$D_DROP_TOTAL += $drop["amt"];
}

$DEPOSIT_TOTAL = number_format($DEPOSIT_TOTAL,2);
$UD_DROP_TOTAL = number_format($UD_DROP_TOTAL,2);
$D_DROP_TOTAL = number_format($D_DROP_TOTAL,2);

if (!isset($REPORT)) $REPORT = "";
$REPORT .= <<<EOF
<h3>Deposit Report</h3>
From $START until $END<br>
Deposit total for this period: <b>$$DEPOSIT_TOTAL</b><br>
Undeposited Drop total for this period: <b>$$UD_DROP_TOTAL</b><br>
Deposited Drop total for this period: <b>$$D_DROP_TOTAL</b><br><br>

<h3>Deposits</h3>
<table border="0">
 <tr class="heading" align="center">
  <td>Date</td>
  <td>User</td>
  <td>Amount</td>
  <td>Drops</td>
 </tr>
EOF;

foreach ($DEPOSITS as $deposit) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>". $deposit["tod"] ."</td>\n";
	$REPORT .= "  <td>". $deposit["username"] ."</td>\n";
	$REPORT .= "  <td>$". number_format($deposit["amt"],2) ."</td>\n";
	$REPORT .= "  <td>";
	$drops = explode(":",$deposit["drops"]);
	foreach ($drops as $drop) {
		if (intval($drop) == 0) continue;
		$REPORT .= "#". $drop ." ";
	}
	$REPORT .= "</td>\n";
	$REPORT .= " </tr>\n";
}

$REPORT .= <<<EOF
</table><br>

<h3>Undeposited Drops</h3>
<table border="0">
 <tr class="heading" align="center">
  <td>#</td>
  <td>Date</td>
  <td>User</td>
  <td>Amount</td>
  <td>Cash / Check</td>
 </tr>
EOF;

foreach ($UD_DROPS as $drop) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>". $drop["id"] ."</td>\n";
	$REPORT .= "  <td>". $drop["ts"] ."</td>\n";
	$REPORT .= "  <td>". $drop["username"] ."</td>\n";
	$REPORT .= "  <td>$". number_format($drop["amt"],2) ."</td>\n";
	$REPORT .= "  <td>". ($drop["is_checks"] ? "Checks" : "Cash") ."</td>\n";
	$REPORT .= " </tr>\n";
}

$REPORT .= <<<EOF
</table><br>

<h3>Deposited Drops</h3>
<table border="0">
 <tr class="heading" align="center">
  <td>#</td>
  <td>Date</td>
  <td>User</td>
  <td>Amount</td>
  <td>Cash / Check</td>
 </tr>
EOF;

foreach ($D_DROPS as $drop) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>". $drop["id"] ."</td>\n";
	$REPORT .= "  <td>". $drop["ts"] ."</td>\n";
	$REPORT .= "  <td>". $drop["username"] ."</td>\n";
	$REPORT .= "  <td>$". number_format($drop["amt"],2) ."</td>\n";
	$REPORT .= "  <td>". ($drop["is_checks"] ? "Checks" : "Cash") ."</td>\n";
	$REPORT .= " </tr>\n";
}

$REPORT .= "</table><br>\n\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
?>
