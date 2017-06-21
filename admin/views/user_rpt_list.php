<?php

$REPORTS = array();
$result = mysql_query("SELECT * FROM user_rpt_templates urt LEFT JOIN users u ON urt.created_by = u.id ORDER BY template_id DESC");
while ($row = mysql_fetch_assoc($result)) {
	$REPORTS[$row["template_id"]] = $row;
	$REPORTS[$row["template_id"]]["fields"] = (substr_count($row["column_data"],"~") + 1);
}

$COUNTS_UNREAD = array();
$COUNTS_READ = array();
$result = mysql_query("SELECT template_id,was_viewed,COUNT(*) AS count FROM user_rpt_submissions GROUP BY template_id,was_viewed");
while ($row = mysql_fetch_assoc($result)) {
	if ($row["was_viewed"]) $COUNTS_READ[$row["template_id"]] = $row["count"];
	else $COUNTS_UNREAD[$row["template_id"]] = $row["count"];
}

?>
<h3>User Report Templates</h3>

<table border="0">
 <tr class="heading" align="center">
  <td>Report Name</td>
  <td>Created By</td>
  <td>Last Modified</td>
  <td>Fields</td>
  <td>New | Read</td>
  <td>View</td>
  <td>Edit</td>
  <td>Delete</td>
 </tr>
<?php

foreach ($REPORTS as $id => $report) {
	if (!isset($COUNTS_READ[$id])) $COUNTS_READ[$id] = 0;
	if (!isset($COUNTS_UNREAD[$id])) $COUNTS_UNREAD[$id] = 0;
	echo " <tr align=\"center\">\n";
	echo "  <td>". $report["template_name"] ."</td>\n";
	echo "  <td>". $report["username"] ."</td>\n";
	echo "  <td>". $report["created_ts"] ."</td>\n";
	echo "  <td>". $report["fields"] ."</td>\n";
	echo "  <td>". alink("[ ".$COUNTS_UNREAD[$id]." ] [ ".$COUNTS_READ[$id]." ]","?module=admin&do=user_rpt_subs&id=$id") ."</td>\n";
	echo "  <td>". alink("View","?module=admin&do=user_rpt_view&id=$id") ."</td>\n";
	echo "  <td>". alink("Edit","?module=admin&do=user_rpt_edit&id=$id") ."</td>\n";
	echo "  <td>". alink_onclick("Delete","?module=admin&do=user_rpt_delete&id=$id","return confirm('Really delete the report \'{$report["template_name"]}\' ? All ".($COUNTS_READ[$id] + $COUNTS_UNREAD[$id])." submissions will be discarded.');") ."</td>\n";
	echo " </tr>\n";
}

?>
</table>