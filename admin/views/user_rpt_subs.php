<?php

function makeLinks($s) {
	return preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $s);
}

if (isset($_GET["sid"])) {
	$SUB_ID = intval($_GET["sid"]);
	$result = mysql_query("SELECT urs.*,u.username FROM user_rpt_submissions urs LEFT JOIN users u ON urs.user_id = u.id WHERE urs.submission_id = $SUB_ID");
	if (!mysql_num_rows($result)) {
		echo "Submission not found.";
		exit;
	}
	$SUB = mysql_fetch_assoc($result);
	$result = mysql_query("SELECT * FROM user_rpt_templates WHERE template_id = ".$SUB["template_id"]);
	if (!mysql_num_rows($result)) {
		echo "Report template not found.";
		exit;
	}
	$REPORT = mysql_fetch_assoc($result);

	$COL = explode("~",$REPORT["column_data"]);
	$columns = array();
	foreach ($COL as $c) {
		$columns[] = explode("|",$c);
	}

	$SUB_COL = explode("||",$SUB["submitted_data"]);
	$sub_columns = array();
	foreach ($SUB_COL as $c) {
		$arr = explode("::",$c);
		$sub_columns[$arr[0]] = $arr[1];
	}

	echo "<h3>".$REPORT["template_name"]."</h3>\n";
	echo "Submitted ".$SUB["submitted_ts"]." by ".$SUB["username"]."<hr>\n";

	foreach($columns as $column) {
		$k = $column[0];
		$line = "<b># ".$k." :</b> ";
		$line .= $column[1] ."<br>\n";
		switch ($column[2]) {
			case 1:
				$line .= makeLinks($sub_columns[$k]);
				break;
			case 2:
			case 3:
				$line .= $sub_columns[$k];
				if ($column[3] != "0,0") {
					$v = explode(",",$column[3]);
					$line .= " (Must be between <b>{$v[0]}</b> and <b>{$v[1]}</b>)";
				}
				break;
			case 4:
				$line .= "<select name=\"n_{$column[0]}\">";
				$options = explode(",",$column[3]);
				foreach ($options as $option) {
					$line .= "<option value=\"$option\"".($option == $sub_columns[$k] ? " SELECTED":"").">$option</option>";
				}
				$line .= "</select>";
				break;
			case 5:
				$line .= "<input type=\"checkbox\" value=\"1\"".($sub_columns[$k] == 1 ? " CHECKED":"").">";
				break;
			default:
				continue;
		}
		$line .= "<hr>\n";
		echo $line;
	}

	mysql_query("UPDATE user_rpt_submissions SET was_viewed = 1 WHERE submission_id = $SUB_ID");

	echo alink("Back to Submissions List","?module=admin&do=user_rpt_subs&id={$SUB["template_id"]}");

} else {
	$REPORT_ID = intval($_GET["id"]);
	$result = mysql_query("SELECT * FROM user_rpt_templates WHERE template_id = $REPORT_ID");
	if (!mysql_num_rows($result)) die("Report template not found.");
	$REPORT = mysql_fetch_assoc($result);

	$result = mysql_query("SELECT urs.*,u.username FROM user_rpt_submissions urs LEFT JOIN users u ON urs.user_id = u.id WHERE urs.template_id = $REPORT_ID ORDER BY was_viewed,submitted_ts");

	echo "<h3>".$REPORT["template_name"]."</h3>\n";

	echo "<table border=\"0\">\n";
	echo " <tr class=\"heading\" align=\"center\">\n";
	echo "  <td>ID</td>\n";
	echo "  <td>Username</td>\n";
	echo "  <td>Submitted</td>\n";
	echo "  <td>Viewed?</td>\n";
	echo "  <td>View</td>\n";
	echo " </tr>\n";

	while ($row = mysql_fetch_assoc($result)) {
		echo " <tr align=\"center\">\n";
		echo "  <td>".$row["submission_id"]."</td>\n";
		echo "  <td>".$row["username"]."</td>\n";
		echo "  <td>".$row["submitted_ts"]."</td>\n";
		echo "  <td>".($row["was_viewed"] ? "Yes" : "No")."</td>\n";
		echo "  <td>".alink("View","?module=admin&do=user_rpt_subs&sid={$row["submission_id"]}")."</td>\n";
		echo " </tr>\n";
	}

	echo "</table><br>\n";

	echo "<hr>\n";
	echo alink("Back to Report List","?module=admin&do=user_rpt_list");
}

?>