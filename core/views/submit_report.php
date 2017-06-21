<?php

if (isset($_GET["id"])) {
	$REPORT_ID = intval($_GET["id"]);
	$result = mysql_query("SELECT * FROM user_rpt_templates WHERE template_id = $REPORT_ID");
	if (!mysql_num_rows($result)) die("Report template not found.");
	$REPORT = mysql_fetch_assoc($result);

	$COL = explode("~",$REPORT["column_data"]);
	$columns = array();
	foreach ($COL as $c) {
		$columns[] = explode("|",$c);
	}

	echo "<h3>".$REPORT["template_name"]."</h3>\n";
	echo "<form action=\"?module=core&do=submit_report\" method=\"post\">\n";
	echo "<input type=\"hidden\" name=\"report_id\" value=\"$REPORT_ID\">\n";

	foreach($columns as $column) {
		$line = "<b># ".$column[0]." :</b> ";
		$line .= $column[1] ."<br>\n";
		switch ($column[2]) {
			case 1:
				if ($column[3] == 0) {
					$line .= "<textarea rows=\"4\" cols=\"40\" name=\"n_{$column[0]}\"></textarea>";
				} else {
					$line .= "<input type=\"text\" name=\"n_{$column[0]}\" size=\"40\" maxlength=\"{$column[3]}\"> ({$column[3]} character limit)";
				}
				break;
			case 2:
			case 3:
				$line .= "<input type=\"text\" name=\"n_{$column[0]}\" size=\"6\">";
				if ($column[3] != "0,0") {
					$v = explode(",",$column[3]);
					$line .= " (Must be between <b>{$v[0]}</b> and <b>{$v[1]}</b>)";
				}
				break;
			case 4:
				$line .= "<select name=\"n_{$column[0]}\">";
				$options = explode(",",$column[3]);
				foreach ($options as $option) {
					$line .= "<option value=\"$option\">$option</option>";
				}
				$line .= "</select>";
				break;
			case 5:
				$line .= "<input type=\"checkbox\" name=\"n_{$column[0]}\" value=\"1\">";
				break;
			default:
				continue;
		}
		$line .= "<hr>\n";
		echo $line;
	}

	echo "<input type=\"submit\" value=\"Submit Report\"></form>\n";

} else {
	$result = mysql_query("SELECT * FROM user_rpt_templates ORDER BY template_name");
	echo "<h3>Select Report</h3>\n\n";
	if (!mysql_num_rows($result)) {
		echo "<font size=\"+1\">There are no report templates configured.</font><br><br>\n";
		echo alink("Back to System","?module=core");
	} else {
		echo "<form action=\"?\" method=\"get\">\n";
		echo "<input type=\"hidden\" name=\"module\" value=\"core\">\n";
		echo "<input type=\"hidden\" name=\"do\" value=\"submit_report\">\n";
		echo "<select name=\"id\">\n";
		while ($row = mysql_fetch_assoc($result)) {
			echo "<option value=\"{$row["template_id"]}\">{$row["template_name"]}</option>\n";
		}
		echo "</select>\n";
		echo "<input type=\"submit\" value=\"Go\"></form>\n";
	}
}

?>