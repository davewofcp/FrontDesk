<?php

if (!isset($USER)) {
	header("Location: /login.php"); exit;
}

display_header();

if (isset($_GET['do'])) {
	switch ($_GET['do']) {
		case "new":
			$account_id = create_account();
			if ($account_id == 0) {
				$RESPONSE = "Unable to create account.";
				include "views/index.php";
			} else {

// 				$ACCOUNT = mysql_fetch_assoc(mysql_query("SELECT *,DATE_ADD(last_invoice,INTERVAL period DAY) AS next_invoice FROM customer_accounts JOIN customers ON customer_accounts.customers__id = customers.id WHERE customer_accounts.id = $account_id"));

$ACCOUNT = mysql_fetch_assoc(mysql_query($sql="
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
WHERE
  a.id = {$account_id}
"));

				include "views/view.php";
			}
			break;
		case "view":
// 			$ACCOUNT = mysql_fetch_assoc(mysql_query("SELECT *,DATE_ADD(last_invoice,INTERVAL period DAY) AS next_invoice FROM accounts JOIN customers ON accounts.customer_id = customers.customer_id WHERE accounts.account_id = ".intval($_GET["id"])));

$ACCOUNT = mysql_fetch_assoc(mysql_query("
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
WHERE
  a.id = ".intval($_GET["id"])
));

			if (!$ACCOUNT) {
				$RESPONSE = "Unable to locate that account.";
				include "views/index.php";
			} else {
				set_customer($ACCOUNT["customers__id"]);
				include "views/view.php";
			}
			break;
		case "pause":
			pause_account(intval($_GET["id"]));
			include "views/index.php";
			break;
		case "unpause":
			unpause_account(intval($_GET["id"]));
			include "views/index.php";
			break;
		case "delete":
			$AID = intval($_GET["id"]);
			delete_account($AID);
			include "views/index.php";
			break;
		case "new_account":
			if (isset($_GET["customer"])) {
				$CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ". intval($_GET["customer"])));
				include "views/new_account_form.php";
			} else {
				include "views/select_customer.php";
			}
			break;
		case "replace_cust":
			$RESPONSE = replace_customer();

// 			$ACCOUNT = mysql_fetch_assoc(mysql_query("SELECT *,DATE_ADD(last_invoice,INTERVAL period DAY) AS next_invoice FROM accounts JOIN customers ON accounts.customer_id = customers.customer_id WHERE accounts.account_id = ".intval($_GET["account_id"])));

$ACCOUNT = mysql_fetch_assoc(mysql_query("
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
WHERE
  a.id = ".intval($_GET["account_id"])
));

			include "views/view.php";
			break;
		case "payment":

// 			$ACCOUNT = mysql_fetch_assoc(mysql_query("SELECT *,DATE_ADD(last_invoice,INTERVAL period DAY) AS next_invoice FROM accounts JOIN customers ON accounts.customer_id = customers.customer_id WHERE accounts.account_id = ".intval($_GET["id"])));

$ACCOUNT = mysql_fetch_assoc(mysql_query("
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
WHERE
  a.id = ".intval($_GET["id"])
));


			if (!$ACCOUNT) {
				$RESPONSE = "Unable to locate that account.";
				include "views/index.php";
			} else {
				accept_payment($ACCOUNT["customers__id"]);
				set_customer($ACCOUNT["customers__id"]);
				include "views/view.php";
			}
			break;
		case "edit":
			edit_account();

// 			$ACCOUNT = mysql_fetch_assoc(mysql_query("SELECT *,DATE_ADD(last_invoice,INTERVAL period DAY) AS next_invoice FROM accounts JOIN customers ON accounts.customer_id = customers.customer_id WHERE accounts.account_id = ".intval($_GET["id"])));

$ACCOUNT = mysql_fetch_assoc(mysql_query("
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
WHERE
  a.id = ".intval($_GET["id"])
));

			if (!$ACCOUNT) {
				$RESPONSE = "Unable to locate that account.";
				include "views/index.php";
			} else {
				set_customer($ACCOUNT["customers__id"]);
				include "views/view.php";
			}
			break;
		default:
			include "views/index.php";
			break;
	}
} else {
	include "views/index.php";
}

display_footer();

function pause_account($id) {
	//mysql_query("UPDATE customer_accounts SET is_disabled = 1 WHERE id = $id");
}

function unpause_account($id) {
	//mysql_query("UPDATE customer_accounts SET is_disabled = 0, last_invoice = '".date('Y-m-d')."' WHERE id = $id");
}

function edit_account() {
// 	$sql = "UPDATE accounts SET ";
// 	$sql .= "period = ". intval($_POST["period"]) .",";
// 	$sql .= "amount = '". floatval($_POST["amount"]) ."',";
// 	$sql .= "block_hours = '". floatval($_POST["block_hours"]) ."',";
// 	$sql .= "block_rate = '". floatval($_POST["block_rate"]) ."',";
// 	$sql .= "overage_rate = '". floatval($_POST["overage_rate"]) ."',";
// 	$sql .= "last_invoice = '". mysql_real_escape_string($_POST["last_invoice"]) ."' ";
// 	$sql .= "WHERE account_id = ". intval($_GET["id"]);

  $sql = "
UPDATE customer_accounts
SET
  period = " . intval($_POST["period"]) . ",
  amount = " . floatval($_POST["amount"]) . ",
  block_hours = " . floatval($_POST["block_hours"]) . ",
  block_rate = " . floatval($_POST["block_rate"]) . ",
  overage_rate = " . floatval($_POST["overage_rate"]) . "
WHERE
  id = " . intval($_GET["id"])
;
  mysql_query($sql) or die(mysql_error() ."::". $sql);

//   $sql = "
// UPDATE invoices
// SET
//   emailed = " . mysql_real_escape_string($_POST["last_invoice"]) . "
// WHERE
//   customer_accounts__id = " . intval($_GET["id"])
// ;
//   mysql_query($sql) or die(mysql_error() ."::". $sql);

}

function replace_customer() {
	$data = mysql_fetch_assoc(mysql_query("SELECT customers__id FROM customer_accounts WHERE id = ".intval($_GET["account_id"])));
	$current_customer_id = $data["customers__id"];
	$new_customer_id = intval($_GET["customer_id"]);

	$result = mysql_query("SELECT * FROM customer_accounts WHERE customers__id = ".$new_customer_id);
	if (mysql_num_rows($result)) {
		return "Error: The new customer you selected already has an account.";
	}

	mysql_query("UPDATE customer_accounts SET customers__id = ".$new_customer_id." WHERE id = ".intval($_GET["account_id"]));
	mysql_query("UPDATE invoices SET customers__id = ".$new_customer_id.", toi = toi WHERE customers__id = ".$current_customer_id);
	mysql_query("UPDATE pos_payments SET customers__id = ".$new_customer_id." WHERE customers__id = ".$current_customer_id);

	return "Account and invoices have been assigned to the new customer.";
}

function accept_payment($id) {
	global $USER;

	$total_paid = round(floatval($_POST["paid_cash"]),2) + round(floatval($_POST["paid_check"]),2) + round(floatval($_POST["paid_credit"]),2);
	$amt_left = $total_paid;

	$applied_to = array();

	// Apply payment to all unpaid selected invoices, oldest first
	$results = mysql_query("SELECT * FROM invoices WHERE customers__id = ". intval($id) ." AND amt_paid < amt ORDER BY toi");
	while ($invoice = mysql_fetch_assoc($results)) {
		if ($amt_left == 0) continue;
		if (!isset($_POST["inv". $invoice["id"]])) continue;

		$applied_to[] = $invoice["id"];

		$to_pay = $invoice["amt"] - $invoice["amt_paid"]; // Amount to pay on this invoice
		if ($to_pay > $amt_left) { // Partial payment
			$new_amt_paid = $invoice["amt_paid"] + $amt_left;
			$amt_left = 0;
			mysql_query("UPDATE invoices SET amt_paid = '". $new_amt_paid ."', toi = toi WHERE id = ". $invoice["id"]);
		} else { // Full payment or the rest of a partial payment
			$new_amt_paid = $invoice["amt"];
			$amt_left -= $to_pay;
			mysql_query("UPDATE invoices SET amt_paid = '". $new_amt_paid ."', ts_paid = NOW(), toi = toi WHERE id = ". $invoice["id"]);
		}
	}

	// Create payment receipt in database
	$sql = "INSERT INTO pos_payments (customers__id,paid_cash,paid_check,paid_credit,applied_to,org_entities__id) VALUES (";
	$sql .= intval($id) .",";
	$sql .= "'". round(floatval($_POST["paid_cash"]),2) ."',";
	$sql .= "'". round(floatval($_POST["paid_check"]),2) ."',";
	$sql .= "'". round(floatval($_POST["paid_credit"]),2) ."',";
	$sql .= "':". implode(":",$applied_to) .":',{$USER['org_entities__id']})";
	mysql_query($sql) or die(mysql_error() ."::". $sql);

	// Insert entry into the cash log
	if (round(floatval($_POST["paid_cash"]),2) > 0) {
		$sql = "INSERT INTO pos_cash_log (users__id,amt,reason,ts,is_reset,pos_transactions__id,org_entities__id) VALUES (";
		$sql .= $USER["id"] .",";
		$sql .= "'". round(floatval($_POST["paid_cash"]),2) ."',";
		$sql .= "'Payment from customer #". intval($id) ."',NOW(),0,";
		$sql .= "NULL,{$USER['org_entities__id']})";
		mysql_query($sql) or die(mysql_error() ."::". $sql);
	}
}

function set_customer($id) {
	global $SESSION;
	$id = intval($id);
	mysql_query("UPDATE sessions SET customers__id='". $id ."' WHERE id='". $SESSION["id"] ."'");
}

function create_account() {
		$CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ". intval($_GET["customer"])));
		if (!$CUSTOMER) return 0;

// 		$sql = "INSERT INTO customer_accounts (customer_id,block_hours,block_rate,overage_rate,last_invoice,period,amount) VALUES (";
// 		$sql .= $CUSTOMER["customer_id"] .",";
// 		$sql .= intval($_POST["block_hours"]) .",";
// 		$sql .= "'". floatval($_POST["block_rate"]) ."',";
// 		$sql .= "'". floatval($_POST["overage_rate"]) ."',";
// 		$sql .= "'". mysql_real_escape_string($_POST["start_date"]) ."',";
// 		$sql .= intval($_POST["period"]) .",";
// 		$sql .= "'". floatval($_POST["amount"]) ."')";

    $sql = "
INSERT INTO customer_accounts
(customers__id,block_hours,block_rate,overage_rate,period,amount)
VALUES (
  {$CUSTOMER["id"]},
" . floatval($_POST["block_hours"]) . ",
" . floatval($_POST["block_rate"]) . ",
" . floatval($_POST["overage_rate"]) . ",
" . intval($_POST["period"]) . ",
" . floatval($_POST["amount"]) . "
)";

		mysql_query($sql) or die(mysql_error() ."::". $sql);

    $last_insert_id = mysql_insert_id();

    # last_invoice = mysql_real_escape_string($_POST["start_date"])
    //mysql_query($sql) or die(mysql_error() ."::". $sql);

		return $last_insert_id;
}
function delete_account($aid) {
	mysql_query("DELETE FROM customer_accounts WHERE id = ". intval($aid));
}

?>
