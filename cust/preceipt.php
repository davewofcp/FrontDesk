<?php

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

$result = mysql_query("SELECT ci.id,ci.customers__id,ci.inventory__id,ci.qty,ci.ts,ci.unit_cost,ci.total_cost,ci.serial_numbers,i.name FROM inventory_items_customer ci JOIN inventory i ON ci.inventory__id = i.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND ci.id = ".intval($_GET["id"]));
if (mysql_num_rows($result)) {
	$PURCHASE = mysql_fetch_assoc($result);
} else {
	die("Purchase ID not found.");
}

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
");
if (mysql_num_rows($result)) {
	$data = mysql_fetch_assoc($result);
	$address = $data["address"];
	$csz = $data["city"] ." ". $data["state"] ." ". $data["postcode"];
} else {
	$address = "617 Central Ave";
	$csz = "Albany NY 12206";
}

?>
<html>
<head>
<style type="text/css">
.receipt_top td {
	text-align: center;
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 10px;
}
.receipt {
	text-align: center;
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 6px;

}
.receipt td{
	text-align: left;
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 10px;
}
.receipt_top img{ BORDER: 0; align: center }
</style>
</head>
<body>
<table width='350px'>
 <tr>
  <td colspan='2' align='center'>
  <img src='../images/logoreceipt.gif' width = '150' height = '84'>
  </td>
 </tr>
 <tr>
  <td colspan='2' align='center' class='receipt_top'><?php echo $address; ?></td>
 </tr>
 <tr>
  <td colspan='2' align='center' class='receipt_top'><?php echo $csz; ?></td>
 </tr>
 <tr>
  <td><font size='-2' face='Verdana'> </font></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Date</strong></td>
  <td><?php echo date("Y.m.d",strtotime($PURCHASE["ts"])); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Time</strong></td>
  <td><?php echo date("H:i:s",strtotime($PURCHASE["ts"])); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Customer ID</strong></td>
  <td><?php echo ($PURCHASE["customers__id"] ? $PURCHASE["customers__id"] : "N/A"); ?></td>
 </tr>
 <tr class='receipt'>
  <td><strong>Purchase Number</strong></td>
  <td><?php echo $PURCHASE["id"]; ?></td>
 </tr>
 <tr class='receipt'>
  <td colspan='2'>

  <hr>
  <table width = '100%' class='receipt'>
   <tr>
    <td><strong>Units</strong></td>
    <td><strong>Description</strong></td>
    <td><strong>Price</strong></td>
   </tr>
   <tr>
    <td><?php echo $PURCHASE["qty"]; ?></td>
    <td><?php echo $PURCHASE["name"]; ?><?php if ($PURCHASE["serial_numbers"] != "") { ?><br><b>S/N:</b> <?php echo $PURCHASE["serial_numbers"]; } ?></td>
    <td><?php echo number_format($PURCHASE["unit_cost"],2); ?></td>
   </tr>
  </table>
  <hr>

  </td>
 </tr>
 <tr class='receipt'>
  <td><strong>Total Paid</strong></td>
  <td><?php echo number_format($PURCHASE["total_cost"],2); ?></td>
 </tr>
 <tr>
  <td><br></td>
 </tr>
</table>
</body>
</html>
