<?php

$REQUIRED = ARRAY
  ( 'dbhost'
  , 'dbpuser'
  , 'dbppass'
  , 'dbname'
  , 'adminuser'
  , 'adminpass'
  );

$DEFAULTS = ARRAY
  ( 'dbhost'    => ''
  , 'dbpuser'   => ''
  , 'dbppass'   => ''
  , 'dbname'    => ''
  , 'adminuser' => ''
  , 'adminpass' => ''
  , 'orgtitle'  => ''
  , 'orgloc'    => ''
  , 'orgaddr'   => ''
  , 'orgcity'   => ''
  , 'orgstate'  => ''
  , 'orgcntry'  => ''
  , 'orgpost'   => ''
  , 'orgphone'  => ''
  , 'orgfax'    => ''
  , 'deftax'    => ''
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
<form action="6.php" method="post">
<p id="messages">{$messages}</p>
<input type="hidden" name="dbhost" value="{$_REQUEST['dbhost']}">
<input type="hidden" name="dbpuser" value="{$_REQUEST['dbpuser']}">
<input type="hidden" name="dbppass" value="{$_REQUEST['dbppass']}">
<input type="hidden" name="dbname" value="{$_REQUEST['dbname']}">
<input type="hidden" name="adminuser" value="{$_REQUEST['adminuser']}">
<input type="hidden" name="adminpass" value="{$_REQUEST['adminpass']}">
<input type="hidden" name="orgtitle" value="{$_REQUEST['orgtitle']}">
<input type="hidden" name="orgloc" value="{$_REQUEST['orgloc']}">
<input type="hidden" name="orgaddr" value="{$_REQUEST['orgaddr']}">
<input type="hidden" name="orgcity" value="{$_REQUEST['orgcity']}">
<input type="hidden" name="orgstate" value="{$_REQUEST['orgstate']}">
<input type="hidden" name="orgcntry" value="{$_REQUEST['orgcntry']}">
<input type="hidden" name="orgpost" value="{$_REQUEST['orgpost']}">
<input type="hidden" name="orgphone" value="{$_REQUEST['orgphone']}">
<input type="hidden" name="orgfax" value="{$_REQUEST['orgfax']}">
<table border="0">
 <tr><th colspan="3" class="heading" align="left">Default Settings</th></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center">
   <p>Please provide default settings for Frontdesk to use when store-specific information is not available. This covers scenarios where data imported into the system is corrupted or incomplete.</p>
  </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td class="heading" align="right">Fallback Sales Tax Rate:</td>
  <td>&nbsp;</td>
  <td width="40%"><input type="edit" name="deftax" size="3" value="{$_REQUEST['deftax']}">% *</td>
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
