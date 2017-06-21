<?php

display_header();

if (!isset($_GET["range"])) $_GET["range"] = 1;

if ($_GET["range"] == "-1") {
	$range = "";
	$viewing = "all transactions";
} else {
	$range = " AND TIMESTAMPDIFF(HOUR,t.tos,NOW()) <= ".(intval($_GET["range"]) * 24);
	$viewing = "past ".intval($_GET["range"])." days";
}

$TLIST = mysql_query("SELECT t.id,t.tos,c.firstname AS cfn,c.lastname AS cln,t.qty,t.amt,u.firstname AS sfn,u.lastname AS sln,t.customers__id FROM pos_transactions t LEFT JOIN customers c ON t.customers__id = c.id LEFT JOIN users u ON t.users__id__sale = u.id WHERE t.line_number = 0$range ORDER BY t.tos DESC");

?>
<h3>Transactions</h3>

<?php echo alink("Today","?module=pos&do=thist&range=1"); ?> |
<?php echo alink("This Week","?module=pos&do=thist&range=7"); ?> |
<?php echo alink("30 Days","?module=pos&do=thist&range=30"); ?> |
<?php echo alink("All Transactions","?module=pos&do=thist&range=-1"); ?><br>
<br>

Viewing <?php echo $viewing; ?> - <?php echo mysql_num_rows($TLIST); ?> transactions<br><br>

<table border="0">
	<tr class="heading" align="center">
		<td>ID</td>
		<td>Date/Time</td>
		<td>Customer</td>
		<td>Salesman</td>
		<td>Items</td>
		<td>Total</td>
		<td>View</td>
		<td>Receipt</td>
	</tr>
<?php

while ($row = mysql_fetch_assoc($TLIST)) {
	echo "	<tr align=\"center\" title=\"".($row["descr"] ? $row["descr"] : "(No Description)")."\">\n";
	echo "		<td>{$row["id"]}</td>\n";
	echo "		<td>".date("Y-m-d H:i",strtotime($row["tos"]))."</td>\n";
	if ($row["cfn"]) {
		echo "		<td>{$row["cfn"]} {$row["cln"]} (".alink("#".$row["customers__id"],"?module=cust&do=view&id=".$row["customers__id"]).")</td>\n";
	} else {
		echo "		<td><i>None</i></td>\n";
	}
	echo "		<td>{$row["sfn"]} {$row["sln"]}</td>\n";
	echo "		<td>{$row["qty"]}</td>\n";
	echo "		<td>$".number_format($row["amt"],2)."</td>\n";
	echo "		<td>".alink("View","?module=pos&do=view_trans&tid=".$row["id"])."</td>\n";
	echo "		<td>".alink("Receipt","?module=pos&do=receipt&tid=".$row["id"])."</td>\n";
	echo "	</tr>\n";
}

?>
</table>

<?php

display_footer();

?>
