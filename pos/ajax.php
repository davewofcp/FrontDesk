<?php

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) exit;

if (isset($_GET["cmd"])) {
	switch ($_GET["cmd"]) {
		case "cart_update":
      // get default org tax rate
      $result = mysql_query("
SELECT
  oe.tax_rate
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Organization'
  AND tax_rate IS NOT NULL
LIMIT 1
");
      if (mysql_num_rows($result)) {
        $data = mysql_fetch_assoc($result);
        $tax_rate = floatval($data["tax_rate"]);
      }
      // try to get store-specific tax rate
      $result = mysql_query("SELECT tax_rate FROM org_entities WHERE id={$USER['org_entities__id']} AND tax_rate IS NOT NULL LIMIT 1");
      if (mysql_num_rows($result)) {
        $data = mysql_fetch_assoc($result);
        $tax_rate = floatval($data["tax_rate"]);
      }
      // hack fallback for now
      if (!isset($tax_rate)) $tax_rate = floatval("0.08");

			$item_id = intval($_GET["id"]);
			$new_qty = intval($_GET["qty"]);
			mysql_query("UPDATE pos_cart_items SET qty = ". $new_qty ." WHERE id = ". $item_id ." AND users__id__sale = '". $USER["id"] ."'");
			$result = mysql_query("SELECT * FROM pos_cart_items WHERE users__id__sale = '". $USER["id"] ."'");
			$subtotal = 0;
			$taxable = 0;
			while ($row = mysql_fetch_assoc($result)) {
				$subtotal += $row["qty"] * $row["amt"];
				if ($row["is_taxable"]) $taxable += $row["qty"] * $row["amt"];
			}
			$tax = round($taxable * $tax_rate,2);
			$total = $subtotal + $tax;
			echo number_format(floatval($subtotal),2,'.','') .":"
			     . number_format(floatval($tax),2,'.','') .":"
			     . number_format(floatval($total),2,'.','');
			break;
		default:
			break;
	}
}

exit;

?>
