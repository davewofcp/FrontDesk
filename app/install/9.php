<?php

# ----------------------------------------------------------------- PROCESSING COMPLETE

ECHO <<<EOHTML
<!DOCTYPE html>
<html>
<head>
<title>Frontdesk Database Installer</title>
<style>body{width:800px;margin:20px auto;}table{border:1px #000 outset;padding:30px;width:100%;}</style>
</head>
<body>
<h3>Frontdesk Database Installer</h3>
<form action="/" method="post">
<table border="0">
 <tr><th colspan="3" class="heading" align="left">Installation Complete</th></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center">
   <p>The Frontdesk database is now installed and ready for use.</p>
   <p>Please be sure you have made the proper edits to the "config.php" file in the root directory of your Frontdesk installation.</p>
   <p>Once that is done, you should be ready to put Frontdesk into production.</p>
  </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center"><input type="submit" value="Go to Login Page"></td>
 </tr>
</table>
</form>
</body>
</html>
EOHTML;

EXIT;

# ----------------------------------------------------------------- EOF
?>
