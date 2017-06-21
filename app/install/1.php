<?php

$REQUIRED = ARRAY
  ( 'dbhost'
  , 'dbpuser'
  , 'dbppass'
  );

$DEFAULTS = ARRAY
  ( 'dbhost'  => ''
  , 'dbpuser' => ''
  , 'dbppass' => ''
  , 'dbname'  => 'frontdesk'
  );

$_REQUEST = ARRAY_MERGE( $DEFAULTS , ARRAY_DIFF( $_REQUEST , ARRAY( '' ) ) );

$ACTIONS = ARRAY();
$RESULTS = ARRAY();

_check_required();

_connect_to_database_server();

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
<form action="2.php" method="post">
<p id="messages">{$messages}</p>
<input type="hidden" name="dbhost" value="{$_REQUEST['dbhost']}">
<input type="hidden" name="dbpuser" value="{$_REQUEST['dbpuser']}">
<input type="hidden" name="dbppass" value="{$_REQUEST['dbppass']}">
<table border="0">
 <tr><th colspan="3" class="heading" align="left">Application Database</th></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center">
    <p>Please provide a name for the new application database. A restricted user account will be automatically created to allow Frontdesk access to this database.</p>
   </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td class="heading" align="right">Database Name:</td>
  <td>&nbsp;</td>
  <td><input type="edit" name="dbname" size="30" value="{$_REQUEST['dbname']}"> *</td>
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

FUNCTION _connect_to_database_server() {
  GLOBAL $ACTIONS , $RESULTS;
  $dbhost = $_REQUEST['dbhost'];
  $dbpuser = $_REQUEST['dbpuser'];
  $dbppass = $_REQUEST['dbppass'];
  $ACTIONS[] = 'Connecting to Database Server...';
  IF ( ! IS_RESOURCE( $DB = MYSQL_CONNECT( $dbhost , $dbpuser , $dbppass ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not connect to the database server.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Connection established.';
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
<style>body{width:800px;margin:20px auto;}table{border:1px #000 outset;padding:30px;width:100%;}#messages{background:#f7f7f7;padding:30px;font-size:small;}
</style>
</head>
<body>
<h3>Frontdesk Database Installer</h3>
<form action="1.php" method="post">
<p id="messages">{$messages}</p>
<table border="0">
 <tr><th colspan="3" class="heading" align="left">Database Server</th></tr>
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
  <td colspan="3" align="center"><input type="submit" value="Try Again"></td>
 </tr>
</table>
</form>
</body>
</html>
EOHTML;
}

# ----------------------------------------------------------------- EOF
?>
