<?php

$result = mysql_query("SELECT * FROM user_rpt_templates urt LEFT JOIN users u ON urt.created_by = u.id WHERE template_id = ".intval($_GET["id"]));
if (!mysql_num_rows($result)) die("Invalid template ID.");
$REPORT = mysql_fetch_assoc($result);

$COL = explode("~",$REPORT["column_data"]);
$columns = array();
foreach ($COL as $c) {
	$columns[] = explode("|",$c);
}

?>
<h3>Template: '<?php echo $REPORT["template_name"]; ?>'</h3>
Created by <?php echo (isset($REPORT["username"]) ? $REPORT["username"] : "<i>Deleted User</i>"); ?>, Last Modified <?php echo $REPORT["created_ts"]; ?><br>

<?php

echo alink("Submissions","?module=admin&do=user_rpt_subs&id={$REPORT["template_id"]}") ." &nbsp; - &nbsp; ";
echo alink("Edit","?module=admin&do=user_rpt_edit&id={$REPORT["template_id"]}") ." &nbsp; - &nbsp; ";
echo alink_onclick("Delete","?module=admin&do=user_rpt_delete&id={$REPORT["template_id"]}","return confirm('Are you sure you want to delete this template? All submissions will be discarded.');") ."<br><hr>\n";

foreach($columns as $column) {
	$line = "<b># ".$column[0]." :</b> ";
	$line .= $column[1] ."<br>\n";
	switch ($column[2]) {
		case 1:
			if ($column[3] == 0) {
				$line .= "<textarea rows=\"4\" cols=\"40\" name=\"n_{$column[0]}\" disabled=\"disabled\"></textarea>";
			} else {
				$line .= "<input type=\"text\" name=\"n_{$column[0]}\" size=\"40\" maxlength=\"{$column[3]}\" disabled=\"disabled\"> ({$column[3]} character limit)";
			}
			break;
		case 2:
		case 3:
			$line .= "<input type=\"text\" name=\"n_{$column[0]}\" size=\"6\" disabled=\"disabled\">";
			if ($column[3] != "0,0") {
				$v = explode(",",$column[3]);
				$line .= " (Must be between <b>{$v[0]}</b> and <b>{$v[1]}</b>)";
			}
			break;
		case 4:
			$line .= "<select name=\"n_{$column[0]}\" disabled=\"disabled\">";
			$options = explode(",",$column[3]);
			foreach ($options as $option) {
				$line .= "<option value=\"$option\">$option</option>";
			}
			$line .= "</select>";
			break;
		case 5:
			$line .= "<input type=\"checkbox\" name=\"n_{$column[0]}\" value=\"1\" disabled=\"disabled\">";
			break;
		default:
			continue;
	}
	$line .= "<hr>\n";
	echo $line;
}

?>