<?php

$REQUIRED = ARRAY
  ( 'dbhost'
  , 'dbpuser'
  , 'dbppass'
  , 'dbname'
  );

$DEFAULTS = ARRAY
  ( 'dbhost'  => ''
  , 'dbpuser' => ''
  , 'dbppass' => ''
  , 'dbname'  => ''
  );

$_REQUEST = ARRAY_MERGE( $DEFAULTS , ARRAY_DIFF( $_REQUEST , ARRAY( '' ) ) );

$ACTIONS = ARRAY();
$RESULTS = ARRAY();

$upload_max_filesize = _convert_bytes( INI_GET( 'upload_max_filesize' ) );
$max_post_size = _convert_bytes( INI_GET( 'post_max_size' ) );

$MAX_FILE_SIZE = ( $upload_max_filesize > $max_post_size ) ? $upload_max_filesize : $max_post_size;
$MAX_FILE_MEGS = $MAX_FILE_SIZE / 1048576;

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
<form action="8-process.php" method="post" enctype="multipart/form-data">
<p id="messages">{$messages}</p>
<input type="hidden" name="dbhost" value="{$_REQUEST['dbhost']}">
<input type="hidden" name="dbpuser" value="{$_REQUEST['dbpuser']}">
<input type="hidden" name="dbppass" value="{$_REQUEST['dbppass']}">
<input type="hidden" name="dbname" value="{$_REQUEST['dbname']}">
<table border="0">
 <tr><th colspan="3" class="heading" align="left">Single Store Database Migration Tool</th></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center">
   <p>This tool will attempt to convert one or more old single store databases to the current schemas, with the results being merged into the new combined instance database you created earlier.</p>
   <p>You will need an uncompressed SQL dump file of the old single store database that you wish to have processed. If you have one, please select a file for upload below.</p>
  </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td width="30%" class="heading" align="right">SQL Dump File:</td>
  <td>&nbsp;</td>
  <td>
    <input type="hidden" name="MAX_FILE_SIZE" value="{$MAX_FILE_SIZE}">
    <input type="file" name="sqldump"> * ({$MAX_FILE_MEGS}MB max.)
  </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center"><input type="submit" value="Upload"></td>
 </tr>
</table>
</form>
<form action="9.php" method="post">
<input type="hidden" name="dbhost" value="{$_REQUEST['dbhost']}">
<input type="hidden" name="dbpuser" value="{$_REQUEST['dbpuser']}">
<input type="hidden" name="dbppass" value="{$_REQUEST['dbppass']}">
<input type="hidden" name="dbname" value="{$_REQUEST['dbname']}">
<table border="0">
 <tr>
  <td colspan="3" align="center"><input type="submit" value="Exit Database Migration Tool"></td>
 </tr>
</table>
</form>
</body>
</html>
EOHTML;

EXIT;

# ----------------------------------------------------------------- INIT FUNCTIONS

FUNCTION _convert_bytes( $value ) {
  IF ( IS_NUMERIC( $value ) ) {
    RETURN $value;
  }
  ELSE {
    $value_length = STRLEN( $value );
    $qty = SUBSTR( $value , 0 , $value_length - 1 );
    $unit = STRTOLOWER( SUBSTR( $value , $value_length - 1 ) );
    SWITCH ( $unit ) {
      CASE 'k':
        $qty *= 1024;
        BREAK;
      CASE 'm':
        $qty *= 1048576;
        BREAK;
      CASE 'g':
        $qty *= 1073741824;
        BREAK;
    }
    RETURN $qty;
  }
}

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
