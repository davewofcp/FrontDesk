<?php

$DEFAULTS = ARRAY
  ( 'dbhost'  => 'localhost'
  , 'dbpuser' => ''
  , 'dbppass' => ''
  );

$_REQUEST = ARRAY_MERGE( $DEFAULTS , ARRAY_DIFF( $_REQUEST , ARRAY( '' ) ) );

ECHO <<<EOHTML
<!DOCTYPE html>
<html>
<head>
<title>Frontdesk Database Installer</title>
<style>body{width:800px;margin:20px auto;}table{border:1px #000 outset;padding:30px;width:100%;}</style>
</head>
<body>
<h3>Frontdesk Database Installer</h3>
<form action="1.php" method="post">
<table border="0">
 <tr><th colspan="3" class="heading" align="left">Database Server</th></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center">
    <p>This installer requires privileged access to your database server in order to create the application database and a user account with restricted privileges for use by Frontdesk.</p>
    <p><b>Important:</b> The privileged user account you provide must possess the 'Super' privilege on your database server.</p>
   </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td class="heading" align="right">Server Hostname:</td>
  <td>&nbsp;</td>
  <td><input type="edit" name="dbhost" size="30" value="{$_REQUEST['dbhost']}"> *</td>
 </tr>
 <tr>
  <td class="heading" align="right">Privileged Access Username:</td>
  <td>&nbsp;</td>
  <td><input type="edit" name="dbpuser" size="30" value="{$_REQUEST['dbpuser']}"> *</td>
 </tr>
 <tr>
  <td class="heading" align="right">Privileged Access Password:</td>
  <td>&nbsp;</td>
  <td><input type="edit" name="dbppass" size="30" value="{$_REQUEST['dbppass']}"> *</td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center"><input type="submit" value="Continue"></td>
 </tr>
</table>
</form>
</body>
</html>
EOHTML;

?>
