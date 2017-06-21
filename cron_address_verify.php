<?php

// TO BE RUN EVERY NIGHT AT 11:00PM

require_once("mysql_connect.php");
require_once("cust/usps.php");

$logfile = "cron_address_verify.log";
$log = fopen($logfile, 'a') or die("FATAL: Can't open log file.");

echo ts()." START\n";
fwrite($log,ts()." START\n");

function ts() {
	return "[". date("Y-m-d H:i:s") ."]";
}

// $result = mysql_query("SELECT customer_id,address,apt,city,state,zip FROM customers WHERE v_address IS NULL");

$result = mysql_query("SELECT id,address,apt,city,state,postcode FROM customers WHERE v_address IS NULL");

while ($row = mysql_fetch_assoc($result)) {
	if (!$row["address"] || $row["address"] == "" || !$row["postcode"] || $row["postcode"] == "") {
		mysql_query("UPDATE customers SET v_address = 'INVALID' WHERE id = {$row["id"]}");
		echo ts()." MISSING-DATA {$row["id"]}\n";
		fwrite($log,ts()." MISSING-DATA {$row["id"]}\n");
		continue;
	}
	$x = address_validate($row["address"],$row["apt"],$row["city"],$row["state"],$row["postcode"]);
	if (!$x) {
		mysql_query("UPDATE customers SET v_address = 'INVALID' WHERE id = {$row["id"]}");
		echo ts()." INVALID {$row["id"]} - [{$row["address"]}, {$row["apt"]}, {$row["city"]}, {$row["state"]}, {$row["postcode"]}]\n";
		fwrite($log,ts()." INVALID {$row["id"]} - [{$row["address"]}, {$row["apt"]}, {$row["city"]}, {$row["state"]}, {$row["postcode"]}]\n");
		sleep(1);
		continue;
	}
	if ($x == "ERROR-CONNECT") {
		echo ts()." ERROR-CONNECT {$row["id"]}\n";
		fwrite($log,ts()." ERROR-CONNECT {$row["id"]}\n");
		fclose($log);
		exit;
	}
	if ($x == "ERROR-NODATA") {
		echo ts()." ERROR-NODATA {$row["id"]}\n";
		fwrite($log,ts()." ERROR-NODATA {$row["id"]}\n");
		fclose($log);
		exit;
	}
	if (!isset($x["address"])) $x["address"] = "";
	if (!isset($x["address2"])) $x["address2"] = "";
	if (!isset($x["city"])) $x["city"] = "";
	if (!isset($x["state"])) $x["state"] = "";
	if (!isset($x["postcode"])) $x["postcode"] = "";
	if (!isset($x["zip4"])) $x["zip4"] = "";
	$zip4 = "";
	if ($x["zip4"] != "") $zip4 = "-".$x["zip4"];
	$v_address = $x["address"] .", ". $x["address2"] .", ". $x["city"] .", ". $x["state"] .", ". $x["postcode"] . $zip4;
	if (isset($x["no_service"])) $v_address = "NO SERVICE TO: ". $v_address;
	mysql_query("UPDATE customers SET v_address = '".mysql_real_escape_string($v_address)."' WHERE id = {$row["id"]}");
	echo ts()." VALID {$row["id"]} - [$v_address]\n";
	fwrite($log,ts()." VALID {$row["id"]} - [$v_address]\n");
	sleep(1);
}

echo ts()." DONE\n";
fwrite($log,ts()." DONE\n");
fclose($log);

?>
