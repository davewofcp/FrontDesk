<?php

if (!isset($USER)) exit;

$time_interval = 15;
$first_hour = 7;
$last_hour = 23;

$EVENT_TYPE = array(
		"Hours",              //0-Hours
);
$EVENT_STYLE = array(
		"blueEvent",          //0-Hours
);
$EVENT_FORM = array(
		"blueForm",           //0-Hours  DEFAULT
);

$USERS = array();
$result = mysql_query("SELECT * FROM users WHERE 1") OR die(mysql_error());
while($row=mysql_fetch_assoc($result)){
	$USERS[$row["id"]] = array();
	$USERS[$row["id"]]["name"]=''.$row["firstname"].' '.$row["lastname"].'';
	$USERS[$row["id"]]["firstname"]=''.$row["firstname"].'';
	$USERS[$row["id"]]["lastname"]=''.$row["lastname"].'';
	$USERS[$row["id"]]["id"]=''.$row["id"].'';
	//$USERS[$row["id"]]["isadmin"]=''.$row["isadmin"].'';
	$USERS[$row["id"]]["is_disabled"]=''.$row["is_disabled"].'';
}

function pTime($hr,$min,$stop){
	global $time_interval;
	$hr = $hr;$min = $min;$time = $hr."".$min;
	while(intval($time)<=$stop){
		$hr = str_pad($hr,2,"0",STR_PAD_LEFT);$min = str_pad($min,2,"0",STR_PAD_LEFT);
		echo '<option value="'.$hr.':'.$min.'">'.$hr.':'.$min.'</option>';
		$min+=$time_interval;
		if($min=="60"){
			$min="00";$hr++;
		}
		$hr = str_pad($hr,2,"0",STR_PAD_LEFT);$min = str_pad($min,2,"0",STR_PAD_LEFT);
		$time = $hr."".$min;
	}
}

function findMonday($d="",$format="Y-m-d") {
	if($d=="") $d=date("Y-m-d");
	$dparts = explode("-",$d);
	$mydate = mktime(12,0,0,$dparts[1],$dparts[2],$dparts[0]);
	$weekday = ((int)date( 'w', $mydate ) + 6 ) % 7;
	$prevmonday = $mydate - $weekday * 24 * 3600;
	return date($format,$prevmonday);
}

?>
<html><head><title>Calendar</title>
<link rel="stylesheet" type="text/css" href="calendar.css">
<link rel="stylesheet" type="text/css" href="jquery-ui.css" />
<style type="text/css">
.prev_button {
	background-image: url(images/buttons.png);
	background-position: 0 0;
	width: 29px;
	height: 17px;
	left: 50px;
	cursor: pointer;
}
.next_button {
	background-image: url(images/buttons.png);
	background-position: -30px 0;
	width: 29px;
	height: 17px;
	left: 80px;
	cursor: pointer;
}
.noselect {
	-webkit-touch-callout: none;
	-webkit-user-select: none;
	-khtml-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}
.nooverflow {
	text-overflow: clip;
	overflow: hidden;
}
.event_new {
	background-color: #FFE763;
	border: 1px solid #B7A543;
}
.event_new_hours {
	color: #87A543;
	border-bottom: 1px dotted #87A543;
}
.event_hours {
	background-color: #CEE6FF;
	border: 1px solid #0033CC;
}
.event_hours_hours {
	color: #0033CC;
	border-bottom: 1px dotted #0033CC;
}
.event_onsite {
	background-color: #DEF1B3;
	border: 1px solid #476800;
}
.event_onsite_hours {
	color: #476800;
	border-bottom: 1px dotted #476800;
}
.event_remote {
	background-color: #FFDB93;
	border: 1px solid #926900;
}
.event_remote_hours {
	color: #926900;
	border-bottom: 1px dotted #926900;
}
.event_internal {
	background-color: #D3D3D3;
	border: 1px solid #666666;
}
.event_internal_hours {
	color: #666666;
	border-bottom: 1px dotted #666666;
}
.time-gradient {
	background-image: linear-gradient(left , #B2C7E1 80%, #FFFFFF 100%);
	background-image: -o-linear-gradient(left , #B2C7E1 80%, #FFFFFF 100%);
	background-image: -moz-linear-gradient(left , #B2C7E1 80%, #FFFFFF 100%);
	background-image: -webkit-linear-gradient(left , #B2C7E1 80%, #FFFFFF 100%);
	background-image: -ms-linear-gradient(left , #B2C7E1 80%, #FFFFFF 100%);

	background-image: -webkit-gradient(
		linear,
		left top,
		right top,
		color-stop(0.8, #B2C7E1),
		color-stop(1, #FFFFFF)
	);
}
.newEventBar-gradient {
	background-image: linear-gradient(bottom, #CFE4FE 80%, #FFFFFF 100%);
	background-image: -o-linear-gradient(bottom, #CFE4FE 80%, #FFFFFF 100%);
	background-image: -moz-linear-gradient(bottom, #CFE4FE 80%, #FFFFFF 100%);
	background-image: -webkit-linear-gradient(bottom, #CFE4FE 80%, #FFFFFF 100%);
	background-image: -ms-linear-gradient(bottom, #CFE4FE 80%, #FFFFFF 100%);

	background-image: -webkit-gradient(
		linear,
		left bottom,
		left top,
		color-stop(0.8, #CFE4FE),
		color-stop(1, #FFFFFF)
	);
}
.newEventButton {
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #ededed), color-stop(1, #def1b3) );
	background:-moz-linear-gradient( center top, #ededed 5%, #def1b3 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ededed', endColorstr='#def1b3');
	background-color:#ededed;
	-moz-border-radius:3px;
	-webkit-border-radius:3px;
	border-radius:3px;
	border:1px solid #476800;
	display:inline-block;
	color:#000000;
	font-family:Verdana;
	font-size:9px;
	font-weight:normal;
	padding:0px 100px;
	text-decoration:none;
}.newEventButton:hover {
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #def1b3), color-stop(1, #ededed) );
	background:-moz-linear-gradient( center top, #def1b3 5%, #ededed 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#def1b3', endColorstr='#ededed');
	background-color:#def1b3;
}.newEventButton:active {
	position:relative;
	top:1px;
}
</style>
</head>
<body>
<script type="text/javascript" src="js/calendar.js"></script>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery-ui.js"></script>
<script type="text/javascript">
var users = [];
<?php

foreach($USERS as $id => $arr) {
	if ($arr["is_disabled"]) continue;
	echo "users[$id] = '".str_replace("'","\\'",$arr["name"])."';\n";
}

?>

var EVENT_FORM = new Array();
<?php
foreach($EVENT_FORM as $num => $var){
	echo "EVENT_FORM[".$num."] = '".$var."';\n";
}
?>

var EVENT_TYPE = new Array();
<?php
foreach($EVENT_TYPE as $num => $var){
	echo "EVENT_TYPE[".$num."] = '".$var."';\n";
}
?>

var EVENT_STYLE = new Array();
<?php
foreach($EVENT_STYLE as $num => $var){
	echo "EVENT_STYLE[".$num."] = '".$var."';\n";
}
?>

var mvEventOffsets = [
[20,20], // Hours
];

var month = '<?php echo date('Y-m-d'); ?>';
var calStartDate = '<?php echo findMonday(); ?>';
var display = 'week';

var viewJustMe = <?php echo ($USER["id"] < 1) ? "false":"true"; ?>;
var myUserId = <?php echo intval($USER["id"]); ?>;

var ajaxActive = false;
var calAjax;
if (window.XMLHttpRequest) {
	// code for IE7+, Firefox, Chrome, Opera, Safari
	calAjax=new XMLHttpRequest();
} else {
	// code for IE6, IE5
	calAjax=new ActiveXObject("Microsoft.XMLHTTP");
}
calAjax.onerror = function(evt) {
	alert(dump(evt));
}

function calAjaxHandler() {
	if (calAjax.readyState == 4 && calAjax.status == 200) {
		var data = eval("("+calAjax.responseText+")");
		//alert(dump(data));
		if (!data || !data.action) {
			html('status').innerHTML = '';
			ajaxActive = false;
			return;
		}
		for (var i = 0; i < data.action.length; i++) {
			switch (data.action[i]) {
				case "status":
					html('status').innerHTML = data.status;
					break;
				case "error":
					alert(data.error);
					break;
				case "new":
					for (var j = 0; j < calEvents.length; j++) {
						if (calEvents[j].id == data.event.oldId) {
							calEvents[j].id = data.event.id;
							if (data.event.issue_id) {
								calEvents[j].issue_id = data.event.issue_id;
							}
						}
					}
					html('status').innerHTML = 'Event Saved';
					drawEvents();
					break;
				case "add":
					for (var j = 0; j < data.events.length; j++) {
						var evt = data.events[j];
						var ev = new CalEvent();
						ev.id = evt.id;
						ev.user_id = evt.user_id;
						ev.startTime = evt.startTime;
						ev.endTime = evt.endTime;
						ev.date = evt.date;
						ev.recurring = evt.recurring;
						ev.rec_type = evt.rec_type;
						ev.rec_endDate = evt.rec_endDate;
						ev.issue_id = evt.issue_id;
						ev.x = getXForDate(ev.date) + 15;
						ev.y = getYForTime(ev.startTime);
						ev.w = 85;
						ev.h = getYForTime(ev.endTime) - ev.y;
						calEvents.push(ev);
					}
					drawEvents();
					break;
				case "refresh":
					html('status').innerHTML = '';
					calEvents = [];
					for (var j = 0; j < data.events.length; j++) {
						var evt = data.events[j];
						var ev = new CalEvent();
						ev.id = evt.id;
						ev.user_id = evt.user_id;
						ev.startTime = evt.startTime;
						ev.endTime = evt.endTime;
						ev.date = evt.date;
						ev.recurring = evt.recurring;
						ev.rec_type = evt.rec_type;
						ev.rec_endDate = evt.rec_endDate;
						ev.issue_id = evt.issue_id;
						ev.x = getXForDate(ev.date) + 15;
						ev.y = getYForTime(ev.startTime);
						ev.w = 85;
						ev.h = getYForTime(ev.endTime) - ev.y;
						calEvents.push(ev);
					}
					drawEvents();
					break;
				case "delete":
					if (data.deleted) {
						for (var k = 0; k < data.deleted.length; k++) {
							for (var j = 0; j < calEvents.length; j++) {
								if (calEvents[j].id == data.deleted[k].id) {
									calEvents[j].remove();
									break;
								}
							}
						}
					}
					if (data.delete_all) {
						for (var j = 0; j < calEvents.length; j++) {
							if (baseId(calEvents[j].id) == data.delete_all) {
								calEvents[j].remove();
								j--;
							}
						}
					}
					drawEvents();
					break;
				case "save":
					break;
			}
		}
		ajaxActive = false;
	} else if (calAjax.readyState == 4) {
		ajaxActive = false;
	}
}

calAjax.onreadystatechange = calAjaxHandler;

function ajax(operation,ev) {
	calAjax.abort();
	calAjax.onreadystatechange = calAjaxHandler;
	calAjax.open("POST","time/ajax.php",true);
	calAjax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajaxActive = true;
	switch (operation) {
		case "save_all":
			var cols = ['action','display','start','id','user_id','startTime','endTime','date','recurring','rec_type','rec_endDate'];
			var vals = ['save_all',display,calStartDate,ev.id,ev.user_id,ev.startTime,ev.endTime,ev.date];
			if (ev.recurring) {
				vals.push('1');
				vals.push(ev.rec_type);
				vals.push(ev.rec_endDate);
			} else {
				vals.push('0');
				vals.push('');
				vals.push('');
			}
			var params = buildParams(cols,vals);
			calAjax.send(params);
			break;
		case "save":
			var cols = ['action','display','start','id','user_id','startTime','endTime','date','recurring','rec_type','rec_endDate'];
			var vals = ['save',display,calStartDate,ev.id,ev.user_id,ev.startTime,ev.endTime,ev.date];
			if (ev.recurring) {
				vals.push('1');
				vals.push(ev.rec_type);
				vals.push(ev.rec_endDate);
			} else {
				vals.push('0');
				vals.push('');
				vals.push('');
			}
			var params = buildParams(cols,vals);
			calAjax.send(params);
			break;
		case "new":
			var cols = ['action','display','start','id','user_id','startTime','endTime','date','recurring','rec_type','rec_endDate'];
			var vals = ['new',display,calStartDate,ev.id,ev.user_id,ev.startTime,ev.endTime,ev.date];
			if (ev.recurring) {
				vals.push('1');
				vals.push(ev.rec_type);
				vals.push(ev.rec_endDate);
			} else {
				vals.push('0');
				vals.push('');
				vals.push('');
			}
			var params = buildParams(cols,vals);
			calAjax.send(params);
			break;
		case "delete":
			var cols = ['action','id'];
			var vals = ['delete',ev.id];
			var params = buildParams(cols,vals);
			calAjax.send(params);
			break;
		case "delete_all":
			var cols = ['action','id'];
			var vals = ['delete_all',baseId(ev.id)];
			var params = buildParams(cols,vals);
			calAjax.send(params);
			break;
		case "refresh":
			html('status').innerHTML = "Loading...";
			var cols = ['action','display','start'];
			var vals = ['refresh',display,calStartDate];
			var params = buildParams(cols,vals);
			calAjax.send(params);
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

// Used for Week view
function getDateForX(x) {
	var r = x % 100;
	x -= r;
	var dt = parseDate(calStartDate);
	for (var i = 0; i < 700; i += 100) {
		if (x == i) return dt.format("yyyy-mm-dd");
		dt.setDate(dt.getDate()+1);
	}
}

// Used for Week/Month views
function getXForDate(dt) {
	var dtGiven = parseDate(dt);
	var dtx = parseDate(calStartDate);
	if (display == 'week') {
		for (var i = 0; i < 700; i += 100) {
			if (dtGiven.format('yyyy-mm-dd') == dtx.format('yyyy-mm-dd')) return i;
			dtx.setDate(dtx.getDate()+1);
		}
		return -1;
	} else if (display == 'month') {
		var x = 0;
		for (var i = 0; i < 42; i++) {
			if (dtGiven.format('yyyy-mm-dd') == dtx.format('yyyy-mm-dd')) return x;
			x += 100;
			if (x == 700) x = 0;
			dtx.setDate(dtx.getDate()+1);
		}
		return -1;
	}
}

// Used for Month view
function getYForDate(dt) {
	if (display != 'month') return -1;
	var dtGiven = parseDate(dt);
	var dtx = parseDate(calStartDate);
	var x = 0, row = 0;
	for (var i = 0; i < 43; i++) {
		if (dtGiven.format('yyyy-mm-dd') == dtx.format('yyyy-mm-dd')) return row * 100;
		x++;
		if (x == 7) {
			x = 0;
			row++;
		}
		dtx.setDate(dtx.getDate() + 1);
	}
	return -1;
}

// Used for Week/Day views
function getTimeForY(y) {
	var hour = Math.floor(y / 40) + 7;
	var minutes = '' + (((y - ((hour - 7) * 40)) / 10) * 15);
	var hour = '' + hour;
	var dateStr = '';
	if (hour.length == 1) dateStr += '0';
	dateStr += hour +':';
	if (minutes.length == 1) dateStr += '0';
	dateStr += minutes;
	return dateStr;
}

function getYForTime(t) {
	var timeArr = t.split(':');
	if (parseInt(timeArr[0],10) < 7) return -1;
	if (parseInt(timeArr[0],10) == 23 && parseInt(timeArr[1],10) > 0) return 640;
	if (parseInt(timeArr[0],10) > 23) return 640;
	var retVal = (parseInt(timeArr[0],10) - 7) * 40;
	retVal += (parseInt(timeArr[1],10) / 15) * 10;
	return retVal;
}

function parseDate(input) {
	  var parts = input.match(/(\d+)/g);
	  return new Date(parts[0], parts[1]-1, parts[2]); // months are 0-based
}

function getCalEventIndex(id) {
	for (var i = 0; i < calEvents.length;i++) {
		if (calEvent[i].id == id) return i;
	}
	return -1;
}

function CalEvent() {
	this.id = '0#'+(counter++);
	this.user_id = 0;
	this.event_type = -1;
	this.startTime = '07:00';
	this.endTime = '23:00';
	this.date = calStartDate;
	this.recurring = false;
	this.rec_type = '';
	this.rec_endDate = '';
	this.issue_id = 0;
	this.column = 0;
	this.right_offset = 0;
	this.x = 0;
	this.y = 0;
	this.w = 0;
	this.h = 0;

	this.checkCollision = function(x1,y1) {
		return isRect(x1,y1,this.x + (this.column * 10),this.y,this.w - (this.column * 10) - (this.right_offset * 10),this.h);
	}

	this.collidesWith = function(ev) {
		return isRectRect(this.x,this.y,this.w,this.h,ev.x,ev.y,ev.w,ev.h);
	}

	this.remove = function() {
		for (var i = 0; i < calEvents.length; i++) {
			if (calEvents[i].id == this.id) {
				calEvents.splice(i,1);
				return;
			}
		}
	}

	this.removeAll = function() {
		var loopArray = jQuery.extend(true, {}, calEvents);
		for (var i = 0; i < loopArray.length; i++) {
			if (baseId(loopArray[i].id) == baseId(this.id)) {
				loopArray.splice(i,1);
				i--;
			}
		}
	}
}

function removeEvent(id) {
	for (var i = 0; i < calEvents.length; i++) {
		if (calEvents[i].id == id) {
			calEvents.splice(i,1);
			return;
		}
	}
}

function removeAllEvents(id) {
	for (var i = 0; i < calEvents.length; i++) {
		if (baseId(calEvents[i].id) == baseId(id)) {
			calEvents.splice(i,1);
			i--;
		}
	}
}

var calEvents = [];

var isIE = document.all ? true : false;
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
	posX = _x;
	posY = _y;
	if (isRect(posX,posY,divX,divY,700,640)) {
		inside = true;
	} else {
		inside = false;
	}

	if (dragging) {
		dragH = posY - divY - dragY;
		if (dragH < 10) dragH = 10;
		rH = Math.round(dragH / 10);
		dragH = rH * 10;
		if (dragY + dragH > 640) {
			dragH = 640 - dragY;
		}
		drawBox();
	}
}

function html(id) {
	return document.getElementById(id);
}

function baseId(id) {
	var idp = id.split('#');
	return idp[0];
}

function mDown(e) {
<?php if (TFD_HAS_PERMS('admin','use')) { ?>
	if (formDisplaying) return true;
	if (html('dragEvent') && isRect(posX - divX,posY - divY,drawnX,drawnY,100,dragH)) {
		return true;
	}
	if (display == 'week') {
		for (var i = 0;i<calEvents.length;i++) {
			if (calEvents[i].checkCollision(posX - divX,posY - divY)) return true;
		}
		if (isRect(posX,posY,divX,divY,700,640)) {
			dragging = true;
			dragX = posX - divX;
			dragY = posY - divY;
			var rX = dragX % 100;
			var rY = Math.round(dragY / 10);
			dragX -= rX;
			dragY = rY * 10;
		}
	}
	return false;
<?php } else { ?>
	return true;
<?php } ?>
}

function mUp(e) {
	dragging = false;
	if (html('dragEvent')) {
		html('dragEvent').parentNode.removeChild(html('dragEvent'));
		var ev = new CalEvent();
		ev.x = drawnX + 15;
		ev.y = drawnY;
		ev.w = 85;
		ev.h = dragH;
		ev.date = getDateForX(drawnX);
		ev.startTime = getTimeForY(drawnY);
		ev.endTime = getTimeForY(drawnY + dragH);
		//alert(dump(ev));
		calEvents.push(ev);
		drawEvents();
		open_form(ev);
	}
}

function drawEvents() {
	packEvents();
	calEvents = calEvents.sort(function(e1,e2) {
	      if (e1.y < e2.y) return -1;
	      if (e1.y > e2.y) return 1;
	      if (e1.y + e1.h < e2.y + e2.h) return -1;
	      if (e1.y + e1.h > e2.y + e2.h) return 1;
	      return 0;
	});
	html('events').innerHTML = '';
	if (display == 'week') {
		for (var i = 0; i < calEvents.length; i++) {
			if (getXForDate(calEvents[i].date) == -1) continue;
			if (viewJustMe && calEvents[i].user_id != myUserId) continue;
			drawEvent(calEvents[i]);
		}
	} else if (display == 'month') {
		var sd = parseDate(calStartDate);
		var dx = parseDate(calStartDate);
		var mp = month.split('-');
		var eom = false;
		var day = 0;
		while (!eom || day != 0) {
			var mx = getXForDate(dx.format("yyyy-mm-dd"));
			var my = getYForDate(dx.format("yyyy-mm-dd"));
			var events = [];
			//for (var i = 0; i < 4; i++) {
				events[0] = getEventsForDay(dx.format("yyyy-mm-dd"));
				if (events[0].length > 0)
					html('events').innerHTML = html('events').innerHTML + '<div name="dayEvents'+i+'" class="'+EVENT_STYLE[0]+'" style="position:absolute;left:'+(mx+mvEventOffsets[0][0])+'px;top:'+(my+mvEventOffsets[0][1])+'px;width:20px;height:20px;-moz-border-radius:3px;border-radius:3px;" align="center">'+events[0].length+'</div>';
			//}
			day++;
			if (day == 7) day = 0;
			if ((parseInt(dx.format("m"),10) > parseInt(mp[1],10) && dx.format("yyyy") == mp[0]) || parseInt(dx.format("yyyy")) > parseInt(mp[0])) eom = true;
			dx.setDate(dx.getDate() + 1);
		}
	}
}

// Takes a date string yyyy-mm-dd and optional event type
function getEventsForDay(dt) {
	//et = typeof et !== 'undefined' ? et : -1;
	var retArr = [];
	for (var i = 0; i < calEvents.length; i++) {
		//if (et != -1 && calEvents[i].event_type != et) continue;
		if (viewJustMe && calEvents[i].user_id != myUserId) continue;
		if (calEvents[i].date == dt) retArr.push(calEvents[i]);
	}
	return retArr;
}

function toggleJustMe() {
	viewJustMe = !viewJustMe;
	drawEvents();
	if (viewJustMe) {
		html('just_me').innerHTML = '<a href="#" onClick="toggleJustMe();">Show All</a>';
	} else {
		html('just_me').innerHTML = '<a href="#" onClick="toggleJustMe();">Show Mine</a>';
	}
}

function packEvents() {
	var day = parseDate(calStartDate);
	for (var i = 0; i < 7; i++) {
		var columns = [];
		var lastEventEnding = null;
		var events = getEventsForDay(day.format('yyyy-mm-dd'));
		events = events.sort(function(e1,e2) {
		      if (e1.y < e2.y) return -1;
		      if (e1.y > e2.y) return 1;
		      if (e1.y + e1.h < e2.y + e2.h) return -1;
		      if (e1.y + e1.h > e2.y + e2.h) return 1;
		      return 0;
		});

		for (var j = 0; j < events.length; j++) {
			if (lastEventEnding !== null && events[j].y >= lastEventEnding) {
				PackColumns(columns);
				columns = [];
				lastEventEnding = null;
			}

			var placed = false;
			for (var k = 0; k < columns.length; k++) {
				var col = columns[k];
				if (!col[col.length - 1].collidesWith(events[j])) {
					col.push(events[j]);
					placed = true;
					break;
				}
			}

			if (!placed) {
				columns.push([events[j]]);
			}

			if (lastEventEnding == null || events[j].y + events[j].h > lastEventEnding) {
				lastEventEnding = events[j].y + events[j].h;
			}
		}

		if (columns.length > 0) {
			PackColumns(columns);
		}

		day.setDate(day.getDate() + 1);
	}
}

function PackColumns(columns) {
	var n = columns.length;
	for (var i = 0; i < n; i++) {
		var col = columns[i];
		for (var j = 0; j < col.length; j++) {
			col[j].column = i;
		}
	}
}

function drawEvent(ev) {
	var className = 'noselect nooverflow';
	var hoursClass = '';
	if (ev.id.indexOf("0#") >= 0) {
			className += " event_new";
			hoursClass = "event_new_hours";
	} else {
			className += " event_hours";
			hoursClass = "event_hours_hours";
	}
	var rec = '';
	if (ev.recurring) rec = 'border-left:2px solid red;border-bottom:2px solid red;';
	var content = html('events').innerHTML;
	if (ev.column > 4) {
		ev.column = 0;
		ev.right_offset = 1;
	} else {
		ev.right_offset = 0;
	}
	if (ev.column > 1) {
		var hours = getTimeForY(ev.y)+' - ';
	} else {
		var hours = getTimeForY(ev.y)+' - '+getTimeForY(ev.y + ev.h);
	}
	var newDiv = '<div name="event" id="event_'+ev.id+'" class="'+className+'" style="position:absolute;left:'+(ev.x + (ev.column * 10))+';top:'+ev.y+';width:'+(ev.w - (ev.column * 10) - (ev.right_offset * 10) - 2)+';height:'+(ev.h - 2)+';-moz-border-radius: 5px;border-radius: 5px;z-index:2;'+rec+'"><div id="event_'+ev.id+'_hours" class="'+hoursClass+'" style="font-size:12px;width:100%;height:15px;font-weight:bold;" align="center">'+hours+'</div>';
	newDiv += '<div style="font-family:Tahoma;font-size:6pt;" align="center">';
	if (parseInt(ev.user_id,10) > 0) {
		newDiv += '<b>Assigned To:</b><br>';
		if (users[parseInt(ev.user_id,10)]) {
			newDiv += users[parseInt(ev.user_id,10)]+"<br>";
		} else {
			newDiv += "<i>Invalid User ID</i><br>";
		}
	}
	newDiv += '</div>';
	newDiv += '</div>';
	html('events').innerHTML = content + newDiv;
}

function mOpen() {
	if (!inside) return true;
	if (display == 'month') {
		var dx = parseDate(calStartDate);
		for (var i = 0; i < 43; i++) {
			var mx = getXForDate(dx.format("yyyy-mm-dd"));
			var my = getYForDate(dx.format("yyyy-mm-dd"));
			if (isRect(posX - divX,posY - divY,mx,my,100,100)) {
				for (var j = 0; j < mvEventOffsets.length; j++) {
					if (isRect(posX - divX,posY - divY,mx + mvEventOffsets[j][0],my + mvEventOffsets[j][1],20,20)) {
						var events = getEventsForDay(dx.format("yyyy-mm-dd"),j);
						if (events.length == 0) continue;
						open_event_picker(dx.format("yyyy-mm-dd"),events,j);
						return true;
					}
				}
				var events = getEventsForDay(dx.format("yyyy-mm-dd"));
				if (events.length == 0) continue;
				open_event_picker(dx.format("yyyy-mm-dd"),events);
			}
			dx.setDate(dx.getDate() + 1);
		}
	} else if (display == 'week') {
		for (var i = calEvents.length;i>0;i--) {
			if (calEvents[i-1].checkCollision(posX - divX,posY - divY)) {
				//alert(dump(calEvents[i-1]));
				open_form(calEvents[i-1]);
				return false;
			}
		}
	}
	return false;
}

function drawBox() {
	boxExists = true;
	drawnX = dragX;
	drawnY = dragY;
	if (html('dragEvent')) {
		html('dragEvent').style.height = dragH;
		html('dragHours').innerHTML = getTimeForY(dragY) +' - '+getTimeForY(dragY + dragH);
	} else {
		var content = html('events').innerHTML;
		html('events').innerHTML = content + '<div name="event" id="dragEvent" class="noselect nooverflow event_new" style="position:absolute;left:'+dragX+';top:'+dragY+';width:'+100+';height:'+dragH+';-moz-border-radius: 5px;border-radius: 5px;z-index:2;" align="center"><div id="dragHours" class="event_new_hours" style="font-size:12px;width:100%;height:15px;font-weight:bold;">'+getTimeForY(dragY)+' - '+getTimeForY(dragY + dragH)+'</div></div>';
	}
}

function isRect(x,y,rx,ry,rw,rh) {
	if (x < rx) return false;
	if (x > rx + rw) return false;
	if (y < ry) return false;
	if (y > ry + rh) return false;
	return true;
}

function isRectRect(x1,y1,w1,h1,x2,y2,w2,h2) {
	if (x1 >= x2 + w2) return false;
	if (x1 + w1 <= x2) return false;
	if (y1 >= y2 + h2) return false;
	if (y1 + h1 <= y2) return false;
	return true;
}

$(document).ready(calendarInit);
var counter = 1;
var boxExists = false;
var divX,divY;
var posX,posY;
var inside = false;
var dragging = false;
var drawnX,drawnY;
var dragX, dragY, dragH;
function calendarInit() {
	html('container').onmousemove = mouseMoved;
	html('container').onmousedown = mDown;
	html('container').onmouseup = mUp;
	html('container').ondragstart = function() { return false; };
	calendar.set("start_date");
	calendar.set("r_end_date");

	$( "#slider_start" ).slider({
		value:420,
		min: 420,
		max: 1380,
		step: 15,
		smooth: false,
		slide: function( event, ui ) {
			var hour = Math.floor(ui.value / 60);
			var minute = ''+ (ui.value - (hour * 60));
			hour = '' + hour;
			if (hour.length == 1) hour = '0'+hour;
			if (minute.length == 1) minute = '0'+minute;
			ChangeSelectByValue('start_time',hour+':'+minute,false);
		}
	});

	$( "#slider_end" ).slider({
		value:420,
		min: 420,
		max: 1380,
		step: 15,
		smooth: false,
		slide: function( event, ui ) {
			var hour = Math.floor(ui.value / 60);
			var minute = ''+ (ui.value - (hour * 60));
			hour = '' + hour;
			if (hour.length == 1) hour = '0'+hour;
			if (minute.length == 1) minute = '0'+minute;
			ChangeSelectByValue('end_time',hour+':'+minute,false);
		}
	});

	var my_div = html('container');

	try {
		box = my_div.getBoundingClientRect();
	}
	catch(e)
	{}

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

	updateWeekView();

	ajax("refresh",null);
}

/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */

var dateFormat = function () {
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) val = "0" + val;
			return val;
		};

	// Regexes and supporting functions are cached through closure
	return function (date, mask, utc) {
		var dF = dateFormat;

		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date;
		if (isNaN(date)) throw SyntaxError("invalid date");

		mask = String(dF.masks[mask] || mask || dF.masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var	_ = utc ? "getUTC" : "get",
			d = date[_ + "Date"](),
			D = date[_ + "Day"](),
			m = date[_ + "Month"](),
			y = date[_ + "FullYear"](),
			H = date[_ + "Hours"](),
			M = date[_ + "Minutes"](),
			s = date[_ + "Seconds"](),
			L = date[_ + "Milliseconds"](),
			o = utc ? 0 : date.getTimezoneOffset(),
			flags = {
				d:    d,
				dd:   pad(d),
				ddd:  dF.i18n.dayNames[D],
				dddd: dF.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dF.i18n.monthNames[m],
				mmmm: dF.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
}();

// Some common format strings
dateFormat.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
	dayNames: [
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	monthNames: [
		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]
};

// For convenience...
Date.prototype.format = function (mask, utc) {
	return dateFormat(this, mask, utc);
};

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

function getMinutesForTime(t) {
	var tp = t.split(':');
	var hour = parseInt(tp[0],10);
	var minute = parseInt(tp[1],10);
	return (hour * 60) + minute;
}

function updateSlider(s) {
	time = html(s+'_time').options[html(s+'_time').selectedIndex].value;
	$('#slider_'+s).slider("value", getMinutesForTime(time));
}

function updateWeekView() {
	var dt = parseDate(calStartDate);
	html('date_range').innerHTML = dt.format("d mmm yyyy");
	if (display == 'week') {
		for (var i = 1; i < 8; i++) {
			html('day'+i).innerHTML = dt.format("ddd, mmmm d");
			if (i == 7) {
				html('date_range').innerHTML = html('date_range').innerHTML + " - " + dt.format("d mmm yyyy");
			}
			dt.setDate(dt.getDate()+1);
		}
	} else if (display == 'month') {
		html('day1').innerHTML = 'Monday';
		html('day2').innerHTML = 'Tuesday';
		html('day3').innerHTML = 'Wednesday';
		html('day4').innerHTML = 'Thursday';
		html('day5').innerHTML = 'Friday';
		html('day6').innerHTML = 'Saturday';
		html('day7').innerHTML = 'Sunday';
		html('date_range').innerHTML = parseDate(month).format("mmmm yyyy");
	}
}

function changeForm() {
	html("eventForm").className=EVENT_FORM[0];
}

var formDisplaying = null;

function open_form(ev) {
	formDisplaying = ev;
	ChangeSelectByValue('user_id',''+ev.user_id,false);
	if (ev.recurring) {
		html('recurring_y').checked = true;
		html('recurring_n').disabled = 'disabled';
		html('rec_box').style.display='';
		var rec = ev.rec_type.split('_');
		ChangeSelectByValue('r_type',rec[0],false);
		ChangeSelectByValue('r_length',rec[1],false);
		html('r_end_date').value = ev.rec_endDate;
		if (html('deleteAll')) html('deleteAll').style.display = '';
		if (html('saveAll')) html('saveAll').style.display = '';
	} else {
		html('recurring_n').checked = true;
		html('recurring_n').disabled = '';
		html('rec_box').style.display='none';
		ChangeSelectByValue('r_type','day',false);
		ChangeSelectByValue('r_length','1',false);
		html('r_end_date').value = '';
		if (html('deleteAll')) html('deleteAll').style.display = 'none';
		if (html('saveAll')) html('saveAll').style.display = 'none';
	}
	ChangeSelectByValue('start_time',''+ev.startTime,false);
	ChangeSelectByValue('end_time',''+ev.endTime,false);
	updateSlider('start');
	updateSlider('end');
	html('start_date').value = ev.date;

	html('eventForm').className = EVENT_FORM[0];

	html('eventForm').style.display = '';
}

function close_form() {
	if (baseId(formDisplaying.id) == '0') {
		formDisplaying.remove();
		drawEvents();
	}
	formDisplaying = null;
	html('eventForm').style.display = 'none';
}

function save_form() {
	if (formDisplaying) {
		var ev = formDisplaying;
		ev.user_id = html('user_id').options[html('user_id').selectedIndex].value;
		ev.event_type = 0;
		ev.startTime = html('start_time').options[html('start_time').selectedIndex].value;
		ev.endTime = html('end_time').options[html('end_time').selectedIndex].value;

		if (getMinutesForTime(ev.endTime) < getMinutesForTime(ev.startTime)) {
			alert("End time cannot precede start time.");
			return;
		}
		if (ev.endTime == ev.startTime) {
			alert("Events must be at least 15 minutes long.");
			return;
		}
		ev.date = html('start_date').value;
		ev.recurring = html('recurring_y').checked;
		if (ev.recurring) {
			ev.rec_type = html('r_type').options[html('r_type').selectedIndex].value +'_'+ html('r_length').options[html('r_length').selectedIndex].value;
			ev.rec_endDate = html('r_end_date').value;
		} else {
			ev.rec_type = '';
			ev.rec_endDate = '';
		}

		if (formDisplaying.rec_type != ev.rec_type || formDisplaying.rec_endDate != ev.rec_endDate) {
			alert("You must use 'Save All' to change recurrence settings.");
			return;
		}

		ev.x = getXForDate(ev.date) + 15;
		ev.y = getYForTime(ev.startTime);
		ev.w = 85;
		ev.h = getYForTime(ev.endTime) - ev.y;
		if (ev.recurring && ev.rec_endDate == '') {
			alert('You must enter an end date for the recurring event.');
			return;
		}
		ev.remove();
		calEvents.push(ev);
		drawEvents();
		formDisplaying = null;
		html('eventForm').style.display = 'none';
		if (baseId(ev.id) == '0') {
			ajax('new',ev);
		} else {
			ajax('save',ev);
		}
	}
}

function confirm_action(text,func) {
	var selection = confirm(text);
	if (!selection) return;
	var getType = {};
	if (func && getType.toString.call(func) == '[object Function]') func();
}

function delete_event() {
	if (!formDisplaying) return;
	if (baseId(formDisplaying.id) == '0') {
		close_form();
		return;
	}
	ajax("delete",formDisplaying);
	formDisplaying.remove();
	drawEvents();
	close_form();
}

function delete_all() {
	if (!formDisplaying) return;
	if (baseId(formDisplaying.id) == '0') {
		close_form();
		return;
	}
	ajax("delete_all",formDisplaying);
	removeAllEvents(formDisplaying.id);
	drawEvents();
	close_form();
}

function newEvent() {
	var ev = new CalEvent();
	open_form(ev);
}

function find_monday(date) {
	var dt = parseDate(date);
	var diff = (dt.getDay() + 6) % 7;
	var d = new Date(dt - diff *24*60*60*1000);
	return d.format("yyyy-mm-dd");
}

// Switch between week and month views
function switch_view(type) {
	switch (type) {
		case "week":
			html('events').innerHTML = '';
			html('month_lines').innerHTML = '';
			html('container').style.height = 640;
			html('times').style.height = html('container').style.height;
			var hlines = document.getElementsByName('hline');
			for (var i = 0; i < hlines.length; i++) {
				hlines[i].style.display = '';
			}
			var tbs = document.getElementsByName('timebox');
			for (var i = 0; i < tbs.length; i++) {
				tbs[i].style.display = '';
			}
			display = 'week';
			var dps = month.split("-");
			var today = new Date();
			if (dps[0] == today.format("yyyy") && dps[1] == today.format("mm")) {
				calStartDate = find_monday(today.format("yyyy-mm-dd"));
			} else {
				calStartDate = find_monday(calStartDate);
			}
			updateWeekView();
			html('switch_to').innerHTML = '<a href="#" onClick="switch_view(\'month\');">Month View</a>';
			ajax("refresh",null);
			break;
		case "month":
			html('events').innerHTML = '';
			var hlines = document.getElementsByName('hline');
			for (var i = 0; i < hlines.length; i++) {
				hlines[i].style.display = 'none';
			}
			var tbs = document.getElementsByName('timebox');
			for (var i = 0; i < tbs.length; i++) {
				tbs[i].style.display = 'none';
			}
			var dps = calStartDate.split("-");
			month = dps[0]+"-"+dps[1]+"-01";
			calStartDate = find_monday(dps[0]+"-"+dps[1]+"-01");
			display = 'month';
			drawMonthLines();
			updateWeekView();
			html('switch_to').innerHTML = '<a href="#" onClick="switch_view(\'week\');">Week View</a>';
			ajax("refresh",null);
			break;
	}
}

function drawMonthLines() {
	html('month_lines').innerHTML = '';
	var sd = parseDate(calStartDate);
	var dx = parseDate(calStartDate);
	var mp = month.split("-");
	var eom = false;
	var day = 0;
	while (!eom || day != 0) {
		var mx = getXForDate(dx.format("yyyy-mm-dd"));
		var my = getYForDate(dx.format("yyyy-mm-dd"));
		var date = dx.format("dd");
		var color = "#000";
		if (dx.format("mm",10) == mp[1]) color = "#000";
		else color = "#999";
		//alert("date:"+dx.format("yyyy-mm-dd")+"dx-format-m:"+dx.format("mm",10)+" :: mp1:"+mp[1]);
		if (dx.format("yyyy-mm-dd") == (new Date()).format("yyyy-mm-dd")) color = "#F00";
		date = '<div style="position:absolute;top:3px;right:3px;font-family:Tahoma;font-size:9pt;font-weight:bold;color:'+color+';">'+date+'</div>';
		html('month_lines').innerHTML = html('month_lines').innerHTML + '<div name="mline" style="position:absolute;border:1px solid #A4BED4;left:'+mx+'px;top:'+my+'px;width:100px;height:100px;">'+date+'</div>';
		day++;
		if (day == 7) day = 0;
		if ((parseInt(dx.format("m"),10) > parseInt(mp[1],10) && dx.format("yyyy") == mp[0]) || parseInt(dx.format("yyyy")) > parseInt(mp[0])) eom = true;
		dx.setDate(dx.getDate() + 1);
	}
	html('container').style.height = getYForDate(dx.format("yyyy-mm-dd"));
	html('times').style.height = html('container').style.height;
	//alert("EOM true.\n\ndx.format('m') = "+parseInt(dx.format("m"),10)+"\nmp[1] = "+parseInt(mp[1],10)+"\n\ndx.format('yyyy') = "+parseInt(dx.format("yyyy"))+"\nmp[0] = "+parseInt(mp[0]));
}

function calendar_next() {
	if (ajaxActive) return;
	var dt = parseDate(calStartDate);
	if (display == 'week') {
		dt.setDate(dt.getDate() + 7);
		calStartDate = dt.format("yyyy-mm-dd");
	} else if (display == 'month') {
		var dps = month.split("-");
		dps[0] = parseInt(dps[0],10);
		dps[1] = parseInt(dps[1],10);
		dps[1]++;
		if (dps[1] == 13) {
			dps[1] = 1;
			dps[0]++;
		}
		calStartDate = find_monday(dps[0]+"-"+dps[1]+"-01");
		month = dps[0]+"-"+dps[1]+"-01";
		drawMonthLines();
	}
	updateWeekView();
	drawEvents();
	ajax("refresh",null);
}

function calendar_prev() {
	if (ajaxActive) return;
	var dt = parseDate(calStartDate);
	if (display == 'week') {
		dt.setDate(dt.getDate() - 7);
		calStartDate = dt.format("yyyy-mm-dd");
	} else if (display == 'month') {
		var dps = month.split("-");
		dps[0] = parseInt(dps[0],10);
		dps[1] = parseInt(dps[1],10);
		dps[1]--;
		if (dps[1] == 0) {
			dps[1] = 12;
			dps[0]--;
		}
		calStartDate = find_monday(dps[0]+"-"+dps[1]+"-01");
		month = dps[0]+"-"+dps[1]+"-01";
		drawMonthLines();
	}
	updateWeekView();
	drawEvents();
	ajax("refresh",null);
}

function open_event_picker(date,events,et) {
	et = typeof et !== 'undefined' ? et : -1;
	var dt = parseDate(date);
	events = events.sort(function(e1,e2) {
	      if (e1.y < e2.y) return -1;
	      if (e1.y > e2.y) return 1;
	      if (e1.y + e1.h < e2.y + e2.h) return -1;
	      if (e1.y + e1.h > e2.y + e2.h) return 1;
	      return 0;
	});
	while (html('eventPicker_table').rows.length > 1) {
		html('eventPicker_table').deleteRow(html('eventPicker_table').rows.length - 1);
	}
	for (var i = 0; i < events.length; i++) {
		var row = html('eventPicker_table').insertRow(html('eventPicker_table').rows.length);
		row.align = 'center';
		var cell = row.insertCell(0);
		cell.innerHTML = '<input type="radio" name="eventPicker_event" value="'+events[i].id+'">';
		cell = row.insertCell(1);
		cell.innerHTML = users[parseInt(events[i].user_id,10)];
		cell = row.insertCell(2);
		cell.innerHTML = events[i].startTime;
		cell = row.insertCell(3);
		cell.innerHTML = events[i].endTime;
		cell = row.insertCell(4);
		cell.innerHTML = events[i].recurring ? "Yes":"No";
	}
	var etd = et == -1 ? '' : ' ('+EVENT_TYPE[et]+')';
	html('eventPicker_heading').innerHTML = 'Events on '+dt.format("ddd, d mmm yyyy")+etd;
	html('eventPickerForm').style.display = '';
}

function close_event_picker() {
	html('eventPickerForm').style.display = 'none';
}

function pick_event() {
	var buttons = document.getElementsByName('eventPicker_event');
	for (var i = 0; i < buttons.length; i++) {
		if (buttons[i].checked) {
			for (var j = 0; j < calEvents.length; j++) {
				if (calEvents[j].id == buttons[i].value) {
					close_event_picker();
					open_form(calEvents[j]);
					return;
				}
			}
		}
	}
	close_event_picker();
}

</script>
<style type="text/css">@import url('lbform.css');</style>
<style type="text/css">@import url('default.css');</style>

<div id="eventPickerForm" style="display:none;position:absolute;left:100px;top:50px;width:550px;height:300px;z-index:14;font:13px Tahoma;text-align:left;border-radius:10px;" class="blueForm">
	<div id="eventPicker_heading" align="center" style="position:absolute;top:5px;width:100%;font-size:13pt;"></div>

	<div class="clear"><br><br></div>

	<div class="relative" style="padding:7px;font-size:9pt;height:200px;overflow-y:scroll;" align="center">
	<table id="eventPicker_table" border="0" width="95%" style="font-size:9pt;">
		<tr align="center">
			<td><b>Select</b></td>
			<td><b>Assigned To</b></td>
			<td><b>Start Time</b></td>
			<td><b>End Time</b></td>
			<td><b>Recurring</b></td>
		</tr>
	</table>
	</div>
	<div class="clear"><br></div>

	<div align="center"><input type="button" onClick="pick_event();" value="Edit Event"> &nbsp;&nbsp;&nbsp; <a href="#" onClick="close_event_picker();">Cancel</a></div>
</div>

<div id="eventForm" style="display:none;position:absolute;width:500px;height:250px;left:80px;top:40px;z-index:10;font:13px Tahoma;text-align:left;border-radius:10px;" class="blueForm">
<form name="event_form">

    <div class="absolute" style="font-size:10px;font-style:oblique;margin:3 0 0 5;">Logged in as <b><?php echo $USER["firstname"] ." ". $USER["lastname"]; ?></b></div>

    <div class="clear"><br><br></div>

    <div class="relative" style="padding:7px;">
      <div class="floatL">

        <div class="lboxHead clearL">User:</div>
        <div class="lboxBody">
          <select name="user_id" id="user_id" style="width:150px;"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>>
          <option value="0">Nobody</option>
<?php
	foreach($USERS as $var){
		if($var["is_disabled"])continue;
		echo '          <option value="'.$var["id"].'">'.$var["firstname"].' '.$var["lastname"].'</option>\n';
	}

?>
          </select></div>
      </div>

      <div class="lboxHead" id="rec_dateText">Recurring</div>
      <div class="lboxBody" id="rec_dateBody">
        <input type="radio" id="recurring_y" onclick="html('rec_box').style.display=''" name="recurring" value="1"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>>Yes
        <input type="radio" id="recurring_n" onclick="html('rec_box').style.display='none'" name="recurring" CHECKED value="0"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>>No
      </div>

      <div class="clearL"><br></div>

     <div class="floatL">
      <div class="floatL">
        <div class="lboxHead" id="start_dateText">Start Time</div>
        <div class="lboxBody"><select name="start_time" id="start_time" onChange="updateSlider('start');"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>><?php pTime("07","00","2300"); ?></select><input type="text" name="start_date" value="" size="9" id="start_date"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>></div>
		<?php if (TFD_HAS_PERMS('admin','use')) { ?>
        <div class="clearL floatL" style="margin:0px 0px 0px 22px;width:150px;" id="slider_start"></div>
        <?php } ?>
      </div>

      <div class="clearL"><br></div>

      <div class="floatL">
        <div class="lboxHead" id="end_dateText">End Time</div>
        <div class="lboxBody"><select name="end_time" id="end_time" onChange="updateSlider('end');"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>><?php pTime("07","00","2300"); ?></select><!-- input type="text" onclick="show_minical('end_date','end_cal');" name="end_date" value="" size="9" id="end_date"><div class="" id="end_cal" style=""></div --></div>
		<?php if (TFD_HAS_PERMS('admin','use')) { ?>
        <div class="clearL floatL" style="margin:0px 0px 0px 22px;width:150px;" id="slider_end"></div>
        <?php } ?>
      </div>
     </div>
     <div class="floatL" id="rec_box" style="margin-left:10px;display:none;">
        <div class="floatL">
          <div class="lboxHead" style="width:110px;">Recurring Type</div>
          <div class="lboxBody">
            <select name="r_type" id="r_type"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>>
              <option value="day">Day</option>
              <option value="week">Week</option>
              <option value="month">Month</option>
            </select>
          </div>
        </div>
        <div class="clearL">
          <div class="lboxHead" style="width:110px;">Frequency</div>
          <div class="lboxBody">
            <select name="r_length" id="r_length"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>>
              <option value="1">1</option>
              <option value="2">2</option>
              <option value="3">3</option>
              <option value="4">5</option>
              <option value="6">6</option>
            </select>
          </div>
        </div>
        <div class="clearL floatL">
          <div class="lboxHead" style="width:110px;">Until Date</div>
          <div class="lboxBody"><input type="text" name="r_end_date" value="" size="9" id="r_end_date"<?php if (!TFD_HAS_PERMS('admin','use')) { echo " disabled=\"disabled\""; } ?>></div>
        </div>
      </div>

      <div class="clearL"><br></div>
      <!--  div class="lboxHead" id="rec_dateText">Recurring</div>
      <div class="lboxBody" id="rec_dateBody">
        <input type="radio" id="recurring_y" onclick="html('rec_box').style.display=''" name="recurring" value="1">Yes
        <input type="radio" id="recurring_n" onclick="html('rec_box').style.display='none'" name="recurring" CHECKED value="0">No
      </div -->

      <div class="clearL" id="rec_dateClear"><br></div>

	</div>
    <div class="absolute" style="bottom:5px;width:100%;">
      <div class="relative center" style="width:100%;">
      	<?php if (TFD_HAS_PERMS('admin','use')) { ?>
        <input type="button" name="save" value="Save" id="save" class="floatL lightBoxButton" style="width:100px;margin:0 0 5 12;" onClick="save_form()">
        <input type="button" name="saveAll" value="Save All" id="saveAll" class="absolute lightBoxButton" style="width:100px;left:12;bottom:5;" onClick="save_all()">
        <input type="button" name="close" value="Close" id="close" class="floatL lightBoxButton" style="width:100px;margin:0 0 5 3;" onClick="close_form()">
        <input type="button" name="delete" value="Delete" id="delete" class="absolute lightBoxButton" style="width:100px;right:12;" onClick="confirm_action('Event will be deleted permanently. Are you sure?',delete_event)">
        <input type="button" name="deleteAll" value="Delete All" id="deleteAll" class="absolute lightBoxButton" style="width:100px;right:12;bottom:5;display:none;" onClick="confirm_action('All instances of this recurring event will be deleted. Are you sure?',delete_all)">
		<?php } else { ?>
		<input type="button" name="close" value="Close" id="close" class="floatR lightBoxButton" style="width:100px;margin-right:5px;" onClick="close_form()">
		<?php } ?>
      </div>
  	</div>
</form>
</div>
<div id="calendarTop" style="position:absolute;left:0px;top:0px;width:752px;height:65px;background-color:#EBEBEB;">
	<div id="schedulesHeading" style="position:absolute;top:0px;width:100%;font-family:Tahoma;font-size:32pt;color:#AAAAAA;" align="center">Timesheets</div>
	<div id="status" style="position:absolute;top:5px;right:5px;width:200px;font-family:Tahoma;font-size:10pt;" align="right">Loading...</div>
	<div class="prev_button" style="border:1px solid #000;position:absolute;left:15px;bottom:2px;" onclick="calendar_prev();"></div>
	<div class="next_button" style="border:1px solid #000;position:absolute;left:50px;bottom:2px;" onclick="calendar_next();"></div>
	<div id="date_range" style="position:absolute;left:100px;bottom:3px;font-family:Tahoma;font-weight:bold;font-size:9pt;"></div>
	<div id="just_me" style="position:absolute;left:570px;top:45px;width:80px;height:25px;font-family:Tahoma;font-size:9pt;" align="center"><a href="#" onClick="toggleJustMe();"><?php echo (TFD_HAS_PERMS('admin','use') ? "Show Mine":"Show All"); ?></a></div>
	<div id="switch_to" style="position:absolute;left:650px;top:45px;width:80px;height:25px;font-family:Tahoma;font-size:9pt;" align="center"><a href="#" onClick="switch_view('month');">Month View</a></div>
</div>
<div id="dateBar" style="background-color:#B9CCE4;position:absolute;left:0px;top:65px;width:752px;height:20px;font-family:Tahoma;font-size:8pt;">
	<div id="day1" style="position:absolute;left:50px;top:3px;width:100px;" align="center"></div>
	<div id="day2" style="position:absolute;left:150px;top:3px;width:100px;" align="center"></div>
	<div id="day3" style="position:absolute;left:250px;top:3px;width:100px;" align="center"></div>
	<div id="day4" style="position:absolute;left:350px;top:3px;width:100px;" align="center"></div>
	<div id="day5" style="position:absolute;left:450px;top:3px;width:100px;" align="center"></div>
	<div id="day6" style="position:absolute;left:550px;top:3px;width:100px;" align="center"></div>
	<div id="day7" style="position:absolute;left:650px;top:3px;width:100px;" align="center"></div>
</div>
<div id="newEventButtonBar" class="newEventBar-gradient" style="border:1px solid #A4BED4;background-color:#CFE4FE;position:absolute;left:0px;top:85px;width:750px;height:20px;">
<?php if (TFD_HAS_PERMS('admin','use')) { ?>
	<div id="newEventButton" align="center" style="position:absolute;top:2px;width:100%;"><a href="#" class="newEventButton" onClick="newEvent();">New Hours</a></div>
<?php } ?>
</div>
<div id="times" class="time-gradient" style="background-color:#B2C6E1;position:absolute;left:0px;top:105px;width:50px;height:640px;font-family:Tahoma;font-size:9pt;">
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
<div id="container" style="border:1px solid #A4BED4;background-color:#FFFFFF;position:absolute;left:50px;top:105px;width:700px;height:640px;z-index:0;" ondblclick="mOpen();">
<div id="line1" style="background-color:#A4BED4;position:absolute;left:100px;top:0px;width:1px;height:100%;z-index:2;"></div>
<div id="line2" style="background-color:#A4BED4;position:absolute;left:200px;top:0px;width:1px;height:100%;z-index:2;"></div>
<div id="line3" style="background-color:#A4BED4;position:absolute;left:300px;top:0px;width:1px;height:100%;z-index:2;"></div>
<div id="line4" style="background-color:#A4BED4;position:absolute;left:400px;top:0px;width:1px;height:100%;z-index:2;"></div>
<div id="line5" style="background-color:#A4BED4;position:absolute;left:500px;top:0px;width:1px;height:100%;z-index:2;"></div>
<div id="line6" style="background-color:#A4BED4;position:absolute;left:600px;top:0px;width:1px;height:100%;z-index:2;"></div>
<div name="hline" id="hline1" style="background-color:#ECEEF4;position:absolute;left:0px;top:20px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline2" style="background-color:#ECEEF4;position:absolute;left:0px;top:60px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline3" style="background-color:#ECEEF4;position:absolute;left:0px;top:100px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline4" style="background-color:#ECEEF4;position:absolute;left:0px;top:140px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline5" style="background-color:#ECEEF4;position:absolute;left:0px;top:180px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline6" style="background-color:#ECEEF4;position:absolute;left:0px;top:220px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline7" style="background-color:#ECEEF4;position:absolute;left:0px;top:260px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline8" style="background-color:#ECEEF4;position:absolute;left:0px;top:300px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline9" style="background-color:#ECEEF4;position:absolute;left:0px;top:340px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline10" style="background-color:#ECEEF4;position:absolute;left:0px;top:380px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline11" style="background-color:#ECEEF4;position:absolute;left:0px;top:420px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline12" style="background-color:#ECEEF4;position:absolute;left:0px;top:460px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline13" style="background-color:#ECEEF4;position:absolute;left:0px;top:500px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline14" style="background-color:#ECEEF4;position:absolute;left:0px;top:540px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline15" style="background-color:#ECEEF4;position:absolute;left:0px;top:580px;width:700px;height:20px;z-index:1;"></div>
<div name="hline" id="hline16" style="background-color:#ECEEF4;position:absolute;left:0px;top:620px;width:700px;height:20px;z-index:1;"></div>
<div id="month_lines"></div>
<div id="events"></div>
</div>

</body>
</html>
