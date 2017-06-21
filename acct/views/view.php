<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
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
	if (s.value == '') return;
	xmlhttp.abort();
	xmlhttp.onreadystatechange = resultHandler;
	xmlhttp.open("GET","cust/ajax.php?cmd=search&page=areplace&aid=<?php echo $ACCOUNT["id"]; ?>&str="+s.value+"&sb=6",true);
	xmlhttp.send();
}
</script>
<style type="text/css">
.itemcontent {
  padding: 5px 0px 5px;
  text-align: center;
}
.itemrow {
  clear: left;
  position: relative;
  float: none;
  display: block;
  text-align: center;
  margin-left: 250px;
}
</style><?php

?>
<div class="relative center inline" style="padding:7px;"><?php echo alink("View Accounts","?module=acct"); ?></div>
&#149;
<div class="relative center inline" style="padding:7px;"><?php echo alink("Create Invoice","?module=invoice&do=create&customer_id=".$ACCOUNT["customers__id"]); ?></div>
&#149;
<div class="relative center inline" style="padding:7px;"><?php echo alink("Create New Issue","?module=iss&do=new&account=".$ACCOUNT["id"]); ?></div>
&#149;
<div class="relative center inline" style="padding:7px;"><?php echo alink_onclick("Delete","?module=acct&do=delete&id=".$ACCOUNT["id"],"javascript:return confirm('Are you sure you want to delete this account?');"); ?></div>

<h2>Account for <?php echo $ACCOUNT["firstname"] ." ". $ACCOUNT["lastname"]; ?> (<?php echo alink("#". $ACCOUNT["customers__id"],"?module=cust&do=view&id=".$ACCOUNT["customers__id"]); ?>)</h2>

<?php if (isset($RESPONSE)) { echo "<b>". $RESPONSE ."</b><br><br>\n\n"; } ?>

<table border="0">
 <tr class="heading" align="center">
  <td>Sex</td>
  <td>DOB (age)</td>
  <td>Company</td>
  <td>Address</td>
 </tr>
 <tr align="center">
  <td><?php echo ($ACCOUNT["is_male"] ? "Male" : "Female"); ?></td>
  <td><?php echo $ACCOUNT["dob"] ." (". age($ACCOUNT["dob"]) .")"; ?></td>
  <td><?php echo $ACCOUNT["company"]; ?></td>
  <td><?php echo $ACCOUNT["address"] ."<br>\n". $ACCOUNT["city"] .", ". $ACCOUNT["state"] ." ". $ACCOUNT["postcode"]; ?></td>
 </tr>
 <tr class="heading" align="center">
  <td>Email</td>
  <td>Phone (H)</td>
  <td>Phone (C)</td>
  <td>Referral</td>
 </tr>
 <tr align="center">
  <td><?php echo $ACCOUNT["email"]; ?></td>
  <td><?php echo display_phone($ACCOUNT["phone_home"]); ?></td>
  <td><?php echo display_phone($ACCOUNT["phone_cell"]); ?></td>
  <td><?php echo $ACCOUNT["referral"]; ?></td>
 </tr>
</table><br><br>

<b>Account #<?php echo $ACCOUNT["id"]; ?> created <?php echo $ACCOUNT["created"]; ?></b><br>
Last Invoice: <b><?php echo $ACCOUNT["last_invoice"]; ?></b><br>
Next Invoice: <b><?php echo $ACCOUNT["next_invoice"]; ?></b><br><br>

<form action="?module=acct&do=edit&id=<?php echo $ACCOUNT["id"]; ?>" method="post">
<table border="0">
 <tr class="heading" align="center">
  <td>Term Duration (days)</td>
  <td>Term Payment</td>
  <td>Block Hours</td>
  <td>Rate/hr *</td>
  <td>Overage Rate/hr</td>
  <!--td>Last Invoice</td-->
 </tr>
 <tr align="center">
  <td><input type="edit" name="period" size="3" value="<?php echo $ACCOUNT["period"]; ?>"></td>
  <td>$<input type="edit" name="amount" size="5" value="<?php echo number_format($ACCOUNT["amount"],2); ?>"></td>
  <td><input type="edit" name="block_hours" size="2" value="<?php echo $ACCOUNT["block_hours"]; ?>"></td>
  <td>$<input type="edit" name="block_rate" size="4" value="<?php echo number_format(floatval($ACCOUNT["block_rate"]),2); ?>"></td>
  <td>$<input type="edit" name="overage_rate" size="4" value="<?php echo number_format(floatval($ACCOUNT["overage_rate"]),2); ?>"></td>
  <!--td><input type="edit" id="last_invoice" name="last_invoice" size="10" value="<?php echo $ACCOUNT["last_invoice"]; ?>"></td-->
 </tr>
 <tr align="center">
  <td colspan="6">
  * Leave Rate at 0 unless you want to override the Term Payment amount.<br>
  <input type="submit" value="Edit Account">
  </td>
 </tr>
</table>
</form>

<b>Transfer Account to Customer:</b> <input id="search" size="30" onKeyUp="showResult()">
<div id="resultbox" style="background-color:#fff;position:absolute;width:400px;left:50%;margin-left:-200px;"></div>
<br>
<b>Note:</b> This will transfer all existing invoices and<br>payments for this customer to the new customer as well.<br>

<hr>

<h3>Hours Worked This Term</h3>
<table border="0">
 <tr class="heading" align="center">
  <td>Date</td>
  <td>Tech</td>
  <td>Issue</td>
  <td>Amount</td>
 </tr>
<?php

$total = 0;
$result = mysql_query("SELECT * FROM issue_labor JOIN users ON issue_labor.users__id = users.id WHERE issue_labor.customer_accounts__id = ".$ACCOUNT["id"]." AND CAST(ts AS date) >= '".$ACCOUNT["last_invoice"]."' AND CAST(ts AS date) < '".$ACCOUNT["next_invoice"]."'");
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\">\n";
	echo "  <td>". $row["ts"] ."</td>\n";
	echo "  <td>". $row["username"] ."</td>\n";
	echo "  <td>". alink("#".$row["issues__id"],"?module=iss&do=view&id=".$row["issues__id"]) ."</td>\n";
	echo "  <td>". floatval($row["amount"]) ."</td>\n";
	echo " </tr>\n";
	$total += $row["amount"];
}

echo " <tr align=\"center\"><td colspan=\"4\">". $total ." Hours Worked</td></tr>\n";

?>
</table>

<hr>

<form action="?module=acct&do=payment&id=<?php echo $ACCOUNT["id"]; ?>" method="post">
<h3>Unpaid Invoices</h3>
<table border="0">
 <tr class="heading">
  <td>Pay</td>
  <td>Invoice ID</td>
  <td>Amount</td>
  <td>Amount Paid</td>
  <td>Date/Time</td>
  <td>View</td>
 </tr>
<?php

$total = 0;
$result = mysql_query("SELECT * FROM invoices WHERE customers__id = ". $ACCOUNT["customers__id"] ." AND amt_paid < amt");
while ($invoice = mysql_fetch_assoc($result)) {
	echo " <tr>\n";
	echo "  <td><input type=\"checkbox\" name=\"inv". $invoice["id"] ."\" value=\"1\"></td>\n";
	echo "  <td>". $invoice["id"] ."</td>\n";
	echo "  <td>$". number_format($invoice["amt"],2) ."</td>\n";
	echo "  <td>$". number_format($invoice["amt_paid"],2) ."</td>\n";
	echo "  <td>". $invoice["toi"] ."</td>\n";
	echo "  <td>". alink("View","?module=invoice&do=view&id=". $invoice["id"]) ."</td>\n";
	echo " </tr>\n";

	$total += $invoice["amt"] - $invoice["amt_paid"];
}

?>
</table>

<h3>Total to be paid: $<?php echo number_format($total,2); ?></h3>

<table border="0">
 <tr>
  <td class="heading">Cash Amount</td>
  <td>$<input type="edit" name="paid_cash" size="6"></td>
 </tr>
 <tr>
  <td class="heading">Check Amount</td>
  <td>$<input type="edit" name="paid_check" size="6"></td>
 </tr>
 <tr>
  <td class="heading">Credit Amount</td>
  <td>$<input type="edit" name="paid_credit" size="6"></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <input type="submit" value="Post Payment">
  </td>
 </tr>
</table></form>

<hr>

<h3>Payments Received</h3>
<table border="0">
 <tr class="heading">
  <td>Date</td>
  <td>Cash</td>
  <td>Check</td>
  <td>Credit</td>
  <td>Total</td>
  <td>Applied To</td>
 </tr>
<?php

$result = mysql_query("SELECT * FROM pos_payments WHERE customers__id = ". $ACCOUNT["customers__id"]);
while ($payment = mysql_fetch_assoc($result)) {
	echo " <tr>\n";
	echo "  <td>". $payment["top"] ."</td>\n";
	echo "  <td>$". number_format($payment["paid_cash"],2) ."</td>\n";
	echo "  <td>$". number_format($payment["paid_check"],2) ."</td>\n";
	echo "  <td>$". number_format($payment["paid_credit"],2) ."</td>\n";
	echo "  <td>$". number_format($payment["paid_cash"] + $payment["paid_check"] + $payment["paid_credit"],2) ."</td>\n";
	echo "  <td>";
	$applied_to = explode(":",$payment["applied_to"]);
	$did = 0;
	foreach ($applied_to as $invoice) {
		if (intval($invoice) == 0) continue;
		$did++;
		echo alink("#". $invoice,"?module=invoice&do=view&id=".$invoice) ." ";
	}
	if ($did == 0) echo "<i>Nothing</i>";
	echo "</td>";
	echo " </tr>\n";
}

?>
</table>

<script type="text/javascript">
calendar.set("last_invoice");
</script>
