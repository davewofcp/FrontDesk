<?php

function display_header() {
	global $USER, $MODULES, $ACTIVE_MODULE, $SESSION;
	$NAVBAR = "<div id=\"navbar\" class=\"navbar\">";
	$i = 0;
	if (!isset($USER)) {
		$NAVBAR .= "<div id=\"nb_login\" class=\"nb_button outlined\" style=\"top:0;\"><font class=\"nb_link\">Please Log In</font></div>\n";
	} else {
		foreach ($MODULES as $this_module) {
			if (!$this_module["in_nav"]) continue;
			if (!TFD_HAS_PERMS($this_module['module'],'use')){
        continue;
      }
			$nb_name = "nb_". $this_module["module"];
			if ($ACTIVE_MODULE == $this_module["module"]) {
				$NAVBAR .= "  <div id=\"".$nb_name."\" class=\"nb_button_sel outlined\" style=\"top:".($i * 30).";\">";
				$NAVBAR .= "  <a href=\"?module=".$this_module["module"]."\" class=\"nb_link\">".$this_module["title"]."</a></div>\n";
			} else {
				$NAVBAR .= "  <div id=\"".$nb_name."\" class=\"nb_button outlined\" style=\"top:".($i * 30).";\"><a href=\"?module=".$this_module["module"]."\" onMouseOver=\"hover('".$nb_name."');\" onMouseOut=\"noHover('".$nb_name."');\" class=\"nb_link\">".$this_module["title"]."</a></div>\n";
			}
			$i++;
		}
		$NAVBAR .= "  <div id=\"nb_logout\" class=\"nb_button outlined\" style=\"top:".($i * 30).";\"><a href=\"login.php?logout=1\" onMouseOver=\"hover('nb_logout');\" onMouseOut=\"noHover('nb_logout');\" class=\"nb_link\">Log Out</a></div>\n";

  $NAVBAR .= "  <div id=\"nb_notepad\" class=\"nb_button sel outlined\" style=\"top:".($i * 30 + (90)).";\">
    <div id=\"notepad\" style=\"display:none;position:fixed;top:50px;margin-left:150px;\"></div>
    <a href=\"#notepad_nomove\" onClick=\"notepad();\" onMouseOver=\"hover('nb_notepad');\" onMouseOut=\"noHover('nb_notepad');\" class=\"nb_link\">Notepad</a>
  </div>\n";

  $NAVBAR .= "  <div id=\"nb_cookieTime\" class=\"nb_button bnone relative\" style=\"width:0px;height:0px;top:".($i * 30 + (120)).";\">

    <div id=\"cookieBox\" style=\"position:fixed;\">
      <div id=\"cookieUpdate\" style=\"display:none;\">Updating...</div>
      Logout Time:
      <div id=\"cookieTime\"></div>
      <div id=\"cookieRefresh\" class=\"absolute\" style=\"left:7;bottom:7;\"><a href=\"#refresh\" onClick=\"cookieReload();\"><img src=\"images/refresh-small-black.png\"></a></div>
    </div>

  </div>\n";

	}

	$NAVBAR .= "</div>\n";

	if (isset($USER)) {
    $cart = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count,SUM(amt * qty) AS sum FROM pos_cart_items WHERE users__id__sale = ". $USER["id"] ." AND is_heading = 0"));
    $inbox = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM messages WHERE users__id__1 = ".$USER["id"]." AND is_read = 0 AND box = 1"));
		$msgs = intval($inbox["count"]);
	}

?><html>
<head>
<meta HTTP-EQUIV="CACHE-CONTROL" CONTENT="NO-CACHE">

<link rel="stylesheet" type="text/css" href="default.css">
<script type="text/javascript">
var html = function(id) { return document.getElementById(id); };
function hover(id) {
	document.getElementById(id).setAttribute("class","nb_button_hover outlined");
}
function noHover(id) {
	document.getElementById(id).setAttribute("class","nb_button outlined");
}
function alertObj(obj){
		alert(JSON.stringify(obj));
}

function lcount(str,num){
    if(document.getElementById(str).value.length<num){
      alert("Enter atleast "+num+" characters in the description.");
      return false;
    }
}

var xmlcust,xmltopissue,xmlnotepad,xmlnotepad_update,xmlcookie;
if (window.XMLHttpRequest) {
  // IE7+, Firefox, Chrome, Opera, Safari
	xmlcust=new XMLHttpRequest();
	xmltopissue=new XMLHttpRequest();
	xmlnotepad=new XMLHttpRequest();
	xmlnotepad_update=new XMLHttpRequest();
	xmlcookie=new XMLHttpRequest();
	xmltasks = new XMLHttpRequest();
} else {
  // IE6, IE5
	xmlcust=new ActiveXObject("Microsoft.XMLHTTP");
	xmltopissue=new ActiveXObject("Microsoft.XMLHTTP");
	xmlnotepad=new ActiveXObject("Microsoft.XMLHTTP");
	xmlnotepad_update=new ActiveXObject("Microsoft.XMLHTTP");
	xmlcookie=new ActiveXObject("Microsoft.XMLHTTP");
	xmltasks=new ActiveXObject("Microsoft.XMLHTTP");
}
xmlcust.onreadystatechange = xmlcustHandler;
xmltasks.onreadystatechange = xmlTasksHandler;

function xmlTasksHandler() {
	if (xmltasks.readyState == 4 && xmltasks.status == 200) {
		var data = eval("("+xmltasks.responseText+")");
		if (!data || !data.action) return;
		switch (data.action) {
			case "error":
				alert(data.error);
				break;
			case "did":
				html('task_descr_'+data.id).style.color = '#008000';
				html('task_'+data.id).checked = true;
				break;
			case "didnt":
				if (data.msg) alert(data.msg);
				html('task_descr_'+data.id).style.color = '';
				html('task_'+data.id).checked = false;
				break;
			default:
				break;
		}
	}
}

function taskClick(id) {
	if (!html("task_"+id)) return;

	html('task_descr_'+id).style.color = '#FFFF00';

	var d = html("task_"+id).checked;
	xmltasks.abort();
	xmltasks.onreadystatechange = xmlTasksHandler;
	xmltasks.open("POST","core/task_ajax.php",true);
	xmltasks.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	var cols = ['task_action','id'];
	if (d) {
		var vals = ['did',id];
	} else {
		var vals = ['didnt',id];
	}
	var params = buildParams(cols,vals);
	xmltasks.send(params);
}

function buildParams(cols,vals) {
	var retArr = [];
	for (var i = 0; i < cols.length; i++) {
		var thisVal = vals[i] || '';
		retArr.push(cols[i]+'='+encodeURIComponent(''+thisVal));
	}
	return retArr.join('&');
}

function xmlcustHandler() {
	if (xmlcust.readyState == 4 && xmlcust.status == 200) {
		document.getElementById("custresultbox").innerHTML = xmlcust.responseText;
		document.getElementById("custresultbox").style.border = "1px solid #120A8F";
		document.getElementById("custresultbox").style.display = "block";
	}
}
xmltopissue.onreadystatechange = function() {
	if (xmltopissue.readyState == 4 && xmltopissue.status == 200) {
		document.getElementById("topissueresultbox").innerHTML = xmltopissue.responseText;
		document.getElementById("topissueresultbox").style.border = "1px solid #120A8F";
		document.getElementById("topissueresultbox").style.display = "block";
	}
}
xmlnotepad.onreadystatechange = function() {
	if (xmlnotepad.readyState == 4 && xmlnotepad.status == 200) {
	 var notepad = document.getElementById("notepad");
	 if(notepad.style.display=="none"){
	   notepad.innerHTML = xmlnotepad.responseText;
     notepad.style.display = "block";
   } else {notepad.style.display = "none";}
	}
}
xmlnotepad_update.onreadystatechange = function() {
	if (xmlnotepad_update.readyState == 4 && xmlnotepad_update.status == 200) {	notepad();}
}

function xmlcookieHandler() {
	if (xmlcookie.readyState == 4 && xmlcookie.status == 200) {
		if(xmlcookie.responseText == '') return;
	var response = JSON.parse(xmlcookie.responseText);
    var min = Math.floor(response.remaining/60);
    var sec = response.remaining % 60;
    var time = min+":"+sec;

    document.getElementById("cookieTime").innerHTML = time;
    document.getElementById("cookieUpdate").style.display = "none";
    countdown(min,sec);
	} else if (xmlcookie.readyState == 4) {
		document.getElementById("cookieUpdate").style.display = "none";
	}
}
xmlcookie.onreadystatechange = xmlcookieHandler;

function pad2(number) {
    return (number < 10 ? '0' : '') + number;
}
var ctdwn;
function countdown(min,sec){
  sec = sec - 1;
  if(sec < 0){
	    min = min - 1;
	    sec = 59;
	    cookieCheck();
	}
  if(min<0 || isNaN(min) || sec<-1 || isNaN(sec)){
    document.getElementById("cookieUpdate").style.display = "inline";
    document.getElementById("cookieUpdate").innerHTML = "Logging Out!";
    xmlcookie.abort();
    xmlcookie.onreadystatechange = xmlcookieHandler;
    clearInterval(ctdwn);
    setTimeout(function(){window.location = "login.php?logout";},500);
    return;
  }
  sec = pad2(sec);
  document.getElementById("cookieTime").innerHTML = min+":"+sec;
  if(ctdwn)clearInterval(ctdwn);
  ctdwn = setInterval(function(){countdown(min,sec);},1000);
}
function cookieCheck(){
  var str="";
  if(html("cookieUpdate"))document.getElementById("cookieUpdate").style.display = "inline";
  xmlcookie.abort();
  xmlcookie.onreadystatechange = xmlcookieHandler;
  xmlcookie.open("GET","cust/ajax.php?cmd=cookie&str="+str,true);
  xmlcookie.send();
}
function cookieReload(){
	xmlcookie.abort();
	xmlcookie.onreadystatechange = xmlcookieHandler;
	xmlcookie.open("GET","cust/ajax.php?cmd=cookie_refresh",true);
	xmlcookie.send();
}

cookieCheck();

function custResult() {
	var s = document.getElementById("customersearch");
	document.getElementById("custresultbox").innerHTML = "";
	document.getElementById("custresultbox").style.border = "0px";
	var sbOption = 6;
	if (s.value == '') return;
	xmlcust.abort();
	xmlcust.onreadystatechange = xmlcustHandler;
	xmlcust.open("GET","cust/ajax.php?cmd=search&str="+s.value+"&sb="+sbOption,true);
	xmlcust.send();
}
function topissueResult() {
	var s = document.getElementById("topissueid");
	document.getElementById("topissueresultbox").innerHTML = "";
	document.getElementById("topissueresultbox").style.border = "0px";
  document.getElementById("topissueresultbox").style.display = "block";
	var sbOption = 7;
	if (s.value == '') return;
	xmltopissue.open("GET","cust/ajax.php?cmd=search&str="+s.value+"&sb="+sbOption,true);
	xmltopissue.send();
}

function notepad() {
	var notepad = document.getElementById("notepad");
	if(notepad.style.display=="block"){
   notepad.style.display = "none";
   return;
  }
  var str="";
	xmlnotepad.open("GET","cust/ajax.php?cmd=notepad&str="+str,true);
	xmlnotepad.send();
}
var tarea;
function notepad_update() {
  tarea = "<?php echo (isset($_POST["notepad_textarea"]) ? str_replace("\r",'',str_replace("\n",'\\n',str_replace('"','\\"',$_POST["notepad_textarea"]))) : ""); ?>";
	xmlnotepad_update.open("GET","cust/ajax.php?cmd=notepad_update&str="+encodeURIComponent(tarea),true);
	xmlnotepad_update.send();
}
<?php echo (isset($_POST["notepad_textarea"]) ? "notepad_update();" : ""); ?>

function getElementsByClass(searchClass,node,tag) {
	var classElements = new Array();
	if ( node == null )
		node = document;
	if ( tag == null )
		tag = '*';
	var els = node.getElementsByTagName(tag);
	var elsLen = els.length;
	var pattern = new RegExp("(^|\\s)"+searchClass+"(\\s|$)");
	for (i = 0, j = 0; i < elsLen; i++) {
		if ( pattern.test(els[i].className) ) {
			classElements[j] = els[i];
			j++;
		}
	}
	return classElements;
}

function confirmSubmit(){
  var agree=confirm("Are you sure you wish to continue?");
  if(agree){return true;}else{return false;}
}
function init(){
  return true;
}
function gotolast() {
	var selection = html('gotolast').options[html('gotolast').selectedIndex].value;
	if (selection == '0') return;
	var sel = selection.split('_');
	switch (sel[0]) {
		case "cust":
			window.location = '?module=cust&do=view&id='+sel[1];
			break;
		case "iss":
			window.location = '?module=iss&do=view&id='+sel[1];
			break;
		case "dev":
			window.location = '?module=cust&do=edit_dev&id='+sel[1];
			break;
	}
}
function open_taskList() {
	if (html('tasklist').style.display == 'none') {
		html('tasklist').style.display = '';
	} else {
		html('tasklist').style.display = 'none';
	}
}
</script>


<?php
 if(isset($_GET["module"]) && $_GET["module"]=="acct"){
 echo "
<script type=\"text/javascript\" src=\"js/calendarDateInput.js\"></script>
 ";
  }
?>

<title>Computer Answers - Front Desk</title>
</head>
<?php
  if(isset($_GET["module"]) && ($_GET["module"]=="sheets" || $_GET["module"]=="time" || $_GET["module"]=="admin")){
    echo "<body onLoad=\"init();\">\n";
  } else {
    echo "<body>\n";
  }

  if (isset($USER)){
    $result = mysql_query("
SELECT
  oe.*,
  oet.title AS type
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
");
    $entity = mysql_fetch_assoc($result);
  };
?>

<div style="position:relative;width:950px;margin:0px auto;">
 <div id="logo" style="position:absolute;top:0;left:0;width:150px;height:100px;">
  <img src="images/logo.gif" width="150" height="80" alt="Logo here">
 </div>
 <div id="top" style="position:absolute;top:0;left:150;background-color:#ccf;width:800px;height:100px;">
  <div style="background-image:url('images/top_left.jpg');position:absolute;top:0;left:0;width:63px;height:100px;"></div>
  <div style="background-image:url('images/top_right.jpg');position:absolute;top:0;right:0;width:63px;height:100px;"></div>
  <div style="position:absolute;top:50%;left:50%;margin-top:-10px;margin-left:-78px;"><font color="#99f" size="+2"><b>Front Desk</b></font></div>
<?php if (isset($USER)) { ?>
  <div style="position:absolute;top:15px;left:35px;">Logged in as <b><?php echo $USER["firstname"] ." ". $USER["lastname"]; ?></b> <?php echo (($entity['type']=='Store')?'at Store #'.$entity['location_code'].' ('.$entity['title'].')':'(Organization Level)'); ?></div>
  <?php if ($SESSION["customers__id"] || $SESSION["issues__id"] || $SESSION["inventory_items__id"]) { ?><div style="position:absolute;top:3px;left:50%;width:170px;margin-left:80px;">Go To Last <select id="gotolast" onChange="gotolast();"><option value="0">Pick One</option><?php if ($SESSION["customers__id"]) { ?><option value="cust_<?php echo $SESSION["customers__id"]; ?>">Customer</option><?php } if ($SESSION["issues__id"]) { ?><option value="iss_<?php echo $SESSION["issues__id"]; ?>">Issue</option><?php } if ($SESSION["inventory_items__id"]) { ?><option value="dev_<?php echo $SESSION["inventory_items__id"]; ?>">Device</option><?php } ?></select></div><?php } ?>
  <div style="position:absolute;top:35px;left:35px;"><?php echo alink("Inbox","?module=msg"); ?> : <?php echo ($msgs > 0 ? "<b><font color=\"#FF0000\">".$msgs."</font></b>":"0"); ?> unread messages</div>
  <div style="position:absolute;top:15px;right:35px;text-align:center;"><?php echo alink_plain("<b>".intval($cart["count"])."</b> items in cart","?module=pos"); ?><br>Subtotal: <b>$<?php echo number_format(floatval($cart["sum"]),2); ?></b></div>
  <div style="position:absolute;bottom:-5px;left:35px;"><form action="?" method="get"><input type="hidden" name="module" value="iss"><input type="hidden" name="do" value="view"><input type="edit" id="topissueid" name="id" onKeyUp="topissueResult()" size="10" autocomplete="off"><div id="topissueresultbox" style="background-color:#fff;position:absolute;width:80px;left:0px;display:none;z-index:50;"></div><input type="submit" value="Go to Issue ID"></form></div>
<?php
$DTS = "";
$result = mysql_query("SELECT task_id,descr,done_by,report_id FROM recurring_tasks ORDER BY task_id");
//if (!mysql_num_rows($result)) echo "<i>None configured.</i>\n";
$dts_has_not_done = false;
while ($row = mysql_fetch_assoc($result)) {
	$dbm = "";
	$font = "";
	$done_by_me = false;
	if ($row["done_by"] && $row["done_by"] != '') {
		$done_by = explode(",",$row["done_by"]);
		foreach ($done_by as $uid) {
			if ($uid == $USER["id"]) {
				$dbm = " CHECKED";
				$font = " style=\"color:#008000;\"";
				$done_by_me = true;
				break;
			}
		}
	}
	if (!$done_by_me) $dts_has_not_done = true;
	$desc = $row["descr"];
	if ($row["report_id"]) {
		$desc .= " ". alink("Submit Report","?module=core&do=submit_report&id={$row["report_id"]}");
	}
	$DTS .= '<input type="checkbox" id="task_'. $row["task_id"] .'" onClick="taskClick('. $row["task_id"] .');"'.$dbm.'> <span id="task_descr_'.$row["task_id"].'"'.$font.'>'.$desc.'</span><br>'."\n";
}

?>
  <div style="position:absolute;bottom:5px;left:50%;margin-left:-120px;"><?php echo alink("Punch In / Out","?module=core&do=punch"); ?> &nbsp;&nbsp; <?php echo ($dts_has_not_done ? alink_onclick("Daily Tasks","#","open_taskList();",true) : alink_onclick("Daily Tasks","#","open_taskList();")); ?>
<div id="tasklist" align="left" style="z-index:100;display:none;position:absolute;top:25px;right:0px;background-color:#CCCCCC;padding:5px;border-radius:5px;">
<div align="center"><b>Daily Tasks</b></div>
<?php

echo $DTS;

?>
</div>
  </div>
  <div style="position:absolute;bottom:-5px;right:35px;"><form action="?module=cust&do=view" method="get"><input type="edit" id="customersearch" name="customersearch" onKeyUp="custResult()" size="10" autocomplete="off"><input type="submit" value="Go to Customer"></form></div>
<?php } ?>

 </div>

 <?php echo $NAVBAR; ?>

 <div id="content" class="content" align="center">
  <div id="custresultbox" style="background-color:#fff;position:absolute;width:400px;right:0px;top:0px;display:none;z-index:50;"></div>

<?php
}
?>
