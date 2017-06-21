<script type="text/javascript">
var xmlacci;
if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
	xmlacci=new XMLHttpRequest();
} else {// code for IE6, IE5
	xmlacci=new ActiveXObject("Microsoft.XMLHttp");
}

xmlacci.onreadystatechange = resultHandler;
function resultHandler() {
	if (xmlacci.readyState == 4 && xmlacci.status == 200) {
		document.getElementById("resultbox").innerHTML = xmlacci.responseText;
		document.getElementById("resultbox").style.border = "1px solid #120A8F";
	}
}

function showResult() {
	var s = document.getElementById("search");
	document.getElementById("resultbox").innerHTML = "";
	document.getElementById("resultbox").style.border = "0px";
	var sb = document.getElementById("searchby");
	var sbOption = sb.options[sb.selectedIndex].value;
	if (s.value == '') return;
	xmlacci.abort();
	xmlacci.onreadystatechange = resultHandler;
	xmlacci.open("GET","cust/ajax.php?cmd=search&str="+s.value+"&sb="+sbOption+"&acci=1",true);
	xmlacci.send();
}
</script>
<h2>Select Customer to Create Account For</h2>

Search By

<select id="searchby" onChange="showResult()">
<option value="1">Customer ID</option>
<option value="2">First Name</option>
<option value="3">Last Name</option>
<option value="4">Phone</option>
<option value="6" SELECTED>Full Name</option>
<option value="8">Company</option>
</select>
<input id="search" size="30" onKeyUp="showResult()">
<div id="resultbox" style="background-color:#fff;position:absolute;width:400px;left:50%;margin-left:-200px;"></div>
<br><br>
