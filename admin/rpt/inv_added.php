<?php

// if store is not set by calling script, set it to current user store

if (!isset($store_id)) {
  if (ISSET($USER)) $store_id = $USER['org_entities__id'];
  else $store_id = 0;
}

if (!isset($REPORT)) $REPORT = "";

$REPORT .= "<h3>Inventory Added Report</h3>";

if (!isset($START)) $START = mysql_real_escape_string($_POST["start"]);
if (!isset($END)) $END = mysql_real_escape_string($_POST["end"]);

$REPORT .= "<table border=\"0\">\n";
$REPORT .= " <tr align=\"center\" class=\"heading\">\n";
$REPORT .= "  <td>Product ID</td>\n";
$REPORT .= "  <td>Item ID</td>\n";
$REPORT .= "  <td>Name</td>\n";
$REPORT .= "  <td>Category</td>\n";
$REPORT .= "  <td>QTY</td>\n";
$REPORT .= "  <td>User</td>\n";
$REPORT .= "  <td>Time / Date</td>\n";
$REPORT .= " </tr>\n";

$sql = "
SELECT
  ic.inventory__id,
  ic.inventory_item_number,
  ic.qty,
  ic.ts,
  i.name,
  c.category_name,
  u.username,
  ic.ts
FROM inventory_changes ic
LEFT JOIN inventory i
ON ic.inventory__id = i.id
LEFT JOIN users u
ON ic.users__id = u.id
LEFT JOIN categories c
ON i.item_type_lookup = c.id
WHERE ic.org_entities__id = {$store_id}
AND ic.varref_change_code = 1
AND CAST(ic.ts AS date) >= '$START'
AND CAST(ic.ts AS date) < '$END'";

$result = @mysql_query($sql);
while ($row = mysql_fetch_assoc($result)) {
	$REPORT .= " <tr align=\"center\">\n";
	$REPORT .= "  <td>". $row["inventory__id"] ."</td>\n";
	$REPORT .= "  <td>". ($row["inventory_item_number"] ? $row["inventory_item_number"] : "<i>N/A</i>") ."</td>\n";
	$REPORT .= "  <td align=\"left\">". $row["name"] ."</td>\n";
	$REPORT .= "  <td>". $row["category_name"] ."</td>\n";
	$REPORT .= "  <td>". ($row["inventory_item_number"] ? "<i>N/A</i>" : $row["qty"]) ."</td>\n";
	$REPORT .= "  <td>". $row["username"] ."</td>\n";
	$REPORT .= "  <td>". $row["ts"] ."</td>\n";
	$REPORT .= " </tr>\n";
}

$REPORT .= "</table><br>\n";

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}

?>
