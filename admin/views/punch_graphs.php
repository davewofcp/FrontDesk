<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<script type="text/javascript">
<?php

if (isset($_POST["date"])) $entered_date = $_POST["date"];
if (isset($_GET["date"])) $entered_date = $_GET["date"];

if (isset($entered_date) && preg_match('/\d{4}\-\d{2}\-\d{2}/',$entered_date)) {
	$BEGIN = $entered_date;
} else {
	$BEGIN = date("Y-m-d");
}

$result = mysql_query("SELECT id,firstname,lastname FROM users WHERE org_entities__id = {$USER['org_entities__id']}");
echo "var users = [];\n";
while ($row = mysql_fetch_assoc($result)) {
	echo "users[{$row["id"]}] = '{$row["firstname"]} {$row["lastname"]}';\n";
}

$result = mysql_query("SELECT * FROM payroll_timecards WHERE org_entities__id = {$USER['org_entities__id']} AND punch_date = '$BEGIN'");
echo "var hours = [];\n";
while ($row = mysql_fetch_assoc($result)) {
	if ($row["punch_in"]) {
		$sa = explode(":",$row["punch_in"]);
		$start = ($sa[0] * 60) + $sa[1];
		$st = $sa[0].":".$sa[1];
	} else {
		continue;
	}
	if ($row["punch_out"]) {
		$ea = explode(":",$row["punch_out"]);
		$end = ($ea[0] * 60) + $ea[1];
		$et = $ea[0].":".$ea[1];
	} else {
		continue;
	}
	echo "hours.push({user_id:{$row["id"]},start:$start,end:$end,st:'$st',et:'$et'});\n";
}

?>
function html(id) {
	return document.getElementById(id);
}

var hourObjects = [];

function Hours() {
	this.user_id = 0;
	this.start = -1;
	this.end = -1;
	this.x = 0;
	this.y = 0;
	this.w = 0;
	this.h = 0;
	this.st = '';
	this.et = '';
}

function PackHours() {
	hourObjects = [];
	html('hours').innerHTML = '';
	var columns = [];
	var lastEventEnding = null;
	hours = hours.sort(function(e1,e2) {
	      if (e1.start < e2.start) return -1;
	      if (e1.start > e2.start) return 1;
	      if (e1.end < e2.end) return -1;
	      if (e1.end > e2.end) return 1;
	      return 0;
	});

	for (var j = 0; j < hours.length; j++) {
		if (lastEventEnding !== null && hours[j].start >= lastEventEnding) {
			PackColumns(columns);
			columns = [];
			lastEventEnding = null;
		}

		var placed = false;
		for (var k = 0; k < columns.length; k++) {
			var col = columns[k];
			if (!overlap(col[col.length - 1],hours[j])) {
				col.push(hours[j]);
				placed = true;
				break;
			}
		}

		if (!placed) {
			columns.push([hours[j]]);
		}

		if (lastEventEnding == null || hours[j].end > lastEventEnding) {
			lastEventEnding = hours[j].end;
		}
	}

	if (columns.length > 0) {
		PackColumns(columns);
	}
}

function PackColumns(columns) {
	var n = columns.length;
	for (var i = 0; i < n; i++) {
		var col = columns[i];
		for (var j = 0; j < col.length; j++) {
			var obj = col[j];
			var ho = new Hours();
			ho.user_id = obj.user_id;
			ho.start = obj.start;
			ho.end = obj.end;
			ho.st = obj.st;
			ho.et = obj.et;
			ho.x = (i / n) * 600;
			ho.w = (600 / n) - 1;
			ho.y = ((obj.start - 420) / 15) * 10;
			ho.h = (((obj.end - 420) / 15) * 10) - ho.y;
			DrawHours(ho);
			hourObjects.push(ho);
		}
	}
}

function DrawHours(h) {
	var content = html('hours').innerHTML;
	var newDiv = '<div style="border:1px solid #000;font-family:Tahoma;font-size:9pt;position:absolute;top:'+h.y+'px;left:'+h.x+'px;width:'+h.w+'px;height:'+h.h+'px;background-color:#CCCCFF;z-index:2;" align="center"><b>'+users[h.user_id]+'</b><br>'+h.st+' - '+h.et+'</div>';
	html('hours').innerHTML = content +'\n' + newDiv;
}

function round_to(num, dec) {
    return Math.round(num * Math.pow(10, dec)) / Math.pow(10, dec);
}

function FindOverlaps() {
	for (var i = 0; i < hourObjects.length; i++) {
		var thisUser = [];
		var thisHours = hourObjects[i];
		for (var j = 0; j < hourObjects.length; j++) {
			if (i == j) continue;
			if (overlap(hourObjects[j],thisHours)) {
				var startOverlap = max(hourObjects[j].start,thisHours.start);
				var endOverlap = min(hourObjects[j].end,thisHours.end);
				var st = getTime(startOverlap);
				var et = getTime(endOverlap);
				var hours = endOverlap - startOverlap;
				thisUser.push({user_id:hourObjects[j].user_id,st:st,et:et,hours:hours});
			}
		}
		if (thisUser.length > 0) {
			var content = html('overlaps').innerHTML;
			var ac = '<b>'+users[thisHours.user_id]+':</b><br>\n';
			for (var j = 0; j < thisUser.length; j++) {
				var thisOverlap = thisUser[j];
				ac = ac + '- <b>'+users[thisOverlap.user_id]+':</b> '+thisOverlap.st+' - '+thisOverlap.et+' ('+round_to(thisOverlap.hours / 60,2)+' hours)<br>\n';
			}
			html('overlaps').innerHTML = content + ac;
		}
	}
}

function max(a,b) {
	if (a > b) return a;
	return b;
}

function min(a,b) {
	if (a < b) return a;
	return b;
}

function getTime(x) {
	var hours = Math.floor(x / 60);
	var minutes = x - (hours * 60);
	var hh = ''+hours;
	var mm = ''+minutes;
	if (hh.length < 2) hh = '0'+hh;
	if (mm.length < 2) mm = '0'+mm;
	return hh+':'+mm;
}

function overlap(h1,h2) {
	return h1.end > h2.start && h1.start < h2.end;
}
window.onload = function() {
	PackHours();
	calendar.set("date");
	FindOverlaps();
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
</script>
<h3>Hours Worked <?php echo $BEGIN; ?></h3>
<form action="?module=admin&do=punch_graph" method="post">
<b>Select Date:</b> <input type="edit" name="date" id="date" size="10" value="<?php echo $BEGIN; ?>"> <input type="submit" value="Go">
</form>
<div id="times" class="time-gradient" style="position:absolute;left:75px;top:100px;background-color:#B2C6E1;width:50px;height:640px;font-family:Tahoma;font-size:9pt;">
	<div name="timebox" id="time7" style="border:1px solid #A4BED4;position:absolute;left:0px;top:0px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">07:00</div></div>
	<div name="timebox" id="time8" style="border:1px solid #A4BED4;position:absolute;left:0px;top:40px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">08:00</div></div>
	<div name="timebox" id="time9" style="border:1px solid #A4BED4;position:absolute;left:0px;top:80px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">09:00</div></div>
	<div name="timebox" id="time10" style="border:1px solid #A4BED4;position:absolute;left:0px;top:120px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">10:00</div></div>
	<div name="timebox" id="time11" style="border:1px solid #A4BED4;position:absolute;left:0px;top:160px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">11:00</div></div>
	<div name="timebox" id="time12" style="border:1px solid #A4BED4;position:absolute;left:0px;top:200px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">12:00</div></div>
	<div name="timebox" id="time13" style="border:1px solid #A4BED4;position:absolute;left:0px;top:240px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">13:00</div></div>
	<div name="timebox" id="time14" style="border:1px solid #A4BED4;position:absolute;left:0px;top:280px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">14:00</div></div>
	<div name="timebox" id="time15" style="border:1px solid #A4BED4;position:absolute;left:0px;top:320px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">15:00</div></div>
	<div name="timebox" id="time16" style="border:1px solid #A4BED4;position:absolute;left:0px;top:360px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">16:00</div></div>
	<div name="timebox" id="time17" style="border:1px solid #A4BED4;position:absolute;left:0px;top:400px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">17:00</div></div>
	<div name="timebox" id="time18" style="border:1px solid #A4BED4;position:absolute;left:0px;top:440px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">18:00</div></div>
	<div name="timebox" id="time19" style="border:1px solid #A4BED4;position:absolute;left:0px;top:480px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">19:00</div></div>
	<div name="timebox" id="time20" style="border:1px solid #A4BED4;position:absolute;left:0px;top:520px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">20:00</div></div>
	<div name="timebox" id="time21" style="border:1px solid #A4BED4;position:absolute;left:0px;top:560px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">21:00</div></div>
	<div name="timebox" id="time22" style="border:1px solid #A4BED4;position:absolute;left:0px;top:600px;width:50px;height:39px;"><div style="position:absolute;top:50%;margin-top:-10px;width:100%;" align="center">22:00</div></div>
</div>
<div id="container" style="position:absolute;left:125px;top:100px;width:600px;height:640px;background-color:#FFFFFF;">
<div name="hline" id="hline1" style="background-color:#ECEEF4;position:absolute;left:0px;top:20px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline2" style="background-color:#ECEEF4;position:absolute;left:0px;top:60px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline3" style="background-color:#ECEEF4;position:absolute;left:0px;top:100px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline4" style="background-color:#ECEEF4;position:absolute;left:0px;top:140px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline5" style="background-color:#ECEEF4;position:absolute;left:0px;top:180px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline6" style="background-color:#ECEEF4;position:absolute;left:0px;top:220px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline7" style="background-color:#ECEEF4;position:absolute;left:0px;top:260px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline8" style="background-color:#ECEEF4;position:absolute;left:0px;top:300px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline9" style="background-color:#ECEEF4;position:absolute;left:0px;top:340px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline10" style="background-color:#ECEEF4;position:absolute;left:0px;top:380px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline11" style="background-color:#ECEEF4;position:absolute;left:0px;top:420px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline12" style="background-color:#ECEEF4;position:absolute;left:0px;top:460px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline13" style="background-color:#ECEEF4;position:absolute;left:0px;top:500px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline14" style="background-color:#ECEEF4;position:absolute;left:0px;top:540px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline15" style="background-color:#ECEEF4;position:absolute;left:0px;top:580px;width:600px;height:20px;z-index:1;"></div>
<div name="hline" id="hline16" style="background-color:#ECEEF4;position:absolute;left:0px;top:620px;width:600px;height:20px;z-index:1;"></div>
<div id="hours"></div>
</div>
<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br><br><br><br><br>
<br><br>
<h3>Overlapping Hours</h3>
<div id="overlaps" align="left" style="width:350px;border:1px solid #000;">

</div>
