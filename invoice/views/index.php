<?php

$view = "ar";
if (isset($_GET["view"]) && $_GET["view"] == "paid") $view = "paid";
$acct = "no";
if (isset($_GET["acct"]) && $_GET["acct"] == "yes") $acct = "yes";

display_header();

?><h2>Invoices<?php echo ($view == "ar" ? "<!--Accounts Receivable-->" : " - Paid"); ?></h2>

<?php if (isset($RESPONSE)) { ?><font color="red"><b><?php echo $RESPONSE; ?></b></font><br><br><?php } ?>

<script type="text/javascript">
window.onload = function() { html('barcode').focus(); }
</script>

<form action="?module=invoice&do=scan" method="post">
<b>Scan or Type Invoice # and hit Enter:</b> <input type="edit" name="id" id="barcode" size="10">
</form>

<?php
  echo "<div class=\"inline\" style=\"padding:13px;\">". alink("Create Invoice","?module=invoice&do=create") ."</div>";
if ($view == "ar") {
  echo "<div class=\"inline\" style=\"padding:13px;\">". alink("View Paid","?module=invoice&view=paid&acct=$acct") ."</div>";
} else {
  echo "<div class=\"inline\" style=\"padding:13px;\">". alink("View Unpaid","?module=invoice&view=ar&acct=$acct") ."</div>";
}
if ($acct == "no") {
	echo "<div class=\"inline\" style=\"padding:13px;\">". alink("View Account Invoices","?module=invoice&view=$view&acct=yes") ."</div>";
} else {
	echo "<div class=\"inline\" style=\"padding:13px;\">". alink("View Customer Invoices","?module=invoice&view=$view&acct=no") ."</div>";
}
?><br><br>


<?php

$where = "WHERE ";

if ($view == "ar"){
	$where .= "amt_paid < amt";
} else {
	$where .= "amt_paid >= amt";
}

if ($acct == "no") {
	$where .= " AND invoices.customer_accounts__id IS NULL";
} else {
	$where .= " AND invoices.customer_accounts__id IS NOT NULL";
}

$result = mysql_query("SELECT *,invoices.customers__id AS cust_id,CAST(toi AS date) AS dt FROM invoices LEFT JOIN customers ON invoices.customers__id = customers.id LEFT JOIN customer_accounts ON customers.id = customer_accounts.customers__id $where GROUP BY invoices.id DESC");

$TOTAL = 0;
while ($row = mysql_fetch_assoc($result)) {
	if ($view != "paid") {
		$TOTAL += $row["amt"] - $row["amt_paid"];
	} else {
		$TOTAL += $row["amt_paid"];
	}

	if ($view!="paid") { $button_checkout = alink("Checkout","?module=invoice&do=checkout&id=". $row["id"]) ." - "; } else { $button_checkout = ""; }
	$button_view = alink("View","?module=invoice&do=view&id=". $row["id"]);
	$button_delete = alink_onclick("Delete","?module=invoice&do=delete&id=". $row["id"],"return confirm('Are you sure you wish to delete this Invoice?');");
	$button_print = alink_pop("Print","?module=invoice&do=print&id=". $row["id"]);
	$button_cust = alink("Customer","?module=cust&do=view&id=". $row["cust_id"]);

	if ($row["customer_accounts__id"] != null) {
		$button_acct = " - ". alink("Account","?module=acct&do=view&id=". $row["customer_accounts__id"]);
		$button_email = " - ". alink_onclick("Re-Email","invoice/resend.php?id=". $row["customer_accounts__id"],"return confirm('Are you sure you want to send another email?');");
		$customer = $row["company"];
	} else {
		$button_acct = "";
		$button_email = "";
		$customer = $row["firstname"] ." ". $row["lastname"];
	}

	$phone_home = display_phone($row["phone_home"]);
	$phone_cell = display_phone($row["phone_cell"]);
	$amount = number_format($row["amt"],2);
	$paid = number_format($row["amt_paid"],2);
	$due = number_format($row["amt"] - $row["amt_paid"],2);

	$emailed = ($row["emailed"] == null ? "No" : $row["emailed"]);

	echo <<<EOF
<div class="maininv" style="width:770px;margin:3px 0px 3px 0px;">
<table width="100%">
 <tr class="heading" align="center">
  <td>#</td>
  <td>Date</td>
  <td>Customer</td>
  <td>Phone (H)</td>
  <td>Phone (C)</td>
  <td>Amount</td>
EOF;
	if ($view != "paid") {
		echo <<<EOF
  <td>Paid</td>
  <td>Due</td>
  <td>Emailed</td>
EOF;
	}
	echo <<<EOF
 </tr>
 <tr align="center">
  <td>{$row["id"]}</td>
  <td>{$row["dt"]}</td>
  <td>$customer</td>
  <td>$phone_home</td>
  <td>$phone_cell</td>
  <td>$$amount</td>
EOF;
	if ($view != "paid") {
		echo <<<EOF
  <td>$$paid</td>
  <td>$$due</td>
  <td>$emailed</td>
EOF;
	}
	echo <<<EOF
 </tr>
 <tr>
  <td colspan="9" align="center">
  $button_checkout$button_view - $button_delete - $button_print - $button_cust$button_acct$button_email
  </td>
 </tr>
</table>
</div>
EOF;
}

?>
<div class="clear"><br></div>

<font size="+2">Total<?php if ($view != "paid") { echo " Due"; } ?>: $<?php echo number_format($TOTAL,2); ?></font>

<div class="clear"><br><br></div>
<?php

display_footer();

?>
