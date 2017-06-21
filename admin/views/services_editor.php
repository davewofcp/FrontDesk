<?php

if (isset($_GET["delete"])) {
	mysql_query("DELETE FROM service_steps WHERE services__id = ".intval($_GET["delete"]));
	mysql_query("DELETE FROM services WHERE id = ".intval($_GET["delete"]));
}

if (isset($_POST["svc"])) {
	foreach ($_POST["svc"] as $x => $id) {
		if ($id == "0") {
			if ($_POST["svcName"][$x] == "") continue;
			$sql = "INSERT INTO services (name,cost) VALUES (";
			$sql .= "'". mysql_real_escape_string($_POST["svcName"][$x]) ."',";
			$sql .= "'". floatval($_POST["svcCost"][$x]) ."')";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		} else {
			$sql = "UPDATE services SET ";
			$sql .= "name = '". mysql_real_escape_string($_POST["svcName"][$x]) ."',";
			$sql .= "cost = '". floatval($_POST["svcCost"][$x]) ."' ";
			$sql .= "WHERE id = ".intval($id);
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}
	}
}

$SERVICES = array();
$result = mysql_query("SELECT * FROM services ORDER BY id");
while ($row = mysql_fetch_assoc($result)) {
	$SERVICES[$row["id"]] = array();
	$SERVICES[$row["id"]]["name"] = $row["name"];
	$SERVICES[$row["id"]]["cost"] = $row["cost"];
}

?>
<script type="text/javascript">
function addRow() {
	var table=document.getElementById("svcTable");
	var row=table.insertRow(1);
	row.align = 'center';
	var cell1=row.insertCell(0);
	var cell2=row.insertCell(1);
	var cell3=row.insertCell(2);
	var cell4=row.insertCell(3);
	cell1.innerHTML = '<input type="hidden" name="svc[]" value="0">--';
	cell2.innerHTML = '<input type="text" name="svcName[]" size="35">';
	cell3.innerHTML = '$<input type="text" name="svcCost[]" size="5">';
	cell4.innerHTML = '--';
}
</script>
<h3>Services</h3>

<?php echo alink_onclick("Add New Service","#","addRow();"); ?><br><br>

<form action="?module=admin&do=svc_edit" method="post">
<table border="0" id="svcTable">
 <tr align="center" class="heading">
  <td>ID</td>
  <td>Name</td>
  <td>Cost</td>
  <td>Delete</td>
 </tr>
<?php

foreach ($SERVICES as $id => $svc) {
	echo " <tr align=\"center\">\n";
	echo "  <td><input type=\"hidden\" name=\"svc[]\" value=\"$id\">$id</td>\n";
	echo "  <td><input type=\"text\" name=\"svcName[]\" size=\"35\" value=\"".str_replace('"',"`",$svc["name"])."\"></td>\n";
	echo "  <td>$<input type=\"text\" name=\"svcCost[]\" size=\"5\" value=\"".number_format($svc["cost"],2)."\"></td>\n";
	echo "  <td>".alink("Delete","?module=admin&do=svc_edit&delete=$id")."</td>\n";
	echo " </tr>\n";
}

?>
</table>
<input type="submit" value="Save Changes">
</form>
