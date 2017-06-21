<?php

require_once("../mysql_connect.php");
require_once("../core/sessions.php");

if (!isset($USER)) exit;
if (!TFD_HAS_PERMS('admin','use')) exit;

$TABLE = "customers";
$ID_FIELD = "id";

if (isset($_GET["import"])) {
	$TABLE = "customer_import";
	$ID_FIELD = "id";
}

$ID = intval($_GET["id"]);
$FIELD = mysql_real_escape_string($_GET["field"]);
$STR = mysql_real_escape_string($_GET["str"]);

@mysql_query("UPDATE $TABLE SET $FIELD = '$STR' WHERE $ID_FIELD = $ID");

if ($TABLE == "customers" && ($FIELD == "address" || $FIELD == "apt" || $FIELD == "city" || $FIELD == "state" || $FIELD == "postcode")) {
	@mysql_query("UPDATE $TABLE SET v_address = NULL WHERE $ID_FIELD = $ID");
}

echo "$ID:$FIELD";
exit;

?>
