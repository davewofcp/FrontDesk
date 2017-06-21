<?php

if (isset($_GET["delete"])) {
	// Remove the step from all issues that have it
	$result = mysql_query("SELECT for_service FROM service_steps WHERE id = ".intval($_GET["delete"]));
	if (mysql_num_rows($result)) {
		$data = mysql_fetch_assoc($result);
		$svc = $data["for_service"];
		$result = mysql_query("SELECT id,service_steps FROM issues WHERE services LIKE '%:$svc:%'");
		while ($row = mysql_fetch_assoc($result)) {
			$steps = explode("|",$row["service_steps"]);
			$new_steps = array();
			foreach ($steps as $step) {
				$kv = explode(":",$step);
				if ($kv[0] == $svc && $kv[1] == intval($_GET["delete"])) continue;
				$new_steps[] = $step;
			}
			mysql_query("UPDATE issues SET service_steps = '".join("|",$new_steps)."' WHERE id = ".$row["id"]);
		}
	}

	// Remove the step from the database
	mysql_query("DELETE FROM service_steps WHERE id = ".intval($_GET["delete"]));
}

if (isset($_POST["step_id"])) {
	foreach ($_POST["step_id"] as $x => $id) {
		if ($id == "0") {
			if ($_POST["step_text"][$x] == "") continue;
			$sql = "INSERT INTO service_steps (services__id,`order`,step) VALUES (";
			$sql .= intval($_POST["step_fs"][$x]) .",";
			$sql .= intval($_POST["step_order"][$x]) .",";
			$sql .= "'". mysql_real_escape_string($_POST["step_text"][$x]) ."')";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		} else {
			$sql = "UPDATE service_steps SET ";
			$sql .= "`order` = '". intval($_POST["step_order"][$x]) ."',";
			$sql .= "step = '". mysql_real_escape_string($_POST["step_text"][$x]) ."' ";
			$sql .= "WHERE id = ".intval($id);
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}
	}
}

$SERVICES = array();
$result = mysql_query("SELECT id,name FROM services ORDER BY name");
while ($row = mysql_fetch_assoc($result)) {
	$SERVICES[$row["id"]] = $row["name"];
}

$STEPS = array();
$result = mysql_query("SELECT * FROM service_steps WHERE 1 ORDER BY services__id,`order`");
while ($row = mysql_fetch_assoc($result)) {
	if (!isset($STEPS[$row["services__id"]])) $STEPS[$row["services__id"]] = array();
	$STEPS[$row["services__id"]][$row["id"]] = array();
	$STEPS[$row["services__id"]][$row["id"]]["order"] = $row["order"];
	$STEPS[$row["services__id"]][$row["id"]]["step"] = $row["step"];
}


?>
<script type="text/javascript">
function addRow(id) {
	var table=document.getElementById("svc"+id);
	var row=table.insertRow(-1);
	row.align = 'center';
	var cell1=row.insertCell(0);
	var cell2=row.insertCell(1);
	var cell3=row.insertCell(2);
	cell1.innerHTML = '<input type="hidden" name="step_fs[]" value="'+id+'"><input type="hidden" name="step_id[]" value="0"><input type="text" name="step_order[]" size="3">';
	cell2.innerHTML = '<input type="text" name="step_text[]" size="50">';
	cell3.innerHTML = '--';
}
</script>
<h3>Service Steps</h3>

<form action="?module=admin&do=step_edit" method="post">
<?php

if (!isset($_GET["sid"])) {
	echo "<table border=\"0\">\n";
	echo " <tr align=\"center\" class=\"heading\">\n";
	echo "  <td>Service</td><td>Edit</td>\n";
	echo " </tr>\n";
	foreach ($SERVICES as $svc_id => $service) {
		echo "<tr align=\"center\"><td>$service</td><td>". alink("Edit","?module=admin&do=step_edit&sid=$svc_id") ."</td></tr>\n";
	}
	echo "</table>\n";
} else {

	foreach ($SERVICES as $svc_id => $service) {
		if ($svc_id != intval($_GET["sid"])) continue;
		echo "<b>$service</b><br>";
		echo "<table border=\"0\" id=\"svc$svc_id\">\n";
		echo " <tr align=\"center\" class=\"heading\" style=\"font-size:8pt;\">\n";
		echo "  <td>Order</td>\n";
		echo "  <td>Step</td>\n";
		echo "  <td>Delete</td>\n";
		echo " </tr>\n";
		if (isset($STEPS[$svc_id])) {
			foreach ($STEPS[$svc_id] as $step_id => $step) {
				echo " <tr align=\"center\">\n";
				echo "  <td><input type=\"hidden\" name=\"step_fs[]\" value=\"$svc_id\"><input type=\"hidden\" name=\"step_id[]\" value=\"$step_id\"><input type=\"text\" name=\"step_order[]\" size=\"3\" value=\"{$step["order"]}\"></td>\n";
				echo "  <td><input type=\"text\" name=\"step_text[]\" size=\"50\" value=\"".str_replace('"',"`",$step["step"])."\"></td>\n";
				echo "  <td>".alink("Delete","?module=admin&do=step_edit&sid=$svc_id&delete=$step_id")."</td>\n";
				echo " </tr>\n";
			}
		}
		echo "</table>\n";
		echo alink_onclick("Add Step","#","addRow($svc_id);") ."<br><br>\n";
	}
}

?>
<input type="submit" value="Save Changes"> &nbsp;
<?php echo alink("Cancel","?module=admin&do=step_edit"); ?>
</form>
