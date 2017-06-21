<!DOCTYPE HTML>
<html>
<head>
<title>Drop Receipt</title>
</head>
<body onload="window.print();">
<?php

$result = mysql_query("SELECT * FROM pos_cash_log cl LEFT JOIN users u ON cl.users__id = u.id WHERE cl.is_drop = 1 AND cl.id = ".intval($_GET["id"]));
if (!mysql_num_rows($result)) {
	echo "Invalid drop ID.</body></html>";
	exit;
}
$drop = mysql_fetch_assoc($result);

echo "<font size=\"+1\">Drop {$_GET["id"]}</font><br>\n";
echo "Made by <b>{$drop["username"]}</b> (<b>{$drop["firstname"]} {$drop["lastname"]}</b>)<br><br>\n";
echo "Amount: <b>$".number_format($drop["amt"],2)."</b><br>\n";
echo "Type: <b>".($drop["is_checks"] ? "Check":"Cash")."</b><br>\n";
echo "Timestamp: <b>{$drop["ts"]}</b><br>\n";

?>
</body>
</html>
