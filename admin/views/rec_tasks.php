<?php

if (isset($_GET["location"])) {
	$eid = $_GET["location"];
	$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  AND oe.id = '".mysql_real_escape_string($eid)."'
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
	} else {
		$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  AND oe.id = {$USER['org_entities__id']}
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

$R_TASKS = array();
$result = mysql_query("SELECT rt.*,urt.template_name FROM recurring_tasks rt LEFT JOIN user_rpt_templates urt ON rt.report_id = urt.template_id WHERE rt.org_entities__id = {$eid} ORDER BY task_id");
while ($row = mysql_fetch_assoc($result)) {
	$R_TASKS[$row["task_id"]] = $row;
}

$today = new DateTime(date("Y-m-d"));
$begin = clone $today;
$begin->sub(new DateInterval("P6D"));
$start = $begin->format("Y-m-d");

$C_TASKS = array();
$result = mysql_query("SELECT * FROM tasks_completed WHERE org_entities__id = {$eid} AND date_done > '$start' ORDER BY id");
while ($row = mysql_fetch_assoc($result)) {
	$C_TASKS[$row["task_id"]] = $row;
}

?>
<script type="text/javascript">
function refreshLocation() {
	var s = document.getElementById('location');
	var store = s.options[s.selectedIndex].value;
	window.location = '?module=admin&do=rec_tasks&location='+store;
}
</script>
<h3>Daily Tasks</h3>

<?php if (isset($RESPONSE)) { ?><font size="+1"><?php echo $RESPONSE; ?></font><br><br><?php } ?>

<font size="+1">Add Daily Task</font>
<form action="?module=admin&do=new_rec_task" method="post">
<table border="0">
 <tr>
  <td class="heading" align="right">Store:</td>
  <td>
  <select id="location" name="location" onChange="refreshLocation();">
<?php
$sql = '';
$result = mysql_query("
SELECT
  oe.id,
  oe.title
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
ORDER BY
  oe.id ASC
");
while ($row = mysql_fetch_assoc($result)) {
	echo "<option value=\"{$row["id"]}\"".($row["id"] == $eid ? " SELECTED":"").">{$row["title"]}</option>\n";
}

?>
   </select>
  </td>
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
  <td class="heading" align="right">Report:</td>
  <td><select name="report"><option value="0">None</option>
<?php

$result = mysql_query("SELECT template_id,template_name FROM user_rpt_templates ORDER BY template_id");
while ($row = mysql_fetch_assoc($result)) {
	echo "<option value=\"{$row["template_id"]}\">{$row["template_name"]}</option>\n";
}

?>
  </select></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  <input type="submit" value="Assign Task">
  </td>
 </tr>
</table>
</form>
<br>

<font size="+1">Active Daily Tasks</font><br>
<table border="0">
 <tr class="heading" align="center">
  <td>ID</td>
  <td>Task Description</td>
  <td>Point Value</td>
  <td>Report</td>
  <td>Delete</td>
 </tr>
<?php

foreach ($R_TASKS as $id => $task) {
	echo " <tr align=\"center\">\n";
	echo "  <td>". $id ."</td>\n";
	echo "  <td>". $task["descr"] ."</td>\n";
	echo "  <td>". $task["points"] ."</td>\n";
	if ($task["report_id"]) {
		echo "  <td>".$task["template_name"]." (".alink("View","?module=admin&do=user_rpt_view&id={$task["report_id"]}").")</td>\n";
	} else {
		echo "  <td><i>None</i></td>\n";
	}
	echo "  <td>". alink("Delete","?module=admin&do=rec_task_delete&id=$id") ."</td>\n";
	echo " </tr>\n";
}

?>
</table><br>

<font size="+1">Completed Tasks (since <?php echo $start; ?>)</font><br>
<font size="-1">Will not include tasks completed today.</font><br>
<table border="0">
 <tr class="heading" align="center">
  <td>Date Done</td>
  <td width="200">Task</td>
  <td>Point Value</td>
  <td>Users</td>
 </tr>
<?php

foreach ($C_TASKS as $id => $task) {
	if (!isset($R_TASKS[$task["id"]])) continue;
	echo " <tr align=\"center\">\n";
	echo "  <td>". $task["date_done"] ."</td>\n";
	echo "  <td align=\"left\">". $R_TASKS[$task["task_id"]]["descr"] ."</td>\n";
	echo "  <td>". $task["points"] ."</td>\n";
	echo "  <td style=\"border:1px solid #000;\">";
	if ($task["user_ids"] && $task["user_ids"] != "") {
		$uids = explode(",",$task["user_ids"]);
		foreach ($uids as $uid) {
			echo $USERS[$uid]["username"] ."<br>\n";
		}
	} else {
		echo "<i>None</i>";
	}
	echo "</td>\n";
	echo " </tr>\n";
}

?>
</table><br>
