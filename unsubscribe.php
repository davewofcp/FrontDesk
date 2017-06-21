<html><head><title>Unsubscribe</title></head>
<body><div align="center">
<?php

if (isset($_GET["email"])) $EMAIL = $_GET["email"];
if (isset($_POST["email"])) $EMAIL = $_POST["email"];

if (isset($EMAIL)) {
	require("mysql_connect.php");
  $result = mysql_query("SELECT is_subscribed FROM customers WHERE email = '".mysql_real_escape_string($EMAIL)."'");
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
		if ($data["is_subscribed"] == 0) {
			echo "<b>$EMAIL</b> was already unsubscribed.</body></html>";
			exit;
		} else {
			mysql_query("UPDATE customers SET is_subscribed = 0 WHERE email = '".mysql_real_escape_string($EMAIL)."'");
			echo "<b>$EMAIL</b> has been unsubscribed.</body></html>";
			exit;
		}
	} else {
		echo "<b>$EMAIL</b> was not found in the system. Perhaps you misspelled it?<br><br>";
	}
}

?>
<h3>Unsubscribe</h3>

<form action="?" method="post">
<input type="text" name="email" size="30"><input type="submit" value="Unsubscribe">
</form>

</div>
</body>
</html>
