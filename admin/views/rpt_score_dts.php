<?php
$YTDAY = new DateTime(date("Y-m-d"));
$YTDAY->sub(new DateInterval("P1D"));
?>
<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<h3>User Score Report</h3>
<form action="?module=admin&do=user_score" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Select Date</td>
  <td><input type="text" name="start" id="start" size="10" value="<?php echo $YTDAY->format("Y-m-d"); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">End Date (not inclusive)</td>
  <td><input type="text" name="end" id="end" value="<?php echo date('Y-m-d'); ?>" size="10"></td>
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
