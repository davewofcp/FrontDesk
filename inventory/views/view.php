<h3><?php echo $ITEM["name"]; ?></h3>
<?php if (isset($RESPONSE)) { ?><font color="#CC0000"><b><?php echo $RESPONSE; ?></b></font><br><br><?php } ?>
<?php

if ($ITEM["is_qty"])
	echo alink_pop("Print Label","inventory/label.php?id={$ITEM["id"]}");

if ($ITEM["is_qty"]) echo " &nbsp;&nbsp;&nbsp;";
echo alink("Edit","?module=inventory&do=edit&id=".$ITEM["id"]);

?><br><br>

<?php
if ($ITEM["is_qty"]) {
	$result = mysql_query("
SELECT
  oe.id,
  oe.location_code,
  oe.title
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id != {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
	if (mysql_num_rows($result)) {

?>

<form action="?module=inventory&do=transfer&id=<?php echo $ITEM["id"]; ?>" method="post">
<b>Inter-Store Transfer:</b> <select name="location_id">
<?php

		while ($row = mysql_fetch_assoc($result)) {
			echo "<option value=\"{$row["id"]}\">#{$row["location_code"]} - {$row["title"]}</option>\n";
		}

?>
</select> <input type="submit" value="Go">
</form>
<?php } } ?>

<table border="0">
 <tr>
  <td align="right" class="heading">Product ID</td>
  <td><?php echo $ITEM["id"]; ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">UPC</td>
  <td><?php echo ($ITEM["upc"] != null && $ITEM["upc"] != "" ? $ITEM["upc"] : "<i>None</i>"); ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">Inventory Type</td>
  <td><?php echo $ITEM["is_qty"] ? "Quantity" : "Individually Tracked"; ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">QTY In Stock</td>
  <td><?php echo ($ITEM["is_qty"] ? $ITEM["qty"] : $ITEM["iqty"]); ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">Low QTY Notify</td>
  <td><?php echo ($ITEM["do_notify_low_qty"] ? "Enabled <= ". $ITEM["low_qty"] : "Disabled"); ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">Name</td>
  <td><?php echo $ITEM["name"]; ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">Description</td>
  <td><?php echo $ITEM["descr"]; ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">Device Type</td>
  <td><?php echo ($ITEM["category_name"] ? $ITEM["category_name"] : "<i>None</i>"); ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">Purchase Price</td>
  <td>$<?php echo number_format($ITEM["purchase_price"],2); ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">Sale Price</td>
  <td>$<?php echo number_format($ITEM["cost"],2); ?></td>
 </tr>
 <tr>
  <td align="right" class="heading">Taxable</td>
  <td><?php echo ($ITEM["is_taxable"] ? "Yes" : "No"); ?></td>
 </tr>
</table>

<?php if (!$ITEM["is_qty"]) { ?>
<h3>Items In Stock</h3>
<?php echo alink("Add Item","?module=inventory&do=add_item&id=".$ITEM["id"]); ?><br><br>

<table border="0">
 <tr class="heading" align="center" style="font-size:12px;">
  <td>Item #</td>
  <td>Serial No.</td>
  <td width="220">Notes</td>
  <td>Location</td>
  <td>Status</td>
  <td>Device</td>
  <td>Issue</td>
  <td>Edit</td>
  <td>Label</td>
  <td>Add to Cart</td>
 </tr>
<?php

//$result = mysql_query("SELECT * FROM inventory_items ii LEFT JOIN optionvalues o ON ii.location = o.option_id WHERE ii.in_transit = 0 AND ii.inventory_id = ".$ITEM["inventory_id"]);
$result = mysql_query("SELECT ii.*,il.title FROM inventory_items ii LEFT JOIN inventory_locations il ON ii.inventory_locationss__id = il.id WHERE ii.is_in_transit = 0 AND ii.inventory__id = ".$ITEM["id"]);
if (!mysql_num_rows($result)) {
	echo "<tr><td colspan=\"9\" align=\"center\"><i>None In Stock</i></td></tr>\n";
}
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\" style=\"font-size:12px;\">\n";
	echo "  <td>{$row["id"]}</td>\n";
	echo "  <td>{$row["sn"]}</td>\n";
	echo "  <td align=\"left\">{$row["notes"]}</td>\n";
	echo "  <td>{$row["title"]}</td>\n";
	echo "  <td>{$INVENTORY_STATUS[$row["varref_status"]]}</td>\n";
	if ($row["item_table_lookup"]) {
		echo "  <td>".alink("Device","?module=cust&do=edit_dev&id=".$row["item_table_lookup"])."</td>\n";
	} else {
		echo "  <td><i>None</i></td>\n";
	}
	if ($row["issues__id"]) {
		echo "  <td>".alink("View","?module=iss&do=view&id=".$row["issues__id"])."</td>\n";
	} else {
		echo "  <td><i>None</i></td>\n";
	}
	echo "  <td>".alink("Edit","?module=inventory&do=edit_item&id=".$row["id"])."</td>\n";
	echo "  <td>".alink_pop("Print","inventory/label.php?iid={$row["id"]}")."</td>\n";
	if ($row["varref_status"] < 7) {
		echo "  <td>".alink("Add to Cart","?module=inventory&do=add_to_cart&iid={$row["id"]}")."</td>\n";
	} else {
		echo "  <td><i>N/A</i></td>\n";
	}
	echo " </tr>\n";
}

?>
</table>
<?php } ?>

<h3>Product Change Log</h3>
<table border="0">
 <tr class="heading" align="center">
  <td>User</td>
  <td>Time</td>
  <td>Change</td>
  <td>QTY</td>
  <td>Description</td>
  <td>Reason</td>
 </tr>
<?php

$result = mysql_query("SELECT u.username,ic.varref_change_code,ic.qty,ic.ts,ic.descr,ic.reason FROM inventory_changes ic LEFT JOIN users u ON ic.users__id = u.id WHERE ic.inventory__id = {$ITEM["id"]} AND ic.inventory_item_number IS NULL ORDER BY ic.ts DESC");
if (!mysql_num_rows($result)) echo " <tr><td colspan=\"5\" align=\"center\"><i>No Changes to Display</i></td></tr>\n";
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($INVENTORY_CHANGE_CODE[$row["varref_change_code"]])) $row["varref_change_code"] = 0;
	echo " <tr align=\"center\">\n";
	echo "  <td>{$row["username"]}</td>\n";
	echo "  <td>".date("Y-m-d H:i",strtotime($row["ts"]))."</td>\n";
	echo "  <td>".$INVENTORY_CHANGE_CODE[$row["varref_change_code"]]."</td>\n";
	echo "  <td>".($row["qty"] == null ? "" : $row["qty"])."</td>\n";
	if ($row["varref_change_code"] == 3) echo "  <td><i>Details In Database</i></td>\n";
	else echo "  <td>".$row["descr"]."</td>\n";
	echo "  <td>".$row["reason"]."</td>\n";
	echo " </tr>\n";
}

?>
</table>

<?php /*
//TODO: ALL THIS SHIT

<h3>Orders</h3>

<table border="0">
 <tr align="center" class="heading">
  <td>Date</td>
  <td>QTY</td>
  <td>Price</td>
  <td>Order</td>
  <td>Status</td>
 </tr>
<?php

$x = false;
$result = mysql_query("SELECT CAST(o.order_date AS date) AS o_date,oi.qty,oi.cost,o.order_id,oi.status FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE inventory_id = ".$ITEM["inventory_id"]);
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\">\n";
	echo "  <td>".$row["o_date"]."</td>\n";
	echo "  <td>".$row["qty"]."</td>\n";
	echo "  <td>$".number_format($row["cost"],2)."</td>\n";
	echo "  <td>".alink("#".$row["order_id"],"?module=orders&do=view&id=".$row["order_id"])."</td>\n";
	echo "  <td>".$ORDER_STATUS[$row["status"]]."</td>\n";
	echo " </tr>\n";
	$x = true;
}
if (!$x) echo "<tr><td align=\"center\" colspan=\"5\"><i>Never Ordered</i></td></tr>\n";

?>
</table><br>

<h3>Purchased From Customers</h3>

<table border="0">
 <tr align="center" class="heading">
  <td>Date</td>
  <td>Customer</td>
  <td>QTY</td>
  <td>Unit Price</td>
  <td>Total Cost</td>
 </tr>
<?php

$x = false;
$result = mysql_query("SELECT ci.customer_id,ci.qty,CAST(ci.ts AS date) AS sale_dt,ci.unit_cost,ci.total_cost,c.firstname,c.lastname FROM customer_inv ci JOIN customers c ON ci.customer_id = c.customer_id WHERE inventory_id = ".$ITEM["inventory_id"]." ORDER BY ci.ts DESC");
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\">\n";
	echo "  <td>".$row["sale_dt"]."</td>\n";
	echo "  <td>".$row["firstname"]." ".$row["lastname"]." (".alink("#".$row["customer_id"],"?module=cust&do=view&id=".$row["customer_id"]).")</td>\n";
	echo "  <td>".$row["qty"]."</td>\n";
	echo "  <td>$".number_format($row["unit_cost"],2)."</td>\n";
	echo "  <td>$".number_format($row["total_cost"],2)."</td>\n";
	echo " </tr>\n";
	$x = true;
}

if (!$x) echo "<tr><td align=\"center\" colspan=\"5\"><i>Never Purchased</i></td></tr>\n";

?>
</table><br>

<h3>Transactions</h3>

<table border="0">
 <tr align="center" class="heading">
  <td>Date</td>
  <td>Customer</td>
  <td>QTY</td>
  <td>Price</td>
  <td>Transaction</td>
 </tr>
<?php

$x = false;
$result = mysql_query("SELECT CAST(t.tos AS date) AS sale_dt,c.firstname,c.lastname,t.customer,t.amt,t.qty,t.transaction_id FROM transactions t JOIN customers c ON t.customer = c.customer_id WHERE t.from_table = 'inventory' AND t.from_key = ".$ITEM["inventory_id"]." ORDER BY t.tos DESC");
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\">\n";
	echo "  <td>".$row["sale_dt"]."</td>\n";
	echo "  <td>".$row["firstname"]." ".$row["lastname"]." (".alink("#".$row["customer"],"?module=cust&do=view&id=".$row["customer"]).")</td>\n";
	echo "  <td>".$row["qty"]."</td>\n";
	echo "  <td>".$row["amt"]."</td>\n";
	echo "  <td>".alink("#".$row["transaction_id"],"?module=pos&do=view_trans&tid=".$row["transaction_id"])."</td>\n";
	echo " </tr>\n";
	$x = true;
}
if (!$x) echo "<tr><td align=\"center\" colspan=\"5\"><i>Never Sold</i></td></tr>\n";

?>
</table><br>

<h3>Invoices</h3>

<table border="0">
 <tr align="center" class="heading">
  <td>Date</td>
  <td>Customer</td>
  <td>QTY</td>
  <td>Price</td>
  <td>Invoice</td>
 </tr>
<?php

$x = false;
$result = mysql_query("SELECT CAST(i.toi AS date) AS sale_dt,i.customer_id,ii.qty,ii.cost,ii.invoice_id FROM invoice_items ii JOIN invoices i ON ii.invoice_id = i.invoice_id WHERE ii.from_table = 'inventory' AND ii.from_key = ".$ITEM["inventory_id"]." ORDER BY i.toi DESC,ii.invoice_item_id");
while ($row = mysql_fetch_assoc($result)) {
	if ($row["customer_id"] != null) $cust = mysql_fetch_assoc(mysql_query("SELECT firstname,lastname FROM customers WHERE customer_id = ".$row["customer_id"]));
	echo " <tr align=\"center\">\n";
	echo "  <td>".$row["sale_dt"]."</td>\n";
	if ($row["customer_id"] != null) {
		echo "  <td>".$cust["firstname"]." ".$cust["lastname"]." (".alink("#".$row["customer_id"],"?module=cust&do=view&id=".$row["customer_id"]).")</td>\n";
	} else {
		echo "  <td><i>None</i></td>\n";
	}
	echo "  <td>".$row["qty"]."</td>\n";
	echo "  <td>$".number_format($row["cost"],2)."</td>\n";
	echo "  <td>".alink("#".$row["invoice_id"],"?module=invoice&do=view&id=".$row["invoice_id"])."</td>\n";
	echo " </tr>\n";
	$x = true;
}
if (!$x) echo "<tr><td align=\"center\" colspan=\"5\"><i>Not On Any Invoices</i></td></tr>\n";

?>
</table>

*/ ?>
