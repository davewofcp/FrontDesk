<?php

if (isset($_GET["delete"])) {
	mysql_query("DELETE FROM categories WHERE id = ".intval($_GET["delete"]));
}

if (isset($_POST["cat"])) {
	foreach ($_POST["cat"] as $x => $id) {
		if ($id == "") {
			if ($_POST["catName"][$x] == "") continue;
			$sql = "INSERT INTO categories (category_set,category_name,parent_id) VALUES (";
			$sql .= "'". mysql_real_escape_string($_POST["catSet"][$x]) ."',";
			$sql .= "'". mysql_real_escape_string($_POST["catName"][$x]) ."',";
			$sql .= (($_POST["catParent"][$x] == '' ) ? 'NULL': intval($_POST["catParent"][$x])) .")";
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		} else {
			$sql = "UPDATE categories SET ";
			$sql .= "category_set = '". mysql_real_escape_string($_POST["catSet"][$x]) ."',";
			$sql .= "category_name = '". mysql_real_escape_string($_POST["catName"][$x]) ."',";
			$sql .= "parent_id = ". (($_POST["catParent"][$x] == '' ) ? 'NULL': intval($_POST["catParent"][$x])) ." ";
			$sql .= "WHERE id = ".intval($id);
			mysql_query($sql) or die(mysql_error() ."::". $sql);
		}
	}
}

?>
<script type="text/javascript">
function addRow() {
	var table=document.getElementById("catTable");
	var row=table.insertRow(1);
	row.align = 'center';
	var cell1=row.insertCell(0);
	var cell2=row.insertCell(1);
	var cell3=row.insertCell(2);
	var cell4=row.insertCell(3);
	var cell5=row.insertCell(4);
	cell1.innerHTML = '<input type="hidden" name="cat[]" value="0">--';
	cell2.innerHTML = '<input type="text" name="catSet[]" size="15">';
	cell3.innerHTML = '<input type="text" name="catName[]" size="30">';
	cell4.innerHTML = '<input type="text" name="catParent[]" size="4">';
	cell5.innerHTML = '--';
}
</script>
<h3>Categories</h3>

<?php echo alink_onclick("Add New Category","#","addRow();"); ?><br><br>

<form action="?module=admin&do=cat_edit" method="post">
<table border="0" id="catTable">
 <tr align="center" class="heading">
  <td>ID</td>
  <td>Set</td>
  <td>Name</td>
  <td>Parent</td>
  <td>Delete</td>
 </tr>
<?php

$result = mysql_query("SELECT * FROM categories WHERE 1 ORDER BY category_set,id");
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\">\n";
	echo "  <td><input type=\"hidden\" name=\"cat[]\" value=\"".$row["id"]."\">".$row["id"]."</td>\n";
	echo "  <td><input type=\"text\" name=\"catSet[]\" size=\"15\" value=\"".$row["category_set"]."\"></td>\n";
	echo "  <td><input type=\"text\" name=\"catName[]\" size=\"30\" value=\"".$row["category_name"]."\"></td>\n";
	echo "  <td><input type=\"text\" name=\"catParent[]\" size=\"4\" value=\"".$row["parent_id"]."\"></td>\n";
	echo "  <td>".alink("Delete","?module=admin&do=cat_edit&delete=".$row["id"])."</td>\n";
	echo " </tr>\n";
}

?>
</table>
<input type="submit" value="Save Changes">
</form>
