<?php

require_once("../mysql_connect.php");
require_once("../core/common.php");
require_once("../core/sessions.php");

if (!isset($USER)) exit;

if (!TFD_HAS_PERMS('admin','use')) {
	echo "You do not have the needed permissions to access this page.";
	exit;
}

$ran = false;

$customer_refs = array(
// 	"accounts" => "customer_id",
// 	"customer_inv" => "customer_id",
// 	"devices" => "customer_id",
// 	"feedback" => "customer_id",
// 	"invoices" => "customer_id",
// 	"issues" => "customer_id",
// 	"payments" => "customer_id",
// 	"sessions" => "customer",
// 	"transactions" => "customer"


  "customer_accounts" => "customers__id",
  "inventory_items_customer" => "customers__id",
  "inventory_type_devices" => "customers__id",
  "feedback" => "customers__id",
  "invoices" => "customers__id",
  "issues" => "customers__id",
  "pos_payments" => "customers__id",
  "sessions" => "customers__id",
  "pos_transactions" => "customers__id"
);

if (isset($_GET["do"])) {
	switch($_GET["do"]) {
		case "run":
			$ran = true;
			mysql_query("INSERT INTO customer_dupes SELECT c1.id,c1.firstname,c1.lastname,c1.is_male,c1.dob,c1.company,c1.address,c1.city,c1.state,c1.postcode,c1.email,c1.phone_home,c1.phone_cell,c1.referral,c1.is_subscribed,c1.v_address	FROM customers c1 JOIN	(SELECT v_address FROM customers WHERE v_address != 'INVALID' AND v_address IS NOT NULL GROUP BY v_address HAVING count(*) > 1) c2 ON c1.v_address = c2.v_address");
			break;
		case "distinct":
			if (isset($_GET["v_address"])) {
				mysql_query("DELETE FROM customer_dupes WHERE v_address = '".mysql_real_escape_string($_GET["v_address"])."'");
			} else {
				mysql_query("DELETE FROM customer_dupes WHERE id = ".intval($_GET["cid"]));
			}
			break;
		case "merge":
			if (isset($_POST["cid2"])) {
				$result = mysql_query("SELECT id FROM customer_dupes WHERE id = ".intval($_POST["cid2"]));
			} else {
				$result = mysql_query("SELECT id FROM customer_dupes WHERE v_address = '".mysql_real_escape_string($_POST["v_address"])."' AND id != ".intval($_POST["cid"]));
			}
			$others = array(0);
			while ($row = mysql_fetch_assoc($result)) {
				$others[] = $row["id"];
			}
			$ostr = join(",",$others);
			foreach ($customer_refs as $table => $col) {
				mysql_query("UPDATE $table SET $col = ".intval($_POST["cid"])." WHERE $col IN ($ostr)");
			}
			mysql_query("DELETE FROM customers WHERE id IN ($ostr)");
			if (isset($_POST["cid2"])) {
				mysql_query("DELETE FROM customer_dupes WHERE id = ".intval($_POST["cid2"]));
			} else {
				mysql_query("DELETE FROM customer_dupes WHERE v_address = '".mysql_real_escape_string($_POST["v_address"])."'");
			}
			$RESPONSE = "Merged ".count($others)." customers into customer ID ".intval($_POST["cid"]).".";
			break;
	}
}

$data = mysql_fetch_assoc(mysql_query("SELECT IFNULL(COUNT(*),0) AS count FROM customer_dupes"));
$count = $data["count"];

$VAS = array();
$result = mysql_query("SELECT v_address,count(*) AS count FROM customer_dupes GROUP BY v_address");
while ($row = mysql_fetch_assoc($result)) {
	$VAS[$row["v_address"]] = $row["count"];
}

$ENTRIES = mysql_query("SELECT * FROM customer_dupes ORDER BY v_address,id");

?>
<!DOCTYPE HTML>
<html>
<head>
<title>Duplicate Customer Corrector</title>
</head>
<body>
<div align="center">
<h3>Duplicate Customer Corrector</h3>

<?php echo alink("Back to Administration","/?module=admin"); ?><br><br>

<?php if (isset($RESPONSE)) { ?><font size="+1"><?php echo $RESPONSE; ?></font><br><br><?php } ?>

<?php if ($ran) { ?>
<font size="+1">Found <?php echo count($VAS); ?> groups of duplicates (<?php echo $count; ?> customers total).<?php if (count($VAS) > 10) { ?><br>
Displaying the first 10 sets.<?php } ?></font><br><br>
<?php } else if ($count == 0) { ?>
<form action="?do=run" method="post">
<font size="+1">There are no customers in the work table.</font><br>
<input type="submit" value="Find Duplicates">
</form><br>
<?php } else { ?>
<font size="+1">There are <?php echo count($VAS); ?> groups of duplicates.<?php if (count($VAS) > 10) { ?><br>
Displaying the first 10 sets.<?php } ?></font><br><br>
<?php } ?>

<?php

$this_vas = "--start";
$customers = array();
$did = 0;
$divs = 0;
while ($row = mysql_fetch_assoc($ENTRIES)) {
	if (!$did) {
		echo "<div style=\"border:1px solid #000;border-radius:10px;width:850px;\" align=\"center\">\n";
		$divs++;
	}
	if ($this_vas == "--start") $this_vas = $row["v_address"];
	if ($row["v_address"] != $this_vas && $did) {
		echo "</div><br><div style=\"border:1px solid #000;border-radius:10px;width:850px;\" align=\"center\">\n";
		$customers = array();
		$this_vas = $row["v_address"];
		$divs++;
	}
	$customers[] = $row["id"];
	echo "<table border=\"0\" width=\"100%\" style=\"font-size:8pt;\">\n";
	echo "	<tr align=\"center\" style=\"font-weight:bold;\">\n";
	echo "		<td>ID</td>\n";
	echo "		<td>First Name</td>\n";
	echo "		<td>Last Name</td>\n";
	echo "		<td>Sex</td>\n";
	echo "		<td>DOB</td>\n";
	echo "		<td>Company</td>\n";
	echo "		<td>Address</td>\n";
	echo "		<td>Email</td>\n";
	echo "		<td>Phone (H)</td>\n";
	echo "		<td>Phone (C)</td>\n";
	echo "		<td>Remove</td>\n";
	echo "	</tr>\n";
	echo "	<tr align=\"center\">\n";
	echo "		<td>".alink($row["id"],"/new/?module=cust&do=view&id={$row["id"]}")."</td>\n";
	echo "		<td>{$row["firstname"]}</td>\n";
	echo "		<td>{$row["lastname"]}</td>\n";
	echo "		<td>".($row["is_male"] ? "M":"F")."</td>\n";
	echo "		<td>{$row["dob"]}</td>\n";
	echo "		<td>{$row["company"]}</td>\n";
	echo "		<td>{$row["address"]}".($row["apt"] && $row["apt"] != "" ? " #".$row["apt"] : "")."<br>{$row["city"]}, {$row["state"]} {$row["postcode"]}</td>\n";
	echo "		<td>{$row["email"]}</td>\n";
	echo "		<td>".display_phone($row["phone_home"])."</td>\n";
	echo "		<td>".display_phone($row["phone_cell"])."</td>\n";
	echo "		<td>".alink("Not A Dupe","?do=distinct&cid={$row["id"]}")."</td>\n";
	echo "	</tr>\n";
	echo "</table><br>\n";

	if (count($customers) == $VAS[$this_vas]) {
		echo alink("None of these are dupes","?do=distinct&v_address=".urlencode($row["v_address"]))."<br>\n";
		echo "<form action=\"?do=merge\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"v_address\" value=\"{$row["v_address"]}\">\n";
		echo "Use data from customer ";
		echo "<select name=\"cid\">\n";
		foreach ($customers as $cid) {
			echo "<option value=\"$cid\">$cid</option>\n";
		}
		echo "</select> and <input type=\"submit\" value=\"Merge These\"></form>\n";

		echo "<form action=\"?do=merge\" method=\"post\">\n";
		echo "<input type=\"hidden\" name=\"v_address\" value=\"{$row["v_address"]}\">\n";
		echo "Use data from customer ";
		echo "<select name=\"cid\">\n";
		foreach ($customers as $cid) {
			echo "<option value=\"$cid\">$cid</option>\n";
		}
		echo "</select> and merge with customer ";
		echo "<select name=\"cid2\">\n";
		foreach ($customers as $cid) {
			echo "<option value=\"$cid\">$cid</option>\n";
		}
		echo "</select> ";
		echo "<input type=\"submit\" value=\"Merge 2\"></form>\n";

		if ($divs >= 10) {
			break;
		}
	}

	$did++;
}

if ($did) echo "</div><br>\n";

?>

</div>
</body>
</html>
