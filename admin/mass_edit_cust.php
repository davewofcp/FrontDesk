<?php

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) exit;

if (!TFD_HAS_PERMS('admin','use')) {
	echo "You do not have the needed permissions to access this page.";
	exit;
}

$PAGE = 1;
$RESULTS_PER_PAGE = 100;

// if (isset($_GET["page"])) {
// 	$PAGE = intval($_GET["page"]);
// 	if ($PAGE <= 0) $PAGE = 1;
// 	mysql_query("UPDATE config SET value = '".$PAGE."' WHERE setting = 'mec_page'");
// } else {
// 	$result = mysql_query("SELECT value FROM config WHERE setting = 'mec_page'");
// 	if (mysql_num_rows($result)) {
// 		$data = mysql_fetch_assoc($result);
// 		$PAGE = intval($data["value"]);
// 	} else {
// 		mysql_query("INSERT INTO config (setting,value) VALUES ('mec_page','1')");
// 	}
// }

$data = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM customers WHERE 1"));
$COUNT = $data["count"];

$result = mysql_query("SELECT * FROM customers WHERE 1 ORDER BY id LIMIT ".(($PAGE - 1) * $RESULTS_PER_PAGE).",". $RESULTS_PER_PAGE);

?>
<html>
<head>
<title>Mass-Edit Customers</title>
<style>
.small {
	font-size:11px;
	font-family:tahoma arial;
}
.grey {
	background-color:#CCCCCC;
}
.white {
	background-color:#FFFFFF;
}
</style>
</head>
<body>
<script type="text/javascript">
var xmlhttp;
if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp=new XMLHttpRequest();
} else {// code for IE6, IE5
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
}

xmlhttp.onreadystatechange = function() {
	if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
		var response = xmlhttp.responseText;
		var responseArr = response.split(":");
		document.getElementById("result"+responseArr[0]).innerHTML = "<font size=\"-1\" color=\"green\">Updated "+responseArr[1]+"</font>";
	}
}

function updateField(id,field,obj) {
	document.getElementById("result"+id).innerHTML = "<font size=\"-1\" color=\"red\">Updating...</font>";
	xmlhttp.open("GET","cust_ajax.php?id="+id+"&field="+field+"&str="+obj.value,true);
	xmlhttp.send();
}
</script>
<h3>Mass-Edit Customers</h3>

<a href="/?module=admin">Back to Administration</a><br><br>

<b>NOTE:</b> Changing Address, Apartment #, City, State, or Zip <b>will remove the stored 'validated address'</b> obtained from USPS. It will be filled in again the next time the batch validator runs.<br><br>

Go to page <?php

$LAST = ceil($COUNT / $RESULTS_PER_PAGE);
if ($LAST < 1) $LAST = 1;

for ($i = 1; $i <= $LAST; $i++) {
	if ($i == $PAGE) {
		echo "[ $PAGE ] ";
	} else {
		echo "<a href=\"?page=$i\">[ $i ]</a> ";
	}
}

?><br><br>

<table style="border: 1px solid #000;">
 <tr align="center">
  <td>ID</td>
  <td>First Name</td>
  <td>Last Name</td>
  <td>Male (0 or 1)</td>
  <td>DOB (yyyy-mm-dd)</td>
  <td>Company</td>
  <td>Address</td>
  <td>Apt #</td>
  <td>City</td>
  <td>State</td>
  <td>Zip</td>
  <td>Email</td>
  <td>Home Phone</td>
  <td>Cell Phone</td>
  <td>Referral</td>
  <td>Subscribed (0 or 1)</td>
  <td width="100">.</td>
 </tr>
<?php

$x = true;
while ($row = mysql_fetch_assoc($result)) {
	$x = !$x;
	if ($x) $class = "grey";
	else $class = "white";
	echo " <tr align=\"center\" class=\"$class\">\n";
	echo "  <td>".$row["id"]."</td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'firstname',this)\" size=\"15\" value=\"".$row["firstname"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'lastname',this)\" size=\"15\" value=\"".$row["lastname"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'is_male',this)\" size=\"2\" value=\"".intval($row["is_male"])."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'dob',this)\" size=\"9\" value=\"".$row["dob"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'company',this)\" size=\"15\" value=\"".$row["company"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'address',this)\" size=\"20\" value=\"".$row["address"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'apt',this)\" size=\"4\" value=\"".$row["apt"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'city',this)\" size=\"10\" value=\"".$row["city"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'state',this)\" size=\"3\" value=\"".$row["state"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'zip',this)\" size=\"6\" value=\"".$row["postcode"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'email',this)\" size=\"20\" value=\"".$row["email"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'phone_home',this)\" size=\"11\" value=\"".$row["phone_home"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'phone_cell',this)\" size=\"11\" value=\"".$row["phone_cell"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'referral',this)\" size=\"20\" value=\"".$row["referral"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small $class\" onBlur=\"updateField(".$row["id"].",'subscribed',this)\" size=\"2\" value=\"".intval($row["is_subscribed"])."\"></td>\n";
	echo "  <td><div id=\"result".$row["id"]."\"></div></td>\n";
	echo " </tr>\n";
}

?>
</table>

Go to page <?php

for ($i = 1; $i <= $LAST; $i++) {
	if ($i == $PAGE) {
		echo "[ $PAGE ] ";
	} else {
		echo "<a href=\"?page=$i\">[ $i ]</a> ";
	}
}

?><br><br>

</body>
</html>
