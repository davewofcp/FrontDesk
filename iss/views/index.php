<?php

display_header();

$USERS = array();
$result = mysql_query("SELECT id,firstname,lastname FROM users ORDER BY firstname");
while ($row = mysql_fetch_assoc($result)) {
	$USERS[$row["id"]] = $row["firstname"]." ".$row["lastname"];
}

$SERVICES = array();
$result = mysql_query("SELECT * FROM services WHERE 1");
while ($row = mysql_fetch_assoc($result)) {
	$SERVICES[$row["id"]] = $row["name"];
}
// in-store locations
$LOCATIONS = array();
$result = mysql_query("SELECT id,title FROM inventory_locations WHERE 1");
while ($row = mysql_fetch_assoc($result)) {
	$LOCATIONS[$row["id"]] = $row["title"];
}
asort($LOCATIONS);

$SORT_OPTIONS = array(
	"Status",
	"Intake Time",
	"Device Type",
	"Assigned To"
);

?>
<style type="text/css">
.ilink {
	cursor:pointer;
	font-weight:bold;
	/* font-family:Tahoma; */
}
.blue {
	color:#3333CC;
}
.green {
	color:#009900;
}
</style>
<script type="text/javascript">
var users = [];
users[0] = 'Nobody';
<?php
foreach ($USERS as $id => $user) {
	echo "users[$id] = '".$user."';\n";
}
?>

var services = [];
<?php
foreach ($SERVICES as $id => $service) {
	echo "services[$id] = '$service';\n";
}
?>

var istatus = [];
<?php
foreach ($STATUS as $id => $status) {
	echo "istatus[$id] = '$status';\n";
}
?>

var st_colors = [];
<?php
foreach ($ST_COLORS as $id => $color) {
	echo "st_colors[$id] = '$color';\n";
}
?>

var iss_num_pages = 1;
var iss_page = 1;
var iss_filter = [<?php echo ($SESSION["issue_filter"] ? $SESSION["issue_filter"] : "1,0,0,0,0,0,0,1,0"); ?>];
if (iss_filter[8]) iss_page = iss_filter[8];
html('content').oncontextmenu = function() {
	html('iss_filter_box').style.top = mouseY - divY - 100;
	html('iss_filter_box').style.left = mouseX - divX - 200;
	html('iss_filter_box').style.display = '';
	return false;
}
html('content').onmousemove = mouseMoved;
window.onload = function() {
	ajax("index");
	ChangeSelectByValue('iss_select_type',iss_filter[0],false);
	ChangeSelectByValue('iss_select_status',iss_filter[1],false);
	ChangeSelectByValue('iss_select_user',iss_filter[2],false);
	ChangeSelectByValue('iss_select_ittech',iss_filter[3],false);
	ChangeSelectByValue('iss_select_loc',iss_filter[4],false);
	ChangeSelectByValue('iss_select_co',iss_filter[5],false);
	ChangeSelectByValue('iss_select_sort1',iss_filter[6],false);
	ChangeSelectByValue('iss_select_sort2',iss_filter[7],false);
	if (iss_filter[8] == 1) html('iss_select_hidenf').checked = true;
}
var isIE = document.all ? true : false;
var mouseX,mouseY;
var box,divX,divY;
function mouseMoved(e) {
	var _x;
	var _y;
	if (!isIE) {
		_x = e.pageX;
		_y = e.pageY;
	} else {
		_x = event.clientX + document.body.scrollLeft;
		_y = event.clientY + document.body.scrollTop;
	}
	mouseX = _x;
	mouseY = _y;
}
try {
	box = html('content').getBoundingClientRect();
} catch(e) {}

var doc = document,
	docElem = doc.documentElement,
	body = document.body,
	win = window,
	clientTop  = docElem.clientTop  || body.clientTop  || 0,
	clientLeft = docElem.clientLeft || body.clientLeft || 0,
	scrollTop  = win.pageYOffset || body.scrollTop,
	scrollLeft = win.pageXOffset || body.scrollLeft;
divY = box.top  + scrollTop  - clientTop,
divX = box.left + scrollLeft - clientLeft;

function ChangeSelectByValue(ddlID, value, change) {
    var ddl = document.getElementById(ddlID);
    for (var i = 0; i < ddl.options.length; i++) {
        if (ddl.options[i].value == ''+value) {
            if (ddl.selectedIndex != i) {
                ddl.selectedIndex = i;
                if (change)
                    ddl.onchange();
            }
            break;
        }
    }
}

function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;

	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";

	if(typeof(arr) == 'object') { //Array/Hashes/Objects
		for(var item in arr) {
			var value = arr[item];

			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}

var issAjax;
if (window.XMLHttpRequest) {
	// code for IE7+, Firefox, Chrome, Opera, Safari
	issAjax=new XMLHttpRequest();
} else {
	// code for IE6, IE5
	issAjax=new ActiveXObject("Microsoft.XMLHTTP");
}

function issAjaxHandler() {
	if (issAjax.readyState == 4 && issAjax.status == 200) {
		var data = eval("("+issAjax.responseText+")");
		if (!data || !data.action) return;
		for (var i = 0; i < data.action.length; i++) {
			switch (data.action[i]) {
				case "index":
					html('iss_loading').style.display = 'none';
					html('iss_page_display').innerHTML = data.page;
					iss_page = data.page;
					html('iss_pages_display').innerHTML = data.pages;
					iss_num_pages = data.pages;
					html('iss_issue_count').innerHTML = data.count;
					html('iss_select_page').options.length = 0;
					for (var i = 1; i <= data.pages; i++) {
						html('iss_select_page').add(new Option(''+i,''+i));
					}
					ChangeSelectByValue('iss_select_page',''+data.page,false);

					if (iss_page == 1) html('button_prev').style.color = '#CCCCCC';
					else html('button_prev').style.color = '#000000';

					if (iss_page == iss_num_pages) html('button_next').style.color = '#CCCCCC';
					else html('button_next').style.color = '#000000';

					while (html('iss_table_index').rows.length > 1) html('iss_table_index').deleteRow(1);
					for (var j = data.issues.length - 1; j >= 0; j--) {
						if (j < data.issues.length - 1) {
							var lrow = html('iss_table_index').insertRow(1);
							var lcell = lrow.insertCell(0);
							lcell.colSpan = '9';
							lcell.innerHTML = '<div style="width:100%;height:1px;background-color:#000000;"></div>';
						}

						var row = html('iss_table_index').insertRow(1);
						row.align = 'center';
						row.style.backgroundColor = st_colors[data.issues[j].status] ? st_colors[data.issues[j].status] : '';
						if (data.issues[j].red == 1) row.style.backgroundColor = '#FF0000';
						var cell = row.insertCell(0);
						cell.innerHTML = data.issues[j].id;
						cell = row.insertCell(1);
						cell.innerHTML = data.issues[j].customer ? data.issues[j].customer.name : '<i>Nobody</i>';
						cell = row.insertCell(2);
						cell.innerHTML = data.issues[j].intake;
						cell = row.insertCell(3);
						cell.innerHTML = data.issues[j].device;
						cell = row.insertCell(4);
						cell.innerHTML = users[data.issues[j].assigned_to] ? users[data.issues[j].assigned_to] : '<i>Nobody</i>';
						cell = row.insertCell(5);
						cell.innerHTML = data.issues[j].location;
						cell = row.insertCell(6);
						if (data.issues[j].services == '') {
							cell.innerHTML = '<i>None</i>';
						} else {
							var s = data.issues[j].services.split(':');
							var sn = [];
							for (var k = 0; k < s.length; k++) {
								if (s[k] == '') continue;
								var x = parseInt(s[k],10);
								if (services[x]) sn.push(services[x]);
							}
							if (sn.length == 0) cell.innerHTML = '<i>None</i>';
							else cell.innerHTML = '- '+sn.join('<br>- ');
						}
						cell = row.insertCell(7);
						if (data.issues[j].status == 1 && data.issues[j].wstatus) {
							cell.innerHTML = (istatus[data.issues[j].wstatus] ? 'W:'+istatus[data.issues[j].wstatus] : '<i>None</i>');// + cns; // cns is not defined
						} else {
							cell.innerHTML = (istatus[data.issues[j].status] ? istatus[data.issues[j].status] : '<i>None</i>');// + cns; // cns is not defined
						}
						cell = row.insertCell(8);
						var buttons = '<a href="?module=iss&do=view&id='+data.issues[j].id+'" class="blue ilink" style="text-decoration:none;"><img src="images/view.gif" alt="View" title="View" style="position:relative;top:3px;" border="0"> View</a><br>';
						if (data.issues[j].invoice_id > 0) buttons += '<a href="?module=invoice&do=view&id='+data.issues[j].invoice_id+'" class="blue ilink" style="text-decoration:none;"><img src="images/view_invoice.gif" alt="View Invoice" title="View Invoice" style="position:relative;top:3px;" border="0"> Invoice</a>';
						else buttons += '<a href="?module=invoice&do=create_from_issue&id='+data.issues[j].id+'" class="blue ilink" style="text-decoration:none;"><img src="images/create_invoice.gif" alt="Create Invoice" title="Create Invoice" style="position:relative;top:3px;" border="0"> Invoice</a>';
						cell.innerHTML = buttons;
					}

					//alert(dump(data));
					break;
				case "error":
					alert(data.error);
					break;
			}
		}
	}
}

issAjax.onreadystatechange = issAjaxHandler;

function ajax(action) {
	issAjax.abort();
	issAjax.onreadystatechange = issAjaxHandler;
	issAjax.open("POST","iss/ajax.php",true);
	issAjax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	switch (action) {
		case "index":
			while (html('iss_table_index').rows.length > 1) html('iss_table_index').deleteRow(1);
			html('iss_loading').style.display = '';
			var cols = ['action','options'];
			var vals = ['index',iss_filter.join(',')+","+iss_page];
			var params = buildParams(cols,vals);
			issAjax.send(params);
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

function getValue(oid) {
	return html(oid).options[html(oid).selectedIndex].value;
}

function iss_next_page() {
	if (iss_page == iss_num_pages) return;
	iss_page++;
	ajax("index");
}

function iss_prev_page() {
	if (iss_page == 1) return;
	iss_page--;
	ajax("index");
}

function iss_gotopage() {
	iss_page = parseInt(getValue('iss_select_page'),10);
	ajax('index');
}

function iss_update() {
	iss_filter = [];
	iss_filter.push(parseInt(getValue('iss_select_type'),10));
	iss_filter.push(parseInt(getValue('iss_select_status'),10));
	iss_filter.push(parseInt(getValue('iss_select_user'),10));
	iss_filter.push(parseInt(getValue('iss_select_ittech'),10));
	iss_filter.push(parseInt(getValue('iss_select_loc'),10));
	iss_filter.push(parseInt(getValue('iss_select_co'),10));
	iss_filter.push(parseInt(getValue('iss_select_sort1'),10));
	iss_filter.push(parseInt(getValue('iss_select_sort2'),10));
	if (html('iss_select_hidenf').checked) iss_filter.push(1);
	else iss_filter.push(0);
	iss_page = 1;
	html('iss_filter_box').style.display = 'none';
	ajax("index");
}

</script>
<div id="iss_open_filter" style="display:none;position:absolute;top:5px;left:5px;font-family:Tahoma;font-size:13pt;"><a onClick="html('iss_filter_box').style.display='';" class="green ilink">&#9636; FILTER</a></div>
<div id="iss_filter_box" style="z-index:3;display:none;background-color:#CCCCCC;font-family:Tahoma;border:1px solid #000;border-radius:5px;position:absolute;width:450px;height:280px;left:5px;top:5px;">
<div style="position:absolute;bottom:3px;right:3px;font-size:8pt;"><a onClick="html('iss_filter_box').style.display='none';" class="green ilink">&#10006; CLOSE</a></div>
<font style="font-size:15pt;">Filter</font><br>
<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr><td align="right"><b>Issue Type:</b></td><td><select id="iss_select_type"><option value="0">All Types</option><?php

foreach ($ISSUE_TYPE as $id => $type) {
	if ($id == 0) continue;
	echo "<option value=\"$id\">$type</option>";
}

?></select></td></tr>
<tr><td align="right"><b>Status:</b></td><td><select id="iss_select_status"><option value="0">Any</option><?php

foreach ($STATUS as $id => $status) {
	if ($id == 0) continue;
	echo "<option value=\"$id\">$status</option>\n";
}

?></select></td></tr>
<tr><td align="right"><b>Assigned To:</b></td><td><select id="iss_select_user"><option value="0">Any User</option><?php

foreach ($USERS as $id => $_user) {
	echo "<option value=\"$id\">$_user</option>\n";
}

?></select></td></tr>
<tr><td align="right"><b>Intake Tech:</b></td><td><select id="iss_select_ittech"><option value="0">Any User</option><?php

foreach ($USERS as $id => $_user) {
	echo "<option value=\"$id\">$_user</option>\n";
}

?></select></td></tr>
<tr><td align="right"><b>Location:</b></td><td><select id="iss_select_loc"><option value="0">Anywhere</option><?php

foreach ($LOCATIONS as $id => $loc) {
	echo "<option value=\"$id\">$loc</option>\n";
}

?></select></td></tr>
<tr><td align="right"><b>Checked Out:</b></td><td><select id="iss_select_co"><option value="0">No</option><option value="1">Yes</option></select></td></tr>
<tr><td align="right"><b>Sort By (1):</b></td><td><select id="iss_select_sort1"><?php

foreach ($SORT_OPTIONS as $id => $option) {
	echo "<option value=\"$id\"".($id == 0 ? " SELECTED":"").">$option</option>";
}

?></select></td></tr>
<tr><td align="right"><b>Sort By (2):</b></td><td><select id="iss_select_sort2"><?php

foreach ($SORT_OPTIONS as $id => $option) {
	echo "<option value=\"$id\"".($id == 1 ? " SELECTED":"").">$option</option>";
}

?></select></td></tr>
<tr><td colspan="2" align="center"><b>Hide No Go and Finished</b> <input type="checkbox" id="iss_select_hidenf" value="1"></td></tr>
</table><br>

<span id="iss_filter_update_button"><a onClick="iss_update();" class="green ilink">Update</a></span>
</div>
<div id="iss_loading" class="outlined" style="display:none;z-index:2;position:absolute;width:200px;height:35px;top:230px;left:290px;font-family:Tahoma;font-size:18pt;color:#FFF;">Loading...</div>
<div align="center"><a onClick="iss_prev_page();" class="ilink"><span id="button_prev">&#9668;</span></a> &nbsp; Page <b><span id="iss_page_display">0</span></b> of <b><span id="iss_pages_display">0</span></b> &nbsp; Go To Page: <select id="iss_select_page" onChange="iss_gotopage();"><option value="0">0</option></select> &nbsp; <b><span id="iss_issue_count">0</span></b> issues in <a onClick="html('content').oncontextmenu();" class="green ilink">filter</a> &nbsp; <a onClick="iss_next_page();" class="ilink"><span id="button_next">&#9658;</span></a></div>
<table id="iss_table_index" width="100%" cellpadding="0" cellspacing="0" style="font-family:Tahoma;font-size:8pt;">
	<tr class="major_heading" align="center">
		<td>#</td>
		<td>Customer</td>
		<td width="95">Intake Date</td>
		<td>Device Type</td>
		<td>Assigned To</td>
		<td>Location</td>
		<td>Services</td>
		<td>Status</td>
		<td width="60">Options</td>
	</tr>
</table>
