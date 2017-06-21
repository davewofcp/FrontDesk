<?php
//in-store locations
$LOCATIONS = array();
$result = mysql_query("SELECT * FROM inventory_locations WHERE 1");
while(false!==($row=mysql_fetch_assoc($result)))$LOCATIONS[$row['id']]=$row['title'];
asort($LOCATIONS);

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
<h3>Add Product</h3>

<?php if (isset($RESPONSE)) { ?><font color="red" size="+1"><b><?php echo $RESPONSE; ?></b></font><br><br><?php } ?>

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
$SUBS = array();
while ($row2 = mysql_fetch_assoc($result2)) {
	if (!isset($SUBS[$row2["parent_id"]])) $SUBS[$row2["parent_id"]] = array();
	$SUBS[$row2["parent_id"]][] = $row2;
}
while ($row = mysql_fetch_assoc($result)) {
	echo "   <option value=\"{$row["id"]}\"".($row["id"] == $f_cat ? " SELECTED":"").">{$row["category_name"]}</option>\n";
	if (isset($SUBS[$row["id"]])) {
		foreach($SUBS[$row["id"]] as $row2) {
			echo "   <option value=\"{$row2["id"]}\"".($row2["id"] == $f_cat ? " SELECTED":"").">- {$row2["category_name"]}</option>\n";
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
   <input type="radio" name="tracking" id="tracking1" onClick="trackChange('1');" value="1"<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "1" ? " CHECKED" : ""); ?>> Quantity
   <input type="radio" name="tracking" id="tracking2" onClick="trackChange('2');" value="2"<?php echo (isset($_POST["f_tracking"]) && $_POST["f_tracking"] == "2" ? " CHECKED" : ""); ?>> Individual
  </td>
 </tr>
 <tr id="row_location" style="<?php echo (isset($_POST["f_location"]) ? "" : "display:none;"); ?>">
  <td class="heading" align="right">Location</td>
  <td>
  <select id="location">
<?php
$result = mysql_query("SELECT inventory_locations__id AS default FROM xref__org_entities__inventory_locations WHERE org_entities__id = {$USER['org_entities__id']} AND is_default = 1");
if (false !==($row = mysql_fetch_assoc($result))) $inv_loc = $row;

foreach ($LOCATIONS as $id => $loc) {
  if(isset($inv_loc) && $id==$inv_loc["default"]){
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
   <input type="checkbox" name="is_lowqty" id="is_lowqty" value="1"<?php echo (isset($_POST["f_is_lowqty"]) && $_POST["f_is_lowqty"] == "1" ? " CHECKED" : ""); ?>> Notify when &lt;=
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
 <tr>
  <td colspan="2" align="center">
  <input type="button" value="Add Product" onClick="submit_addinv_form();">
  </td>
 </tr>
</table>

<form action="?module=inventory&do=add_sub" method="post" id="add_inv_form">
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
</form>

<script type="text/javascript">
var tracking_opt = "0";

function html(id) {
	return document.getElementById(id);
}

window.onload = function() {
	html("upc").focus();
}

function submit_addinv_form() {
	html('f_upc').value = html('upc').value;
	html('f_name').value = html('name').value;
	html('f_descr').value = html('descr').value;
	html('f_cost').value = html('cost').value;
	html('f_retail').value = html('retail').value;
	html('f_taxable').value = (html('taxable').checked ? "1" : "0");
	html('f_category').value = html('category').options[html('category').selectedIndex].value;
	if (html('f_category').value == "0") {
		alert("Please select a device category.");
		return;
	}
	if (tracking_opt == "0") {
		alert("Please designate item as either Quantity or Individually-tracked.");
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
	html('add_inv_form').submit();
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
</script>
