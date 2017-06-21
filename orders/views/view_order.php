<h3>Viewing Order <?php echo $ORDER["id"]; ?> |
<?php
echo alink("Edit","?module=orders&do=edit&id=".$ORDER["id"]);
if ($ORDER["receive_date"] == null) {
	echo " | ". alink("Receive","?module=orders&do=receive&id=".$ORDER["id"]);
}
?></h3>
<table border="0" cellspacing="3">
 <tr>
  <td class="heading" align="right">Ordered</td>
  <td><?php echo $ORDER["order_date"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Status</td>
  <td><?php echo $ORDER_STATUS[$ORDER["varref_status"]]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Purchased From</td>
  <td><?php echo $ORDER["purchased_from"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Order Number</td>
  <td><?php echo $ORDER["order_number"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Shipping Type</td>
  <td><?php echo $ORDER_SHIPPING_TYPES[$ORDER["shipping_type"]]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Tracking Number</td>
  <td><?php echo $ORDER["tracking_number"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Received</td>
  <td><?php echo ($ORDER["receive_date"] ? $ORDER["receive_date"] : "N/A"); ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Subtotal</td>
  <td>$<?php echo number_format(floatval($ORDER["subtotal"]),2); ?></td>
 </tr>
  <tr>
  <td class="heading" align="right">Tax</td>
  <td>$<?php echo number_format(floatval($ORDER["tax"]),2); ?></td>
 </tr>
  <tr>
  <td class="heading" align="right">Total</td>
  <td>$<?php echo number_format(floatval($ORDER["subtotal"] + $ORDER["tax"]),2); ?></td>
 </tr>
</table>

<h3>Items On This Order</h3>

<table border="0">
 <tr class="heading" align="center">
  <td>ID</td>
  <td>Name</td>
  <td>Description</td>
  <td>Price</td>
  <td>QTY</td>
  <td>Status</td>
 </tr>
<?php

$ORDER_ITEMS = mysql_query("SELECT i.id,i.name,i.descr,o.cost,o.qty,o.varref_status FROM order_items o JOIN inventory i ON o.inventory__id = i.id WHERE o.orders__id = ". $ORDER["id"]);
while ($item = mysql_fetch_assoc($ORDER_ITEMS)) {
	echo " <tr>\n";
	echo "  <td align=\"center\">".$item["id"]."</td>\n";
	echo "  <td align=\"center\">".$item["name"]."</td>\n";
	echo "  <td>". $item["descr"] ."</td>\n";
	echo "  <td align=\"center\">$". number_format($item["cost"],2) ."</td>\n";
	echo "  <td align=\"center\">". $item["qty"] ."</td>\n";
	echo "  <td align=\"center\">". $ORDER_STATUS[$item["varref_status"]] ."</td>\n";
	echo " </tr>\n";
}

?>
</table>
