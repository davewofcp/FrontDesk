<?php

//$data = mysql_query("SELECT *,DATE_ADD(last_invoice,INTERVAL period DAY) AS next_invoice FROM accounts JOIN customers ON accounts.customer_id = customers.customer_id");

$data = mysql_query("
SELECT
  c.*,
  a.*,
  inv.id AS last_inv_id,
  inv.emailed AS last_invoice,
  DATE_ADD( inv.emailed, INTERVAL period DAY ) AS next_invoice
FROM
  customer_accounts AS a
  JOIN
    customers AS c
  ON
    a.customers__id = c.id
  LEFT OUTER JOIN
    (
      SELECT
        customers__id,
        MAX( emailed ) AS latest
      FROM invoices
      GROUP BY customers__id
    )
    AS i
  ON
    i.customers__id = c.id
  LEFT OUTER JOIN
    invoices inv
  ON
    (
      inv.customers__id = i.customers__id
      AND inv.emailed = i.latest
    )
WHERE 1
");

$ACCOUNTS = array();
while ($row = mysql_fetch_assoc($data)) {
	$ACCOUNTS[$row["id"]] = $row;
}
?><h2>Customer Accounts</h2>

<?php echo alink("New Account","?module=acct&do=new_account"); ?>


<?php
echo "<br><br>";

if(isset($_GET["delete"]))echo "<b>Account Deleted!</b><br><br>";

foreach ($ACCOUNTS as $id => $account) {
	$customer = $account["firstname"] ." ". $account["lastname"];
	if ($account["company"] != "") $customer = $account["company"];
  	$phone_home = display_phone($account["phone_home"]);
  	$phone_cell = display_phone($account["phone_cell"]);
  	$amt = number_format($account["amount"],2);
  	$rate = number_format($account["block_rate"],2);
  	$orate = number_format($account["overage_rate"],2);

  	$button_view = alink("View / Edit","?module=acct&do=view&id=".$id);
  	$button_cust = alink("Go To Customer","?module=cust&do=view&id=".$account["customers__id"]);
  	$button_inv = alink("Create Invoice","?module=invoice&do=create&customer_id=".$account["customers__id"]);
  	$button_iss = alink("New Issue","?module=iss&do=new&account=".$id);
  	if ($account["is_disabled"]) {
  		$button_pause = alink_onclick("Enable","?module=acct&do=unpause&id=".$id,"javascript:return confirm('This account's billing cycle of {$account["period"]} days will start today. Are you sure you want to do this?');");
  	} else {
  		$button_pause = alink_onclick("Disable","?module=acct&do=pause&id=".$id,"javascript:return confirm('This account will not be billed until {$account["period"]} days after it is re-enabled. Are you sure you want to do this?');");
  	}
  	$button_delete = alink_onclick("Delete","?module=acct&do=delete&id=".$id,"javascript:return confirm('Are you sure you want to delete this account?');");
	echo <<<EOF
<div class="maininv">
<table border="0" width="100%">
 <tr class="heading" align="center">
  <td>ID</td>
  <td>Customer</td>
  <td>Phone (H)</td>
  <td>Phone (C)</td>
  <td>Amount</td>
  <td>Hours</td>
  <td>Rate/hr</td>
  <td>Ovg Rate/hr</td>
  <td>Last Invoice</td>
  <td>Next Invoice</td>
 </tr>
 <tr align="center">
  <td>$id</td>
  <td>$customer</td>
  <td>$phone_home</td>
  <td>$phone_cell</td>
  <td>$$amt</td>
  <td>{$account["block_hours"]}</td>
  <td>$$rate</td>
  <td>$$orate</td>
  <td>{$account["last_invoice"]}</td>
  <td>{$account["next_invoice"]}</td>
 </tr>
 <tr>
  <td colspan="10" align="center">
  $button_view - $button_cust - $button_inv - $button_iss - $button_pause - $button_delete
  </td>
 </tr>
</table>
</div>
EOF;
}

	if (TFD_HAS_PERMS('admin','use')) {
		echo "<div class=\"clear\">". alink("Manually Run Invoice Generator","cron_account_invoices.php") ."</div>";
	}

?>
