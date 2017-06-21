<?php if (!isset($USER)) { header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit; }

$ENTITIES = ARRAY();
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
");
WHILE (false!==($row=mysql_fetch_assoc($result))) $ENTITIES[$row['id']] = $row['title'];
asort($ENTITIES);

$ROLES = ARRAY();
$result = mysql_query("SELECT id,title FROM user_roles WHERE id>0");
WHILE (false!==($row=mysql_fetch_assoc($result))) $ROLES[$row['id']] = $row['title'];
asort($ROLES);

?>
<h3>Edit User</h3>
<?php
if(isset($_GET["edited"])){
  echo "User Updated!<br>";
}
?>
<form action="?module=admin&do=edit_user&id=<?php echo $THIS_USER["id"]; ?>" method="post">
<table border="0" cellspacing="3">
 <tr>
  <td class="heading" align="right">User ID</td>
  <td><?php echo $THIS_USER["id"]; ?></td>
 </tr>
 <tr>
  <td class="heading" align="right">Username</td>
  <td><input type="edit" name="username" value="<?php echo $THIS_USER["username"]; ?>" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Is disabled</td>
  <td><input type="checkbox" name="is_disabled" value="1"<?php if ($THIS_USER["is_disabled"]) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Location</td>
  <td><select type="edit" name="org_entities__id">
<?php FOREACH($ENTITIES AS $id=>$title){
$selected = ($id==$THIS_USER["org_entities__id"]) ? ' selected':'';
echo '<option value="'.$id.'"'.$selected.'>'.$title.'</option>' . "\n";
}?>
   </select></td>
 </tr>
 <tr>
  <td class="heading" align="right">Role</td>
  <td><select type="edit" name="user_roles__id">
<?php FOREACH($ROLES AS $id=>$title){
$selected = ($id==$THIS_USER["user_roles__id"]) ? ' selected':'';
echo '<option value="'.$id.'"'.$selected.'>'.$title.'</option>' . "\n";
}?>
   </select></td>
 </tr>
 <tr>
  <td class="heading" align="right">Password</td>
  <td><input type="edit" name="password" value="" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">First Name</td>
  <td><input type="edit" name="firstname" value="<?php echo $THIS_USER["firstname"]; ?>" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Last Name</td>
  <td><input type="edit" name="lastname" value="<?php echo $THIS_USER["lastname"]; ?>" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Phone</td>
  <td><input type="edit" name="phone" value="<?php echo $THIS_USER["phone"]; ?>" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Email</td>
  <td><input type="edit" name="email" value="<?php echo $THIS_USER["email"]; ?>" size="40"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Is onsite</td>
  <td><input type="checkbox" name="is_onsite" value="1"<?php if ($THIS_USER["is_onsite"]) echo " CHECKED"; ?>></td>
 </tr>
 <tr>
  <td class="heading" align="right">Hourly Rate</td>
  <td><input type="edit" name="hourlyrate" value="<?php echo $THIS_USER["hourlyrate"]; ?>" size="10"></td>
 </tr>
 <tr>
  <td colspan="2" align="center"><input type="submit" value="Save Changes">
  <?php echo alink("Cancel","?module=admin&do=list_users"); ?>
  </td>
 </tr>
</table>
</form>
