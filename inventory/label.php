<!DOCTYPE HTML>
<html>
<head>
<title>Inventory Label</title>
</head>
<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

//$result = mysql_query("SELECT name FROM locations WHERE is_here = 1");
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
if (!mysql_num_rows($result)) $name = "Unknown Location";
else {
	$row = mysql_fetch_assoc($result);
	$name = $row["title"];
}

if (isset($_GET["id"])) {
	$result = mysql_query("SELECT c.category_name,i.id,i.upc,i.descr,i.purchase_price,i.cost,i.is_taxable,i.item_type_lookup,i.name,i.is_qty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE org_entities__id = {$USER['org_entities__id']} AND i.id = ".intval($_GET["id"]));
	if (!mysql_num_rows($result)) {
		echo "Product ".intval($_GET["id"])." not found.";
		exit;
	}
	$ITEM = mysql_fetch_assoc($result);
	if (!$ITEM["is_qty"]) {
		echo "This is an individually-tracked product.<br>Please print a barcode for an instance of this product.<br><br>";
		exit;
	}
	$upc = "41".str_pad($ITEM["id"],9,"0",STR_PAD_LEFT);
	$upc .= calculate_check_digit($upc);
?>
<body onload="window.print();">
<div style="zoom:50%;position:absolute;top:200px;left:-220px;height:200px;width:600px;overflow:hidden;font-size:18pt;-webkit-transform: rotate(-90deg); -moz-transform:rotate(-90deg);" align="center">
 <div style="float:left;width:100%;height:100%;">
  <table border="0" width="100%" height="100%">
   <tr><td align="center" valign="center">
 <?php echo $name; ?> Inventory Label<br>
 <?php echo barcode_img($upc,$ITEM["cost"]); ?>
   </td></tr>
  </table>
 </div>
</div>
</body></html>
<?php
} else if (isset($_GET["iid"])) {
	$result = mysql_query("SELECT c.category_name,i.id as inventory__id,ii.id,i.upc,i.descr,i.purchase_price,i.cost,i.is_taxable,i.item_type_lookup,i.name,ii.sn FROM inventory i LEFT JOIN inventory_items ii ON ii.inventory__id = i.id LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND ii.id = ".intval($_GET["iid"]));
	if (!mysql_num_rows($result)) {
		echo "Item ".intval($_GET["iid"])." not found.";
		exit;
	}
	$ITEM = mysql_fetch_assoc($result);
	$upc = "42".str_pad($ITEM["id"],9,"0",STR_PAD_LEFT);
	$upc .= calculate_check_digit($upc);
?>
<body onload="window.print();">
<div style="zoom:50%;position:absolute;top:200px;left:-220px;height:200px;width:600px;overflow:hidden;font-size:18pt;-webkit-transform: rotate(-90deg); -moz-transform:rotate(-90deg);" align="center">
  <table border="0" width="100%" height="100%">
   <tr><td align="center" valign="center">
 <?php echo $name; ?> Inventory Label<br>
 <?php echo barcode_img($upc,$ITEM["cost"]); ?>
   </td></tr>
  </table>
</div>
</body></html>
<?php
} else {
	echo "Invalid request.";
	exit;
}

function barcode_img($upc,$price) {
	return "<img src=\"../core/upca.php?barcode=$upc\">";
}

// Calculate checksum digit
function calculate_check_digit($upc11) {
	$keys = array('0','1','2','3','4','5','6','7','8','9');
	$odd = true;
	$checksum=0;
	for($i=strlen($upc11);$i>0;$i--) {
		if($odd) {
			$multiplier=3;
		}
		else {
			$multiplier=1;
		}
		$odd = !$odd;
		$checksum += $keys[$upc11[$i - 1]] * $multiplier;
	}
	$checksum = 10 - $checksum % 10;
	$checksum = ($checksum == 10)?0:$checksum;
	return $keys[$checksum];
}

?>
