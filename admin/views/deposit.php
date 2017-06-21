<?php

$TOTAL = 0;
while ($entry = mysql_fetch_assoc($LOG)) {

	if ($entry["is_reset"]) {
		$TOTAL = $entry["amt"];
	} elseif($entry["is_drop"]) {
      $TOTAL -= $entry["amt"];
  } else {
      $TOTAL += $entry["amt"];
  }

}

?>
<h3>Deposit</h3>
<?php if (isset($DEPOSITED)) { ?>Deposited <b>$<?php echo number_format($DEPOSITED,2); ?></b><br><br><?php } ?>
<div>Albany Drawer Total: <b>$<?php echo number_format($TOTAL,2); ?></b></div>
<div class="clear"><br></div>
<form action="?module=admin&do=deposit_sub" method="post">
<table border="0" width="700">
 <tr class="heading" align="center">
  <td>Deposit</td>
  <td>Date / Time</td>
  <td>User</td>
  <td>Amount</td>
  <td>Cash / Check</td>
  <td width="200">Reason</td>
  <td>Total</td>
 </tr>
<?php

$LOG = mysql_query("SELECT * FROM pos_cash_log JOIN users ON pos_cash_log.users__id = users.id WHERE pos_cash_log.org_entities__id = {$USER['org_entities__id']} AND is_drop = 1 AND is_deposited = 0 ORDER BY ts");
while ($entry = mysql_fetch_assoc($LOG)) {
	echo " <tr align=\"center\" bgcolor=\"#CCCCFF\">\n";
	echo "  <td><input type=\"checkbox\" name=\"drop". $entry["id"] ."\" value=\"1\"></td>\n";
	echo "  <td>". $entry["ts"] ."</td>\n";
	echo "  <td>". $entry["username"] ."</td>\n";
	echo "  <td>$". number_format($entry["amt"],2) ."</td>\n";
	echo "  <td>". ($entry["is_checks"] ? "Check":"Cash") ."</td>\n";
	echo "  <td>". $entry["reason"] ."</td>\n";
	echo "  <td>$". number_format($entry["amt"],2) ."</td>\n";
	echo " </tr>\n";
}

?>
</table>

<br />

<input type="submit" value="Deposit Selected Drops">
</form>
