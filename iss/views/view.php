<?php

display_header();
// in-store locations
$LOCATIONS = array();
$result = mysql_query("SELECT * FROM inventory_locations WHERE 1");
while(false!==($row=mysql_fetch_assoc($result)))$LOCATIONS[$row['id']]=$row['title'];
asort($LOCATIONS);

$SERVICES = array();
$result = mysql_query("SELECT * FROM services WHERE 1 ORDER BY name");
while ($row = mysql_fetch_assoc($result)) {
	$SERVICES[$row["id"]] = array();
	$SERVICES[$row["id"]]["name"] = $row["name"];
	$SERVICES[$row["id"]]["cost"] = $row["cost"];
}

$DEVICE_TYPES = array();
$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory'");
while ($row = mysql_fetch_assoc($result)) {
	$DEVICE_TYPES[$row["id"]] = $row["category_name"];
}

$USERS = array();
$result = mysql_query("SELECT * FROM users WHERE 1");
while ($row = mysql_fetch_assoc($result)) {
	$USERS[$row["id"]] = $row;
}

$result = mysql_query("SELECT id FROM issues WHERE id < ".$ISSUE["id"]." ORDER BY id DESC LIMIT 1");
if (mysql_num_rows($result)) {
	$data = mysql_fetch_assoc($result);
	echo '<div style="position:absolute;top:5px;left:5px;width:80px;font-size:30px;">';
	echo "<a href=\"?module=iss&do=view&id={$data["id"]}\" style=\"text-decoration: none;\" class=\"blue ilink\">&#9668;</a>";
	echo '</div>';
}

$result = mysql_query("SELECT id FROM issues WHERE id > ".$ISSUE["id"]." ORDER BY id LIMIT 1");
if (mysql_num_rows($result)) {
	$data = mysql_fetch_assoc($result);
	echo '<div style="position:absolute;top:5px;left:90px;width:80px;font-size:30px;">';
	echo "<a href=\"?module=iss&do=view&id={$data["id"]}\" style=\"text-decoration: none;\" class=\"blue ilink\">&#9658;</a>";
	echo '</div>';
}

if ($ISSUE["customers__id"]) {
	$result = mysql_query("SELECT * FROM customers WHERE id = {$ISSUE["customers__id"]}");
	if (mysql_num_rows($result)) $CUSTOMER = mysql_fetch_assoc($result);
}

if ($ISSUE["device_id"]) {
	$result = mysql_query("SELECT d.*,i.id FROM inventory_type_devices d LEFT JOIN inventory_items ii ON d.inventory_item_number = ii.id LEFT JOIN inventory i ON ii.inventory__id = i.id LEFT JOIN inventory_locations il ON d.in_store_location = il.id WHERE d.id = {$ISSUE["device_id"]}");
	if (mysql_num_rows($result)) $DEVICE = mysql_fetch_assoc($result);
}

$result = mysql_query("SELECT id,users__id__target,start,start_time,end_time FROM calendar WHERE issues__id = {$ISSUE["id"]} ORDER BY id LIMIT 1");
if (mysql_num_rows($result)) {
	$data = mysql_fetch_assoc($result);
	$CALENDAR = array();
	$CALENDAR["id"] = $data["id"];
	$CALENDAR["users__id__target"] = $data["users__id__target"];
	$CALENDAR["start"] = $data["start"];
	$CALENDAR["start_time"] = explode(":",$data["start_time"]);
	$CALENDAR["end_time"] = explode(":",$data["end_time"]);
}

?>
<style type="text/css">
.ilink {
	cursor:pointer;
	font-weight:bold;
	font-family:Tahoma;
}
.blue {
	color:#3333CC;
}
.green {
	color:#009900;
}
</style>
<link rel="stylesheet" type="text/css" href="calendar.css">
<link rel="stylesheet" href="jquery-ui.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery-ui.js"></script>
<script type="text/javascript">
var issue_id = <?php echo $ISSUE["id"]; ?>;
var issue_type = <?php echo $ISSUE["varref_issue_type"]; ?>;
var issue_status = <?php echo $ISSUE["varref_status"]; ?>;
var issue_wstatus = <?php echo intval($ISSUE["warranty_status"]); ?>;
var issue_quote_price = '<?php echo floatval($ISSUE["quote_price"]); ?>';
var issue_do_price = '<?php echo floatval($ISSUE["do_price"]); ?>';
var issue_invoice_id = <?php echo intval($ISSUE["invoices__id"]); ?>;

<?php if (isset($CALENDAR)) { ?>
var calendar_evt_id = <?php echo intval($CALENDAR["id"]); ?>;
var calendar_assigned_to = <?php echo intval($CALENDAR["users__id__target"]); ?>;
var calendar_date = '<?php echo $CALENDAR["start"]; ?>';
var calendar_start_hour = '<?php echo $CALENDAR["start_time"][0]; ?>';
var calendar_start_minute = '<?php echo $CALENDAR["start_time"][1]; ?>';
var calendar_end_hour = '<?php echo $CALENDAR["end_time"][0]; ?>';
var calendar_end_minute = '<?php echo $CALENDAR["end_time"][1]; ?>';
<?php } ?>

var svc_names = [];
<?php

foreach ($SERVICES as $id => $svc) {
	echo "svc_names[$id] = '".str_replace("'","`",$svc["name"])."';\n";
}

?>

function html(id) {
	return document.getElementById(id);
}

function ChangeSelectByValue(ddlID, value, change) {
    var ddl = document.getElementById(ddlID);
    for (var i = 0; i < ddl.options.length; i++) {
        if (ddl.options[i].value == value) {
            if (ddl.selectedIndex != i) {
                ddl.selectedIndex = i;
                if (change)
                    ddl.onchange();
            }
            break;
        }
    }
}

function sh_note(id) {
	if (!html('iss_note_edit_'+id)) return;
	if (html('iss_note_display_'+id).style.display == 'none') {
		html('iss_note_display_'+id).style.display = '';
		html('iss_note_edit_'+id).style.display = 'none';
		html('iss_note_button_'+id).innerHTML = 'Edit';
	} else {
		html('iss_note_display_'+id).style.display = 'none';
		html('iss_note_edit_'+id).style.display = '';
		html('iss_note_button_'+id).innerHTML = 'Cancel';
	}
}

function sh(name) {
	if (!html(name)) return;
	if (html(name).style.display == 'none') {
		html(name).style.display = '';
		html(name+'_link').innerHTML = 'Less';
	} else {
		html(name).style.display = 'none';
		html(name+'_link').innerHTML = 'More';
	}
}
function sh_update_status() {
	if (html('iss_update_status').style.display == 'none') {
		html('iss_update_status').style.display = '';
		html('iss_update_status_link').innerHTML = 'Cancel';
	} else {
		html('iss_update_status').style.display = 'none';
		html('iss_update_status_link').innerHTML = 'Update Status';
	}
}
var iss_step_clicked = 0;
function iss_step(id) {
	iss_step_clicked = id;
	ajax("step");
}
function iss_add_do_price() {
	var content = '$<input type=\"edit\" id=\"add_do_price\" size=\"6\"> <input type=\"button\" value=\"Set Do It Price\" onClick=\"iss_add_do_price_submit();\">';
	html('iss_add_do_price').innerHTML = content;
}
function iss_edit_quote_price() {
	var content = '$<input type=\"edit\" id=\"edit_quote_price\" size=\"6\"> <input type=\"button\" value=\"Set Quote Price\" onClick=\"iss_edit_quote_price_submit();\">';
	html('iss_edit_quote_price').innerHTML = content;
}
function iss_add_do_price_submit() {
	if (!html('add_do_price')) return;
	issue_do_price = html('add_do_price').value;
	html('iss_add_do_price').innerHTML = 'Updating...';
	ajax("do_price");
}
function iss_edit_quote_price_submit() {
	if (!html('edit_quote_price')) return;
	issue_quote_price = html('edit_quote_price').value;
	html('iss_edit_quote_price').innerHTML = 'Updating...';
	ajax("quote_price");
}
function iss_edit_services() {
	services = [];
	var boxes = document.getElementsByName('iss_service_box');
	for (var i = 0; i < boxes.length; i++) {
		if (boxes[i].checked) services.push(boxes[i].value);
	}
	html('iss_edit_services').style.display = 'none';
	html('iss_services_total').innerHTML = '<i>Updating</i>';
	ajax("services");
}
function iss_reset_services() {
	var boxes = document.getElementsByName('iss_service_box');
	for (var i = 0; i < boxes.length; i++) {
		boxes[i].checked = false;
	}
	for (var i = 0; i < services.length; i++) {
		if (!html('iss_service_'+services[i])) continue;
		html('iss_service_'+services[i]).checked = true;
	}
}
var iss_add_part_qty = '0';
var iss_add_part_id = '0';
var iss_add_part_add = '0';
var iss_add_part_item = '0';
function iss_add_part(inv_id,inv_item_id) {
	if (!inv_item_id) {
		iss_add_part_item = '0';
		iss_add_part_qty = html('iss_part_qty_'+inv_id).value;
	} else {
		iss_add_part_item = inv_item_id;
		iss_add_part_qty = '1';
	}
	iss_add_part_id = inv_id;

	html('iss_add_part_dialog').style.display = '';
	$( "#iss_add_part_dialog" ).dialog({
	    resizable: false,
	    height:140,
	    modal: true,
	    buttons: {
	        "No, it's included": function() {
	            iss_add_part_add = '0';
	            html('iss_add_part_dialog').style.display = 'none';
	            html('iss_new_part').style.display = 'none';
	            $( "#iss_add_part_dialog" ).dialog("close");
	            iss_reset_parts_search();
	            ajax("part");
	        },
	        "Yes": function() {
	            iss_add_part_add = '1';
	            html('iss_add_part_dialog').style.display = 'none';
	            html('iss_new_part').style.display = 'none';
	            $( "#iss_add_part_dialog" ).dialog("close");
	            iss_reset_parts_search();
				ajax("part");
	       }
	    }
	});
	$( "#iss_add_part_dialog" ).dialog("open");
}

iss_type = <?php echo $ISSUE["varref_issue_type"]; ?>;
iss_new_type = 0;
function iss_change_type() {
	html('iss_change_type_dialog').style.display = '';
	$( "#iss_change_type_dialog" ).dialog({
	    resizable: false,
	    height:140,
	    modal: true,
	    buttons: {
	        "In-Store": function() {
	        	iss_new_type = '1';
	            html('iss_change_type_dialog').style.display = 'none';
	            $( "#iss_change_type_dialog" ).dialog("close");
	            ajax("type");
	        },
	        "On-Site": function() {
	        	iss_new_type = '2';
	            html('iss_change_type_dialog').style.display = 'none';
	            $( "#iss_change_type_dialog" ).dialog("close");
				ajax("type");
	       	},
		    "Remote Support": function() {
		        iss_new_type = '3';
		        html('iss_change_type_dialog').style.display = 'none';
		        $( "#iss_change_type_dialog" ).dialog("close");
				ajax("type");
		    },
		 	"Internal": function() {
			    iss_new_type = '4';
			    html('iss_change_type_dialog').style.display = 'none';
			    $( "#iss_change_type_dialog" ).dialog("close");
				ajax("type");
			},
			"Cancel": function() {
			    iss_new_type = '0';
			    html('iss_change_type_dialog').style.display = 'none';
			    $( "#iss_change_type_dialog" ).dialog("close");
			}
	    }
	});
	$( "#iss_change_type_dialog" ).dialog("open");
}

function iss_reset_parts_search() {
	html('iss_part_category').options[0].selected = true;
	html('iss_part_search_input').value = '';
	html('iss_part_results').innerHTML = '';
	html('iss_part_results').style.display = 'none';
}

var iss_part_to_remove = '0';
function iss_remove_part(id) {
	iss_part_to_remove = id;
	var div = html('part'+id);
	div.parentNode.removeChild(div);
	ajax("remove_part");
}

function iss_add_part_display(part) {
	if (html('iss_parts').innerHTML == '') {
		html('iss_parts').innerHTML = '<div class="floatL" style="width:250px;" align="center"><b>Name</b></div><div class="floatL" style="width:50px;" align="center"><b>Cost</b></div><div class="floatL" style="width:40px;" align="center"><b>QTY</b></div><div class="floatL" style="width:50px;" align="center"><b>Total</b></div><div class="floatL" style="width:50px;" align="center"><b>Remove</b></div><div class="clearL"></div>';
	}

	var content = "<div id=\"part"+part.id+"\">";
	content += "<div class=\"floatL\" style=\"width:250px;\" title=\""+part.descr.replace('"',"'")+"\">"+part.name+"</div>";
	content += "<div class=\"floatL\" align=\"center\" style=\"width:50px;\">$"+part.cost+"</div>";
	content += "<div class=\"floatL\" align=\"center\" style=\"width:40px;\">"+part.qty+"</div>";
	content += "<div class=\"floatL\" align=\"center\" style=\"width:50px;\">"+part.total+"</div>";
	content += "<div class=\"floatL\" align=\"center\" style=\"width:50px;\"><a onClick=\"iss_remove_part('"+part.id+"');\" class=\"green ilink\">Remove</a></div>";
	content += "<div class=\"clearL\"></div></div>\n";
	html('iss_parts').innerHTML = html('iss_parts').innerHTML + content;
}

function iss_status_option_change() {
	var new_status = parseInt(html('iss_edit_new_status').options[html('iss_edit_new_status').selectedIndex].value,10);
	if (new_status == 3) {
		html('iss_edit_quote_price').style.display = '';
	} else {
		html('iss_edit_quote_price').style.display = 'none';
	}

	if (new_status == 5) {
		html('iss_edit_do_price').style.display = '';
	} else {
		html('iss_edit_do_price').style.display = 'none';
	}

	if (new_status == 1) {
		html('iss_edit_wstatus_row').style.display = '';
	} else {
		html('iss_edit_wstatus_row').style.display = 'none';
	}
}

var iss_new_status = 0;
var iss_new_wstatus = 0;
var iss_new_assigned_to = 0;
var iss_new_location = 0;
var iss_new_quote_price = 0;
var iss_new_do_price = 0;
var iss_new_diagnosis = '';
var iss_new_finalsummary = '';
var iss_new_hoursworked = 0;
var iss_new_hourlyrate = 0;
var iss_new_travel = 0;
var iss_check_notes = 0;
function iss_update_status() {
	iss_new_status = parseInt(html('iss_edit_new_status').options[html('iss_edit_new_status').selectedIndex].value,10);
	if (html('iss_edit_warranty_status')) {
		iss_new_wstatus = parseInt(html('iss_edit_warranty_status').options[html('iss_edit_warranty_status').selectedIndex].value,10);
	} else {
		iss_new_wstatus = 0;
	}
	iss_new_assigned_to = parseInt(html('iss_edit_assign_to').options[html('iss_edit_assign_to').selectedIndex].value,10);
	if (html('iss_edit_location')) iss_new_location = parseInt(html('iss_edit_location').options[html('iss_edit_location').selectedIndex].value,10);
	else iss_new_location = 0;
	iss_new_quote_price = html('iss_input_quote_price').value;
	iss_new_do_price = html('iss_input_do_price').value;
	iss_new_diagnosis = html('iss_input_diagnosis').value;
	iss_new_finalsummary = html('iss_input_finalsummary').value;
	if (html('iss_input_hoursworked')) {
		iss_new_hoursworked = html('iss_input_hoursworked').value;
		iss_new_hourlyrate = html('iss_input_hourlyrate').value;
		iss_new_travel = html('iss_input_travel').checked ? 1 : 0;
		html('iss_input_hoursworked').value = '0';
	}
	iss_check_notes = html('iss_check_notes').checked ? 1 : 0;
	sh_update_status();
	html('iss_input_quote_price').value = '';
	html('iss_input_do_price').value = '';
	ajax("status");
}

var iss_note_id = 0;
var iss_new_note_text = '';
function iss_update_note(note_id) {
	iss_note_id = note_id;
	html('iss_note_edit_'+note_id).style.display = 'none';
	html('iss_note_display_'+note_id).innerHTML = '<i>Updating...</i>';
	html('iss_note_display_'+note_id).style.display = '';
	html('iss_note_button_'+note_id).innerHTML = 'Edit';
	iss_new_note_text = html('iss_note_box_'+note_id).value;
	ajax('edit_note');
}

function iss_own_device() {
	if (!confirm("Are you sure you want to add this device to inventory?")) return;
	ajax("own_device");
}

function iss_flip_charger() {
	html('iss_display_charger').innerHTML = '...';
	ajax("charger");
}

var issAjax;
if (window.XMLHttpRequest) {
	// code for IE7+, Firefox, Chrome, Opera, Safari
	issAjax=new XMLHttpRequest();
} else {
	// code for IE6, IE5
	issAjax=new ActiveXObject("Microsoft.XMLHTTP");
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

function issAjaxHandler() {
	if (issAjax.readyState == 4 && issAjax.status == 200) {
		var data = eval("("+issAjax.responseText+")");
		if (!data || !data.action) return;
		//alert(dump(data));
		for (var i = 0; i < data.action.length; i++) {
			switch (data.action[i]) {
				case "own_device":
					html('iss_device_inv').innerHTML = '<div style="float:left;"><b>Device Is In Inventory</b></div><div style="float:left;margin-left:5px;margin-top:6px;"><a href="?module=inventory&do=view&id='+data.id+'" class="blue ilink" style="text-decoration:none;"><font style="font-size:8pt;">View</font></a></div>';
					break;
				case "edit_note":
					html('iss_note_display_'+data.id).innerHTML = data.text;
					break;
				case "calendar":
					var st = data.start_time.split(':');
					var et = data.end_time.split(':');
					html('iss_calendar_display').innerHTML = '<b>'+data.date+'</b> from <b>'+st[0]+':'+st[1]+'</b> to <b>'+et[0]+':'+et[1]+'</b>';
					break;
				case "error":
					alert(data.error);
					break;
				case "steps":
					loop1:
					for (var j = 0; j < data.steps.length; j++) {
						loop2:
						for (var k = 0; k < steps.length; k++) {
							if (steps[k].id == data.steps[j].id) {
								steps[k].complete = data.steps[j].complete;
								continue loop1;
							}
						}
					}
					var complete = 0;
					var total = 0;
					for (var j = 0; j < steps.length; j++) {
						total++;
						if (steps[j].complete) {
							html('iss_step_'+steps[j].id).src = 'images/check-small.png';
							complete++;
						} else {
							html('iss_step_'+steps[j].id).src = 'images/x-small.png';
						}
					}
					var perc = Math.round((complete / total) * 100);
					html('iss_steps_summary').innerHTML = '<b>'+complete+'/'+total+'</b> Steps Complete (<b>'+perc+'%</b>)';
					break;
				case "note":
					note_count++;
					html('iss_notes').innerHTML = '<hr>\nAdded by <b>'+data.note.user+'</b> on <b>'+data.note.date+'</b> at <b>'+data.note.time+'</b><br>\n'+data.note.text + html('iss_notes').innerHTML;
					html('iss_display_notes').innerHTML = '<b>'+note_count+'</b> Notes';
					break;
				case "part_search":
					html('iss_part_results').style.display = '';
					html('iss_part_results').innerHTML = data.results;
					break;
				case "part":
					iss_add_part_display(data.part);
					html('iss_parts_summary').innerHTML = '<b>'+data.part_count+'</b> Parts Attached';
					html('iss_parts_total').innerHTML = " | "+data.part_total;
					break;
				case "part_totals":
					if (parseInt(data.part_count,10) == 0) {
						html('iss_parts_summary').innerHTML = '<i>No Parts Attached</i>';
						html('iss_parts_total').innerHTML = '';
						html('iss_parts').innerHTML = '';
					} else {
						html('iss_parts_summary').innerHTML = '<b>'+data.part_count+'</b> Parts Attached';
						html('iss_parts_total').innerHTML = ' | '+data.part_total;
					}
					break;
				case "services":
					while (html('iss_table_services').rows.length > 1) html('iss_table_services').deleteRow(0);
					for (var j = data.services.length - 1; j >= 0; j--) {
						var row = html('iss_table_services').insertRow(0);
						var cell = row.insertCell(0);
						cell.innerHTML = data.services[j].name;
						cell = row.insertCell(1);
						cell.innerHTML = data.services[j].cost;
						cell.align = 'center';
					}
					if (data.services.length == 0) {
						var row = html('iss_table_services').insertRow(0);
						var cell = row.insertCell(0);
						cell.colSpan = '2';
						cell.align = 'center';
						cell.innerHTML = '<i>None</i>';
					}
					steps = [];
					html('iss_steps').innerHTML = '';
					var content = '';
					var last_service = 0;
					var counter = 1;
					for (var j = 0; j < data.steps.length; j++) {
						steps.push({for_service:data.steps[j].for_service,id:data.steps[j].id,step:data.steps[j].step,complete:data.steps[j].complete});
						if (data.steps[j].for_service != last_service) {
							content += '<b>'+svc_names[data.steps[j].for_service]+'</b><br>\n';
							counter = 1;
							last_service = data.steps[j].for_service;
						}
						var button = "<a onClick=\"iss_step("+data.steps[j].id+");\" class=\"ilink\">";
						if (data.steps[j].complete) {
							button += "<img id=\"iss_step_"+data.steps[j].id+"\" src=\"images/check-small.png\" border=\"0\"></a>";
						} else {
							button += "<img id=\"iss_step_"+data.steps[j].id+"\" src=\"images/x-small.png\" border=\"0\"></a>";
						}
						content += button +' '+counter+'. '+data.steps[j].step+'<br>\n';
						counter++;
					}
					html('iss_steps').innerHTML = content;

					var complete = 0;
					var total = 0;
					for (var j = 0; j < steps.length; j++) {
						total++;
						if (steps[j].complete) complete++;
					}

					if (total == 0) {
						html('iss_steps_summary').innerHTML = '<i>Nothing On Checklist</i>';
					} else {
						var perc = Math.round((complete / total) * 100);
						html('iss_steps_summary').innerHTML = '<b>'+complete+'/'+total+'</b> Steps Complete (<b>'+perc+'%</b>)';
					}

					html('iss_services_total').innerHTML = data.service_total;
					break;
				case "status":
					if (st_colors[data.status]) html('iss_display_status_bar').style.background = st_colors[data.status];
					if (data.status != issue_status || (data.wstatus && data.wstatus != issue_wstatus)) {
						if (data.wstatus) {
							data.status_text = 'Warranty: '+ data.wstatus_text;
							issue_wstatus = data.wstatus;
						}
						html('iss_display_status').innerHTML = data.status_text +' (0 hours ago)';
						issue_status = data.status;
					}
					if (data.quote_price) {
						html('iss_display_quote_price').innerHTML = '$'+data.quote_price;
						html('iss_line_quote_price').style.display = '';
					}
					if (data.do_price) {
						issue_do_price = data.do_price;
						html('iss_display_do_price').innerHTML = '$'+data.do_price;
						html('iss_line_do_price').style.display = '';
					}
					if (data.location) {
						html('iss_device_location').innerHTML = data.location;
					}
					if (data.diagnosis) {
						html('iss_display_diagnosis1').style.display = '';
						html('iss_display_diagnosis2').style.display = '';
						html('iss_display_diagnosis3').style.display = '';
						html('iss_display_diagnosis2').innerHTML = data.diagnosis;
						//html('iss_edit_diagnosis').style.display = 'none';
					}
					if (data.final_summary) {
						html('iss_display_finalsummary1').style.display = '';
						html('iss_display_finalsummary2').style.display = '';
						html('iss_display_finalsummary3').style.display = '';
						html('iss_display_finalsummary2').innerHTML = data.final_summary;
						//html('iss_edit_finalsummary').style.display = 'none';
					}
					if (data.labor) {
						var table = html('iss_table_laborlog');
						var row = table.insertRow(1);
						row.align = 'center';
						var c = row.insertCell(0);
						c.innerHTML = data.labor.ts;
						c = row.insertCell(1);
						c.innerHTML = data.labor.hours;
						c = row.insertCell(2);
						c.innerHTML = '$'+ data.labor.rate;
						c = row.insertCell(3);
						c.innerHTML = data.labor.travel;
						c = row.insertCell(4);
						c.innerHTML = '$' + data.labor.charge;
						c = row.insertCell(5);
						c.innerHTML = data.labor.tech;
						iss_labor_total += parseFloat(data.labor.charge);
						html('iss_total_labor_display').innerHTML = '<b>$'+iss_labor_total+'</b>';
					}
					if (data.check_notes == '1') {
						html('iss_check_notes').checked = true;
						html('iss_check_notes_bar').style.display = '';
					} else {
						html('iss_check_notes').checked = false;
						html('iss_check_notes_bar').style.display = 'none';
					}
					//TODO: Add new issue change
					break;
				case "do_price":
					issue_do_price = data.dp;
					html('iss_display_do_price').innerHTML = '$'+data.dp;
					html('iss_line_do_price').style.display = '';
					html('iss_add_do_price').innerHTML = '<a onClick="iss_add_do_price();" class="green ilink">Edit Do It Price</a>';
					break;
				case "quote_price":
					issue_quote_price = data.qp;
					html('iss_display_quote_price').innerHTML = '$'+data.qp;
					html('iss_line_quote_price').style.display = '';
					html('iss_edit_quote_price').innerHTML = '<a onClick="iss_edit_quote_price();" class="green ilink">Edit Quote Price</a>';
					break;
				case "soptions":
					html('iss_edit_new_status').options.length = 0;
					for (var x in data.soptions) {
						html('iss_edit_new_status').options[html('iss_edit_new_status').options.length] = new Option(data.soptions[x],x);
					}
					ChangeSelectByValue('iss_edit_new_status',''+issue_status,false);
					break;
				case "charger":
					if (data.charger) {
						html('iss_display_charger').innerHTML = 'Yes';
					} else {
						html('iss_display_charger').innerHTML = 'No';
					}
					break;
			}
		}
	}
}

function iss_list_items(id) {
	iss_part_list_id = id;
	ajax("list_items");
}

issAjax.onreadystatechange = issAjaxHandler;

var iss_part_list_id = 0;
var pstid = 0;
var searchParams = '';
function ajax(action) {
	issAjax.abort();
	issAjax.onreadystatechange = issAjaxHandler;
	issAjax.open("POST","iss/ajax.php",true);
	issAjax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	switch (action) {
		case "own_device":
			var cols = ['action','id'];
			var vals = ['own_device',issue_id];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "edit_note":
			var cols = ['action','id','text'];
			var vals = ['edit_note',iss_note_id,iss_new_note_text];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "calendar":
			html('iss_calendar_display').innerHTML = "<i>Updating...</i>";
			var cdate = html('iss_calendar_edit_date').value;
			var cst_h = html('iss_calendar_edit_st_hour').options[html('iss_calendar_edit_st_hour').selectedIndex].value;
			var cst_m = html('iss_calendar_edit_st_min').options[html('iss_calendar_edit_st_min').selectedIndex].value;
			var cet_h = html('iss_calendar_edit_et_hour').options[html('iss_calendar_edit_et_hour').selectedIndex].value;
			var cet_m = html('iss_calendar_edit_et_min').options[html('iss_calendar_edit_et_min').selectedIndex].value;
			var cat = html('iss_calendar_assigned_to').options[html('iss_calendar_assigned_to').selectedIndex].value;
			var cols = ['action','id','start','start_time','end_time','assigned_to'];
			var vals = ['calendar',calendar_evt_id,cdate,cst_h+':'+cst_m+':00',cet_h+':'+cet_m+':00',cat];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "step":
			html('iss_steps_summary').innerHTML = '<i>Updating...</i>';
			var cols = ['action','id','step'];
			var vals = ['step',issue_id,iss_step_clicked];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "charger":
			var cols = ['action','id'];
			var vals = ['charger',issue_id];
			var params = buildParams(cols,vals);
			issAjax.send(params);
		case "do_price":
			var cols = ['action','id','dp'];
			var vals = ['do_price',issue_id,issue_do_price];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "quote_price":
			var cols = ['action','id','qp'];
			var vals = ['quote_price',issue_id,issue_quote_price];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "note":
			var cols = ['action','id','text'];
			var vals = ['note',issue_id,html('iss_edit_note').value];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			html('iss_new_note').style.display = 'none';
			html('iss_edit_note').value = '';
			break;
		case "part_search_all":
			html('iss_part_results').innerHTML = 'Listing all in this category...';
			html('iss_part_results').style.display = '';
			if (pstid != 0) clearTimeout(pstid);
			var cols = ['action','category','str'];
			var vals = ['part_search',html('iss_part_category').options[html('iss_part_category').selectedIndex].value,'%'];
			searchParams = buildParams(cols,vals);
			ajax("ps");
			break;
		case "part_search":
			if (html('iss_part_search_input').value.length < 3) {
				html('iss_part_results').style.display = 'none';
				return;
			}
			html('iss_part_results').innerHTML = 'Searching for \''+html('iss_part_search_input').value+'\'...';
			html('iss_part_results').style.display = '';
			if (pstid != 0) clearTimeout(pstid);
			var cols = ['action','category','str'];
			var vals = ['part_search',html('iss_part_category').options[html('iss_part_category').selectedIndex].value,html('iss_part_search_input').value];
			searchParams = buildParams(cols,vals);
			pstid = setTimeout(function(){ajax("ps");},500); // Prevent ajax calls while still typing
			break;
		case "list_items":
			html('iss_part_results').innerHTML = 'Getting Item List...';
			var cols = ['action','id'];
			var vals = ['part_item_list',iss_part_list_id];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "ps":
			issAjax.send(searchParams);
			pstid = 0;
			searchParams = '';
			break;
		case "part":
			var cols = ['action','id','inv_id','inv_item_id','qty','add'];
			var vals = ['part',issue_id,iss_add_part_id,iss_add_part_item,iss_add_part_qty,iss_add_part_add];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "type":
			var cols = ['action','id','type'];
			var vals = ['type',issue_id,iss_new_type];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "remove_part":
			var cols = ['action','id','part_id'];
			var vals = ['remove_part',issue_id,iss_part_to_remove];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "services":
			var cols = ['action','id','services'];
			var vals = ['services',issue_id,services.join(',')];
			var params = buildParams(cols,vals);
			issAjax.send(params);
			break;
		case "status":
			var cols = ['action','id','status','assigned_to','location','quote_price','do_price','diagnosis','finalsummary','hours','rate','travel','check_notes'];
			var vals = ['status',issue_id,iss_new_status,iss_new_assigned_to,iss_new_location,iss_new_quote_price,iss_new_do_price,iss_new_diagnosis,iss_new_finalsummary,iss_new_hoursworked,iss_new_hourlyrate,iss_new_travel,iss_check_notes];
			if (iss_new_wstatus != 0) {
				cols.push('wstatus');
				vals.push(iss_new_wstatus);
			}
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

var st_colors = [];
<?php
foreach ($ST_COLORS as $id => $color) {
	echo "st_colors[$id] = '$color';\n";
}

?>

function sh_options() {
	if (html('iss_options').style.display == 'none') {
		html('iss_options').style.display = '';
	} else {
		html('iss_options').style.display = 'none';
	}
}
function iss_invoice(warranty) {
	if (issue_do_price == 0) {
		var ok = confirm('Issue is currently $0. Was this your intention?');
	} else {
		var ok = confirm('Do It price OK? : $'+issue_do_price+'\n\nIf not, click Cancel and change it before you create an invoice.\n\nParts are either extra or included on an individual basis.\n\nServices and Labor are guides and do not factor into the final price.');
	}

	if (!ok) return;
	if (warranty) {
		window.location = '?module=invoice&do=create_from_issue&id='+issue_id;
	} else {
		if (issue_invoice_id == 0) {
			window.location = '?module=invoice&do=create_from_issue&id='+issue_id;
		} else {
			window.location = '?module=invoice&do=view&id=' + issue_invoice_id;
		}
	}
}
function iss_add_to_cart() {
	window.location = '?module=iss&do=add_to_cart&id='+issue_id;
}
function iss_issue_receipt() {
	window.open('?module=iss&do=receipt&id='+issue_id);
}
function iss_barcode() {
	window.open('?module=iss&do=barcode&id='+issue_id);
}
function iss_mark_resolved() {
	window.location = '?module=iss&do=resolve&id='+issue_id;
}
function iss_cust_ack() {
	window.location = '?module=iss&do=cust_ack&id='+issue_id;
}
function iss_transfer() {
	alert('Function not yet implemented.');
	return;
	window.location = '?module=iss&do=transfer&id='+issue_id;
}
</script>
<div style="position:absolute;top:5px;right:5px;"><a onClick="sh_options();" class="green ilink">Options</a></div>
<div id="iss_options" align="right" style="display:none;position:absolute;top:25px;right:5px;background-color:#CCCCCC;padding:5px;border-radius:5px;">
<a onClick="iss_invoice(false);" class="blue ilink"><?php echo (intval($ISSUE["invoices__id"]) == 0 ? "Create":"View"); ?> Invoice</a><br>
<?php if ($ISSUE["varref_status"] == 1) { ?><a onClick="iss_invoice(true);" class="blue ilink">Warranty Invoice</a><br><?php } ?>
<a onClick="iss_add_to_cart();" class="blue ilink">Add to Cart</a><br>
<a onClick="iss_issue_receipt();" class="blue ilink">Issue Receipt</a><br>
<a onClick="iss_barcode();" class="blue ilink">Barcode</a><br>
<a onClick="iss_mark_resolved();" class="blue ilink">Mark <?php echo ($ISSUE["is_resolved"] ? "Unresolved":"Resolved"); ?></a><br>
<a onClick="iss_cust_ack();" class="blue ilink">Acknowledgement Page</a><br>
<a onClick="iss_transfer();" class="blue ilink">Transfer Issue</a><br>
<a onClick="iss_change_type();" class="green ilink">Change Issue Type</a>
</div>
<div align="center" style="font-family:Tahoma;font-size:20px;"><?php echo $ISSUE_TYPE[$ISSUE["varref_issue_type"]]; ?> Issue <?php echo $ISSUE["id"]; ?></div>
<div class="clearL"><br></div>
<?php if (isset($RESPONSE)) { ?>
<div align="center" style="font-family:Tahoma;font-size:18px;"><?php echo $RESPONSE; ?></div><br>
<?php } ?>

<?php if (isset($CALENDAR)) { ?>
<div style="float:left;font-family:Tahoma;margin-top:5px;width:100px;"><b>Scheduled:</b></div>
<div style="float:left;border:1px solid #000;border-radius:5px;font-family:Tahoma;margin-left:10px;padding:5px;" align="left"><?php

$cal_dt_p = explode("-",$CALENDAR["start"]);
$cal_dti = mktime(12,0,0,$cal_dt_p[1],$cal_dt_p[2],$cal_dt_p[0]);
$cal_day = date("l j M Y",$cal_dti);

echo "<span id=\"iss_calendar_display\"><b>". $cal_day ."</b> from <b>". $CALENDAR["start_time"][0].":".$CALENDAR["start_time"][1] ."</b> to <b>";
echo $CALENDAR["end_time"][0].":".$CALENDAR["end_time"][1] ."</b></span>";
echo "	<div id=\"iss_calendar_edit\" style=\"display:none;\">\n";

echo "		<div style=\"float:left;width:130px;\"><b>Change Date:</b></div>";
echo "<div style=\"float:left;\"><input type=\"edit\" id=\"iss_calendar_edit_date\" size=\"10\" value=\"{$CALENDAR["start"]}\"></div><div class=\"clearL\"></div>\n";
echo "		<div style=\"float:left;width:130px;\"><b>Start Time:</b></div>";
echo "<div style=\"float:left;\"><select id=\"iss_calendar_edit_st_hour\">";
for ($x = 7; $x <= 23; $x++) {
	if ($x < 10) $hr = "0".$x;
	else $hr = $x;
	echo "<option value=\"$hr\"".($hr == $CALENDAR["start_time"][0] ? " SELECTED":"").">$hr</option>";
}
echo "</select>:<select id=\"iss_calendar_edit_st_min\">";
for ($x = 0; $x < 60; $x += 15) {
	if ($x < 15) $min = "0".$x;
	else $min = $x;
	echo "<option value=\"$min\"".($min == $CALENDAR["start_time"][1] ? " SELECTED":"").">$min</option>";
}
echo "</select></div><div class=\"clearL\"></div>\n";
echo "		<div style=\"float:left;width:130px;\"><b>End Time:</b></div>";
echo "<div style=\"float:left;\"><select id=\"iss_calendar_edit_et_hour\">";
for ($x = 7; $x <= 23; $x++) {
	if ($x < 10) $hr = "0".$x;
	else $hr = $x;
	echo "<option value=\"$hr\"".($hr == $CALENDAR["end_time"][0] ? " SELECTED":"").">$hr</option>";
}
echo "</select>:<select id=\"iss_calendar_edit_et_min\">";
for ($x = 0; $x < 60; $x += 15) {
	if ($x < 15) $min = "0".$x;
	else $min = $x;
	echo "<option value=\"$min\"".($min == $CALENDAR["end_time"][1] ? " SELECTED":"").">$min</option>";
}
echo "</select></div><div class=\"clearL\"></div>\n";
echo "		<div style=\"float:left;width:130px;\"><b>Assigned To:</b></div>";
echo "<div style=\"float:left;\"><select id=\"iss_calendar_assigned_to\">";
foreach ($USERS as $id => $_user) {
	if ($_user["is_disabled"]) continue;
	echo "<option value=\"$id\"".($id == $CALENDAR["users__id__target"] ? " SELECTED":"").">{$_user["firstname"]} {$_user["lastname"]}</option>\n";
}
echo "</select></div><div class=\"clearL\"></div>\n";
echo "		<div align=\"center\"><a class=\"green ilink\" onClick=\"ajax('calendar');\">Update Calendar</a></div>\n";
echo "	</div>\n";
echo "</div>\n";
echo "<div style=\"float:left;margin-top:9px;margin-left:5px;\"><a onClick=\"sh('iss_calendar_edit');\" id=\"iss_calendar_edit_link\" class=\"green ilink\" style=\"font-size:8pt;\">More</a></div>\n";

?>
<script src="js/calendar.js" type="text/javascript"></script>
<script type="text/javascript">
calendar.set("iss_calendar_edit_date");
</script>
<div class="clearL"><br></div>
<?php } ?>

<div style="float:left;font-family:Tahoma;margin-top:5px;width:100px;"><b>Customer:</b></div>
<div style="float:left;border:1px solid #000;border-radius:5px;font-family:Tahoma;margin-left:10px;padding:5px;" align="left"><?php

if (isset($CUSTOMER)) {
	echo "	<a href=\"?module=cust&do=view&id=".$CUSTOMER["id"]."\" style=\"text-decoration:none;\" class=\"blue ilink\">";
	echo "	". $CUSTOMER["firstname"]." ".$CUSTOMER["lastname"] ."</a>";
	if ($CUSTOMER["phone_home"] && $CUSTOMER["phone_home"] != "") echo "	 | <b>Home Phone:</b> ".display_phone($CUSTOMER["phone_home"]);
	if ($CUSTOMER["phone_cell"] && $CUSTOMER["phone_cell"] != "") echo "	 | <b>Cell Phone:</b> ".display_phone($CUSTOMER["phone_cell"]);
	echo "	<div id=\"iss_customer_info\" style=\"display:none;\">\n";
	if ($CUSTOMER["company"] && $CUSTOMER["company"] != "") echo "		<div style=\"float:left;width:100px;\"><b>Company:</b></div><div style=\"float:left;margin-left:5px;\">".$CUSTOMER["company"]."</div><div class=\"clearL\"></div>\n";
	if ($CUSTOMER["email"] && $CUSTOMER["email"] != "") echo "		<div style=\"float:left;width:100px;\"><b>Email:</b></div><div style=\"float:left;margin-left:5px;\">".$CUSTOMER["email"]."</div><div class=\"clearL\"></div>\n";
	if ($CUSTOMER["address"] && $CUSTOMER["address"] != "") echo "		<div style=\"float:left;width:100px;\"><b>Address:</b></div><div style=\"float:left;margin-left:5px;\">{$CUSTOMER["address"]}".($CUSTOMER["apt"] ? " #".$CUSTOMER["apt"] : "")."<br>{$CUSTOMER["city"]}, {$CUSTOMER["state"]} {$CUSTOMER["postcode"]}</div><div class=\"clearL\"></div>\n";
	echo "		<div style=\"float:left;width:100px;\"><b>Sex:</b></div><div style=\"float:left;margin-left:5px;\">".($CUSTOMER["is_male"] ? "Male":"Female")."</div><div class=\"clearL\"></div>\n";
	if ($CUSTOMER["dob"] != "0000-00-00") echo "		<div style=\"float:left;width:100px;\"><b>DOB (age):</b></div><div style=\"float:left;margin-left:5px;\">{$CUSTOMER["dob"]} (".age($CUSTOMER["dob"]).")</div><div class=\"clearL\"></div>\n";
	echo "	</div>\n";
	echo "</div>\n";
	echo "<div style=\"float:left;margin-top:9px;margin-left:5px;\"><a onClick=\"sh('iss_customer_info');\" id=\"iss_customer_info_link\" class=\"green ilink\" style=\"font-size:8pt;\">More</a></div>\n";
} else {
	echo "<i>None</i>";
	echo "</div>";
}


?>
<div class="clearL"><br></div>

<div style="float:left;font-family:Tahoma;margin-top:5px;width:100px;"><b>Device:</b></div><div style="float:left;border:1px solid #000;border-radius:5px;font-family:Tahoma;margin-left:10px;padding:5px;" align="left"><?php

if (isset($DEVICE)) {
	echo "<!-- \n";
	print_r($DEVICE);
	echo "\n-->\n";
	echo "	<a href=\"?module=cust&do=edit_dev&id=".$DEVICE["id"]."\" style=\"text-decoration:none;\" class=\"blue ilink\">";
	if ($DEVICE["manufacturer"] && $DEVICE["manufacturer"] != "") echo $DEVICE["manufacturer"]." ";
	else echo "(Unknown) ";
	if ($DEVICE["model"] && $DEVICE["model"] != "") echo $DEVICE["model"];
	else echo "(Unknown)";
	echo "</a> | <b>Location:</b> <span id=\"iss_device_location\">".(isset($LOCATIONS[$DEVICE["in_store_location"]]) ? $LOCATIONS[$DEVICE["in_store_location"]] : "<i>Nowhere</i>")."</span>";
	echo "	<div id=\"iss_device_info\" style=\"display:none;\">\n";
	echo "		<div style=\"float:left;width:150px;\"><b>Device Type:</b></div><div style=\"float:left;margin-left:5px;\">".$DEVICE_TYPES[$DEVICE["categories__id"]]."</div><div class=\"clearL\"></div>\n";
	if ($DEVICE["operating_system"] && $DEVICE["operating_system"] != "") echo "		<div style=\"float:left;width:150px;\"><b>OS:</b></div><div style=\"float:left;margin-left:5px;\">{$DEVICE["operating_system"]}</div><div class=\"clearL\"></div>\n";
	echo "		<div style=\"float:left;width:150px;\"><b>With Charger:</b></div><div id=\"iss_display_charger\" style=\"float:left;margin-left:5px;\">".($ISSUE["has_charger"] ? "Yes":"No")."</div><div style=\"float:left;margin-left:5px;margin-top:6px;\"><a onClick=\"iss_flip_charger();\" class=\"green ilink\"><font style=\"font-size:8pt;\">Change</font></a></div><div class=\"clearL\"></div>\n";
	if ($DEVICE["serial_number"] && $DEVICE["serial_number"] != "") echo "		<div style=\"float:left;width:150px;\"><b>Serial Number:</b></div><div style=\"float:left;margin-left:5px;\">{$DEVICE["serial_number"]}</div><div class=\"clearL\"></div>\n";
	if ($DEVICE["username"] && $DEVICE["username"] != "") echo "		<div style=\"float:left;width:150px;\"><b>Username:</b></div><div style=\"float:left;margin-left:5px;\">{$DEVICE["username"]}</div><div class=\"clearL\"></div>\n";
	if ($DEVICE["password"] && $DEVICE["password"] != "") echo "		<div style=\"float:left;width:150px;\"><b>Password:</b></div><div style=\"float:left;margin-left:5px;\">{$DEVICE["password"]}</div><div class=\"clearL\"></div>\n";
	if ($DEVICE["inventory__id"]) echo "		<div id=\"iss_device_inv\" align=\"center\"><div style=\"float:left;\"><b>Device Is In Inventory</b></div><div style=\"float:left;margin-left:5px;margin-top:6px;\"><a href=\"?module=inventory&do=view&id={$DEVICE["inventory__id"]}\" class=\"blue ilink\" style=\"text-decoration:none;\"><font style=\"font-size:8pt;\">View</font></a></div></div><div class=\"clearL\"></div>\n";
	else echo "		<div id=\"iss_device_inv\" align=\"center\"><a class=\"green ilink\" onClick=\"iss_own_device();\">Take Ownership</a></div><div class=\"clearL\"></div>\n";
	echo "	</div>\n";
	echo "</div>\n";
	echo "<div style=\"float:left;margin-top:9px;margin-left:5px;\"><a onClick=\"sh('iss_device_info');\" id=\"iss_device_info_link\" class=\"green ilink\" style=\"font-size:8pt;\">More</a></div>\n";
} else {
	echo "<i>On-Site Device</i>";
	echo "</div>\n";
}

?>
<div class="clearL"><br></div>

<div id="iss_display_status_bar" width="100%" style="font-family:Tahoma;background-color:<?php echo $ST_COLORS[$ISSUE["varref_status"]]; ?>;"><b>Status:</b> <span id="iss_display_status"><?php
if ($ISSUE["varref_status"] == 1 && $ISSUE["warranty_status"]) {
	echo "Warranty: ".$STATUS[$ISSUE["warranty_status"]];
} else {
	echo $STATUS[$ISSUE["varref_status"]];
}
echo " (";
$data = mysql_fetch_assoc(mysql_query("SELECT TIMESTAMPDIFF(MINUTE,last_status_chg,NOW()) AS tlsc FROM issues WHERE id = ".$ISSUE["id"]));
$minutes = $data["tlsc"];
$days = floor($minutes / 60 / 24);
$pd = ($days != 1 ? "s":"");
if ($days >= 1) echo $days." day$pd ";
$hours = floor(($minutes - ($days * 60 * 24)) / 60);
$ph = ($hours != 1 ? "s":"");
echo $hours." hour$ph ago)</span>";
?></div>

<div id="iss_check_notes_bar" width="100%" style="font-family:Tahoma;background-color:#FFFF00;display:<?php echo ($ISSUE["check_notes"] ? "''":"none"); ?>;" align="center"><b>Important Notes Below</b></div>

<div class="clearL"><br></div>

<div style="float:left;width:400px;margin-left:160px;border:1px solid #000;border-radius:5px;padding:5px;font-family:Tahoma;">
	<a id="iss_update_status_link" onClick="sh_update_status();" class="green ilink">Update Status</a>
	<div id="iss_update_status" style="display:none;">
		<table width="100%">
			<tr>
				<td align="right"><b>New Status:</b></td>
				<td>
					<select id="iss_edit_new_status" onChange="iss_status_option_change();">
<?php
$onsite = false;
if ($ISSUE["type"] == 2 || $ISSUE["type"] == 3) {
	$onsite = true;
}
foreach ($STATUS as $id => $status) {
	if ($id == "0") continue;
	if ($onsite && !in_array($id,$ONSITE_STATUS_OPTIONS)) continue;
	if ($onsite && !in_array($id,$ONSITE_STATUS_CHG[$ISSUE["varref_status"]])) continue;
	if (!$onsite && !in_array($id,$STATUS_CHG[$ISSUE["varref_status"]])) continue;
	echo "					<option value=\"$id\"".($id == $ISSUE["varref_status"] ? " SELECTED":"").">$status</option>\n";
}
?>
					</select></td>
			</tr>
			<tr id="iss_edit_wstatus_row"<?php if ($ISSUE["varref_status"] != 1) { echo " style=\"display:none;\""; } ?>>
				<td align="right"><b>Warranty Status:</b></td>
				<td>
					<select id="iss_edit_warranty_status">
<?php

foreach ($STATUS as $id => $status) {
	if ($id == "1") continue;
	echo "					<option value=\"$id\"".($id == $ISSUE["warranty_status"] ? " SELECTED":"").">$status</option>\n";
}

?>
					</select>
				</td>
			</tr>
			<tr>
				<td align="right"><b>Assign To:</b></td>
				<td><select id="iss_edit_assign_to"><option value="0">Nobody</option>
<?php

foreach ($USERS as $id => $_user) {
	if ($_user["is_disabled"]) continue;
	echo "				<option value=\"$id\"".($ISSUE["users__id__assigned"] == $id ? " SELECTED":"").">{$_user["firstname"]} {$_user["lastname"]}</option>\n";
}

?>
				</select></td>
			</tr><?php if (isset($DEVICE)) { ?>
			<tr>
				<td align="right"><b>New Location:</b></td>
				<td><select id="iss_edit_location">
<?php

	foreach ($LOCATIONS as $id => $loc) {
		echo "				<option value=\"$id\"".($DEVICE["in_store_location"] == $id ? " SELECTED":"").">$loc</option>\n";
	}

?>
				</select></td>
			</tr><?php } ?>
			<tr id="iss_edit_quote_price"<?php if (floatval($ISSUE["quote_price"]) != 0) { ?> style="display:none;"<?php } ?>>
				<td align="right"><b>Quote Price:</b></td>
				<td><input type="edit" id="iss_input_quote_price" size="6"></td>
			</tr>
			<tr id="iss_edit_do_price"<?php if (floatval($ISSUE["do_price"]) != 0) { ?> style="display:none;"<?php } ?>>
				<td align="right"><b>Do It Price:</b></td>
				<td><input type="edit" id="iss_input_do_price" size="6"></td>
			</tr>
			<tr id="iss_edit_diagnosis">
				<td align="right"><b>Diagnosis:</b></td>
				<td><textarea rows="2" cols="30" id="iss_input_diagnosis"><?php echo $ISSUE["diagnosis"]; ?></textarea></td>
			</tr>
			<tr id="iss_edit_finalsummary">
				<td align="right"><b>Final Summary:</b></td>
				<td><textarea rows="2" cols="30" id="iss_input_finalsummary"><?php echo $ISSUE["final_summary"]; ?></textarea></td>
			</tr>
			<?php if ($ISSUE["varref_issue_type"] == 2 || $ISSUE["varref_issue_type"] == 3) { ?>
			<tr id="iss_edit_hoursworked">
				<td align="right"><b>Hours Worked:</b></td>
				<td><input type="edit" id="iss_input_hoursworked" size="2" value="0">
				@
				$<input type="edit" id="iss_input_hourlyrate" size="2" value="95.00">/hour
				<input type="checkbox" id="iss_input_travel" value="1"> Travel
				</td>
			</tr>
			<?php } ?>
			<tr id="iss_edit_checknotes">
				<td align="right"><b>Check Notes:</b></td>
				<td><input type="checkbox" id="iss_check_notes"<?php if ($ISSUE["check_notes"]) { echo " CHECKED"; } ?>>
				<font size="-1">Will add a yellow "Important Notes" banner to<br>this page and a red star on the index page.</font></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
				<input type="button" value="Update Status" style="width:200px;" onClick="iss_update_status();">
				</td>
			</tr>
		</table>
	</div>
</div>

<div class="clearL"><br></div>

<?php if ($ISSUE["varref_issue_type"] == 2 || $ISSUE["varref_issue_type"] == 3) { ?>

<div style="border:1px solid #000;border-radius:5px;padding:5px;margin-left:10px;margin-right:15px;font-family:Tahoma;" align="center">
<b>Labor Logged</b>
 <table border="0" width="100%" cellspacing="0" id="iss_table_laborlog">
  <tr style="font-weight:bold;font-size:11px;" align="center">
   <td style="border:1px solid #000;">Time/Date</td>
   <td style="border:1px solid #000;">Hours Worked</td>/
   <td style="border:1px solid #000;">Total Charge</td>
   <td style="border:1px solid #000;">Technician</td>
  </tr>
<?php

$result = mysql_query("SELECT * FROM issue_labor l LEFT JOIN users u ON l.users__id = u.id WHERE l.issues__id = {$ISSUE["id"]} ORDER BY l.ts DESC");
$total_charge = 0;
while ($row = mysql_fetch_assoc($result)) {
	echo "  <tr align=\"center\">\n";
	echo "   <td>". $row["ts"] ."</td>\n";
	echo "   <td>". round($row["amount"],2) ."</td>\n";
//	echo "   <td>$". round($row["rate"],2) ."</td>\n";
//	echo "   <td>". ($row["travel"] ? "Yes":"No") ."</td>\n";
	echo "   <td>$". round($row["amount"],2) ."</td>\n"; // * round($row["rate"],2) + ($row["travel"] ? 50:0),2)
	echo "   <td>". $row["username"] ."</td>\n";
	echo "  </tr>\n";
	$total_charge += round($row["amount"],2); // * round($row["rate"],2) + ($row["travel"] ? 50:0),2);
}

$total_charge = round($total_charge,2);
echo "  <tr align=\"center\"><td colspan=\"4\"></td><td style=\"border:1px solid #000;\" id=\"iss_total_labor_display\"><b>$$total_charge</b></td></tr>\n";
echo <<<EOD
<script type="text/javascript">
var iss_labor_total = parseFloat('$total_charge');
</script>
EOD

?>
 </table>
</div>

<div class="clearL"><br></div>
<?php } ?>

<div style="border:1px solid #000;border-radius:5px;padding:5px;margin-left:10px;margin-right:15px;font-family:Tahoma;" align="left">
	<div style="float:left;width:150px;"><b>Description:</b></div><div style="float:left;width:550px;"><?php echo $ISSUE["troubledesc"]; ?></div><div class="clearL"></div>
	<div style="float:left;width:150px;"><b>Saved Files:</b></div><div style="float:left;width:550px;"><?php echo $ISSUE["savedfiles"]; ?></div><div class="clearL"></div>
<?php if (!$ISSUE["diagnosis"] || $ISSUE["diagnosis"] == "") { $diagnosis_style = "display:none;"; } else { $diagnosis_style = ""; } ?>
	<div id="iss_display_diagnosis1" style="float:left;width:150px;<?php echo $diagnosis_style; ?>"><b>Diagnosis:</b></div><div  id="iss_display_diagnosis2" style="float:left;width:550px;<?php echo $diagnosis_style; ?>"><?php echo $ISSUE["diagnosis"]; ?></div><div id="iss_display_diagnosis3" class="clearL" style="<?php echo $diagnosis_style; ?>"></div>
<?php if (!$ISSUE["final_summary"] || $ISSUE["final_summary"] == "") { $fs_style = "display:none;"; } else { $fs_style = ""; } ?>
	<div id="iss_display_finalsummary1" style="float:left;width:150px;<?php echo $fs_style; ?>"><b>Final Summary:</b></div><div id="iss_display_finalsummary2" style="float:left;width:550px;<?php echo $fs_style; ?>"><?php echo $ISSUE["final_summary"]; ?></div><div id="iss_display_finalsummary3" class="clearL" style="<?php echo $fs_style; ?>"></div>
</div>

<div class="clearL"><br></div>

<div class="floatL" style="border:1px solid #000;border-radius:5px;padding:5px;margin-left:10px;width:360;font-family:Tahoma;" align="left">
	<div style="position:relative;">
		<div style="position:absolute;top:0px;right:0px;font-size:8pt;"><a onClick="html('iss_edit_services').style.display='';" class="green ilink" style="font-size:8pt;">Edit</a></div>
		<div id="iss_edit_services" style="display:none;position:absolute;top:-300px;height:350px;width:400px;left:175px;background-color:#CCCCCC;border-radius:5px;" align="center">
			<font style="font-size:15pt;">Add/Remove Services</font><br><br>
			<div class="floatL" style="width:260px;" align="center"><b>Service</b></div><div class="floatL" style="width:70px;" align="center"><b>Price</b></div><div class="floatL" style="width:60px;" align="center"><b>Add</b></div><div class="clearL"></div>
			<div style="overflow-y:scroll;height:220px;">
				<script type="text/javascript">var services = [];</script>
				<table id="iss_table_edit_services" width="100%" border="0">
<?php

$services = ($ISSUE["services"] == null ? array() : explode(":",$ISSUE["services"]));
foreach ($SERVICES as $id => $service) {
	echo "					<tr>\n";
	echo "						<td width=\"270\">".$service["name"]."</td>\n";
	echo "						<td width=\"60\" align=\"right\">$".number_format($service["cost"],2)."</td>\n";
	echo "						<td width=\"40\" align=\"center\"><input type=\"checkbox\" name=\"iss_service_box\" value=\"$id\" id=\"iss_service_$id\"".(in_array($id,$services) ? " CHECKED":"")."></td>\n";
	echo "					</tr>\n";
	if (in_array($id,$services)) echo "<script type=\"text/javascript\">services.push('$id');</script>\n";
}

?>
				</table>
			</div><br>
			<input type="button" onClick="iss_edit_services();" style="width:300px;" value="Save"> &nbsp; <a onClick="html('iss_edit_services').style.display='none';iss_reset_services();" class="green ilink">Cancel</a>
		</div>
	</div>
	<div align="center"><b>Services</b></div>
	<table id="iss_table_services" border="0" width="100%">
<?php

$count = 0;
$total = 0;
foreach ($services as $svc) {
	if (strlen($svc) == 0 || !isset($SERVICES[$svc])) continue;
	echo "		<tr>\n";
	echo "			<td>".$SERVICES[$svc]["name"]."</td>\n";
	echo "			<td align=\"center\">$".number_format(floatval($SERVICES[$svc]["cost"]),2)."</td>\n";
	echo "		</tr>\n";
	$count++;
	$total += floatval($SERVICES[$svc]["cost"]);
}
if ($count == 0) echo "		<tr><td colspan=\"2\" align=\"center\"><i>None</i></td></tr>\n";

?>
		<tr>
			<td><b>TOTAL</b></td>
			<td id="iss_display_service_total" width="70" style="border:1px solid #000;" align="center"><span id="iss_services_total">$<?php echo number_format($total,2); ?></span></td>
		</tr>
	</table>
</div>

<div class="floatL" style="border:1px solid #000;border-radius:5px;padding:5px;margin-left:10px;width:360;font-family:Tahoma;" align="left">
	<div style="float:left;width:150px;"><b>Assigned To:</b></div><div id="iss_display_assigned_to" style="float:left;margin-left:5px;"><?php echo (isset($USERS[$ISSUE["users__id__assigned"]]) ? $USERS[$ISSUE["users__id__assigned"]]["firstname"]." ".$USERS[$ISSUE["users__id__assigned"]]["lastname"] : "<i>Nobody</i>"); ?></div><div class="clearL"></div>
	<div style="float:left;width:150px;"><b>Intake Tech:</b></div><div style="float:left;margin-left:5px;"><?php echo (isset($USERS[$ISSUE["users__id__intake"]]) ? $USERS[$ISSUE["users__id__intake"]]["firstname"]." ".$USERS[$ISSUE["users__id__intake"]]["lastname"] : "<i>Nobody</i>"); ?></div><div class="clearL"></div>
	<div style="float:left;width:150px;"><b>Intake Time:</b></div><div style="float:left;margin-left:5px;"><?php echo date("D, j M Y h:iA",strtotime($ISSUE["intake_ts"])); ?></div><div class="clearL"></div>
	<div id="iss_line_quote_price"<?php if (floatval($ISSUE["quote_price"]) == 0) echo " style=\"display:none;\""; ?>><div style="float:left;width:150px;"><b>Quote Price:</b></div><div id="iss_display_quote_price" style="float:left;margin-left:5px;">$<?php echo number_format($ISSUE["quote_price"],2); ?></div><div class="clearL"></div></div>
	<div id="iss_line_do_price"<?php if (floatval($ISSUE["do_price"]) == 0) echo " style=\"display:none;\""; ?>><div style="float:left;width:150px;"><b>Do It Price:</b></div><div id="iss_display_do_price" style="background-color:#CCCCFF;border:1px solid #000;padding-left:3px;padding-right:3px;float:left;margin-left:5px;">$<?php echo number_format($ISSUE["do_price"],2); ?></div><div class="clearL"></div></div>
	<div id="iss_edit_quote_price" align="center"><a onClick="iss_edit_quote_price();" class="green ilink">Edit Quote Price</a></div>
<?php if (floatval($ISSUE["do_price"]) == 0) { ?>
	<div id="iss_add_do_price" align="center"><a onClick="iss_add_do_price();" class="green ilink">Set Do It Price</a></div>
<?php } else { ?>
	<div id="iss_add_do_price" align="center"><a onClick="iss_add_do_price();" class="green ilink">Edit Do It Price</a></div>
<?php } ?>
</div>

<div class="clearL"><br></div>

<script type="text/javascript">
var steps = [];
</script>
<div style="float:left;font-family:Tahoma;margin-top:5px;width:100px;"><b>Checklist:</b></div><div style="float:left;border:1px solid #000;border-radius:5px;font-family:Tahoma;margin-left:10px;padding:5px;max-width:600px;" align="left"><?php

if (!$ISSUE["service_steps"]) $ISSUE["service_steps"] = "";
$steps = explode("|",$ISSUE["service_steps"]);
$step_ids = array();
$complete = 0;
foreach ($steps as $step) {
	if (!$step) continue;
	$kv = explode(":",$step);
	if (count($kv) < 3) continue;
	$step_ids[] = $kv[1];
	if ($kv[2] == "1") $complete++;
}
if (count($step_ids)) {
	$step_text = array();
	$result = mysql_query("SELECT id,step FROM service_steps WHERE id IN (".implode(",",$step_ids).")");
	while ($row = mysql_fetch_assoc($result)) {
			$step_text[$row["id"]] = $row["step"];
	}

	echo "<span id=\"iss_steps_summary\"><b>".$complete."/".count($step_ids)."</b> Steps Complete (<b>".round($complete/count($step_ids) * 100)."%</b>)</span>";
	echo "<div id=\"iss_steps\" style=\"\">\n";
	$last_service = 0;
	$counter = 1;
	foreach ($steps as $step) {
		if (!$step) continue;
		$kv = explode(":",$step);
		if (count($kv) < 3) continue;
		if (!isset($SERVICES[$kv[0]])) continue;
		if (!isset($step_text[$kv[1]])) continue;
		if ($kv[0] != $last_service) {
			echo "<b>".$SERVICES[$kv[0]]["name"]."</b><br>\n";
			$last_service = $kv[0];
			$counter = 1;
		}
		$button = "<a onClick=\"iss_step({$kv[1]});\" class=\"ilink\">";
		if ($kv[2] == "1") {
			$button .= "<img id=\"iss_step_{$kv[1]}\" src=\"images/check-small.png\" border=\"0\"></a>";
		} else {
			$button .= "<img id=\"iss_step_{$kv[1]}\" src=\"images/x-small.png\" border=\"0\"></a>";
		}
		echo $button." ".$counter.". ".$step_text[$kv[1]]."<br>";
		echo "<script type=\"text/javascript\">steps.push({for_service:{$kv[0]},id:{$kv[1]},step:'".str_replace("'","`",$step_text[$kv[1]])."',complete:".($kv[2] == "1" ? "true":"false")."});</script>";
		$counter++;
	}
	echo "</div></div>\n";
	echo "<div class=\"floatL\" style=\"margin-left:5px;margin-top:9px;font-size:8pt;\"><a id=\"iss_steps_link\" onClick=\"sh('iss_steps');\" class=\"green ilink\">Less</a></div>\n";
} else {
	echo "	<span id=\"iss_steps_summary\"><i>Nothing On Checklist</i></span>";
	echo "<div id=\"iss_steps\" style=\"\"></div>\n";
	echo "</div>\n";
	echo "<div class=\"floatL\" style=\"margin-left:5px;margin-top:9px;font-size:8pt;\"><a id=\"iss_steps_link\" onClick=\"sh('iss_steps');\" class=\"green ilink\">Less</a></div>\n";
}

?>

<div class="clearL"><br></div>

<div id="iss_change_type_dialog" title="Change to what?" style="display:none;"></div>

<div style="float:left;font-family:Tahoma;margin-top:5px;width:100px;"><b>Parts:</b></div><div style="width:450px;float:left;border:1px solid #000;border-radius:5px;font-family:Tahoma;margin-left:10px;padding:5px;" align="left">
<div style="position:relative;"><div id="iss_new_part" style="display:none;background-color:#CCC;border-radius:5px;border:1px solid #000;position:absolute;margin-top:-95px;width:500px;height:95px;left:20px;" align="center">
<div id="iss_add_part_dialog" title="Add Part Cost to Issue?" style="display:none;"></div>
<span style="font-size:15pt;">Add Part From Inventory</span><br>
<b>Category:</b> <select id="iss_part_category">
<option value="0" SELECTED>Any Category</option>
<?php

$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NULL ORDER BY category_name");
$result2 = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NOT NULL ORDER BY category_name");
$SUBS = array();
while ($row = mysql_fetch_assoc($result2)) {
	if (!isset($SUBS[$row["parent_id"]])) $SUBS[$row["parent_id"]] = array();
	$SUBS[$row["parent_id"]][] = $row;
}

while ($row = mysql_fetch_assoc($result)) {
	echo "	<option value=\"{$row["id"]}\">{$row["category_name"]}</option>\n";
	if (isset($SUBS[$row["id"]])) foreach ($SUBS[$row["id"]] as $id => $sub) {
		echo "	<option value=\"{$sub["id"]}\">- {$sub["category_name"]}</option>\n";
	}
}

?>
</select><br>
<b>Search Term:</b> <input type="edit" id="iss_part_search_input" size="17" onKeyUp="ajax('part_search');"><br>
<a onClick="ajax('part_search_all');" class="green ilink">Show All</a> &nbsp;&nbsp;
<a href="?module=inventory&do=add" style="text-decoration:none;" class="blue ilink">New Product</a> &nbsp;&nbsp;
<a onClick="html('iss_new_part').style.display='none';html('iss_part_search_input').value='';" class="green ilink">Cancel</a>
<div id="iss_part_results" style="border-left:1px solid #000;border-right:1px solid #000;border-bottom:1px solid #000;display:none;background-color:#CCC;position:absolute;left:-1px;width:500px;top:90px;" align="center"></div>
</div></div>
<?php /*<script type="text/javascript">var parts = [];</script> */ ?>
<?php

$result = mysql_query("SELECT ii.id,ii.qty,ii.do_add,i.name,i.descr,i.cost FROM issue_inv ii JOIN inventory i ON ii.inventory__id = i.id WHERE ii.issues__id = {$ISSUE["id"]}");
if (mysql_num_rows($result)) {
	echo "	<span id=\"iss_parts_summary\"><b>".mysql_num_rows($result)."</b> Parts Attached</span><span id=\"iss_parts_total\"> | ";
	$data = mysql_fetch_assoc(mysql_query("SELECT IFNULL(SUM(ii.qty * i.cost),0) AS total FROM issue_inv ii JOIN inventory i ON ii.inventory__id = i.id WHERE ii.issues__id = ".$ISSUE["id"]." AND `do_add` = 1"));
	if (floatval($data["total"]) > 0) echo "<b>$".number_format($data["total"],2)."</b> Total";
	else echo "<i>All Parts Included</i>";
	echo "</span> | ";
	echo "<a onClick=\"html('iss_new_part').style.display='';\" class=\"green ilink\">Add Part</a>";
	echo "<div id=\"iss_parts\" style=\"display:none;font-size:8pt;\">\n";
	echo <<<EOD
<div class="floatL" style="width:250px;" align="center"><b>Name</b></div><div class="floatL" style="width:50px;" align="center"><b>Cost</b></div><div class="floatL" style="width:40px;" align="center"><b>QTY</b></div><div class="floatL" style="width:50px;" align="center"><b>Total</b></div><div class="floatL" style="width:50px;" align="center"><b>Remove</b></div><div class="clearL"></div>
EOD;
	while ($row = mysql_fetch_assoc($result)) {
		echo "<div id=\"part{$row["id"]}\">";
		echo "<div class=\"floatL\" style=\"width:250px;\" title=\"".str_replace('"','\"',$row["descr"])."\">{$row["name"]}</div>";
		echo "<div class=\"floatL\" align=\"center\" style=\"width:50px;\">$".number_format($row["cost"],2)."</div>";
		echo "<div class=\"floatL\" align=\"center\" style=\"width:40px;\">{$row["qty"]}</div>";
		echo "<div class=\"floatL\" align=\"center\" style=\"width:50px;\">".($row["do_add"] ? "$".number_format($row["qty"] * $row["cost"],2) : "<i>Included</i>")."</div>";
		echo "<div class=\"floatL\" align=\"center\" style=\"width:50px;\"><a onClick=\"iss_remove_part('".$row["id"]."');\" class=\"green ilink\">Remove</a></div>";
		echo "<div class=\"clearL\"></div></div>\n";
	}
	echo "</div></div>";
	echo "<div class=\"floatL\" style=\"margin-left:5px;margin-top:9px;font-size:8pt;\"><a id=\"iss_parts_link\" onClick=\"sh('iss_parts');\" class=\"green ilink\">More</a></div>\n";
} else {
	echo "	<span id=\"iss_parts_summary\"><i>No Parts Attached</i></span><span id=\"iss_parts_total\"></span> | <a onClick=\"html('iss_new_part').style.display='';\" class=\"green ilink\">Add Part</a>";
	echo "<div id=\"iss_parts\" style=\"display:none;font-size:8pt;\"></div>\n";
	echo "</div>\n";
	echo "<div class=\"floatL\" style=\"margin-left:5px;margin-top:9px;font-size:8pt;\"><a id=\"iss_parts_link\" onClick=\"sh('iss_parts');\" class=\"green ilink\">More</a></div>\n";
}

?>

<div class="clearL"><br></div>

<div style="float:left;font-family:Tahoma;margin-top:5px;width:100px;"><b>Orders:</b></div><div style="float:left;border:1px solid #000;border-radius:5px;font-family:Tahoma;margin-left:10px;padding:5px;" align="left"><?php

$result = mysql_query("SELECT i.name,oi.qty,oi.cost FROM order_items oi JOIN inventory i ON oi.inventory__id = i.id WHERE oi.issues__id = {$ISSUE["id"]}");
if (mysql_num_rows($result)) {
	echo "<b>".mysql_num_rows($result)."</b> Items | <a href=\"?module=orders&do=new&issue_id={$ISSUE["id"]}\" class=\"blue ilink\" style=\"text-decoration:none;\">New Order</a>";
	echo "<div id=\"iss_orders\" style=\"display:none;\">\n";
	while ($row = mysql_fetch_assoc($result)) {
		echo $row["qty"]."x ".$row["name"]." @ $".number_format($row["cost"],2)."ea<br>";
	}
	echo "</div></div>\n";
	echo "<div class=\"floatL\" style=\"margin-left:5px;margin-top:9px;font-size:8pt;\"><a id=\"iss_orders_link\" onClick=\"sh('iss_orders');\" class=\"green ilink\">More</a></div>\n";
} else {
	echo "	<span id=\"iss_orders_summary\"><i>Nothing Ordered For This Issue</i></span> | <a href=\"?module=orders&do=new&issue_id={$ISSUE["id"]}\" class=\"blue ilink\" style=\"text-decoration:none;\">New Order</a>";
	echo "</div>\n";
}

?>

<div class="clearL"><br></div>

<div style="float:left;font-family:Tahoma;margin-top:5px;width:100px;"><b>Notes:</b></div><div style="float:left;border:1px solid #000;border-radius:5px;font-family:Tahoma;margin-left:10px;padding:5px;width:600px;" align="left">
<div style="position:relative;"><div id="iss_new_note" style="display:none;background-color:#CCC;border-radius:5px;border:1px solid #000;position:absolute;margin-top:-230px;width:500px;height:230px;left:20px;" align="center">
<span style="font-size:15pt;">New Note</span><br>
<textarea id="iss_edit_note" rows="10" cols="50"></textarea><br>
<input type="button" style="width:360;" value="Save" onClick="ajax('note');"> &nbsp; <a onClick="html('iss_new_note').style.display='none';html('iss_edit_note').value='';" class="green ilink">Cancel</a>
</div></div>
<?php

$result = mysql_query("SELECT user_notes.id,note,note_ts,firstname,lastname,users.id as users__id FROM user_notes JOIN users ON user_notes.users__id = users.id WHERE for_table = 'issues' AND for_key = ".$ISSUE["id"]." ORDER BY note_ts DESC");
echo "<script type=\"text/javascript\">var note_count = ".mysql_num_rows($result).";</script>";
if (!mysql_num_rows($result)) {
	echo "	<span id=\"iss_display_notes\"><i>No Notes For This Issue</i></span> | <a onClick=\"html('iss_new_note').style.display='';\" class=\"green ilink\">New Note</a>";
	echo "	<div id=\"iss_notes\"></div>\n";
} else {
	echo "	<span id=\"iss_display_notes\"><b>".mysql_num_rows($result)."</b> Notes</span> | <a onClick=\"html('iss_new_note').style.display='';\" class=\"green ilink\">New Note</a>";
	echo "	<div id=\"iss_notes\">\n";
	while ($row = mysql_fetch_assoc($result)) {
		echo "<hr>\n";
		echo "Added by <b>".$row["firstname"]." ".$row["lastname"]."</b> on <b>".date("D, j F Y </\\b>\\a\\t<\\b> h:iA",strtotime($row["note_ts"]))."</b>";
		if ($row["users__id"] == $USER["id"]) echo " <a id=\"iss_note_button_{$row["id"]}\" class=\"green ilink\" onClick=\"sh_note({$row["id"]});\">Edit</a>";
		echo "<br>\n";
		echo "<span id=\"iss_note_display_{$row["id"]}\">".$row["note"]."</span>\n";
		echo "<span id=\"iss_note_edit_{$row["id"]}\" style=\"display:none;\"><textarea id=\"iss_note_box_{$row["id"]}\" rows=\"10\" cols=\"50\">{$row["note"]}</textarea><br><a class=\"green ilink\" onClick=\"iss_update_note({$row["id"]});\">Update Note</a></span>\n";
	}
	echo "	</div>\n";
}

?></div><div style="float:left;margin-left:5px;margin-top:9px;"><a id="iss_notes_link" onClick="sh('iss_notes');" class="green ilink" style="font-size:8pt;">Less</a></div>

<div class="clearL"><br></div>

<div style="float:left;font-family:Tahoma;margin-top:5px;width:100px;"><b>Change Log:</b></div><div style="float:left;border:1px solid #000;border-radius:5px;font-family:Tahoma;margin-left:10px;padding:5px;width:600px;" align="left"><?php

$result = mysql_query("SELECT description,tou,username FROM issue_changes JOIN users ON issue_changes.users__id = users.id WHERE issues__id = ".$ISSUE["id"]." ORDER BY tou DESC");
if (!mysql_num_rows($result)) {
	echo "	<span id=\"iss_display_notes\"><i>No Changes For This Issue</i></span>";
	echo "	<div id=\"iss_changes\" style=\"display:none;\"></div>\n";
} else {
	echo "	<span id=\"iss_display_notes\"><b>".mysql_num_rows($result)."</b> Changes Logged</span>";
	echo "	<div id=\"iss_changes\" style=\"display:none;\">\n";
	while ($row = mysql_fetch_assoc($result)) {
		echo "<hr>\n";
		echo "Change by <b>".$row["username"]."</b> on <b>".date("D, j F Y </\\b>\\a\\t<\\b> h:iA",strtotime($row["tou"]))."</b><br>";
		echo $row["description"];
	}
	echo "	</div>\n";
}

?></div><div style="float:left;margin-left:5px;margin-top:9px;"><a id="iss_changes_link" onClick="sh('iss_changes');" class="green ilink" style="font-size:8pt;">More</a></div>

<div class="clearL"><br></div>
