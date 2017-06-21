<?php

display_header();

if (!isset($_GET["id"]) || intval($_GET["id"]) == 0) {
	echo "Invalid issue ID.";
	display_footer();
	exit;
}

$result = mysql_query("SELECT * FROM issues WHERE id = ".intval($_GET["id"]));
if (!mysql_num_rows($result)) {
	echo "Issue not found.";
	display_footer();
	exit;
}
$ISSUE = mysql_fetch_assoc($result);

if ($ISSUE["customers__id"]) {
	$result = mysql_query("SELECT * FROM customers WHERE id = ".intval($ISSUE["customers__id"]));
	if (mysql_num_rows($result)) {
		$CUSTOMER = mysql_fetch_assoc($result);
	}
}

if ($ISSUE["device_id"]) {
	$result = mysql_query("SELECT * FROM inventory_type_devices d JOIN categories ca ON d.categories__id = ca.id WHERE id = ".intval($ISSUE["device_id"]));
	if (mysql_num_rows($result)) {
		$DEVICE = mysql_fetch_assoc($result);
	}
}

$STORES = array();
//$result = mysql_query("SELECT * FROM locations WHERE is_here = 0");
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id != {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
while ($row = mysql_fetch_assoc($result)) {
	$STORES[$row["id"]] = $row;
}

?>
<script type="text/javascript">
var trAjax;
if (window.XMLHttpRequest) {
	// code for IE7+, Firefox, Chrome, Opera, Safari
	trAjax=new XMLHttpRequest();
} else {
	// code for IE6, IE5
	trAjax=new ActiveXObject("Microsoft.XMLHTTP");
}

function html(id) {
	return document.getElementById(id);
}

function trAjaxHandler() {
	if (trAjax.readyState == 4 && trAjax.status == 200) {
		var data = eval("("+trAjax.responseText+")");
		if (!data || !data.action) return;
		switch (data.action) {
			case "c_results":
				var new_html = '<table border="0"><tr align="center"><td><b>Select</b></td><td><b>Name</b></td><td><b>Company</b></td><td><b>Phone (H)</b></td><td><b>Phone (C)</b></td></tr>\n';
				new_html += '<tr align="center"><td><input type="radio" name="c_select" onChange="list_devices(this.value)" value="0"></td>';
				new_html += '<td align="center" colspan="4"><i>Add New Customer</i></td></tr>';
				for (var i = 0; i < data.results.length; i++) {
					new_html += '<tr align="center"><td><input type="radio" name="c_select" onChange="list_devices(this.value)" value="'+data.results[i].id+'"></td>';
					new_html += '<td>'+data.results[i].fname +' '+ data.results[i].lname+'</td>';
					new_html += '<td>'+data.results[i].company+'</td>';
					new_html += '<td>'+data.results[i].phome+'</td>';
					new_html += '<td>'+data.results[i].pcell+'</td>';
					new_html += '</tr>\n';
				}
				new_html += '</table>\n';
				html('customer_results').innerHTML = new_html;
				break;
			case "d_results":
				var new_html = '<table border="0"><tr align="center"><td><b>Select</b></td><td><b>MFC / Model</b></td><td><b>Category</b></td><td><b>OS</b></td></tr>\n';
				new_html += '<tr align="center"><td><input type="radio" name="d_select" onChange="select_device(this.value)" value="0"></td>';
				new_html += '<td align="center" colspan="3"><i>New Device</i></td></tr>';
				for (var i = 0; i < data.results.length; i++) {
					new_html += '<tr align="center"><td><input type="radio" name="d_select" onChange="select_device(this.value)" value="'+data.results[i].id+'"></td>';
					new_html += '<td>'+data.results[i].mfc+' '+data.results[i].model+'</td>';
					new_html += '<td>'+data.results[i].cat+'</td>';
					new_html += '<td>'+data.results[i].os+'</td>';
					new_html += '</tr>\n';
				}
				new_html += '</table>\n';
				html('device_results').innerHTML = new_html;
				break;
		}
	}
}

trAjax.onreadystatechange = trAjaxHandler;

function ajax(action) {
	trAjax.abort();
	trAjax.onreadystatechange = trAjaxHandler;
	trAjax.open("POST","iss/ajax.php",true);
	trAjax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	switch (action) {
		case "cust":
			var cols = ['action','rstore','c_str'];
			var vals = ['tr_cust_find',to_store,c_str];
			var params = buildParams(cols,vals);
			trAjax.send(params);
			break;
		case "devices":
			var cols = ['action','rstore','c_id'];
			var vals = ['tr_device_list',to_store,c_id];
			var params = buildParams(cols,vals);
			trAjax.send(params);
			break;
	}
}

function buildParams(cols,vals) {
	var retArr = [];
	for (var i = 0; i < cols.length; i++) {
		var thisVal = vals[i] || '';
		retArr.push(cols[i]+'='+encodeURIComponent(''+thisVal));
	}
	return retArr.join('&');
}

var issue_id = '<?php echo $ISSUE["id"]; ?>';
var to_store = '0';
var c_str = '';
var c_id = '-1';
var d_id = '-1';
function set_remote_store(id) {
	to_store = id;
	html('select_customer').style.display = '';
}

function cust_search() {
	hide_ready();
	c_str = html('c_str').value;
	html('customer_results').innerHTML = '<i>Please Wait...</i>';
	html('select_devices').style.display = 'none';
	ajax("cust");
}

function list_devices(id) {
	c_id = id;
	html('select_devices').style.display = '';
	html('device_results').innerHTML = '<i>Please Wait...</i>';
	if (id != "0") {
		hide_ready();
		ajax("devices");
	} else {
		d_id = '0';
		html('select_devices').style.display = '';
		html('device_results').innerHTML = '<i>Device Will Be Inserted in Remote Database</i>';
		show_ready();
	}
}

function select_device(id) {
	d_id = id;
	show_ready();
}

function show_ready() {
	html('ready_box').style.display = '';
}

function hide_ready() {
	html('ready_box').style.display = 'none';
}

function do_transfer() {
	window.location = '?module=iss&do=transfer_sub&id='+issue_id+'&to_store='+encodeURIComponent(to_store)+'&cid='+c_id+'&did='+d_id;
}
</script>
<h2>Transfer Issue <?php echo $ISSUE["id"]; ?></h2>

<div style="left:auto;width:500px;border:1px solid #000;border-radius:10px;">
<table border="0" width="100%">
	<tr>
		<td width="50%" valign="top">
		<?php if (isset($CUSTOMER)) { ?>
		<center><b>Local Customer</b></center>
		<b>Name:</b> <?php echo $CUSTOMER["firstname"]." ".$CUSTOMER["lastname"]; ?><br>
		<?php echo ($CUSTOMER["company"] ? "<b>Company:</b> ".$CUSTOMER["company"]."<br>" : ""); ?>
		<?php echo ($CUSTOMER["phone_home"] ? "<b>Phone (H):</b> ".display_phone($CUSTOMER["phone_home"])."<br>" : ""); ?>
		<?php echo ($CUSTOMER["phone_cell"] ? "<b>Phone (C):</b> ".display_phone($CUSTOMER["phone_cell"])."<br>" : ""); ?>
		<?php } else { ?>
		<center><b>(No Customer)</b></center>
		<?php } ?>
		</td>
		<td width="50%" valign="top">
		<?php if (isset($DEVICE)) { ?>
		<center><b>Local Device</b></center>
		<b>MFC:</b> <?php echo $DEVICE["manufacturer"]; ?><br>
		<b>Model:</b> <?php echo $DEVICE["model"]; ?><br>
		<b>Category:</b> <?php echo $DEVICE["category_name"]; ?><br>
		<b>OS:</b> <?php echo $DEVICE["operating_system"]; ?><br>
		<?php } else { ?>
		<center><b>(On-Site Device)</b></center>
		<?php } ?>
		</td>
	</tr>
</table>
</div><div style="clear:left;"></div><br>

<div style="left:auto;width:300px;border:1px solid #000;border-radius:10px;">
<h3>Remote Store</h3>
<select id="to_store" onChange="set_remote_store(this.options[this.selectedIndex].value);">
<option value="0">Select a Store</option>
<?php

foreach($STORES as $id => $store) {
	echo "<option value=\"$id\">#$id - {$store["title"]}</option>\n";
}

?>
</select><br><br></div><br>

<div id="select_customer" style="left:auto;width:600px;border:1px solid #000;border-radius:10px;display:none;">
<h3>Remote Customer</h3>
<b>Search Term:</b> <input type="edit" id="c_str" size="25"> <input type="button" value="Search" onClick="cust_search();"><br>
<div id="customer_results">
</div><br>
</div><br>

<div id="select_devices" style="left:auto;width:500px;border:1px solid #000;border-radius:10px;display:none;">
<h3>Remote Device</h3>
<div id="device_results">
</div><br>
</div><br>

<div id="ready_box" style="left:auto;width:300px;border:1px solid #000;border-radius:10px;display:none;">
<h3>Ready To Transfer!</h3>
<input type="button" value="Transfer Issue & Notes" onClick="do_transfer();"><br><br>
</div><br>

<?php

display_footer();

?>
