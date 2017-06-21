<h3>Store Locations</h3>
<table width="780" border="0">
 <tr class="heading" style="font-size:10pt;" align="center">
  <td>Store #</td>
  <td>Title</td>
  <td>Address</td>
  <td>Delete</td>
 </tr>
<?php

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
	echo " <tr style=\"font-size:10pt;\" align=\"center\">\n";
	echo "  <td>".$row["location_code"]."</td>\n";
	echo "  <td>".$row["title"]."</td>\n";
	echo "  <td>".$row["address"].", ".$row["city"].", ".$row["state"]." ".$row["postcode"]."</td>\n";
	echo "  <td>".alink("Delete","?module=admin&do=delete_location&id=".$row["id"])."</td>\n";
	echo " </tr>\n";
}

$store_entity_type=mysql_fetch_assoc(mysql_query("SELECT id FROM org_entity_types WHERE title='Store'"));

?>
</table>
<br>
<form action="?module=admin&do=add_location" method="post">
<?php echo '<input type="hidden" name="org_entity_type" value="'.$store_entity_type['id'].'">'."\n"; ?>
<table width="50%">
 <tr>
  <td class="heading" align="right">Store #</td>
  <td><input type="edit" name="store_number" size="10"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Title</td>
  <td><input type="edit" name="name" size="20"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Address</td>
  <td><input type="edit" name="address" size="25"></td>
 </tr>
 <tr>
  <td class="heading" align="right">City</td>
  <td><input type="edit" name="city" size="15"></td>
 </tr>
 <tr>
  <td class="heading" align="right">State</td>
  <td><input type="edit" name="state" size="2"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Zip</td>
  <td><input type="edit" name="zip" size="5"></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Add Location">
  </td>
 </tr>
</table>
</form>

<?php echo alink("Back to Administration","?module=admin"); ?>
