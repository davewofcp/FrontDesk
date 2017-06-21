<?php display_header(); ?>
<h3>Cash Adjustment</h3>
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
Primary use is for drops and opening/closing<br>
Current Drawer Total: <b>$<?php echo number_format($TOTAL,2); ?></b> (Including Checks)<br>

<form action="?module=pos&do=cash_adjust_sub" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Amount</td>
  <td><input type="edit" name="amt" size="10"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Action</td>
  <td>
   <input type="radio" name="action" value="1"> Add<br>
   <input type="radio" name="action" value="2" checked> Subtract<br>
   <input type="radio" name="action" value="3"> Reset Total (Open/Close/Midday)<br>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Cash / Check</td>
  <td>
   <input type="radio" name="is_checks" value="0" checked> Cash<br>
   <input type="radio" name="is_checks" value="1"> Checks<br>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Reason</td>
  <td><textarea name="reason" rows="4" cols="50"></textarea></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <input type="submit" value="Add Adjustment">
   <?php echo alink("Cancel","?module=pos"); ?>
  </td>
 </tr>
</table>
</form>
<?php display_footer(); ?>
