<?php if (!isset($USER)) { header("Location: http://". $_SERVER['SERVER_NAME'] ."/login.php"); exit; }

$ENTITIES = ARRAY();
$result = mysql_query("
SELECT
  oe.*
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
$result = mysql_query("
SELECT
  ur.*
FROM
  user_roles ur,
  app_levels al
WHERE
  ur.app_levels__id = al.id
  AND al.title = 'Entity'
");
WHILE (false!==($row=mysql_fetch_assoc($result))) $ROLES[$row['id']] = $row['title'];
asort($ROLES);
 ?>
<h3>Create New User</h3>
<?php if (isset($RESPONSE)) { ?><font color="red" size="+1"><b><?php echo $RESPONSE; ?></b></font><br><?php } ?>
<form action="?module=admin&do=new_user" method="post">
<table border="0" cellspacing="3">
 <tr>
  <td class="heading" align="right">Username</td>
  <td><input type="edit" name="username" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Location</td>
  <td><select type="edit" name="location">
<?php
  FOREACH($ENTITIES AS $id=>$title){
$selected = ($id==$THIS_USER["org_entities__id"]) ? ' selected':'';
echo '<option value="'.$id.'"'.$selected.'>'.$title.'</option>' . "\n";
}?>
   </select></td>
 </tr>
 <tr>
  <td class="heading" align="right">Role</td>
  <td><select type="edit" name="user_type">
<?php FOREACH($ROLES AS $id=>$title){
$selected = ($id==$THIS_USER["user_roles__id"]) ? ' selected':'';
echo '<option value="'.$id.'"'.$selected.'>'.$title.'</option>' . "\n";
}?>
   </select></td>
 </tr>
 <tr>
  <td class="heading" align="right">Password</td>
  <td><input type="edit" name="password" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">First Name</td>
  <td><input type="edit" name="firstname" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Last Name</td>
  <td><input type="edit" name="lastname" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Phone</td>
  <td><input type="edit" name="phone" size="20"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Email</td>
  <td><input type="edit" name="email" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Is onsite</td>
  <td><input type="checkbox" name="is_onsite" value="1"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Hourly Rate</td>
  <td><input type="edit" name="hourlyrate" size="10"></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <input type="submit" value="Create User">
   <?php echo alink("Cancel","?module=admin"); ?>
  </td>
 </tr>
</table>
</form>
