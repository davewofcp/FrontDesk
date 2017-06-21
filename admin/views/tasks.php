<?php

if (isset($_GET["location"])) {
	$eid = $_GET["location"];
	//$result = mysql_query("SELECT * FROM locations WHERE store_number = '".mysql_real_escape_string($eid)."'");
  $result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = '".mysql_real_escape_string($eid)."'
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
 	if (mysql_num_rows($result)) {
// 		$data = mysql_fetch_assoc($result);
// 		$DB = mysql_connect($data["db_host"],$data["db_user"],$data["db_pass"]);
// 		mysql_select_db($data["db_db"]);
 	} else {
 		$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
 		if (mysql_num_rows($result)) {
 			$data = mysql_fetch_assoc($result);
 			$eid = $data["id"];
 		} else {
 			$eid = 0;
 		}
 	}
} else {
	$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id = {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
		$eid = $data["id"];
	} else {
		$eid = 0;
	}
}

$USERS = array();
$result = mysql_query("SELECT * FROM users WHERE org_entities__id = {$eid} AND is_disabled = 0 ORDER BY username");
while ($row = mysql_fetch_assoc($result)) {
	$USERS[$row["id"]] = $row;
}

$I_TASKS = array();
$C_TASKS = array();
$result = mysql_query("SELECT * FROM tasks WHERE org_entities__id = {$eid} ORDER BY id DESC");
while ($row = mysql_fetch_assoc($result)) {
	if ($row["is_completed"]) $C_TASKS[$row["id"]] = $row;
	else $I_TASKS[$row["id"]] = $row;
}

?>
<script type="text/javascript">
function refreshLocation() {
	var s = document.getElementById('location');
	var store = s.options[s.selectedIndex].value;
	window.location = '?module=admin&do=tasks&location='+store;
}
</script>
<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<h3>Tasks</h3>

<?php if (isset($RESPONSE)) { ?><font size="+1"><?php echo $RESPONSE; ?></font><br><br><?php } ?>

<font size="+1">Assign Task</font>
<form action="?module=admin&do=task_assign" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Store:</td>
  <td>
  <select id="location" name="location" onChange="refreshLocation();">
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
  location_code
");
while ($row = mysql_fetch_assoc($result)) {
	echo "<option value=\"{$row["id"]}\"".($row["id"] == $eid ? " SELECTED":"").">{$row["title"]}</option>\n";
}

?>
   </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">User:</td>
  <td>
  <select name="user">
<?php

foreach ($USERS as $id => $_user) {
	echo "  <option value=\"$id\">{$_user["username"]} - {$_user["firstname"]} {$_user["lastname"]}</option>\n";
}

?>
  </select>
 </tr>
 <tr>
  <td class="heading" align="right">Due Date:</td>
  <td><input type="edit" id="due" name="due" size="10" value="<?php echo date("Y-m-d"); ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Due Time:</td>
  <td>
  <select name="due_hour">
<?php

for ($i = 0; $i < 24; $i++) {
	$hr = str_pad($i,2,"0",STR_PAD_LEFT);
	echo "  <option value=\"$hr\">$hr</option>\n";
}

?>
  </select>:<select name="due_minute">
<?php

for ($i = 0; $i < 60; $i += 5) {
	$min = str_pad($i,2,"0",STR_PAD_LEFT);
	echo "  <option value=\"$min\">$min</option>\n";
}

?>
  </select>
 </tr>
 <tr>
  <td class="heading" align="right">Points:</td>
  <td><input type="edit" name="points" size="4"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Task:</td>
  <td><textarea name="task" rows="5" cols="30"></textarea></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Assign Task">
  </td>
 </tr>
</table>
</form>
<br>

<font size="+1">Incomplete Tasks</font><br>
<table border="0">
 <tr class="heading" align="center">
  <td>Assigned To</td>
  <td>Assigned By</td>
  <td>Due</td>
  <td width="200">Task</td>
  <td>Delete</td>
 </tr>
<?php

foreach ($I_TASKS as $id => $task) {
	echo " <tr>\n";
	echo "  <td align=\"center\">".(isset($USERS[$task["users__id__assigned_to"]]) ? $USERS[$task["users__id__assigned_to"]]["firstname"]." ".$USERS[$task["users__id__assigned_to"]]["lastname"] : "(Other Store)")."</td>\n";
	echo "  <td align=\"center\">".(isset($USERS[$task["users__id__assigned_by"]]) ? $USERS[$task["users__id__assigned_by"]]["firstname"]." ".$USERS[$task["users__id__assigned_by"]]["lastname"] : "(Other Store)")."</td>\n";
	echo "  <td align=\"center\">".$task["due"]."</td>\n";
	echo "  <td>".$task["task"]."</td>\n";
	echo "  <td align=\"center\">".alink("Delete","?module=admin&do=task_delete&id=$id")."</td>\n";
	echo " </tr>\n";
}

?>
</table><br>

<font size="+1">Completed Tasks</font> (<?php echo alink("Delete All","?module=admin&do=task_del_complete&location=$eid"); ?>)<br>
<table border="0">
 <tr class="heading" align="center">
  <td>Assigned To</td>
  <td>Assigned By</td>
  <td>Due</td>
  <td>Completed</td>
  <td width="200">Task</td>
  <td>Delete</td>
 </tr>
<?php

foreach ($C_TASKS as $id => $task) {
	echo " <tr>\n";
	echo "  <td align=\"center\">".(isset($USERS[$task["users__id__assigned_to"]]) ? $USERS[$task["users__id__assigned_to"]]["firstname"]." ".$USERS[$task["users__id__assigned_to"]]["lastname"] : "(Other Store)")."</td>\n";
	echo "  <td align=\"center\">".(isset($USERS[$task["users__id__assigned_by"]]) ? $USERS[$task["users__id__assigned_by"]]["firstname"]." ".$USERS[$task["users__id__assigned_by"]]["lastname"] : "(Other Store)")."</td>\n";
	echo "  <td align=\"center\">".$task["due"]."</td>\n";
	echo "  <td align=\"center\">".date("Y-m-d H:i:s",strtotime($task["toc"]))."</td>\n";
	echo "  <td>".$task["task"]."</td>\n";
	echo "  <td align=\"center\">".alink("Delete","?module=admin&do=task_delete&id=$id&location=$eid")."</td>\n";
	echo " </tr>\n";
}

?>
</table><br>
<script type="text/javascript">
calendar.set("due");
</script>
