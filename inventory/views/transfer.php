<?php

if (isset($_POST["location_id"])) $location_id = intval($_POST["location_id"]);
if (isset($_GET["location_id"])) $location_id = intval($_GET["location_id"]);
if (!isset($location_id)) {
	echo "Error: No location selected.";
	display_footer();
	exit;
}

$DT1 = array();
$result = mysql_query("SELECT id,category_name FROM categories WHERE parent_id IS NULL ORDER BY category_name");
while ($row = mysql_fetch_assoc($result)) {
	$DT1[$row["id"]] = $row["category_name"];
}

$DT2 = array();
$result = mysql_query("SELECT parent_id,id,category_name FROM categories WHERE parent_id IS NOT NULL ORDER BY category_name");
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($DT2[$row["parent_id"]])) $DT2[$row["parent_id"]] = array();
	$DT2[$row["parent_id"]][$row["id"]] = $row["category_name"];
}

if (isset($_POST["device_type"])) {
	if ($_POST["device_type"] == "Any") {
		$device_type = "IS NOT NULL";
		$dts = "Any";
	} else {
		$device_type = "= '". mysql_real_escape_string($_POST["device_type"]) ."'";
		$dts = $_POST["device_type"];
	}
} else {
	$device_type = "= '". mysql_real_escape_string($ITEM["category_name"]) ."'";
	$dts = $ITEM["category_name"];
}

?>

<h3>Inter-Store Transfer</h3>
<b>Item:</b> <?php echo $ITEM["name"]; ?><br>
<b>Device Type:</b> <?php echo $ITEM["category_name"]; ?><br>
<b>QTY In Stock:</b> <?php echo ($ITEM["is_qty"] ? $ITEM["qty"] : $ITEM["iqty"]); ?><br><br>

<form action="?module=inventory&do=transfer&id=<?php echo $ITEM["id"]; ?><?php if (isset($_GET["iid"])) { ?>&iid=<?php echo intval($_GET["iid"]); } ?>&location_id=<?php echo $location_id; ?>" method="post">
<b>Show Inventory of Type:</b> <select name="device_type"><option value="Any"<?php echo ($dts == "Any" ? " SELECTED":""); ?>>Any Type</option>
<?php

foreach ($DT1 as $id => $dt) {
		echo "<option value=\"$dt\"".($dt == $dts ? " SELECTED":"").">$dt</option>\n";
		if (isset($DT2[$id])) {
			foreach ($DT2[$id] as $dt2) {
				echo "<option value=\"$dt2\"".($dt2 == $dts ? " SELECTED":"").">- $dt2</option>\n";
			}
		}
}

?>
</select> <input type="submit" value="Go">
</form>

<form action="?module=inventory&do=transfer&id=<?php echo $ITEM["id"]; ?><?php if (isset($_GET["iid"])) { ?>&iid=<?php echo intval($_GET["iid"]); } ?>&to_loc=<?php echo $location_id; ?>" method="post">
<?php

$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$location_id}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
if (!mysql_num_rows($result)) {
	echo "<b>ERROR: Remote store not found in configuration.</b>";
	display_footer();
	exit;
}
$loc = mysql_fetch_assoc($result);

echo "<font size=\"+1\">Transferring to Store #{$loc["location_code"]} - {$loc["title"]}</font><br>\n";
echo "Please select the corresponding inventory item at the remote location (or 'NOT IN THIS LIST' if it doesn't exist in their inventory).<br><br>\n\n";
?>

<?php if (!isset($_GET["iid"])) { ?>
Transfer Quantity: <input type="edit" name="qty" value="1" size="3"><br><br>
<?php } ?>

<table border="0">
 <tr class="heading" align="center">
  <td>Select</td>
  <td>Remote ID</td>
  <td>Name</td>
  <td>Type</td>
  <td width="200">Description</td>
  <td>QTY</td>
 </tr>
 <tr>
  <td align="center"><input type="radio" name="to_id" value="0"></td>
  <td colspan="5" align="center">NOT IN THIS LIST</td>
 </tr>
<?php

// $DB2 = mysql_connect($loc["db_host"],$loc["db_user"],$loc["db_pass"],true) or die("Couldn't connect to remote database.");
// mysql_select_db($loc["db_db"],$DB2) or die("Couldn't connect to remote database.");

$result = mysql_query("SELECT * FROM inventory i JOIN categories c ON i.item_type_lookup = c.id WHERE org_entities__id = {$loc['id']} AND c.category_name $device_type");
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr>\n";
	echo "  <td align=\"center\"><input type=\"radio\" name=\"to_id\" value=\"{$row["id"]}\"></td>\n";
	echo "  <td align=\"center\">{$row["id"]}</td>\n";
	echo "  <td align=\"center\">{$row["name"]}</td>\n";
	echo "  <td align=\"center\">{$row["category_name"]}</td>\n";
	echo "  <td>{$row["descr"]}</td>\n";
	echo "  <td align=\"center\">{$row["qty"]}</td>\n";
	echo " </tr>\n";
}

?>
</table><br>
<input type="submit" value="Transfer Inventory">
</form>
