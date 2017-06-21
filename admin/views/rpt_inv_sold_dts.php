<?php
$YTDAY = new DateTime(date("Y-m-d"));
$YTDAY->sub(new DateInterval("P6D"));
?>
<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<h3>Inventory Sold Report</h3>
<form action="?module=admin&do=rpt_inv_sold" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Start Date</td>
  <td><input type="text" name="start" id="start" size="10" value="<?php echo $YTDAY->format("Y-m-d"); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">End Date (not inclusive)</td>
  <td><input type="text" name="end" id="end" size="10" value="<?php echo date('Y-m-d'); ?>"></td>
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