<?php

require("config.php");
$DB = mysql_connect($db_host, $db_user, $db_pass) or die("Couldn't connect to database.");
mysql_select_db($db_name) or die("Couldn't select database.");

?>