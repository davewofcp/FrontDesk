<?php

$LOCATIONS = array();
$result = mysql_query("SELECT * FROM inventory_locations WHERE 1");
while(false!==($row=mysql_fetch_assoc($result)))$LOCATIONS[$row['id']]=$row['title'];

asort($LOCATIONS);

$result = mysql_query("SELECT inventory_locations__id FROM xref__org_entities__inventory_locations WHERE org_entities__id = {$USER['org_entities__id']} AND is_default = 1");
if(false!==($row=mysql_fetch_assoc($result))) $default_location = $row['inventory_locations__id'];

$MANUFACTURER = array();
$OS = array();
$result = mysql_query("SELECT * FROM option_values WHERE category IN ('manufacturer','os')");
while ($row = mysql_fetch_assoc($result)) {
	if ($row["category"] == "manufacturer") {
		$MANUFACTURER[$row["id"]] = $row["value"];
	}
	if ($row["category"] == "os") {
		$OS[$row["id"]] = $row["value"];
	}
}

asort($MANUFACTURER);
arsort($OS);

?>
<script type="text/javascript">
function purchase(id,price,indiv) {
	html('epf_inventory_id').innerHTML = id;
	html('epf_unit_price').value = price;
	html('searchInvResult').innerHTML = '';
	if (indiv == '1') {
		html('indiv_item_details').style.display = '';
		html('epf_q_row').style.display = 'none';
		html('epf_indiv').value = '1';
	} else {
		html('indiv_item_details').style.display = 'none';
		html('epf_q_row').style.display = '';
		html('epf_indiv').value = '0';
	}
	html('epf_quantity').focus();
}
function toggleNewItemFields(obj) {
	if (obj.checked) {
		html('new_product_fields').style.display = '';
		html('existing_product_fields').style.display = 'none';
		html('upc').focus();
		html('indiv_item_details').style.display = 'none';
		html('existing_product_fields').style.display = 'none';
	} else {
		html('new_product_fields').style.display = 'none';
		html('existing_product_fields').style.display = '';
	}
}
</script>
<h3>Purchase Inventory</h3>

<?php if (isset($RESPONSE)) { ?><font color="red" size="+1"><b><?php echo $RESPONSE; ?></b></font><br><br><?php } ?>

<font size="+1">New Product?</font> <input type="checkbox" name="new_product" id="new_product" value="1" onChange="toggleNewItemFields(this);"><br><br>

<div id="new_product_fields" style="display:none;">
<table border="0">
 <tr>
  <td class="heading" align="right">UPC</td>
  <td><input type="edit" name="upc" id="upc" size="13" onKeyPress="return keyCheck(event);" value="<?php echo (isset($_POST["f_upc"]) ? $_POST["f_upc"] : ""); ?>"> <div id="search_status" style="float:right;"><b>Scanning will trigger UPC search.</b></div></td>
 </tr>
 <tr>
  <td class="heading" align="right">Name</td>
  <td><input type="edit" name="name" id="name" size="55" value="<?php echo (isset($_POST["f_name"]) ? $_POST["f_name"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Description</td>
  <td><textarea name="descr" id="descr" rows="5" style="width:100%;"><?php echo (isset($_POST["f_descr"]) ? $_POST["f_descr"] : ""); ?></textarea></td>
 </tr>
 <tr>
  <td class="heading" align="right">Purchase Price</td>
  <td><input type="edit" name="cost" id="cost" size="6" value="<?php echo (isset($_POST["f_cost"]) ? $_POST["f_cost"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Sale Price</td>
  <td><input type="edit" name="retail" id="retail" size="6" value="<?php echo (isset($_POST["f_retail"]) ? $_POST["f_retail"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Taxable</td>
  <td><input type="checkbox" name="taxable" id="taxable" value="1"<?php echo (isset($_POST["f_taxable"]) && $_POST["f_taxable"] == "0" ? "" : " CHECKED"); ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Category</td>
  <td>
   <select id="category">
   <option value="0">Select A Category</option>
<?php

if (isset($_POST["f_category"])) $f_cat = $_POST["f_category"];
else $f_cat = 0;

$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NULL ORDER BY category_name");
$result2 = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NOT NULL ORDER BY category_name");
$CATS = array();
$SUBS = array();
while ($row2 = mysql_fetch_assoc($result2)) {
	if (!isset($SUBS[$row2["parent_id"]])) $SUBS[$row2["parent_id"]] = array();
	$SUBS[$row2["parent_id"]][] = $row2;
}
while ($row = mysql_fetch_assoc($result)) {
	$CATS[] = $row;
	echo "   <option value=\"{$row["id"]}\"".($row["id"] == $f_cat ? " SELECTED":"").">{$row["category_name"]}</option>\n";
	if (isset($SUBS[$row["id"]])) {
		foreach($SUBS[$row["id"]] as $row2) {
			echo "   <option value=\"{$row2["category_id"]}\"".($row2["id"] == $f_cat ? " SELECTED":"").">- {$row2["category_name"]}</option>\n";
		}
	}
}

?>
   </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Tracking</td>
  <td>
   <input type="radio" name="tracking" id="tracking1" onClick="trackChange('1');" value="1"<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "2" ? "" : " CHECKED"); ?>> Quantity
   <input type="radio" name="tracking" id="tracking2" onClick="trackChange('2');" value="2"<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "2" ? " CHECKED" : ""); ?>> Individual
  </td>
 </tr>
 <tr id="row_location" style="<?php echo (isset($_POST["f_location"]) ? "" : "display:none;"); ?>">
  <td class="heading" align="right">Location</td>
  <td>
  <select id="location">
<?php

foreach ($LOCATIONS as $id => $loc) {
	if(isset($default_location) && $id==$default_location){	
		$var = " SELECTED";
	} else {
		$var = "";
	}
	if (isset($_POST["f_location"])) {
		$var = "";
		if ($id == $_POST["f_location"]) $var = " SELECTED";
	}
	echo "  <option value=\"".$id."\" ".$var.">".$loc."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Low Quantity</td>
  <td>
   <input type="checkbox" name="is_lowqty" id="is_lowqty" value="1"<?php echo (isset($_POST["f_is_lowqty"]) && $_POST["f_is_lowqty"] == "1" ? " CHECKED" : ""); ?>> Notify when <=
   <input type="edit" name="lowqty" id="lowqty" size="4" value="<?php echo (isset($_POST["f_lowqty"]) ? $_POST["f_lowqty"] : "0"); ?>">
  </td>
 </tr>
 <tr id="row_qty" style="<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "2" ? "display:none;" : ""); ?>">
  <td class="heading" align="right">Quantity</td>
  <td><input type="edit" name="qty" id="qty" size="4" value="<?php echo (isset($_POST["f_qty"]) ? $_POST["f_qty"] : "1"); ?>"></td>
 </tr>
 <tr id="row_indiv_1" style="<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "2" ? "" : "display:none;"); ?>">
  <td class="heading" align="right">Item Notes</td>
  <td><textarea name="notes" id="notes" rows="5" style="width:100%;"><?php echo (isset($_POST["f_notes"]) ? $_POST["f_notes"] : ""); ?></textarea></td>
 </tr>
 <tr id="row_indiv_2" style="<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "2" ? "" : "display:none;"); ?>">
  <td class="heading" align="right">Serial Number</td>
  <td><input type="edit" name="sn" id="sn" size="20" value="<?php echo (isset($_POST["f_sn"]) ? $_POST["f_sn"] : ""); ?>"></td>
 </tr>
 <tr id="row_device_info" style="<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "2" ? "" : "display:none;"); ?>">
  <td class="heading" align="right">Device Info</td>
  <td>
   <input type="checkbox" name="device_info" id="device_info" value="1" onClick="deviceChange();"> Add Device Info<br>
   <div id="device_info_box" style="<?php echo (isset($_POST["f_dev"]) && $_POST["f_dev"] == "1" ? "" : "display:none;"); ?>">
<!-- # START DEVICE INFO # -->
<table border="0">
 <tr>
  <td class="heading" align="right">Manufacturer</td>
  <td>
  <select id="dev_mfc">
<?php

foreach ($MANUFACTURER as $mfc) {
	echo "  <option value=\"".$mfc."\"".(isset($_POST["f_dev_mfc"]) && $mfc == $_POST["f_dev_mfc"] ? " SELECTED":"").">".$mfc."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Model</td>
  <td><input type="edit" id="dev_model" size="20" value="<?php echo (isset($_POST["f_dev_model"]) ? $_POST["f_dev_model"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">OS</td>
  <td>
  <select id="dev_os">
<?php

foreach ($OS as $id => $d_os) {
	if($d_os=="Windows 7"){
		$var = " SELECTED";
	} else {
		$var = " ";
	}
	if (isset($_POST["f_dev_os"])) {
		$var = "";
		if ($d_os == $_POST["f_dev_os"]) $var = " SELECTED";
	}
	echo "  <option value=\"".$d_os."\"".$var.">".$d_os."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Username</td>
  <td><input type="edit" id="dev_uname" size="15" value="<?php echo (isset($_POST["f_dev_uname"]) ? $_POST["f_dev_uname"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Password</td>
  <td><input type="edit" id="dev_passw" size="15" value="<?php echo (isset($_POST["f_dev_passw"]) ? $_POST["f_dev_passw"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">With Charger?</td>
  <td><input type="checkbox" id="dev_charger" value="1"<?php echo (isset($_POST["f_dev_charger"]) && $_POST["f_dev_charger"] == "0" ? "" : " CHECKED"); ?>></td>
 </tr>
</table>
<!-- # END DEVICE INFO # -->
   </div>
  </td>
 </tr>
 <tr id="row_status" style="<?php echo (isset($_POST["f_location"]) ? "" : "display:none;"); ?>">
  <td class="heading" align="right">Item Status</td>
  <td>
   <select id="status">
<?php

foreach ($INVENTORY_STATUS as $id => $status) {
	if ($id == 0) continue;
	echo "   <option value=\"$id\">$status</option>\n";
}

?>
   </select>
  </td>
 </tr>
</table>
</div><br>

<div id="existing_product_fields">
<table border="0">
 <tr>
  <td>Inventory ID</td>
  <td id="epf_inventory_id"><b>N/A</b></td>
 </tr>
 <tr id="epf_q_row">
  <td>Quantity Purchased</td>
  <td><input type="edit" id="epf_quantity" name="epf_quantity" size="4"></td>
 </tr>
 <tr>
  <td>Unit Price</td>
  <td>$<input type="edit" id="epf_unit_price" name="epf_unit_price" size="5"></td>
 </tr>
</table>
</div><br>

<div id="indiv_item_details" style="display:none;">
<font size="+1">Item Details</font><br>
<table border="0">
 <tr>
  <td class="heading" align="right">Location</td>
  <td>
  <select id="ni_location">
<?php

foreach ($LOCATIONS as $id => $loc) {
	if(isset($default_location) && $id==$default_location){
		$var = " SELECTED";
	} else {
		$var = "";
	}
	if (isset($_POST["f_location"])) {
		$var = "";
		if ($id == $_POST["f_location"]) $var = " SELECTED";
	}
	echo "  <option value=\"".$id."\" ".$var.">".$loc."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Item Notes</td>
  <td><textarea name="ni_notes" id="ni_notes" rows="5" style="width:100%;"><?php echo (isset($_POST["nif_notes"]) ? $_POST["nif_notes"] : ""); ?></textarea></td>
 </tr>
 <tr>
  <td class="heading" align="right">Serial Number</td>
  <td><input type="edit" name="ni_sn" id="ni_sn" size="20" value="<?php echo (isset($_POST["nif_sn"]) ? $_POST["nif_sn"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Info</td>
  <td>
   <input type="checkbox" name="ni_device_info" id="ni_device_info" value="1" onClick="ni_deviceChange();"> Add Device Info<br>
   <div id="ni_device_info_box" style="<?php echo (isset($_POST["nif_dev"]) && $_POST["nif_dev"] == "1" ? "" : "display:none;"); ?>">
<!-- # START DEVICE INFO # -->
<table border="0">
 <tr>
  <td class="heading" align="right">Manufacturer</td>
  <td>
  <select id="ni_dev_mfc">
<?php

foreach ($MANUFACTURER as $mfc) {
	echo "  <option value=\"".$mfc."\"".(isset($_POST["nif_dev_mfc"]) && $mfc == $_POST["nif_dev_mfc"] ? " SELECTED":"").">".$mfc."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Model</td>
  <td><input type="edit" id="ni_dev_model" size="20" value="<?php echo (isset($_POST["nif_dev_model"]) ? $_POST["nif_dev_model"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">OS</td>
  <td>
  <select id="ni_dev_os">
<?php

foreach ($OS as $id => $d_os) {
	if($d_os=="Windows 7"){
		$var = " SELECTED";
	} else {
		$var = " ";
	}
	if (isset($_POST["f_dev_os"])) {
		$var = "";
		if ($d_os == $_POST["f_dev_os"]) $var = " SELECTED";
	}
	echo "  <option value=\"".$d_os."\"".$var.">".$d_os."</option>\n";
}

?>
  </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Username</td>
  <td><input type="edit" id="ni_dev_uname" size="15" value="<?php echo (isset($_POST["nif_dev_uname"]) ? $_POST["nif_dev_uname"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Device Password</td>
  <td><input type="edit" id="ni_dev_passw" size="15" value="<?php echo (isset($_POST["nif_dev_passw"]) ? $_POST["nif_dev_passw"] : ""); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">With Charger?</td>
  <td><input type="checkbox" id="ni_dev_charger" value="1"<?php echo (isset($_POST["nif_dev_charger"]) && $_POST["nif_dev_charger"] == "0" ? "" : " CHECKED"); ?>></td>
 </tr>
</table>
<!-- # END DEVICE INFO # -->
   </div>
  </td>
 </tr>
 <tr id="row_status" style="<?php echo (isset($_POST["nif_location"]) ? "" : "display:none;"); ?>">
  <td class="heading" align="right">Item Status</td>
  <td>
   <select id="ni_status">
<?php

foreach ($INVENTORY_STATUS as $id => $status) {
	if ($id == 0) continue;
	echo "   <option value=\"$id\">$status</option>\n";
}

?>
   </select>
  </td>
 </tr>
</table>
</div>

<input type="button" value="Purchase Inventory" onClick="purchaseSubmit();">

<form action="?module=cust&do=purchase&id=<?php echo intval($_GET["id"]); ?>" method="post" id="buy_inv_form">
<input type="hidden" id="f_upc" name="f_upc" value="">
<input type="hidden" id="f_name" name="f_name" value="">
<input type="hidden" id="f_descr" name="f_descr" value="">
<input type="hidden" id="f_cost" name="f_cost" value="">
<input type="hidden" id="f_retail" name="f_retail" value="">
<input type="hidden" id="f_taxable" name="f_taxable" value="">
<input type="hidden" id="f_category" name="f_category" value="">
<input type="hidden" id="f_tracking" name="f_tracking" value="">
<input type="hidden" id="f_location" name="f_location" value="">
<input type="hidden" id="f_is_lowqty" name="f_is_lowqty" value="">
<input type="hidden" id="f_lowqty" name="f_lowqty" value="">
<input type="hidden" id="f_qty" name="f_qty" value="">
<input type="hidden" id="f_notes" name="f_notes" value="">
<input type="hidden" id="f_sn" name="f_sn" value="">
<input type="hidden" id="f_dev" name="f_dev" value="">
<input type="hidden" id="f_dev_mfc" name="f_dev_mfc" value="">
<input type="hidden" id="f_dev_model" name="f_dev_model" value="">
<input type="hidden" id="f_dev_os" name="f_dev_os" value="">
<input type="hidden" id="f_dev_uname" name="f_dev_uname" value="">
<input type="hidden" id="f_dev_passw" name="f_dev_passw" value="">
<input type="hidden" id="f_dev_charger" name="f_dev_charger" value="">
<input type="hidden" id="f_status" name="f_status" value="">

<input type="hidden" id="epf_prod_id" name="epf_prod_id" value="">
<input type="hidden" id="epf_qty" name="epf_qty" value="">
<input type="hidden" id="epf_price" name="epf_price" value="">
<input type="hidden" id="epf_indiv" name="epf_indiv" value="0">

<input type="hidden" id="nif_location" name="nif_location" value="">
<input type="hidden" id="nif_notes" name="nif_notes" value="">
<input type="hidden" id="nif_sn" name="nif_sn" value="">
<input type="hidden" id="nif_dev" name="nif_dev" value="">
<input type="hidden" id="nif_dev_mfc" name="nif_dev_mfc" value="">
<input type="hidden" id="nif_dev_model" name="nif_dev_model" value="">
<input type="hidden" id="nif_dev_os" name="nif_dev_os" value="">
<input type="hidden" id="nif_dev_uname" name="nif_dev_uname" value="">
<input type="hidden" id="nif_dev_passw" name="nif_dev_passw" value="">
<input type="hidden" id="nif_dev_charger" name="nif_dev_charger" value="">
<input type="hidden" id="nif_status" name="nif_status" value="">
</form>

<div id="inventorySearch">
<h3>Inventory Item Being Purchased</h3>
<div width="100%" class="clear center">
  <div class="itemhead">Name/UPC/Description</div>
  <div class="itemcontent"><select id="s_cat">
  <option value="0">Any Category</option>
<?php

foreach ($CATS as $row) {
	echo "   <option value=\"{$row["id"]}\"".($row["id"] == $f_cat ? " SELECTED":"").">{$row["category_name"]}</option>\n";
	if (isset($SUBS[$row["id"]])) {
		foreach($SUBS[$row["id"]] as $row2) {
			echo "   <option value=\"{$row2["id"]}\"".($row2["id"] == $f_cat ? " SELECTED":"").">- {$row2["category_name"]}</option>\n";
		}
	}
}

?>
  </select></div>
  <div class="itemcontent"><input type="edit" name="search_term" onKeyUp="searchInv()" id="search_term" size="30"></div>
  <div class="itemcontent bold" style="width:220px;font-size:16px;margin:10px 0px 0px 8px;">Start typing to search for a product</div>
</div>
<div class="clear"><br></div>

<div id="searchInvResult"></div>
<div class="clear"><br><br></div>
</div>

<script type="text/javascript">
var tracking_opt = "<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "2" ? "2" : "1"); ?>";

function html(id) {
	return document.getElementById(id);
}

function purchaseSubmit() {
	// VALUES FOR ADDING A NEW PRODUCT
	html('f_upc').value = html('upc').value;
	html('f_name').value = html('name').value;
	html('f_descr').value = html('descr').value;
	html('f_cost').value = html('cost').value;
	html('f_retail').value = html('retail').value;
	html('f_taxable').value = (html('taxable').checked ? "1" : "0");
	html('f_category').value = html('category').options[html('category').selectedIndex].value;
	if (html('f_category').value == "0" && html('new_product').checked) {
		alert("Please select a device category.");
		return;
	}
	html('f_tracking').value = tracking_opt;
	html('f_is_lowqty').value = (html('is_lowqty').checked ? "1" : "0");
	html('f_lowqty').value = html('lowqty').value;
	html('f_qty').value = html('qty').value;
	html('f_notes').value = html('notes').value;
	html('f_sn').value = html('sn').value;
	html('f_dev').value = (html('device_info').checked ? "1" : "0");
	html('f_dev_mfc').value = html('dev_mfc').options[html('dev_mfc').selectedIndex].value;
	html('f_dev_model').value = html('dev_model').value;
	html('f_dev_os').value = html('dev_os').options[html('dev_os').selectedIndex].value;
	html('f_dev_uname').value = html('dev_uname').value;
	html('f_dev_passw').value = html('dev_passw').value;
	html('f_location').value = html('location').options[html('location').selectedIndex].value;
	html('f_dev_charger').value = (html('dev_charger').checked ? "1" : "0");
	html('f_status').value = html('status').options[html('status').selectedIndex].value;

	// VALUES FOR EXISTING PRODUCT
	html('epf_prod_id').value = parseInt(html('epf_inventory_id').innerHTML,10);
	html('epf_qty').value = html('epf_quantity').value;
	html('epf_price').value = html('epf_unit_price').value;

	// VALUES FOR ADDING NEW ITEM TO PRODUCT LINE
	html('nif_location').value = html('ni_location').options[html('ni_location').selectedIndex].value;
	html('nif_notes').value = html('ni_notes').value;
	html('nif_sn').value = html('ni_sn').value;
	html('nif_dev').value = (html('ni_device_info').checked ? "1" : "0");
	html('nif_dev_mfc').value = html('ni_dev_mfc').options[html('ni_dev_mfc').selectedIndex].value;
	html('nif_dev_model').value = html('ni_dev_model').value;
	html('nif_dev_os').value = html('ni_dev_os').options[html('ni_dev_os').selectedIndex].value;
	html('nif_dev_uname').value = html('ni_dev_uname').value;
	html('nif_dev_passw').value = html('ni_dev_passw').value;
	html('nif_dev_charger').value = (html('ni_dev_charger').checked ? "1" : "0");
	html('nif_status').value = html('ni_status').options[html('ni_status').selectedIndex].value;

	html('buy_inv_form').submit();
}

function trackChange(n) {
	if (tracking_opt == n) return;
	tracking_opt = n;
	if (n == '1') { // Changed to QTY
		html('row_qty').style.display = '';
		html('row_indiv_1').style.display = 'none';
		html('row_indiv_2').style.display = 'none';
		html('row_device_info').style.display = 'none';
		html('row_location').style.display = 'none';
		html('row_status').style.display = 'none';
	} else { // Changed to Indiv
		html('row_qty').style.display = 'none';
		html('row_indiv_1').style.display = '';
		html('row_indiv_2').style.display = '';
		html('row_device_info').style.display = '';
		html('row_location').style.display = '';
		html('row_status').style.display = '';
	}
}

function deviceChange() {
	var checked = html('device_info').checked;
	if (checked) {
		html('device_info_box').style.display = '';
	} else {
		html('device_info_box').style.display = 'none';
	}
}

function ni_deviceChange() {
	var checked = html('ni_device_info').checked;
	if (checked) {
		html('ni_device_info_box').style.display = '';
	} else {
		html('ni_device_info_box').style.display = 'none';
	}
}

function keyCheck(e) {
	if (e.keyCode == 13) {
		searchUpc();
	}
}

var xmlUpc;
var xmlDescr;
if (window.XMLHttpRequest) {
	xmlUpc = new XMLHttpRequest();
	xmlDescr = new XMLHttpRequest();
} else {
	xmlUpc = new ActiveXObject("Microsoft.XMLHTTP");
	xmlDescr = new ActiveXObject("Microsoft.XMLHTTP");
}

function searchUpc() {
	html("search_status").innerHTML = "<b>Searching UPC</b>";
	xmlUpc.abort();
	xmlUpc.onreadystatechange = xmlUpcResponseHandler;
	var upc = html("upc").value;
	xmlUpc.open("GET","inventory/ajax.php?cmd=upc&str="+upc,true);
	xmlUpc.send();
}

function xmlUpcResponseHandler() {
	if (xmlUpc.readyState == 4 && xmlUpc.status == 200) {
		if(xmlUpc.responseText=="False"){
            html("search_status").innerHTML = "<b>Product Not Found</b>";
		} else {
			html("search_status").innerHTML = "<b>Product Found!</b>";
		}
		var jinv = JSON.parse(xmlUpc.responseText);
		html("name").value = jinv[0].productname;
		html("cost").value = jinv[0].price;

        xmlDescr.abort();
		xmlDescr.onreadystatechange = xmlDescrResponseHandler;

		html("search_status").innerHTML = "<b>Searching for description</b>";

		xmlDescr.open("GET","inventory/ajax.php?cmd=product_descr&str="+ encodeURIComponent(jinv[0].producturl) +"",true);
		xmlDescr.send();
	}
}

function xmlDescrResponseHandler() {
	if (xmlDescr.readyState == 4 && xmlDescr.status == 200) {
		if (xmlDescr.responseText == "False") {
			html("search_status").innerHTML = "<b>Description Not Found</b>";
		} else {
			html("descr").value = xmlDescr.responseText;
			html("search_status").innerHTML = "<b>Found Description</b>";
		}
	}
}

var xmlsearchInv;
if (window.XMLHttpRequest) {
	xmlsearchInv=new XMLHttpRequest();
} else {
	xmlsearchInv=new ActiveXObject("Microsoft.XMLHTTP");
}

xmlsearchInv.onreadystatechange = xmlsearchhandler;

function xmlsearchhandler() {
	if (xmlsearchInv.readyState == 4 && xmlsearchInv.status == 200) {
	  var tt = JSON.parse(xmlsearchInv.responseText);
		html("searchInvResult").style.opacity = 1;
		html("searchInvResult").innerHTML = tt.content;
	}
}

function searchInv() {
	xmlsearchInv.abort();
	xmlsearchInv.onreadystatechange = xmlsearchhandler;
	var sb = document.getElementById("search_term");
	var sc = html('s_cat').options[html('s_cat').selectedIndex].value;
	xmlsearchInv.open("GET","inventory/ajax.php?cmd=search&str="+sb.value+"&cat="+sc+"&invPurchase",true);
	xmlsearchInv.send();
}
</script>
