<?php

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) exit;

if (!TFD_HAS_PERMS('admin','use')) {
	echo "You do not have the needed permissions to access this page.";
	exit;
}

if (isset($_GET["file"])) {
	if ($_FILES["file"]["error"] > 0) {
		$RESPONSE = "Error uploading file: ".$_FILES["file"]["error"];
	} else {
		if (file_exists("upload/" . $_FILES["file"]["name"])) {
			unlink("upload/" . $_FILES["file"]["name"]);
		}

		move_uploaded_file($_FILES["file"]["tmp_name"],"upload/" . $_FILES["file"]["name"]);

		$inserted = 0;
		if (($handle = fopen("upload/". $_FILES["file"]["name"], "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
				if ($data[0] != "CUST") continue;
				$customer = array();
				$customer["address"] = (count($data) > 5 ? $data[5] : "");
				$csz = explode(",",str_replace('"',"",(count($data) > 5 ? $data[6] : "")));
				$customer["city"] = $csz[0];
				$sz = explode(" ",(count($csz) > 1 ? TRIM($csz[1]) : "")); // need to trim, else $sz[0] = ''
				$customer["state"] = $sz[0];
				$customer["zip"] = (count($sz) > 1 ? $sz[1] : "");
				$customer["phone1"] = (count($data) > 14 ? str_replace("-","",$data[14]) : "");
				$customer["phone2"] = (count($data) > 15 ? str_replace("-","",$data[15]) : "");
				$customer["email"] = (count($data) > 17 ? $data[17] : "");
				$customer["company"] = (count($data) > 31 ? $data[31] : "");
				$customer["firstname"] = (count($data) > 32 ? $data[32] : "");
				$customer["lastname"] = (count($data) > 34 ? $data[34] : "");

				if ($customer["firstname"] == "" && $customer["lastname"] == "" && count($data) > 4) {
					$name = explode(" ",$data[4]);
					switch (count($name)) {
						case 1:
							$customer["firstname"] = $name[0];
							break;
						case 2:
							$customer["firstname"] = $name[0];
							$customer["lastname"] = $name[1];
							break;
						case 3:
							$customer["firstname"] = $name[0]." ".$name[1];
							$customer["lastname"] = $name[2];
							break;
						default:
							$customer["firstname"] = $data[4];
					}
				}

				$TABLE = "customer_import";
				$TBLNAME = "temporary table";
				$S1 = "";
				$S2 = "";
				if (isset($_POST["import_all"])) {
					$TABLE = "customers";
					$TBLNAME = "customer database";
					$S1 = ",is_subscribed";
					$S2 = ",1";
				}

				$sql = "INSERT INTO $TABLE (firstname,lastname,is_male,dob,company,address,city,state,postcode,email,";
				$sql .= "phone_home,phone_cell,referral$S1) VALUES (";
				$sql .= "'". mysql_real_escape_string($customer["firstname"]) ."',";
				$sql .= "'". mysql_real_escape_string($customer["lastname"]) ."',";
				$sql .= "0,0,";
				$sql .= "'". mysql_real_escape_string($customer["company"]) ."',";
				$sql .= "'". mysql_real_escape_string($customer["address"]) ."',";
				$sql .= "'". mysql_real_escape_string($customer["city"]) ."',";
				$sql .= "'". mysql_real_escape_string($customer["state"]) ."',";
				$sql .= "'". mysql_real_escape_string($customer["zip"]) ."',";
				$sql .= "'". mysql_real_escape_string($customer["email"]) ."',";
				$sql .= "'". mysql_real_escape_string($customer["phone1"]) ."',";
				$sql .= "'". mysql_real_escape_string($customer["phone2"]) ."',";
				$sql .= "''$S2)";
				mysql_query($sql) or die(mysql_error() ."::". $sql);
				$inserted++;
			}
			$RESPONSE = "Inserted $inserted customers into $TBLNAME.\n";
		} else {
			$RESPONSE = "Error opening file for reading.";
		}
	}
}

$data = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM customer_import"));
$COUNT = $data["count"];

$PAGE = 1;
$RESULTS_PER_PAGE = 50;
if (isset($_GET["page"])) {
	$PAGE = intval($_GET["page"]);
	if ($PAGE <= 0) $PAGE = 1;
}

$IMPORTS = mysql_query("SELECT * FROM customer_import ORDER BY id LIMIT ".(($PAGE - 1) * $RESULTS_PER_PAGE).",". $RESULTS_PER_PAGE);

?>
<html><head><title>Imported Customers</title>
<style type="text/css">
.border {
	margin-left: auto;
	margin-right: auto;
	margin:8px 0px 8px 0px;
	border-radius: 10px;
	-moz-border-radius: 10px;
	-webkit-border-radius: 10px;
	border: 3px ridge #000;
}
.heading {
	font-weight: bold;
	color: #fff;
	background-color: #003399;
}
.small {
	font-size:11px;
	font-family:tahoma arial;
}
</style>
</head><body>
<script type="text/javascript">
var xmlhttp;
if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp=new XMLHttpRequest();
	xbutton=new XMLHttpRequest();
} else {// code for IE6, IE5
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	xbutton=new ActiveXObject("Microsoft.XMLHTTP");
}

xmlhttp.onreadystatechange = function() {
	if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
		var response = xmlhttp.responseText;
		var responseArr = response.split(":");
		document.getElementById("result"+responseArr[0]).innerHTML = "<font size=\"-1\" color=\"green\">Updated "+responseArr[1]+"</font>";
	}
}

xbutton.onreadystatechange = function() {
	if (xbutton.readyState == 4 && xbutton.status == 200) {
		var response = xbutton.responseText;
		var responseArr = response.split(":");
		if (responseArr[1].trim() == 'ERROR') {
			document.getElementById("result"+responseArr[0]).innerHTML = "<font size=\"-1\" color=\"red\">ERROR</font>";
		} else {
			document.getElementById("import"+responseArr[0]).style.display = "none";
		}
	}
}

function updateField(id,field,obj) {
	document.getElementById("result"+id).innerHTML = "<font size=\"-1\" color=\"red\">Updating...</font>";
	xmlhttp.open("GET","cust_ajax.php?import=1&id="+id+"&field="+field+"&str="+obj.value,true);
	xmlhttp.send();
}

function imp(cust,imp) {
	document.getElementById("result"+imp).innerHTML = "<font size=\"-1\" color=\"red\">Please Wait...</font>";
	xbutton.open("GET","import_ajax.php?c="+cust+"&i="+imp+"&op=import",true);
	xbutton.send();
}

function del(id) {
	document.getElementById("result"+id).innerHTML = "<font size=\"-1\" color=\"red\">Please Wait...</font>";
	xbutton.open("GET","import_ajax.php?i="+id+"&op=delete",true);
	xbutton.send();
}
</script>
<div align="center">
<h3>Imported Customers</h3>

<?php if (isset($RESPONSE)) { echo "<b>$RESPONSE</b><br><br>\n"; } ?>

<b><?php echo $COUNT; ?></b> customers in temporary table. Showing <?php echo $RESULTS_PER_PAGE; ?>.<br>
<a href="?refresh">REFRESH</a><br><br>

<a href="/?module=admin">Back to Administration</a><br><br>

<hr>

<?php

while ($import = mysql_fetch_assoc($IMPORTS)) {
	echo <<<EOF
<div class="border" width="100%" align="center" id="import{$import["id"]}">
<table border="0">
 <tr align="center" class="heading">
  <td>#</td>
  <td>First Name</td>
  <td>Last Name</td>
  <td>Sex</td>
  <td>DOB</td>
  <td>Company</td>
  <td>Address</td>
  <td>City</td>
  <td>State</td>
  <td>Zip</td>
  <td>Email</td>
  <td>Home Phone</td>
  <td>Cell Phone</td>
  <td>Referral</td>
  <td width="100">.</td>
 </tr>
EOF;
	echo " <tr align=\"center\">\n";
	echo "  <td>".$import["id"]."</td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'firstname',this)\" size=\"15\" value=\"".$import["firstname"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'lastname',this)\" size=\"15\" value=\"".$import["lastname"]."\"></td>\n";
	echo "  <td><select class=\"small\" onBlur=\"updateField(".$import["id"].",'is_male',this)\"><option value=\"0\"".($import["is_male"] ? "" : " SELECTED").">F</option><option value=\"1\"".($import["is_male"] ? " SELECTED" : "").">M</option></select></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'dob',this)\" size=\"9\" value=\"".$import["dob"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'company',this)\" size=\"15\" value=\"".$import["company"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'address',this)\" size=\"20\" value=\"".$import["address"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'city',this)\" size=\"10\" value=\"".$import["city"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'state',this)\" size=\"3\" value=\"".$import["state"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'zip',this)\" size=\"6\" value=\"".$import["postcode"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'email',this)\" size=\"20\" value=\"".$import["email"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'phone_home',this)\" size=\"11\" value=\"".$import["phone_home"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'phone_cell',this)\" size=\"11\" value=\"".$import["phone_cell"]."\"></td>\n";
	echo "  <td><input type=\"text\" class=\"small\" onBlur=\"updateField(".$import["id"].",'referral',this)\" size=\"20\" value=\"".$import["referral"]."\"></td>\n";
	echo "  <td><div id=\"result".$import["id"]."\"></div></td>\n";
	echo " </tr>\n";
	echo "</table><br>";
	// sorry, had to do upper() like upper() because ILIKE isn't supported. terrible performance though
	$dupes = mysql_query("SELECT * FROM customers WHERE UPPER(lastname) LIKE UPPER( '".mysql_real_escape_string($import["lastname"])."') OR (UPPER(address) LIKE UPPER( '".mysql_real_escape_string($import["address"])."') AND UPPER(city) LIKE UPPER( '".mysql_real_escape_string($import["city"])."'))");
	if (mysql_num_rows($dupes)) {
		echo mysql_num_rows($dupes)." possible matches:<br>\n<pre>";
		while ($dupe = mysql_fetch_assoc($dupes)) {
			echo "#".$dupe["id"]."\t".$dupe["firstname"]." ".$dupe["lastname"]."\t".$dupe["address"].", ".$dupe["city"].", ".$dupe["state"]." ".$dupe["postcode"]."\t".$dupe["phone_home"]."\t".$dupe["phone_cell"]."\t";
			echo "[<a href=\"#\" onClick=\"imp('".$dupe["id"]."','".$import["id"]."');\">MATCH - use IMPORTED info</a> ] ";
			echo "[<a href=\"#\" onClick=\"del('".$import["id"]."');\">MATCH - DISCARD this import</a> ]<br>\n";
		}
	} else {
		echo "No Matches Found - [<a href=\"#\" onClick=\"imp('0','".$import["id"]."');\">NEW CUSTOMER</a>] ";
		echo "[<a href=\"#\" onClick=\"del('".$import["id"]."');\">DELETE</a>]<br>\n";
	}
	echo "</div>\n";
}

?>
</div>
</body>
</html>
