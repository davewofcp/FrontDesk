<script type="text/javascript">
var field_counter = 1;
var opts_counter = {1:1};

function colorize() {
	var t = html('fields');
	for (var i = 1; i < t.rows.length; i++) {
		var r = t.rows[i];
		if (i % 2 == 1) {
			r.style.backgroundColor = '#FFFFFF';
		} else {
			r.style.backgroundColor = '#CCCCCC';
		}
	}
}

function renumber() {
	var t = html('fields');
	for (var i = 1; i < t.rows.length; i++) {
		var r = t.rows[i];
		r.getElementsByTagName('input')[0].value = i;
	}
}

function sortTable(){
    var tbl = document.getElementById("fields").tBodies[0];
    var store = [];
    for(var i=0, len=tbl.rows.length; i<len; i++){
        var row = tbl.rows[i];
        var sortnr = parseFloat(row.getElementsByTagName('input')[0].value);
        if(!isNaN(sortnr)) store.push([sortnr, row]);
    }
    store.sort(function(x,y){
        return x[0] - y[0];
    });
    for(var i=0, len=store.length; i<len; i++){
        tbl.appendChild(store[i][1]);
    }
    store = null;
    colorize();
}

function changeType(field) {
	var c = html('res_'+field);
	var s = html('f'+field+'_type');
	var content = '';
	switch (s.selectedIndex) {
		case 0:
			content = '<input type="text" name="f'+field+'_res" size="4">';
			break;
		case 1:
		case 2:
			content = '<input type="text" name="f'+field+'_res_1" size="3"> to <input type="text" name="f'+field+'_res_2" size="3">';
			break;
		case 3:
			content = '<a href="#" onClick="addOpt('+field+');" class="green ilink" style="text-decoration:none;">Add Option</a>\n';
			content += '<input type="hidden" name="f'+field+'_opts_count" id="f'+field+'_opts_count" value="1">\n';
			content += '<table border="0" id="f'+field+'_opts_container">\n';
			content += '<tr><td><input type="text" id="f'+field+'_opt_1" name="f'+field+'_opt_1" size="12"> <a href="#" onClick="deleteRow(this);" class="green ilink" style="text-decoration:none;">Delete</a></td></tr>\n';
			content += '</table>\n';
			opts_counter[field] = 1;
			break;
		case 4:
			content = '<i>N/A</i>';
			break;
	}
	c.innerHTML = content;
}

function addOpt(field) {
	var t = html('f'+field+'_opts_container');
	var count = ++(opts_counter[field]);
	var r = t.insertRow(-1);
	var c = r.insertCell(0);
	html('f'+field+'_opts_count').value = count;
	c.innerHTML = '<input type="text" id="f'+field+'_opt_'+count+'" name="f'+field+'_opt_'+count+'" size="12"> <a href="#" onClick="deleteRow(this);" class="green ilink" style="text-decoration:none;">Delete</a>';
}

function deleteRow(object) {
	while (object.tagName != 'TR') {
        object = object.parentNode;
    }
    object.parentNode.removeChild(object);
    colorize();
}

function addRow() {
	var field = ++field_counter;
	html('field_count').value = field;
	var t = html('fields');
	var r = t.insertRow(-1);
	r.align = 'center';
	var cell = r.insertCell(0);
	cell.innerHTML = '<input type="text" name="f'+field+'_order" size="2" value="'+field+'">';
	cell = r.insertCell(1);
	cell.innerHTML = '<input type="text" name="f'+field+'_question" size="40">';
	cell = r.insertCell(2);
	var s = '<select id="f'+field+'_type" name="f'+field+'_type" onChange="changeType('+field+');">\n';
	s += '<option value="1" SELECTED>text</option>\n';
	s += '<option value="2">integer</option>\n';
	s += '<option value="3">decimal</option>\n';
	s += '<option value="4">multiple choice</option>\n';
	s += '<option value="5">checkbox</option>\n';
	s += '</select>';
	cell.innerHTML = s;
	cell = r.insertCell(3);
	cell.innerHTML = '<div id="res_'+field+'"><input type="text" name="f'+field+'_res" size="4"></div>';
	cell = r.insertCell(4);
	cell.innerHTML = '<a href="#" onClick="deleteRow(this);" class="green ilink" style="text-decoration:none;">Delete</a>';
	cell = r.insertCell(5);
	cell.innerHTML = '<a href="#" onClick="moveUp(this);" class="green ilink" style="text-decoration:none;">Up</a>\n' +
		  			 '<a href="#" onClick="moveDown(this);" class="green ilink" style="text-decoration:none;">Down</a>';
	colorize();
}

function moveUp(obj) {
	while (obj.tagName != 'TR') {
		obj = obj.parentNode;
		if (!obj) return;
    }
	var prev = obj.previousSibling;
	var par = obj.parentNode;
	if (prev && prev.previousSibling) { // Prevent moving into header row
		par.removeChild(obj);
		par.insertBefore(obj, prev);
		colorize();
	}
}

function moveDown(obj) {
	while (obj.tagName != 'TR') {
		obj = obj.parentNode;
		if (!obj) return;
    }
    var next = obj.nextSibling;
    var par = obj.parentNode;
    if (next) {
    	par.removeChild(obj);
        par.insertBefore(obj, next.nextSibling);
        colorize();
    }
}

</script>
<h3>Create New User Report</h3>

<form action="?module=admin&do=user_rpt_new" method="post">
<input type="hidden" id="field_count" name="field_count" value="1">
<table border="0">
 <tr>
  <td class="heading" align="right">Report Name</td>
  <td><input type="text" name="name" size="50"></td>
 </tr>
</table>
<table border="0" id="fields" cellspacing="0" cellpadding="3">
 <thead>
 <tr align="center" class="heading">
  <td>Order</td>
  <td>Question / Prompt</td>
  <td>Response Type</td>
  <td>Response Range</td>
  <td>Delete</td>
  <td>Move</td>
 </tr>
 </thead>
 <tbody>
 <tr align="center" style="background-color:#FFFFFF;">
  <td><input type="text" name="f1_order" size="2" value="1"></td>
  <td><input type="text" name="f1_question" size="40"></td>
  <td><select id="f1_type" name="f1_type" onChange="changeType(1);">
  <option value="1" SELECTED>text</option>
  <option value="2">integer</option>
  <option value="3">decimal</option>
  <option value="4">multiple choice</option>
  <option value="5">checkbox</option>
  </select></td>
  <!-- MUTLIPLE CHOICE:
  <td>
   <a href="#" onClick="addOpt(1);" class="green ilink" style="text-decoration:none;">Add Option</a>
   <input type="hidden" name="f1_opts_count" id="f1_opts_count" value="1">
   <table border="0" id="f1_opts_container">
    <tr><td><input type="text" id="f1_opt_1" name="f1_opt_1" size="12"> <a href="#" onClick="deleteRow(this);" class="green ilink" style="text-decoration:none;">Delete</a></td></tr>
   </table>
  </td> -->
  <!-- INT/DEC td><input type="text" name="f1_res_1" size="3"> to <input type="text" name="f1_res_2" size="3"></td -->
  <!-- CBOX td><i>N/A</i></td -->
  <td><div id="res_1"><input type="text" name="f1_res" size="4"></div></td>
  <td><a href="#" onClick="deleteRow(this);" class="green ilink" style="text-decoration:none;">Delete</a></td>
  <td><a href="#" onClick="moveUp(this);" class="green ilink" style="text-decoration:none;">Up</a>
  <a href="#" onClick="moveDown(this);" class="green ilink" style="text-decoration:none;">Down</a></td>
 </tr>
 </tbody>
</table><br>
<a href="#" onClick="addRow();" class="green ilink" style="text-decoration:none;">Add Question</a><br><br>
<a href="#" onClick="sortTable();" class="green ilink" style="text-decoration:none;">Sort</a> &nbsp; - &nbsp;
<a href="#" onClick="renumber();" class="green ilink" style="text-decoration:none;">Order As Seen</a><br><br>
<input type="submit" value="Create User Report">
</form><br>

<b>Response Range</b><br>
For a <u>text</u> type, range is the number of characters accepted. Enter 0 for an unlimited textarea.<br>
For an <u>integer</u> (counting numbers), range is two values: lowest accepted and highest accepted. Enter 0 in both fields for unlimited.<br>
For a <u>decimal</u>, range works the same as for integer.<br>
For a <u>multiple choice</u>, enter one choice per box and use "Add" and "Delete" buttons.<br>
