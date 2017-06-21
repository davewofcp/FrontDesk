<?php

if (!isset($_GET["cmd"])) exit;

require_once("../init.php");
require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) { exit; }
if (!isset($_GET["cmd"])) { exit; }

function ds_escape($str) {
	return str_replace('"','``',$str);
}

function sanitize($str) {
	$str = str_replace("'","\\'",$str);
	$str = str_replace("\n","\\n",$str);
	$str = str_replace("\r","",$str);
	return $str;
}

switch ($_GET["cmd"]) {
	case "search":
		search();
		break;
	case "upc":
		upc();
		break;
	case "INV":
		invoice_search();
		break;
	case "list_items_invc":
		list_items_for_invoice();
		break;
	case "product_descr":
		descr();
		break;
}

function list_items_for_invoice() {
	global $INVENTORY_STATUS;

	$sql = "SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.category_name,i.name FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE org_entities__id = {$USER['org_entities__id']} AND i.id = ".intval($_GET["id"]);
	$result = mysql_query($sql) or die(mysql_error()."::".$sql);
	$PRODUCT = mysql_fetch_assoc($result);

	// Need to add "internal location" information to invoice_items table
	//$sql = "SELECT ii.id,ii.sn,ii.status,ii.location,o.value FROM inventory_items ii LEFT JOIN optionvalues o ON ii.location = o.option_id WHERE ii.id = ".intval($_GET["id"])." AND ii.status < 7";
	$sql = "SELECT ii.id,ii.sn,ii.varref_status,ii.in_store_location,il.title FROM inventory_items ii LEFT JOIN inventory_locations il ON ii.in_store_location = il.id WHERE org_entities__id = {$USER['org_entities__id']} AND ii.id = ".intval($_GET["id"])." AND ii.varref_status < 7";
	$result = mysql_query($sql) or die(mysql_error()."::".$sql);
	$iqty = mysql_num_rows($result);

	$bgcolors = array("#FFFFFF","#AAAAAA");
	$c = 0;
	$content = "";
	$content .= "<div class=\"block bolder\" style=\"margin-top:-10px;font-size:18px;\">". mysql_num_rows($sql) ." Results</div>\n";
	$content .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
	$content .= " <tr align=\"center\">\n";
	$content .= "  <td colspan=\"9\"><font size=\"+1\">Product {$PRODUCT['id']}: {$PRODUCT['name']} ($iqty Results)</font></td>\n";
	$content .= " </tr>\n";
	$content .= " <tr align=\"center\" class=\"heading\">\n";
	$content .= "  <td>ID</td><td>Serial No.</td><td>Status</td><td>Add to Invoice</td>\n";
	$content .= " </tr>\n";

	while ($row = mysql_fetch_assoc($result)) {
		$content .= " <tr align=\"center\" style=\"font-size: 9pt;background-color:{$bgcolors[$c]};\" title=\"". ($row["descr"] ? ds_escape($row["descr"]) : "(No Description)") ."\">\n";
		$content .= "  <td>".$row["inv_item_id"]."</td>\n";
		$content .= "  <td>".$row["sn"]."</td>\n";
		$content .= "  <td>". $INVENTORY_STATUS[$row["varref_status"]] ."</td>\n";
		$content .= "  <td>".alink_onclick("Add to Invoice","#add_inv","invoiceInv_add('".sanitize($PRODUCT["name"])."','".sanitize($PRODUCT["descr"])."','".number_format(floatval($PRODUCT["purchase_price"]),2)."','".number_format(floatval($PRODUCT["cost"]),2)."','".$iqty."','".$PRODUCT["is_taxable"]."','".$PRODUCT["id"]."','".$row["id"]."');")."</td>\n";
		$content .= " </tr>\n";

		$c++;
		if ($c == 2) $c = 0;
	}

	$content .= "</table>\n";

	echo '{';
	echo '"content": '. json_encode($content) .'';
	echo '}';
}

function invoice_search() {
	global $USER;

	$xdes=0;
	$content="";

	mysql_query('SET CHARACTER SET utf8');
	$search_string = mysql_real_escape_string($_GET["str"]);
	$category = isset($_GET["cat"]) ? intval($_GET["cat"]) : 0;
	$cats = array($category);

	if (isset($_GET["all"])) $search_string = "";
	if (isset($_GET["hso"])) $hso = true;
	else $hso = false;

	if($search_string=="" && !isset($_GET["all"])){
		$content = "<b>Enter a search term</b>";
		echo '{';
		echo '"content": '. json_encode($content) .'';
		echo '}';
		die();
	}

	$catstring = "";
	if ($category != NULL ) {
		$result = mysql_query("SELECT id FROM categories WHERE parent_id = ".$category);
		if (mysql_num_rows($result)) {
			while ($row = mysql_fetch_assoc($result)) {
				//$cats[] = $row["option_id"];
        $cats[] = $row["id"];
			}
		}
		$catstring = " AND i.item_type_lookup IN (".implode(",",$cats).")";
	}

	$sql = mysql_query("SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.category_name,i.name,i.qty,i.is_qty,(SELECT COUNT(*) FROM inventory_items ii WHERE ii.org_entities__id = {$USER['org_entities__id']} AND ii.inventory__id = i.id AND ii.varref_status < 6) AS iqty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND (i.name LIKE '%$search_string%' OR i.descr LIKE '%$search_string%')$catstring");
	if(mysql_num_rows($sql)){

		$bgcolors = array("#FFFFFF","#AAAAAA");
		$c = 0;

		$content .= "<div class=\"block bolder\" style=\"margin-top:-10px;font-size:18px;\">". mysql_num_rows($sql) ." Results</div>\n";
		$content .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
		$content .= " <tr align=\"center\" class=\"heading\">\n";
		//$content .= "  <td>ID</td><td>Item #</td><td>UPC</td><td>Name (hover for Description)</td><td>Type</td><td>QTY</td><td>Cost</td><td>Retail</td><td>Taxable</td>\n";
    	$content .= "  <td>ID</td><td>UPC</td><td>Name (hover for Description)</td><td>Type</td><td>QTY</td><td>Cost</td><td>Retail</td><td>Taxable</td>\n";
		$content .= " </tr>\n";
		while($row = mysql_fetch_assoc($sql)){
			$qty = ($row["is_qty"] ? $row["qty"] : $row["iqty"]);
			if ($hso && $qty < 1) continue;
			$content .= " <tr align=\"center\" style=\"font-size: 9pt;background-color:{$bgcolors[$c]};\" title=\"". ($row["descr"] ? ds_escape($row["descr"]) : "(No Description)") ."\">\n";
			$content .= "  <td>".$row["id"]."</td>\n";
			$content .= "  <td>".highlight($row["upc"],$search_string)."</td>\n";
			$content .= "  <td align=\"left\">". highlight($row["name"],$search_string) ."</td>\n";
			$content .= "  <td>".(isset($row["category_name"]) ? $row["category_name"] : "<i>None</i>")."</td>\n";
			$content .= "  <td>".($row["is_qty"] ? $row["qty"] : $row["iqty"])."</td>\n";
			$content .= "  <td>$".number_format($row["purchase_price"],2)."</td>\n";
			$content .= "  <td>$".number_format($row["cost"],2)."</td>\n";
			$content .= "  <td>".($row["is_taxable"]==1 ? "Yes" : "No")."</td>\n";
			$content .= " </tr>\n";
			$content .= " <tr align=\"center\" style=\"background-color:{$bgcolors[$c%2]};\" title=\"". ($row["descr"] ? ds_escape($row["descr"]) : "(No Description)") ."\"><td colspan=\"9\">";

			$content .= " [ ". alink("View / Edit","?module=inv&do=view&id=".$row["id"]) ." ] ";

			if (!$row["is_qty"] && isset($_GET["invoiceINV"])) {
				$content .= " [ ". alink_onclick("List Items","#","invoiceListItems('{$row["inventory__id"]}');") ." ] ";
			}

			if(isset($_GET["issue_id"]) && $row["is_qty"] && $row["qty"] > 0){
				//If from issue
				$content .= " [ ". alink("Add Inventory Item to Issue #".intval($_GET["issue_id"])."","?module=iss&do=add_inv&id=".intval($_GET["issue_id"])."&inventory_id=".$row["id"]."") ." ] ";
			} elseif(isset($_GET["invoiceINV"]) && $row["is_qty"] && $row["qty"] > 0) {
				//If adding inventory to invoice
				//$content .= "<div class=\"clear center bold\">";
				$content .= " [ ". alink_onclick("Add to Invoice","#add_inv","invoiceInv_add('".sanitize($row["name"])."','".sanitize($row["descr"])."','".number_format(floatval($row["purchase_price"]),2)."','".number_format(floatval($row["cost"]),2)."','".$row["qty"]."','".$row["is_taxable"]."','".$row["id"]."','0');")." ] ";
				//$content .= "</div>";
			} elseif(isset($_GET["orderINV"])) {
				//If adding inventory to an order
				//$content .= "<div class=\"clear center bold\">";
				$content .= " [ ". alink_onclick("Add to Order","#","orderInv_add('".$row["upc"]."','".sanitize($row["name"])."','".sanitize($row["descr"])."','".number_format(floatval($row["purchase_price"]),2)."','".number_format(floatval($row["cost"]),2)."','".$row["qty"]."','".$row["is_taxable"]."','".$row["category_name"]."','".$row["item_type_lookup"]."','".$row["id"]."');")." ] ";
				//$content .= "</div>";
			} elseif(isset($_GET["invPurchase"])) {
				// If searching for inventory to purchase
				//$content .= "<div class=\"clear center bold\">";
				$content .= " [ ". alink_onclick("Purchase This","#","purchase('".$row["id"]."','".$row["purchase_price"]."');")." ] ";
				//$content .= "</div>";
			}  else {
				//Normal inventory search
				$content .= " [ ". alink("Add To Cart","?module=inv&do=add_to_cart&id={$row["id"]}")." ] ";
			}
			$content .= " [ ". alink_pop("Print Label","inv/label.php?id={$row["id"]}") ." ] ";

			if (($row["is_qty"] && $row["qty"]<1) || (!$row["is_qty"] && $row["iqty"]<1)) {
				//Out of stock
				//$content .= "<div class=\"clear center\" style=\"color:red;font-size:20px;font-weight:900;\">OUT OF STOCK</div>";
				$content .= " <font style=\"color:red;font-size:20px;font-weight:bold;\">OUT OF STOCK</font>";
			}

			$content .= "</td></tr>\n";

			$c = ++$c % 2; // auto-resets counter to modulus with each loop

		}
		$content .= "</table>\n";

	} else {
		$content .= "<b>No Results!</b>";
	}

	echo '{';
	echo '"content": '. json_encode($content) .'';
	echo '}';
}

function search() {
	global $USER;
	mysql_query('SET CHARACTER SET utf8');
	$search_string = mysql_real_escape_string($_GET["str"]);
	$category = isset($_GET["cat"]) ? intval($_GET["cat"]) : 0;
	$cats = array($category);

	if (isset($_GET["all"])) $search_string = "";
	if (isset($_GET["hso"])) $hso = true;
	else $hso = false;

	if($search_string=="" && !isset($_GET["all"])){
		$content = "<b>Enter a search term</b>";
		echo '{';
		echo '"content": '. json_encode($content) .'';
		echo '}';
		die();
	}

	$catstring = "";
	if ($category != 0) {
		$result = mysql_query("SELECT id FROM categories WHERE parent_id = ".$category);
		if (mysql_num_rows($result)) {
			while ($row = mysql_fetch_assoc($result)) {
				//$cats[] = $row["option_id"];
        $cats[] = $row["id"];
			}
		}
		$catstring = " AND i.item_type_lookup IN (".implode(",",$cats).")";
	}

	$sql1 = "SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.category_name,i.name,i.qty,i.is_qty,(SELECT COUNT(*) FROM inventory_items ii WHERE ii.org_entities__id = {$USER['org_entities__id']} AND ii.inventory__id = i.id AND ii.is_in_transit != 1) AS iqty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND (i.name LIKE '%$search_string%' OR i.descr LIKE '%$search_string%')$catstring";

	$sql = mysql_query("SELECT i.id,i.upc,i.descr,i.cost,i.is_taxable,i.purchase_price,i.cost,c.category_name,i.name,i.qty,i.is_qty,(SELECT COUNT(*) FROM inventory_items ii WHERE ii.org_entities__id = {$USER['org_entities__id']} AND ii.inventory__id = i.id AND ii.varref_status < 6) AS iqty FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.org_entities__id = {$USER['org_entities__id']} AND (i.name LIKE '%$search_string%' OR i.descr LIKE '%$search_string%')$catstring");
	if (mysql_num_rows($sql)) {
		$bgcolors = array("#FFFFFF","#AAAAAA");
		$c = 0;
		$content = "<div class=\"block bolder\" style=\"margin-top:-10px;font-size:18px;\">". mysql_num_rows($sql) ." Results</div>\n";
		$content .= "<table border=\"0\" cellpadding=\"2\" cellspacing=\"0\">\n";
		$content .= " <tr align=\"center\" class=\"heading\">\n";
		$content .= "  <td>ID</td><td>UPC</td><td>Name (hover for Description)</td><td>Type</td><td>QTY</td><td>Cost</td><td>Retail</td><td>Taxable</td>\n";
		$content .= " </tr>\n";
		while ($row = mysql_fetch_assoc($sql)) {
			$qty = ($row["is_qty"] ? $row["qty"] : $row["iqty"]);
			if ($hso && $qty < 1) continue;
			$content .= " <tr align=\"center\" style=\"font-size: 9pt;background-color:{$bgcolors[$c]};\" title=\"". ($row["descr"] ? ds_escape($row["descr"]) : "(No Description)") ."\">\n";
			$content .= "  <td>".$row["id"]."</td>\n";
			$content .= "  <td>".$row["upc"]."</td>\n";
			$content .= "  <td align=\"left\">". highlight($row["name"],$_GET["str"]) ."</td>\n";
			$content .= "  <td>".(isset($row["category_name"]) ? $row["category_name"] : "<i>None</i>")."</td>\n";
			$content .= "  <td>".($qty < 1 ? "<font color=red><b>SOLD OUT</b></font>" : $qty)."</td>\n";
			$content .= "  <td>$".number_format($row["purchase_price"],2)."</td>\n";
			$content .= "  <td>$".number_format($row["cost"],2)."</td>\n";
			$content .= "  <td>".($row["is_taxable"]==1 ? "Yes" : "No")."</td>\n";
			$content .= " </tr>\n";
			$content .= " <tr align=\"center\" style=\"background-color:{$bgcolors[$c]};\" title=\"". ($row["descr"] ? ds_escape($row["descr"]) : "(No Description)") ."\"><td colspan=\"9\">";

			if ($row["is_qty"] && $row["qty"] > 0)
				$content .= " [ ". alink("Add To Cart","?module=inventory&do=add_to_cart&id={$row["id"]}") ." ] ";

			$content .= " [ ". alink("View","?module=inventory&do=view&id=".$row["id"]) ." ] ";

			if (TFD_HAS_PERMS('admin','use')) $content .= " [ ". alink("Edit","?module=inventory&do=edit&id=".$row["id"]) ." ] ";

			if (isset($_GET["issue_id"]) && $row["is_qty"] && $row["qty"] > 0){
				//If from issue
				$content .= " [ ". alink("Add to Issue #".intval($_GET["issue_id"])."","?module=iss&do=add_inv&id=".intval($_GET["issue_id"])."&inventory_id=".$row["id"]."") ." ] ";
			} elseif (isset($_GET["invoiceINV"]) && $row["is_qty"] && $row["qty"] > 0) {
            	//If adding inventory to invoice
            	$content .= " [ ". alink_onclick("Add to Invoice","#add_inv","invoiceInv_add('".sanitize($row["name"])."','".sanitize($row["descr"])."','".number_format(floatval($row["purchase_price"]),2)."','".number_format(floatval($row["cost"]),2)."','".$data["qty"]."','".$row["is_taxable"]."','".$row["id"]."');")." ] ";
			} elseif(isset($_GET["orderINV"]) && $row["is_qty"] && $row["qty"] > 0) {
				//If adding inventory to an order
				$content .= " [ ". alink_onclick("Add to Order","#","orderInv_add('".$row["upc"]."','".sanitize($row["name"])."','".sanitize($row["descr"])."','".number_format(floatval($row["purchase_price"]),2)."','".number_format(floatval($row["cost"]),2)."','".$data["qty"]."','".$row["is_taxable"]."','".$row["category_name"]."','".$row["item_type_lookup"]."','".$row["id"]."');")." ] ";
			} elseif(isset($_GET["invPurchase"])) {
				// If searching for inventory to purchase
				$content .= " [ ". alink_onclick("Purchase This","#","purchase('".$row["id"]."','".number_format($row["purchase_price"],2,'.','')."','".($row["is_qty"] ? "0":"1")."');")." ] ";
			}

			$content .= "</td></tr>\n";

			$c++;
			if ($c == 2) $c = 0;
		}

		$content .= "</table>\n";
	} else {
		$content .= "<b>No Results For '$search_string'</b>";
    }

    echo '{';
    echo '"content": '. json_encode($content) .'';
    echo '}';
}

function upc() {
	$UPC = urlencode($_GET["str"]);

	$var = file_get_contents("http://www.searchupc.com/handlers/upcsearch.ashx?request_type=2&access_token=11853E14-C891-4490-9CF5-F4AE129D915A&upc=". $UPC);
	if($var=="False"){
		die("False");
	}

	$var = file_get_contents("http://www.searchupc.com/handlers/upcsearch.ashx?request_type=3&access_token=11853E14-C891-4490-9CF5-F4AE129D915A&upc=". $UPC);
	echo $var;
}

function descr() {
	$str = file_get_contents($_GET["str"]);
	$DOM = new DOMDocument;
	$DOM->loadHTML($str);
	$items = $DOM->getElementById('productDescription');
	if (!$items) die("False");
	$var = $items->nodeValue;
	$var = str_replace("Product Description","",$var);
	$var = trim($var);
	echo $var;
}

function highlight($haystack, $needle) {
	if (strlen($haystack) < 1 || strlen($needle) < 1) {
		return $haystack;
	}
	if (strlen($haystack) == strlen($needle) && stristr($haystack,$needle)) {
		return "<font style=\"background-color:yellow\">". $haystack ."</font>";
	}
	preg_match_all("/$needle+/i", $haystack, $match);
	$exploded = preg_split("/$needle+/i",$haystack);
	$replaced = "";
	foreach($exploded as $e)
		foreach($match as $m)
		if($e!=$exploded[count($exploded)-1]) {
		$replaced .= $e . "<font style=\"background-color:yellow\">" . $m[0] . "</font>";
	} else {$replaced .= $e;
	}
	return $replaced;
}
?>
