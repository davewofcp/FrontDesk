<?php if (!isset($USER)) { header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit; } ?>
<h3>All Users</h3>
<table border="0" width="600">
 <tr align="center">
  <td class="heading">User ID</td>
  <td class="heading">Username</td>
  <td class="heading">Name</td>
  <td class="heading">Disabled</td>
  <td class="heading">Edit</td>
  <td class="heading">Delete</td>
 </tr>
<?php

while ($_user = mysql_fetch_assoc($USERS)) {
	echo " <tr align=\"center\">\n";
	echo "  <td>". $_user["id"] ."</td>\n";
	echo "  <td>". $_user["username"] ."</td>\n";
	echo "  <td>". $_user["firstname"] ." ". $_user["lastname"] ."</td>\n";
	echo "  <td style='font-weight:bold'>". ($_user["is_disabled"] ? "<font style='color:red;font-weight:bold;'>Yes</font>" : "") ."</td>\n";
	echo "  <td>".alink("Edit","?module=admin&do=edit_user&id=". $_user["id"])."</td>\n";
	echo "  <td>".alink_onclick("Delete","?module=admin&do=delete_user&id=". $_user["id"],"return confirm('Are you sure you want to delete this user?');")."</td>\n";
	echo " </tr>\n";
}

?>
</table><br>

<?php echo alink("Back to Administration","?module=admin"); ?>
