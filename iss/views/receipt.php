<!DOCTYPE HTML>
<html>
<head>
<title>Receipt</title>
</head>
<body>
<?php

$counter = 0;
$copyversion = "Store Copy";

$sql = "
SELECT
  oe.phone
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
";
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($row = mysql_fetch_assoc($result)) {
    $storephn = $row['phone'];
}

$sql = "SELECT customers__id FROM issues WHERE id = ".intval($_GET['id']);
$result = mysql_query($sql) or die(mysql_error() ."::". $sql);
while ($counter < 2) {
    while ($row = mysql_fetch_assoc($result)) {
    	$customer_id=$row['customers__id'];
    }
	$timestamp = date("F d, Y");

	echo "<table width='100%' height='500px'><tr><td><table width='100%'><tr><td valign='top'><br />
<table width='100%' height='100%' valign='bottom'><tr><td align='center'>
<img src='images/logo.gif' width = '280' height = '130' align='center'></td>
</tr><tr><td align='center'><strong>Claims Ticket</strong></td>
<tr><tr><td width='100%' align='center'><strong>".$timestamp."</strong></td></tr>
<tr><td align='center'><font size='5' face='Times' color='red'>For Diagnostic Results
CALL Tech Support ".$storephn."</font></td>
</tr><tr><td align='center'>".$copyversion."</td>
</tr></table></td><td>
<table width='100%'><tr><td width='100%' align='center'>Customer ID</td></tr>
<tr><td width='100%' align='center'>
<IMG SRC='core/barcode.php?barcode=".$customer_id."&width=280&height=80' align='center'></td></tr>

<tr><td width='50%' align='center'>Issue ID</td></tr>
<tr><td width='50%' align='center'>
<IMG SRC='core/barcode.php?barcode=".intval($_GET['id'])."&width=280&height=80' align='center'>
</td></tr>
</table></td></tr></table>";

	$sql = "SELECT * FROM ((customers c JOIN issues i ON c.id = i.customers__id) JOIN inventory_type_devices d ON i.device_id = d.id) JOIN categories ca ON d.categories__id = ca.id WHERE i.id = ".intval($_GET['id']);
	$result = mysql_query($sql) or die(mysql_error() ."::". $sql);

	while ($row = mysql_fetch_assoc($result)) {

		echo "  <hr width='90%' align='center'>
   <table width='100%' ><tr><td><table height='100%' width='70%' CELLPADDING='3' align='center'>
  <tr><td align='center'><strong>Customer Name</strong></td><td >".$row['firstname']." ".$row['lastname']."</td>
  <tr><td align='center'><strong>Street Address</strong></td><td >".$row['address']."</td></tr>
  <tr><td align='center'><strong>City</strong></td><td >".$row['city']."</td></tr>
  <tr><td align='center'><strong>Zip Code</strong></td><td >".$row['postcode']."</td></tr>
  <tr><td align='center'><strong>Home Phone</strong></td><td >".display_phone($row['phone_home'])."</td></tr>
  <tr><td align='center'><strong>Cell Phone</strong></td><td >".display_phone($row['phone_cell'])."</td></tr>
  <tr><td align='center'><strong>Email Address</strong></td><td>".$row['email']."</td></tr>

  </table> </td><td>

  <table height='100%' width='100%' align='center' CELLPADDING='3'>
  <tr><td ><strong> Device Type </strong></td><td >".$row['category_name']."</td></tr>
<tr><td ><strong>Manufacturer</strong></td><td >".$row['manufacturer']."</td></tr>
  <tr><td ><strong>Model</strong></td><td >".$row['model']."</td></tr>
  <tr><td ><strong>Serial Number</strong></td><td >".$row['serial_number']."</td></tr>";
  //<tr><td ><strong>Service Type</strong></td><td >".$row['value']."</td></tr>";
		if ($copyversion == "Store Copy"){
			if ($row['password'] != ''){
				echo "<tr><td ><strong> Computer Password </strong></td><td >".$row['password']."</td> </tr>";
 			}
 		}
		if ($row['has_charger'] == '1') {
			echo "<tr><td ><strong> Left The Charger ?? </strong></td><td >Yes</td> </tr>";
		} else {
			echo "<tr><td ><strong> Left The Charger ?? </strong></td><td >No</td> </tr>";
		}


		echo " </table></td></tr><tr><td colspan='2'>
  ";
    	echo "<table width='100%' align='center' CELLPADDING='3'><tr></tr>
<tr><td colspan='2'><strong>Saved Files</strong></td></tr>
    <tr><td width='15%'></td><td align='85%'>".$row['savedfiles']."</td></tr>
<tr><td colspan='2'><strong>Issue Description</strong></td></tr>
<tr><td width='15%'></td><td align='85%'>".$row['troubledesc']."</td></tr><tr><td></td></tr></table>
    </td></tr></table></td></tr>
    <!--<tr><td width='100%' align='left'><strong>Customer Signature  ______________________________________________</strong></td></tr>-->
    <tr><td>
    Computer Answers is not responsible for any lost data under any circumstances.<br><br>

    Please note that by filling out this form you are agreeing to respond or pick up your computer within 30 days of receiving a diagnosis.
    At 30 days after your diagnosis your computer will be considered abandoned property.
     This policy is in place only to ensure quick turn around time on all of our computer repairs. Thank you!
    </td></tr>
    </table>";

	}

    if ($copyversion == "Store Copy") {
		echo "<br /><br /><table align='center' width='90%' align='center' CELLSPACING='0' CELLPADDING='3'><tr>
    </tr>
    <tr><td width='100%' align='left'><strong>Customer Signature  ______________________________________________</strong></td></tr><tr><td></td></tr></table>";
		echo "<div style='page-break-before:always;'></div>";
    }
    $counter = $counter + 1;
    $copyversion = "Customer Copy";
}
?>
</body>
</html>
