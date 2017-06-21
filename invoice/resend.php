<?php

require_once("../mysql_connect.php");
require_once("../core/sessions.php");
require_once("../core/common.php");
if (!isset($USER)) {
	header("Location: login.php");
	exit;
}
if (!TFD_HAS_PERMS('admin','use')) die("Only administrators may use this function.");

if (!isset($_GET["id"])) die("No account specified.");
$account_id = intval($_GET["id"]);

//$result = mysql_query("SELECT *,DATE_SUB(last_invoice,INTERVAL period DAY) AS prev_invoice FROM accounts JOIN customers ON accounts.customer_id = customers.customer_id WHERE account_id = $account_id");
$result = mysql_query("
SELECT
  customer_accounts.*,
  invoices.id AS last_inv_id,
  invoices.emailed AS last_invoice,
  DATE_ADD( last_invoice, INTERVAL period DAY ) AS next_invoice,
  DATE_SUB( last_invoice, INTERVAL period DAY ) AS prev_invoice
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
    invoices
  ON
    (
      invoices.customers__id = i.customers__id
      AND invoices.emailed = i.latest
    )
WHERE
  customer_accounts.id = {$account_id}
");
$row = mysql_fetch_assoc($result);

if (!$row["last_inv_id"]) die("Previous invoice not recorded.");

echo "Re-sending invoice {$row["last_inv_id"]} for {$row["firstname"]} {$row["lastname"]}";

$result = mysql_query("SELECT * FROM invoices WHERE id = ".intval($row["last_inv_id"]));
$data = mysql_fetch_assoc($result);
$TOTAL = $data["amt"];

$ITEMS = array();
$result = mysql_query("SELECT * FROM invoice_items WHERE invoices__id = ".intval($row["last_inv_id"]));
while ($item = mysql_fetch_assoc($result)) {
	$ITEMS[] = $item;
	if (count($ITEMS) == 1) {
		$TERM_DESCR = $item["descr"];
		$TERM_AMOUNT = number_format(floatval($item["cost"]),2);
	} else {
		$OVERAGE_DESCR = $item["descr"];
		$OVERAGE_AMOUNT = number_format(floatval($item["cost"]),2);
	}
}

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
<td>{$row["last_inv_id"]}</td>
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
$SUBJECT = "Computer Answers Invoice for ".$row["prev_invoice"]." to ".$row["last_invoice"];
$MESSAGE = "Your account invoice is attached.";
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
	mysql_query("UPDATE invoices SET emailed = NOW(), toi = toi WHERE invoice_id = ".$row["last_inv_id"]);
} else {
	echo "... ERROR sending email to ".$TO.$br."\n";
}

?>