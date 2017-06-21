<?php

$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
if (mysql_num_rows($result)) {
	$data = mysql_fetch_assoc($result);
	$STORE_NAME = $data["title"];
} else {
	$STORE_NAME = "Albany";
}

?>
<!DOCTYPE HTML>
<html>
<head>
<title>Customer Portal Login Information</title>
</head>
<body onload="window.print();">
<div align="center">
<img src="images/logo.gif" width="150" height="80"><br>
<h2>Customer Portal Login Information</h2>

<b>URL:</b> http://portal.computer-answers.com:6080/customers<br><br>

<table border="0">
 <tr>
  <td align="right"><b>Store:</b></td>
  <td><?php echo $STORE_NAME; ?></td>
 </tr>
 <tr>
  <td align="right"><b>Customer ID:</b></td>
  <td><?php echo $CUSTOMER["id"]; ?></td>
 </tr>
 <tr>
  <td align="right"><b>Password:</b></td>
  <td><?php echo $C_PASS; ?></td>
 </tr>
</table><br>

The customer portal allows you to check on the status of your issues and move them from 'Waiting on Customer' to 'Do It' when appropriate. Additionally, you can view issue details, view any unpaid invoices, and print receipts for all your transactions.<br><br>

It is recommended that you either keep this sheet for future use or change your password to something more memorable (or both).<br><br>

</div>
</body>
</html>
