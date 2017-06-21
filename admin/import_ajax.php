<?php

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) exit;
if (!TFD_HAS_PERMS('admin','use')) exit;
if (!isset($_GET["op"]) || !isset($_GET["i"])) exit;

$ID = intval($_GET["i"]);
if (isset($_GET["c"])) $CID = intval($_GET["c"]);

switch ($_GET["op"]) {
	case "import":
		$result = mysql_query("SELECT * FROM customer_import WHERE id = $ID");
		if (!mysql_num_rows($result)) {
			echo "$ID:ERROR";
		} else {
			$data = mysql_fetch_assoc($result);

			if ($CID > 0) {
				$sql = "UPDATE customers SET ";
				$sql .= "firstname = '".mysql_real_escape_string($data["firstname"])."',";
				$sql .= "lastname = '".mysql_real_escape_string($data["lastname"])."',";
				$sql .= "is_male = ".intval($data["is_male"]).",";
				$sql .= "dob = '".mysql_real_escape_string($data["dob"])."',";
				$sql .= "company = '".mysql_real_escape_string($data["company"])."',";
				$sql .= "address = '".mysql_real_escape_string($data["address"])."',";
				$sql .= "city = '".mysql_real_escape_string($data["city"])."',";
				$sql .= "state = '".mysql_real_escape_string($data["state"])."',";
				$sql .= "postcode = '".mysql_real_escape_string($data["postcode"])."',";
				$sql .= "email = '".mysql_real_escape_string($data["email"])."',";
				$sql .= "phone_home = '".mysql_real_escape_string($data["phone_home"])."',";
				$sql .= "phone_cell = '".mysql_real_escape_string($data["phone_cell"])."',";
				$sql .= "referral = '".mysql_real_escape_string($data["referral"])."' ";
				$sql .= "WHERE id = ".$CID;
				mysql_query($sql) or die("$ID:ERROR");
			} else {
				$sql = "INSERT INTO customers (firstname,lastname,is_male,dob,company,address,city,state,postcode,email,";
				$sql .= "phone_home,phone_cell,referral,is_subscribed) VALUES (";
				$sql .= "'".mysql_real_escape_string($data["firstname"])."',";
				$sql .= "'".mysql_real_escape_string($data["lastname"])."',";
				$sql .= intval($data["is_male"]).",";
				$sql .= "'".mysql_real_escape_string($data["dob"])."',";
				$sql .= "'".mysql_real_escape_string($data["company"])."',";
				$sql .= "'".mysql_real_escape_string($data["address"])."',";
				$sql .= "'".mysql_real_escape_string($data["city"])."',";
				$sql .= "'".mysql_real_escape_string($data["state"])."',";
				$sql .= "'".mysql_real_escape_string($data["postcode"])."',";
				$sql .= "'".mysql_real_escape_string($data["email"])."',";
				$sql .= "'".mysql_real_escape_string($data["phone_home"])."',";
				$sql .= "'".mysql_real_escape_string($data["phone_cell"])."',";
				$sql .= "'".mysql_real_escape_string($data["referral"])."',";
				$sql .= "1)";
				mysql_query($sql) or die("$ID:ERROR");
			}
			mysql_query("DELETE FROM customer_import WHERE id = $ID");
			echo "$ID:IMPORT";
		}
		break;
	case "delete":
		mysql_query("DELETE FROM customer_import WHERE id = $ID");
		echo "$ID:DELETE";
		break;
	default:
}

exit;

?>
