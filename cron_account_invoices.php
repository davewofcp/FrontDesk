<?php

// TO BE RUN DAILY AT ANY CONVENIENT HOUR

require_once("mysql_connect.php");
require_once("core/common.php");

$br = "";
if (isset($_SERVER["SERVER_NAME"])) {
	require_once("core/sessions.php");
	if (!isset($USER)) {
		header("Location: login.php"); exit;
	}
	if (!TFD_HAS_PERMS('admin','use')) {
		echo "Only administrators may run this script from the web.";
		exit;
	}
	$br = "<br>";
}

// $data = mysql_query("
// SELECT *,
//   DATE_ADD(last_invoice,INTERVAL period DAY) AS next_invoice
// FROM customer_accounts
// JOIN customers
//   ON accounts.customer_id = customers.customer_id
// WHERE DATE_ADD(last_invoice,INTERVAL period DAY) <= CAST(NOW() AS date)
// ");

$data = mysql_query("
SELECT
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
    invoices AS inv
  ON
    (
      inv.customers__id = i.customers__id
      AND inv.emailed = i.latest
    )
WHERE
  DATE_ADD( inv.emailed, INTERVAL period DAY ) <= CAST( NOW() AS date )
");
if(!mysql_num_rows($data)) echo '<p style="text-align:center;">Nothing to run. <a href="/?module=acct">Go back.</a>'."\n";
while ($row = mysql_fetch_assoc($data)) {
	unset($OVERAGE_DESCR);
	unset($ZERO_INVOICE);

// 	$result = mysql_query("
// SELECT IFNULL(SUM(amount),0) AS total
// FROM labor
// WHERE account_id = ". $row["account_id"] ."
//   AND CAST(ts AS date) >= '".$row["last_invoice"]."'
//   AND CAST(ts AS date) < '".$row["next_invoice"]."'
// ");

  $result = mysql_query("
SELECT
  IFNULL( SUM( amount ), 0 ) AS total
FROM
  issue_labor
WHERE
  customer_accounts__id = " . $row['id'] . "
  AND CAST( ts AS date ) >= '" . $row['last_invoice'] . "'
  AND CAST( ts AS date ) < '" . $row['next_invoce'] . "'
  ");

	$data2 = mysql_fetch_assoc($result);
	$HOURS_WORKED = floatval($data2["total"]);
	$OVERAGE_HOURS = 0;
	$BLOCK_AMOUNT = 0;
	if ($HOURS_WORKED > $row["block_hours"]) {
		$OVERAGE_HOURS = $row["block_hours"] - $HOURS_WORKED;
		$BLOCK_AMOUNT = $row["block_hours"];
	} else {
		$BLOCK_AMOUNT = $HOURS_WORKED;
	}
	$OVERAGE_AMOUNT = $row["overage_rate"] * $OVERAGE_HOURS;
	if ($OVERAGE_AMOUNT > 0) {
		$OVERAGE_DESCR = "Overage Rate - ".$OVERAGE_HOURS." hours @ $".round(floatval($row["overage_rate"]),2)."/hr ".$row["last_invoice"]." to ".$row["next_invoice"];
	}

	$TERM_AMOUNT = 0;
	if ($row["block_rate"] > 0) { // Ignore Term amount
		if ($HOURS_WORKED > $row["block_hours"]) {
			$TERM_AMOUNT = $row["block_hours"] * $row["block_rate"];
			$TERM_DESCR = "Regular Rate - ".$row["block_hours"]." hours @ $".round(floatval($row["block_rate"]),2)."/hr ".$row["last_invoice"]." to ".$row["next_invoice"];
		} else {
			$TERM_AMOUNT = $HOURS_WORKED * $row["block_rate"];
			$TERM_DESCR = "Regular Rate - ".$HOURS_WORKED." hours @ $".round(floatval($row["block_rate"]),2)."/hr ".$row["last_invoice"]." to ".$row["next_invoice"];
		}
	} else {
		$TERM_AMOUNT = $row["amount"];
		if ($HOURS_WORKED > 0) $TERM_DESCR = "Term Payment - ".$HOURS_WORKED." hours worked ".$row["last_invoice"]." to ".$row["next_invoice"];
		else $TERM_DESCR = "Term Payment - ".$row["last_invoice"]." to ".$row["next_invoice"];
	}

	// If Block Rate is set and no hours were worked, don't generate an invoice (but roll the account over)
	if ($HOURS_WORKED == 0 && $row["block_rate"] > 0) $ZERO_INVOICE = 1;

	$IID = 0;

	if (!isset($ZERO_INVOICE)) {
		// Create Invoice Header
		$TOTAL = $TERM_AMOUNT + $OVERAGE_AMOUNT;

// 		$sql = "INSERT INTO invoices (customer_id,amt,account_id) VALUES (";
// 		$sql .= $row["customer_id"] .",";
// 		$sql .= "'". round($TOTAL,2) ."',";
// 		$sql .= $row["account_id"] .")";

    $sql = "
INSERT INTO
  invoices
    (
      customers__id,
      amt,
      customer_accounts__id,
      org_entities__id
    )
VALUES
  (
    " . $row['customers__id'] . ",
    '" . round( $TOTAL , 2 ) . "',
    " . $row['customer_accounts__id'] . ",
    {$USER['org_entities__id']}
  )
";

		mysql_query($sql) or die(mysql_error() ."::". $sql);

		$IID = mysql_insert_id();

		// Term Payment line item
// 		$sql = "INSERT INTO invoice_items (invoice_id,name,descr,cost,qty,taxable,from_table,from_key_name,from_key,writeback,is_heading,grp) VALUES (";
// 		$sql .= $IID .",";
// 		$sql .= "'Term Payment',";
// 		$sql .= "'". mysql_real_escape_string($TERM_DESCR) ."',";
// 		$sql .= "'". $TERM_AMOUNT ."',";
// 		$sql .= "1,0,NULL,NULL,NULL,'no',0,";
// 		$sql .= "'". new_salt(10) ."')";

    $sql = "
INSERT INTO
  invoice_items
    (
      invoices__id,
      name,
      descr,
      cost,
      qty,
      is_taxable,
      from_table,
      from_key_name,
      from_key,
      writeback,
      is_heading,
      grp,
      org_entities__id
    )
VALUES
  (
    " . $IID . ",
    'Term Payment',
    '" . MYSQL_REAL_ESCAPE_STRING( $TERM_DESCR ) . "',
    '" . $TERM_AMOUNT . "',
    1,
    0,
    NULL,
    NULL,
    NULL,
    'no',
    0,
    '". new_salt( 10 ) . "',
    {$USER['org_entities__id']}
  )
";

		mysql_query($sql) or die(mysql_error() ."::". $sql);

		if (isset($OVERAGE_DESCR)) {
			// Overage Payment line item
// 			$sql = "INSERT INTO invoice_items (invoice_id,name,descr,cost,qty,taxable,from_table,from_key_name,from_key,writeback,is_heading,grp) VALUES (";
// 			$sql .= $IID .",";
// 			$sql .= "'Overage Payment',";
// 			$sql .= "'". mysql_real_escape_string($OVERAGE_DESCR) ."',";
// 			$sql .= "'". $OVERAGE_AMOUNT ."',";
// 			$sql .= "1,0,NULL,NULL,NULL,'no',0,";
// 			$sql .= "'". new_salt(10) ."')";

			$sql = "
INSERT INTO
  invoice_items
    (
      invoice_id,
      name,
      descr,
      cost,
      qty,
      taxable,
      from_table,
      from_key_name,
      from_key,
      writeback,
      is_heading,
      grp,
      org_entities__id
    )
VALUES
  (
    " . $IID .",
    'Overage Payment',
    '" . MYSQL_REAL_ESCAPE_STRING($OVERAGE_DESCR) . "',
    '" . $OVERAGE_AMOUNT . "',
    1,
    0,
    NULL,
    NULL,
    NULL,
    'no',
    0,
    '" . new_salt( 10 ) . "',
    {$USER['org_entities__id']}
  )
";

			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}
		echo "Generated invoice for ".$row["firstname"]." ".$row["lastname"].", total: $".number_format($TOTAL,2);
	} else {
		echo "Skipped invoice for ".$row["firstname"]." ".$row["lastname"].", block rate set and no hours worked$br\n";
	}

	// Roll the account over to the next period
// 	mysql_query("UPDATE accounts SET last_invoice = '".$row["next_invoice"]."', last_inv_id = ".intval($IID)." WHERE account_id = ".$row["account_id"]);

// ray said:
//   no need to update customer_accounts here to roll over, it's automatic now due to the
//   updated query at top, which joins in last_invoice and last_inv_id from invoices

	if (isset($ZERO_INVOICE)) {
		continue;
	}

	$TERM_AMOUNT = number_format($TERM_AMOUNT,2);
	$OVERAGE_AMOUNT = number_format($OVERAGE_AMOUNT,2);
	$TOTAL = number_format($TOTAL,2);

	$semi_rand = md5(time());
	$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

	$TO = $row["email"];
	$TOA = explode(",",$TO);
	foreach ($TOA as $address) {
		if (!check_email_address(trim($address))) {
			echo "... email NOT sent (invalid or missing address: '".trim($address)."')$br\n";
			continue;
		}
	}

	if ($row["company"] == null || $row["company"] == "") {
		$company = "";
	} else {
		$company = $row["company"] ."<br>";
	}

	$ITEMS = "Term Payment" . (isset($OVERAGE_DESCR) ? "<br>\nOverage Payment" : "");
	$DESCRS = $TERM_DESCR . (isset($OVERAGE_DESCR) ? "<br>\n$OVERAGE_DESCR" : "");
	$AMTS = "$$TERM_AMOUNT" . (isset($OVERAGE_DESCR) ? "<br>\n$$OVERAGE_AMOUNT" : "");

	$INVOICE = <<<EOF
<html>
<body>
<table border="0" width="100%">
 <tr>
  <td><img src="http://www.computer-answers.com/images/logo.gif" width="220" height="125"></td>
  <td align="right">
   <font size="+3">Invoice</font><br>
   <table style="border: 1px solid #000;">
    <tr align="center">
     <td>Date</td>
     <td>Invoice #</td>
    </tr>
    <tr align="center">
     <td>{$row["next_invoice"]}</td>
     <td>$IID</td>
    </tr>
   </table>
  </td>
 </tr>
</table><br>

<table style="border: 1px solid #000;">
 <tr><td align="center">Bill To</td></tr>
 <tr><td>
{$row["firstname"]} {$row["lastname"]}<br>
$company
{$row["address"]}<br>
{$row["city"]}, {$row["state"]} {$row["postcode"]}
</table><br>

<table width="100%">
 <tr align="center">
  <td style="border: 1px solid #000;">Item</td>
  <td style="border: 1px solid #000;">Description</td>
  <td style="border: 1px solid #000;">Amount</td>
 </tr>
 <tr>
  <td style="border: 1px solid #000;">$ITEMS</td>
  <td style="border: 1px solid #000;">$DESCRS</td>
  <td style="border: 1px solid #000;" align="right">$AMTS</td>
 </tr>
 <tr>
  <td></td>
  <td align="right"><font size="+2">Total</font></td>
  <td style="border: 1px solid #000;" align="center">$$TOTAL</td>
 </tr>
</table>

</body>
</html>
EOF;

	$FROM = "frontdesk@computer-answers.com";
	$SUBJECT = "Computer Answers Invoice for ".$row["last_invoice"]." to ".$row["next_invoice"];
	$MESSAGE = "Your account invoice is attached. Any hours worked today will be included in your next invoice, ".$row["period"]." days from now.";
	$MESSAGE = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/plain; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $MESSAGE . "\n\n";
	$HEADERS = "From: ".$FROM."\n";
	$HEADERS .= "MIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";
	$MESSAGE .= "--{$mime_boundary}\n";

	$msgdata = chunk_split(base64_encode($INVOICE));
	$MESSAGE .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"Invoice.html\"\n" .
	"Content-Disposition: attachment;\n" . " filename=\"Invoice.html\"\n" .
	"Content-Transfer-Encoding: base64\n\n" . $msgdata . "\n\n";
	$MESSAGE .= "--{$mime_boundary}\n";

	$ok = mail($TO,$SUBJECT,$MESSAGE,$HEADERS);
	if ($ok) {
		echo "... email sent to ".$TO." OK$br\n";
		//mysql_query("UPDATE invoices SET emailed = NOW(), toi = toi WHERE invoice_id = ".$IID);

    mysql_query("UPDATE invoices SET emailed = NOW(), toi = toi WHERE id = ".$IID);

	} else {
		echo "... ERROR sending email to ".$TO.$br."\n";
	}
}

?>