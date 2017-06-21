<?php
$YTDAY = new DateTime(date("Y-m-d"));
$YTDAY->sub(new DateInterval("P1D"));
?>
<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<h3>User Report</h3>

<form action="?module=admin&do=rpt_user" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Start Date</td>
  <td><input type="text" name="start" id="start" value="<?php echo $YTDAY->format("Y-m-d"); ?>" size="10"></td>
 </tr>
  <tr>
  <td class="heading" align="right">End Date (not inclusive)</td>
  <td><input type="text" name="end" id="end" value="<?php echo date('Y-m-d'); ?>" size="10"></td>
 </tr>
  <tr>
  <td class="heading" align="right">User(s)</td>
  <td>
  <input type="checkbox" name="all" value="1" CHECKED> <b>All Users</b><br>
<?php

$result = mysql_query("SELECT * FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND is_disabled = 0 ORDER BY username");
$max = 0;
while ($row = mysql_fetch_assoc($result)) {
	if ($row["id"] > $max) $max = $row["id"];
	echo "  <input type=\"checkbox\" name=\"user".$row["id"]."\" value=\"1\">(". $row["username"] .") ". $row["firstname"] ." ". $row["lastname"] ."<br>\n";
}

echo "<input type=\"hidden\" name=\"users\" value=\"". $max ."\">\n";

?>
  </td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <input type="submit" value="Get Report">
   <?php echo alink("Cancel","?module=admin"); ?>
  </td>
 </tr>
 </table>
</form>
<script type="text/javascript">
calendar.set("start");
calendar.set("end");
</script>
