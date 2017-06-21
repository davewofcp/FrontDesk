<?php

require_once(dirname(__FILE__) ."/../mysql_connect.php");
require_once(dirname(__FILE__) ."/sessions.php");

//date_default_timezone_set("America/New_York"); // got from config.php via mysql_connect.php

$sql="SELECT phone FROM org_entities WHERE id={$USER['org_entities__id']} LIMIT 1";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
    $storephn = $row['phone'];
}

$issue_id = intval($_GET['id']);

$sql="SELECT * FROM customers,issues LEFT JOIN inventory_type_devices ON issues.device_id = inventory_type_devices.id WHERE issues.org_entities__id = {$USER['org_entities__id']} AND customers.id = issues.customers__id AND issues.id = ".$issue_id;

$result = mysql_query($sql) or die(mysql_error() ."::". $sql);

while ($row = mysql_fetch_assoc($result)) {
	$fulldate = $row['intake_ts'];
	$customer_name = $row['firstname'] . " " . $row['lastname'];
    $customer_email = $row['email'];
    if ($row['has_charger']=='1'){
        $hascharger='YES';
    } else {
        $hascharger='No';
    }
    $customer_id = $row['customers__id'];

    $homephone = $row['phone_home'];
    if ($homephone=='') { $homephone = 'None'; }
    $cellphone = $row['phone_cell'];
    if ($cellphone=='') { $cellphone = 'None' ; }

    include('barcodelabel_pdf.php');
}

?>
