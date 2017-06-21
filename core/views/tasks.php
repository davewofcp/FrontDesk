<?php

$TASKS = array();
$result = mysql_query("SELECT * FROM tasks WHERE org_entities__id = {$USER['org_entities__id']} AND is_completed = 0 AND users__id__assigned_to = {$USER["id"]} ORDER BY due");
while ($row = mysql_fetch_assoc($result)) {
	$TASKS[$row["id"]] = $row;
}

?>
<h3>Tasks Assigned To You</h3>

<?php if (isset($RESPONSE)) { ?><font size="+1"><?php echo $RESPONSE; ?></font><br><br><?php } ?>

<?php echo alink("Back To System","?module=core&do=index"); ?><br><br>

<table border="0">
 <tr align="center" class="heading">
  <td>Due</td>
  <td width="200">Task</td>
  <td>Complete</td>
 </tr>
<?php

foreach ($TASKS as $id => $task) {
	echo " <tr>\n";
	if (strtotime($task["due"]) < time()) {
		$bg = " style=\"color:#FF0000;font-weight:bold;\"";
	} else {
		$bg = "";
	}
	echo "  <td align=\"center\"$bg>".$task["due"]."</td>\n";
	echo "  <td>".$task["task"]."</td>\n";
	echo "  <td>".alink("Complete","?module=core&do=task_complete&id=$id")."</td>\n";
	echo " </tr>\n";
}

echo " <tr><td colspan=\"3\" align=\"center\">".count($TASKS)." Tasks</td></tr>\n";

?>
</table>
