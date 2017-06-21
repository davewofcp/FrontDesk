<?php

$REQUIRED = ARRAY
  ( 'dbhost'
  , 'dbpuser'
  , 'dbppass'
  , 'dbname'
  );

$DEFAULTS = ARRAY
  ( 'dbhost'    => ''
  , 'dbpuser'   => ''
  , 'dbppass'   => ''
  , 'dbname'    => ''
  , 'adminuser' => 'admin'
  , 'adminpass' => SUBSTR( STR_SHUFFLE( BASE64_ENCODE( MT_RAND() ) . SUBSTR( '!@#$%&*?' , 0 , MT_RAND( 0 , 8 ) ) ) , 0 , 12 )
  );

$_REQUEST = ARRAY_MERGE( $DEFAULTS , ARRAY_DIFF( $_REQUEST , ARRAY( '' ) ) );

$ACTIONS = ARRAY();
$RESULTS = ARRAY();

_check_required();

# ----------------------------------------------------------------- PROCESSING COMPLETE

$messages = '';
$count = COUNT( $RESULTS );
FOR ( $i = 0 ; $i < $count ; $i++ ) {
  $messages .= '✓ ' . $ACTIONS[$i] . ' ' . $RESULTS[$i] .'<br>' . "\n";
}

ECHO <<<EOHTML
<!DOCTYPE html>
<html>
<head>
<title>Frontdesk Database Installer</title>
<style>body{width:800px;margin:20px auto;}table{border:1px #000 outset;padding:30px;width:100%;}#messages{background:#f7f7f7;padding:30px;font-size:small;}</style>
</head>
<body>
<h3>Frontdesk Database Installer</h3>
<form action="4.php" method="post">
<p id="messages">{$messages}</p>
<input type="hidden" name="dbhost" value="{$_REQUEST['dbhost']}">
<input type="hidden" name="dbpuser" value="{$_REQUEST['dbpuser']}">
<input type="hidden" name="dbppass" value="{$_REQUEST['dbppass']}">
<input type="hidden" name="dbname" value="{$_REQUEST['dbname']}">
<table border="0">
 <tr><th colspan="3" class="heading" align="left">Organization Administrator Account</th></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center">
    <p>Please provide a username and password for the Organization Admnistrator account.</p>
   </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td class="heading" align="right">Account Username:</td>
  <td>&nbsp;</td>
  <td><input type="edit" name="adminuser" size="30" value="{$_REQUEST['adminuser']}"> *</td>
 </tr>
 <tr>
  <td class="heading" align="right">Account Password:</td>
  <td>&nbsp;</td>
  <td><input type="edit" name="adminpass" size="30" value="{$_REQUEST['adminpass']}"> *</td>
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

EXIT;

# ----------------------------------------------------------------- INIT FUNCTIONS

FUNCTION _check_required() {
  GLOBAL $ACTIONS , $RESULTS , $REQUIRED;
  $ACTIONS[] = 'Processing Request...';
  IF ( 0 < COUNT( ARRAY_INTERSECT( $REQUIRED , ARRAY_KEYS( $_REQUEST , '' ) ) ) ) {
    $RESULTS[] = 'Error: Required information is missing. Please fill in all required fields.';
    _output_error_bad_input_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

# ----------------------------------------------------------------- ERROR DOCS

FUNCTION _output_error_default_html() {
  GLOBAL $ACTIONS , $RESULTS;
  $messages = '';
  $count = COUNT( $RESULTS );
  FOR ( $i = 0 ; $i < $count ; $i++ ) {
    $messages .= ( $count === $i + 1 ) ? '✖ ' : '✓ ';
    $messages .= $ACTIONS[$i] . ' ' . $RESULTS[$i] .'<br>' . "\n";
  }
  ECHO <<<EOHTML
<!DOCTYPE html>
<html>
<head>
<title>Frontdesk Database Installer</title>
<style>body{width:800px;margin:20px auto;}table{border:1px #000 outset;padding:30px;width:100%;}#messages{background:#f7f7f7;padding:30px;font-size:small;}</style>
</head>
<body>
<h3>Frontdesk Database Installer</h3>
<p id="messages">{$messages}</p>
</body>
</html>
EOHTML;
}

FUNCTION _output_error_bad_input_html() {
  _output_error_default_html();
}

# ----------------------------------------------------------------- EOF
?>
