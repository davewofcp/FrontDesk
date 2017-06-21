<h2>System</h2>
<?php

if (isset($RESPONSE)) {
	echo "<h3>".$RESPONSE."</h3>";
}

?>
<div style="float:left;width:300px;">
<?php echo alink("Punch In / Out","?module=core&do=punch"); ?>
<hr>
<?php echo alink("Tasks","?module=core&do=tasks"); ?>
<hr>
<?php echo alink("Change Password","?module=core&do=chpass"); ?>
<hr>
<?php echo alink("Submit A Report","?module=core&do=submit_report"); ?>
<hr>
<?php echo alink("My Reports Configuration","?module=core&do=rpt_config"); ?><br>
<?php if (TFD_HAS_PERMS('admin','use')) echo alink("Manually Run Reports Emailer","cron_email_reports.php")."<br>\n"; ?>
</div>

<h2>Recent System Changes</h2>

<?php

$result = mysql_query("SELECT * FROM changes ORDER BY ts DESC LIMIT 5");
while ($row = mysql_fetch_assoc($result)) {
	if ($row["id"] > $USER["rc_read"]) {
		echo "<font color=\"red\" size=\"+1\">{$row["subject"]}</font><br>\n";
	} else {
		echo "<font size=\"+1\">{$row["subject"]}</font><br>\n";
	}
	echo "<i>Posted ".date("Y-m-d \\a\\t H:i",strtotime($row["ts"]))."</i><br>\n";
	echo $row["descr"] ."<br><hr>\n";
}

$data = mysql_fetch_assoc(mysql_query("SELECT IFNULL(MAX(id),0) AS rcid FROM changes"));
mysql_query("UPDATE users SET rc_read = {$data["rcid"]} WHERE id = {$USER["id"]}");

?>