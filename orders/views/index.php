<?php

$ORDERS = mysql_query("SELECT *,CAST(order_date AS date) AS order_dt FROM orders ORDER BY id DESC");
$ORDER_ITEMS = mysql_query("SELECT * FROM order_items ORDER BY id DESC");

$order_count = array();
while ($row = mysql_fetch_assoc($ORDER_ITEMS)) {
	if (!isset($order_count[$row["orders__id"]])) $order_count[$row["orders__id"]] = 0;
	$order_count[$row["orders__id"]] += $row["qty"];
}

if (isset($RESPONSE)) {
	echo "<h3>". $RESPONSE ."</h3>";
}

echo alink("+ Add New Order","?module=orders&do=new");

?>
<br><br>
<table border="0" width="780">
 <tr class="heading" align="center" style="font-size: 9pt;">
  <td>ID</td>
  <td>Ordered</td>
  <td>Purchased From</td>
  <td>Order #</td>
  <td>Shipping Type</td>
  <td>Carrier</td>
  <td>Tracking Number</td>
  <td>Cost</td>
  <td>Items</td>
  <td>View</td>
  <td>Receive(d)</td>
 </tr>
<?php

while ($order = mysql_fetch_assoc($ORDERS)) {
	echo " <tr align=\"center\" style=\"font-size: 9pt;\">\n";
	echo "  <td>". $order["id"] ."</td>\n";
	echo "  <td>". $order["order_dt"] ."</td>\n";
	echo "  <td>". $order["purchased_from"] ."</td>\n";
	echo "  <td>". $order["order_number"] ."</td>\n";
	echo "  <td>". $ORDER_SHIPPING_TYPES[$order["shipping_type"]] ."</td>\n";
	echo "  <td>". $ORDER_CARRIERS[$order["carrier"]] ."</td>\n";
	echo "  <td>". $order["tracking_number"] ."</td>\n";
	echo "  <td>$". number_format(floatval($order["subtotal"] + $order["tax"]),2) ."</td>\n";
	if (!isset($order_count[$order["id"]])) {
		echo "  <td>0</td>\n";
	} else {
		echo "  <td>".$order_count[$order["id"]]."</td>\n";
	}
	echo "  <td>".alink("View","?module=orders&do=view&id=". $order["id"])."</td>\n";
	if ($order["receive_date"] != null) {
		echo "  <td>". $order["receive_date"] ."</td>\n";
	} else {
		echo "  <td>".alink("Receive","?module=orders&do=receive&id=". $order["id"])."</td>\n";
	}
	echo " </tr>\n";
}

?>
</table>
