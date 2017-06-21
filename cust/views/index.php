<script type="text/javascript">
var xmlhttp;
if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp=new XMLHttpRequest();
} else {// code for IE6, IE5
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
}

xmlhttp.onreadystatechange = resultHandler;
function resultHandler() {
	if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
		document.getElementById("resultbox").innerHTML = xmlhttp.responseText;
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
	xmlhttp.abort();
	xmlhttp.onreadystatechange = resultHandler;
	xmlhttp.open("GET","cust/ajax.php?cmd=search&str="+s.value+"&sb="+sbOption,true);
	xmlhttp.send();
}
</script>
<?php echo alink("+ Add New Customer","?module=cust&do=new"); ?>
<br>

<h3>Search</h3>

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

<?php echo alink("View All Customers","?module=cust&do=list"); ?>
