<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<h3>My Reports Configutation</h3>
<?php

$email_valid = true;
if ($USER["email"] == null || $USER["email"] == "") {
	$email_valid = false;
} else {
	$emails = explode(",",$USER["email"]);
	foreach ($emails as $email) {
		if (!check_email_address(trim($email))) {
			$email_valid = false;
		}
	}
}

if ($email_valid) {
	echo "Your email address(es): <b>".$USER["email"]."</b><br><br>\n\n";
} else {
	echo "<b>One or more of your email addresses is invalid.<br>\nPlease consult an administrator.<br><br>\n\n";
}

$hour = "12";
$attach = 0;

$REPORTS = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
$result = mysql_query("SELECT * FROM reports_config WHERE users__id = ".$USER["id"]);
if (mysql_num_rows($result)) {
	$REPORTS_CONFIG = mysql_fetch_assoc($result);
	$data = $REPORTS_CONFIG["reports"];
	for ($i = 0; $i < 15; $i++) {
		if (substr($data,$i,1) != "_") $REPORTS[$i] = "1";
		else $REPORTS[$i] = 0;
	}
	$email_every = intval($REPORTS_CONFIG["email_every"]);
	$STORES = explode(":",$REPORTS_CONFIG["org_entities_list"]);
	$USERS = explode(":",$REPORTS_CONFIG["users_list"]);
	$hour = $REPORTS_CONFIG["hr"];
	$attach = intval($REPORTS_CONFIG["do_attach"]);
}

if (!isset($hour)) $hour = 12;

?>
<form action="?module=core&do=rpt_config_sub" method="post">
<table border="0">
 <tr>
  <td colspan="2" align="center">Email every <input type="edit" name="email_every" size="3" value="<?php if (isset($email_every)) { echo intval($email_every); } else { echo "1"; } ?>"> days after <select name="hour">
  <option value="0"<?php if ($hour == 0) { echo " SELECTED"; } ?>>Midnight</option>
  <option value="1"<?php if ($hour == 1) { echo " SELECTED"; } ?>>1am</option>
  <option value="2"<?php if ($hour == 2) { echo " SELECTED"; } ?>>2am</option>
  <option value="3"<?php if ($hour == 3) { echo " SELECTED"; } ?>>3am</option>
  <option value="4"<?php if ($hour == 4) { echo " SELECTED"; } ?>>4am</option>
  <option value="5"<?php if ($hour == 5) { echo " SELECTED"; } ?>>5am</option>
  <option value="6"<?php if ($hour == 6) { echo " SELECTED"; } ?>>6am</option>
  <option value="7"<?php if ($hour == 7) { echo " SELECTED"; } ?>>7am</option>
  <option value="8"<?php if ($hour == 8) { echo " SELECTED"; } ?>>8am</option>
  <option value="9"<?php if ($hour == 9) { echo " SELECTED"; } ?>>9am</option>
  <option value="10"<?php if ($hour == 10) { echo " SELECTED"; } ?>>10am</option>
  <option value="11"<?php if ($hour == 11) { echo " SELECTED"; } ?>>11am</option>
  <option value="12"<?php if ($hour == 12) { echo " SELECTED"; } ?>>Noon</option>
  <option value="13"<?php if ($hour == 13) { echo " SELECTED"; } ?>>1pm</option>
  <option value="14"<?php if ($hour == 14) { echo " SELECTED"; } ?>>2pm</option>
  <option value="15"<?php if ($hour == 15) { echo " SELECTED"; } ?>>3pm</option>
  <option value="16"<?php if ($hour == 16) { echo " SELECTED"; } ?>>4pm</option>
  <option value="17"<?php if ($hour == 17) { echo " SELECTED"; } ?>>5pm</option>
  <option value="18"<?php if ($hour == 18) { echo " SELECTED"; } ?>>6pm</option>
  <option value="19"<?php if ($hour == 19) { echo " SELECTED"; } ?>>7pm</option>
  <option value="20"<?php if ($hour == 20) { echo " SELECTED"; } ?>>8pm</option>
  <option value="21"<?php if ($hour == 21) { echo " SELECTED"; } ?>>9pm</option>
  <option value="22"<?php if ($hour == 22) { echo " SELECTED"; } ?>>10pm</option>
  <option value="23"<?php if ($hour == 23) { echo " SELECTED"; } ?>>11pm</option>
  </select> reports in <select name="attach">
  <option value="0"<?php if ($attach == 0) { echo " SELECTED"; } ?>>message body</option>
  <option value="1"<?php if ($attach == 1) { echo " SELECTED"; } ?>>attachments</option>
  </select></td>
 </tr>
<?php if (!isset($REPORTS_CONFIG)) { ?>
 <tr>
  <td class="heading">Start Date for<br>First Report</td>
  <td><input type="edit" name="start_date" id="start_date" size="10" value="<?php echo date('Y-m-d'); ?>"></td>
 </tr>
<?php } else { ?>
 <tr>
  <td class="heading" align="right">Last Emailed</td>
  <td><?php echo $REPORTS_CONFIG["last_emailed"]; ?></td>
 </tr>
<?php } ?>
 <tr>
  <td class="heading" align="right">Store Report</td>
  <td><input type="checkbox" name="all_stores" value="1"<?php if (isset($STORES) && in_array("all",$STORES)) echo " CHECKED"; ?>> <b>All Stores</b><br>
<?php

$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
ORDER BY
  location_code");
$max = 0;
while ($row = mysql_fetch_assoc($result)) {
	if ($row["id"] > $max) $max = $row["id"];
	echo "  <input type=\"checkbox\" name=\"store".$row["id"]."\" value=\"1\"". (isset($STORES) && in_array($row["id"],$STORES) ? " CHECKED" : "") ."> #". $row["location_code"] ." ". $row["title"] ." - ". $row["city"] .", ". $row["state"] ."<br>\n";
}
echo "<input type=\"hidden\" name=\"stores\" value=\"". $max ."\">\n";

?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Cash Report</td>
  <td><input type="checkbox" name="rpt0" value="1"<?php if ($REPORTS[0] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Cash Log Report</td>
  <td><input type="checkbox" name="rpt1" value="1"<?php if ($REPORTS[1] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Customer Report</td>
  <td><input type="checkbox" name="rpt2" value="1"<?php if ($REPORTS[2] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Marketing Report</td>
  <td><input type="checkbox" name="rpt3" value="1"<?php if ($REPORTS[3] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Deposits Report</td>
  <td><input type="checkbox" name="rpt4" value="1"<?php if ($REPORTS[4] > 0) echo " CHECKED"; ?>></td>
 </tr>
  <tr>
  <td class="heading" align="right">User Report</td>
  <td><input type="checkbox" name="all_users" value="1"<?php if (isset($USERS) && in_array("all",$USERS)) echo " CHECKED"; ?>> <b>All Users</b><br>
<?php

$result = mysql_query("SELECT * FROM users WHERE org_entities__id = {$USER['org_entities__id']} AND is_disabled = 0 ORDER BY username");
$max = 0;
while ($row = mysql_fetch_assoc($result)) {
	if ($row["id"] > $max) $max = $row["id"];
	echo "  <input type=\"checkbox\" name=\"user".$row["id"]."\" value=\"1\"". (isset($USERS) && in_array($row["id"],$USERS) ? " CHECKED" : "") .">(". $row["username"] .") ". $row["firstname"] ." ". $row["lastname"] ."<br>\n";
}
echo "<input type=\"hidden\" name=\"users\" value=\"". $max ."\">\n";

?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Drop Box Report</td>
  <td><input type="checkbox" name="rpt5" value="1"<?php if ($REPORTS[5] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Punch Cards Report</td>
  <td><input type="checkbox" name="rpt6" value="1"<?php if ($REPORTS[6] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Inventory Requests Report</td>
  <td><input type="checkbox" name="rpt7" value="1"<?php if ($REPORTS[7] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">User Score Report</td>
  <td><input type="checkbox" name="rpt8" value="1"<?php if ($REPORTS[8] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Inventory Added Report</td>
  <td><input type="checkbox" name="rpt9" value="1"<?php if ($REPORTS[9] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
 <td class="heading" align="right">Inventory Sold Report</td>
  <td><input type="checkbox" name="rpt10" value="1"<?php if ($REPORTS[10] > 0) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Save Configuration">
  </td>
 </tr>
</table>
</form>
<script type="text/javascript">
calendar.set("start_date");
</script>
