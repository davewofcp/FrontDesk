<h3>Edit Order <?php echo $ORDER["id"]; ?> |
<?php echo alink("View","?module=orders&do=view&id=".$ORDER["id"]); ?> |
<?php echo alink_onclick("Delete","?module=orders&do=delete&id=".$ORDER["id"],"javascript:return confirm('Are you sure you want to delete this order?');"); ?></h3>
<form action="?module=orders&do=edit&id=<?php echo $ORDER["id"]; ?>" method="post">
<table border="0" cellspacing="3">
 <tr>
  <td class="heading" align="right">Purchased From</td>
  <td><input type="edit" name="purchased_from" size="30" value="<?php echo $ORDER["purchased_from"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Order Number</td>
  <td><input type="edit" name="order_number" size="30" value="<?php echo $ORDER["order_number"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Shipping Carrier</td>
  <td>
    <select name="carrier">
      <option value="1"<?php if ($ORDER["carrier"] == 1) echo " SELECTED"; ?>>USPS</option>
      <option value="2"<?php if ($ORDER["carrier"] == 2) echo " SELECTED"; ?>>UPS</option>
      <option value="3"<?php if ($ORDER["carrier"] == 3) echo " SELECTED"; ?>>FedEx</option>
      <option value="0"<?php if ($ORDER["carrier"] == 0) echo " SELECTED"; ?>>Other</option>
    </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Shipping Type</td>
  <td>
    <select name="shipping_type">
      <option value="1"<?php if ($ORDER["shipping_type"] == 1) echo " SELECTED"; ?>>Overnight (1-2 business days)</option>
      <option value="2"<?php if ($ORDER["shipping_type"] == 2) echo " SELECTED"; ?>>2nd Day Air</option>
      <option value="3"<?php if ($ORDER["shipping_type"] == 3) echo " SELECTED"; ?>>Priority (2-3 business days)</option>
      <option value="4"<?php if ($ORDER["shipping_type"] == 4) echo " SELECTED"; ?>>Ground (5-7+ business days)</option>
    </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Tracking Number</td>
  <td><input type="edit" name="tracking_number" size="30" value="<?php echo $ORDER["tracking_number"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Receive Date</td>
  <td><input type="edit" name="receive_date" size="30" value="<?php echo $ORDER["receive_date"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Subtotal</td>
  <td>$<input type="edit" name="subtotal" size="10" value="<?php echo number_format($ORDER["subtotal"],2); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Tax</td>
  <td>$<input type="edit" name="tax" size="10" value="<?php echo number_format($ORDER["tax"],2); ?>"></td>
 </tr>
</table>

<h3>Remove Items</h3>

<table border="0">
 <tr align="center" class="heading">
  <td>Delete</td>
  <td>Name</td>
  <td>Description</td>
  <td>QTY</td>
  <td>Cost</td>
  <td>Status</td>
 </tr>
<?php

$result = mysql_query("SELECT oi.id,i.name,i.descr,oi.qty,oi.cost,oi.varref_status FROM order_items oi JOIN inventory i ON oi.inventory__id = i.id WHERE orders__id = ".$ORDER["id"]);
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\">\n";
	echo "  <td><input type=\"checkbox\" name=\"delete[]\" value=\"".$row["id"]."\"></td>\n";
	echo "  <td>".$row["name"]."</td>\n";
	echo "  <td>".$row["descr"]."</td>\n";
	echo "  <td>".$row["qty"]."</td>\n";
	echo "  <td>$".number_format($row["cost"],2)."</td>\n";
	echo "  <td>".$ORDER_STATUS[$row["varref_status"]]."</td>\n";
	echo " </tr>\n";
}

?>
</table>

<input type="submit" value="Update Order">

</form>
