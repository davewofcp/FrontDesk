<?php

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);


// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}

if (!isset($REPORT)) $REPORT = "";

$sql = "SELECT * FROM invoice_changes ic LEFT JOIN users u ON u.id = ic.changed_by WHERE ic.org_entities__id = {$store_id} AND CAST(ic.ts AS date) >= '$START' AND CAST(ic.ts AS date) < '$END' ORDER BY ic.ts DESC";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);

$REPORT .= <<<END
<h3>Invoice Change Log</h3>
From $START until $END<br><br>

<table border="0">
 <tr align="center" class="heading">
  <td>Invoice ID</td>
  <td>Changed By</td>
  <td>Date/Time</td>
  <td>Old Amount</td>
  <td>New Amount</td>
  <td>Description</td>
 </tr>
END;

while ($row = mysql_fetch_assoc($result)) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>".$row["invoice_id"]."</td>\n";
	$REPORT .= "  <td>".$row["username"]."</td>\n";
	$REPORT .= "  <td>".$row["ts"]."</td>\n";
	$REPORT .= "  <td>".$row["old_amt"]."</td>\n";
	$REPORT .= "  <td>".$row["new_amt"]."</td>\n";
	$REPORT .= "  <td align=\"left\" width=\"250\">".$row["change_summary"]."</td>\n";
	$REPORT .= " </tr>\n";
}

$REPORT .= "</table><br>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}

?>