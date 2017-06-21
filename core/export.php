<?php

include dirname(__FILE__) ."/../mysql_connect.php";

switch ($_GET["export"]) {
	case "cust":
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=customers.csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		$FIELDS = array();
		$HEADER = array();
		if (isset($_POST["customer_id"])) { $FIELDS[] = "id"; $HEADER[] = "Customer ID"; }
		if (isset($_POST["firstname"])) { $FIELDS[] = "firstname"; $HEADER[] = "First Name"; }
		if (isset($_POST["lastname"])) { $FIELDS[] = "lastname"; $HEADER[] = "Last Name"; }
		if (isset($_POST["sex"])) { $FIELDS[] = "IF(is_male = 1,'Male','Female') AS sex"; $HEADER[] = "Sex"; }
		if (isset($_POST["dob"])) { $FIELDS[] = "dob"; $HEADER[] = "Date of Birth"; }
		if (isset($_POST["company"])) { $FIELDS[] = "company"; $HEADER[] = "Company"; }
		if (isset($_POST["address"])) { $FIELDS[] = "address"; $HEADER[] = "Address"; }
		if (isset($_POST["city"])) { $FIELDS[] = "city"; $HEADER[] = "City"; }
		if (isset($_POST["state"])) { $FIELDS[] = "state"; $HEADER[] = "State"; }
		if (isset($_POST["zip"])) { $FIELDS[] = "postcode"; $HEADER[] = "Zipcode"; }
		if (isset($_POST["email"])) { $FIELDS[] = "email"; $HEADER[] = "Email Address"; }
		if (isset($_POST["phone_home"])) { $FIELDS[] = "phone_home"; $HEADER[] = "Home Phone"; }
		if (isset($_POST["phone_cell"])) { $FIELDS[] = "phone_cell"; $HEADER[] = "Cell Phone"; }
		if (isset($_POST["referral"])) { $FIELDS[] = "referral"; $HEADER[] = "Referral"; }

		echo implode(",",$HEADER) ."\n";

		$SELECT = implode(",",$FIELDS);
		$data = mysql_query("SELECT ".$SELECT." FROM customers ORDER BY id");
		while ($row = mysql_fetch_assoc($data)) {
			$FIELDS = array();
			foreach ($row as $value) {
				$value = str_replace('"','""',$value);
				$FIELDS[] = "\"".$value."\"";
			}
			echo implode(",",$FIELDS) ."\n";
		}
		break;
	case "inventory":
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=inventory.csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		$FIELDS = array();
		$HEADER = array();

		$FIELDS[] = "id";
		$HEADER[] = "ID";
		$FIELDS[] = "upc";
		$HEADER[] = "UPC";
		$FIELDS[] = "descr";
		$HEADER[] = "Description";
		$FIELDS[] = "purchase_price";
		$HEADER[] = "Cost";
		$FIELDS[] = "cost";
		$HEADER[] = "Retail";
		$FIELDS[] = "qty";
		$HEADER[] = "Quantity";
		$FIELDS[] = "is_taxable";
		$HEADER[] = "Taxable";
		$FIELDS[] = "item_type_lookup";
		$HEADER[] = "Category";
		$FIELDS[] = "name";
		$HEADER[] = "Name";

		echo implode(",",$HEADER) ."\n";

		$SELECT = implode(",",$FIELDS);
		$data = mysql_query("SELECT ".$SELECT." FROM inventory WHERE 1 ORDER BY id");
		while ($row = mysql_fetch_assoc($data)) {
			$FIELDS = array();
			foreach ($row as $value) {
				$value = str_replace('"','""',$value);
				$FIELDS[] = "\"".$value."\"";
			}
			echo implode(",",$FIELDS) ."\n";
		}
		break;
}
?>