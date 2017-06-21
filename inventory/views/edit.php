<h3>Editing '<?php echo $ITEM["name"]; ?>' - Product Header</h3>

<?php if (isset($RESPONSE)) { ?><font color="red" size="+1"><b><?php echo $RESPONSE; ?></b></font><?php } ?>

<?php echo alink("View","?module=inventory&do=view&id=".$ITEM["id"]); ?><br><br>

<form action="?module=inventory&do=edit_sub&id=<?php echo $ITEM["id"]; ?>" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Reason for Edit</td>
  <td><input type="edit" id="reason" name="reason" size="40"></td>
 </tr>
 <tr>
  <td align="right" class="heading">Product ID</td>
  <td><?php echo $ITEM["id"]; ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">UPC</td>
  <td><input type="edit" name="upc" size="15" value="<?php echo ($ITEM["upc"] ? $ITEM["upc"] : ""); ?>"></td>
 </tr>
 <tr>
  <td align="right" class="heading">Inventory Type</td>
  <td><?php echo $ITEM["is_qty"] ? "Quantity" : "Individually Tracked"; ?></td>
 </tr>
<?php if ($ITEM["is_qty"]) { ?>
 <tr>
  <td align="right" class="heading">QTY In Stock</td>
  <td><input type="edit" name="qty" size="3" value="<?php echo $ITEM["qty"]; ?>"></td>
 </tr>
<?php } ?>
 <tr>
  <td class="heading" align="right">Low Quantity</td>
  <td>
   <input type="checkbox" name="is_lowqty" id="is_lowqty" value="1"<?php echo ($ITEM["do_notify_low_qty"] ? " CHECKED" : ""); ?>> Notify when <=
   <input type="edit" name="lowqty" id="lowqty" size="4" value="<?php echo ($ITEM["do_notify_low_qty"] ? $ITEM["low_qty"] : ""); ?>">
  </td>
 </tr>
 <tr>
  <td align="right" class="heading">Name</td>
  <td><input type="edit" name="name" size="55" value="<?php echo $ITEM["name"]; ?>"></td>
 </tr>
 <tr>
  <td align="right" class="heading">Description</td>
  <td><textarea name="descr" rows="5" cols="60"><?php echo $ITEM["descr"]; ?></textarea></td>
 </tr>
 <tr>
  <td align="right" class="heading">Device Type</td>
  <td>
   <select name="category">
<?php

$f_cat = $ITEM["category__id"];

$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NULL ORDER BY category_name");
$result2 = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NOT NULL ORDER BY category_name");
$SUBS = array();
while ($row2 = mysql_fetch_assoc($result2)) {
	if (!isset($SUBS[$row2["parent_id"]])) $SUBS[$row2["parent_id"]] = array();
	$SUBS[$row2["parent_id"]][] = $row2;
}
while ($row = mysql_fetch_assoc($result)) {
	echo "   <option value=\"{$row["id"]}\"".($row["id"] == $f_cat ? " SELECTED":"").">{$row["category_name"]}</option>\n";
	if (isset($SUBS[$row["id"]])) {
		foreach($SUBS[$row["id"]] as $row2) {
			echo "   <option value=\"{$row2["id"]}\"".($row2["id"] == $f_cat ? " SELECTED":"").">- {$row2["category_name"]}</option>\n";
		}
	}
}

?>
   </select>
  </td>
 </tr>
 <tr>
  <td align="right" class="heading">Purchase Price</td>
  <td><input type="edit" name="cost" size="6" value="<?php echo str_replace(",","",number_format($ITEM["purchase_price"],2)); ?>"></td>
 </tr>
 <tr>
  <td align="right" class="heading">Sale Price</td>
  <td><input type="edit" name="retail" size="6" value="<?php echo str_replace(",","",number_format($ITEM["cost"],2)); ?>"></td>
 </tr>
 <tr>
  <td align="right" class="heading">Taxable</td>
  <td><input type="checkbox" name="taxable" value="1"<?php echo ($ITEM["is_taxable"] ? " CHECKED" : ""); ?>></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Save Changes" onClick="return checkReason();">
  </td>
 </tr>
</table>
</form><br>

<script type="text/javascript">
function checkReason() {
	if (html('reason').value == '') {
		alert('Please enter a reason for the edit.');
		html('reason').focus();
		return false;
	}
	return true;
}
</script>
