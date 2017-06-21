<?php
$YTDAY = new DateTime(date("Y-m-d"));
$YTDAY->sub(new DateInterval("P1D"));
?>
<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<h3>Store Report</h3>
<form action="?module=admin&do=rpt_store" method="post">
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
  <td class="heading" align="right">Store(s)</td>
  <td>
  <input type="checkbox" name="all" value="1" CHECKED> <b>All Stores</b><br>
<?php

//$result = mysql_query("SELECT * FROM locations ORDER BY store_number");
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
ORDER BY
  location_code
");
$max = 0;
while ($row = mysql_fetch_assoc($result)) {
	if ($row["id"] > $max) $max = $row["id"];
	echo "  <input type=\"checkbox\" name=\"store".$row["id"]."\" value=\"1\"> #". $row["location_code"] ." ". $row["title"] ." - ". $row["city"] .", ". $row["state"] ."<br>\n";
}

echo "<input type=\"hidden\" name=\"stores\" value=\"". $max ."\">\n";

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
