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
  , 'dbnuser' => ( 'frontdesk_' . SUBSTR( TIME() , -6 ) )
  , 'dbnpass' => SUBSTR( STR_SHUFFLE( BASE64_ENCODE( MT_RAND() ) . SUBSTR( '!@#$%&*?' , 0 , MT_RAND( 0 , 8 ) ) ) , 0 , 12 )
  );

$_REQUEST = ARRAY_MERGE( $DEFAULTS , ARRAY_DIFF( $_REQUEST , ARRAY( '' ) ) );

$ACTIONS = ARRAY();
$RESULTS = ARRAY();

_check_required();

_connect_to_database_server();

_check_schema();

_create_database();

_select_created_database();

_create_restricted_user();

_grant_user_server_access();

_grant_user_database_privileges();

_process_schemas();

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
<form action="3.php" method="post">
<p id="messages">{$messages}</p>
<input type="hidden" name="dbhost" value="{$_REQUEST['dbhost']}">
<input type="hidden" name="dbpuser" value="{$_REQUEST['dbpuser']}">
<input type="hidden" name="dbppass" value="{$_REQUEST['dbppass']}">
<input type="hidden" name="dbname" value="{$_REQUEST['dbname']}">
<table border="0">
 <tr>
  <td colspan="3" align="center">
    <p>Database initialization is complete.</p>
   </td>
 </tr>
 <tr><th colspan="3" class="heading" align="left">Access Configuration</th></tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center">
    <p>You will need edit the "config.php" file in the root directory of you Frontdesk installation to include the following information:</p>
   </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td width="30%">&nbsp;</td>
  <td align="left">
    <pre>
\$db_host = "{$_REQUEST['dbhost']}";
\$db_user = "{$_REQUEST['dbnuser']}";
\$db_pass = "{$_REQUEST['dbnpass']}";
\$db_name = "{$_REQUEST['dbname']}";
    </pre>
   </td>
  <td width="30%">&nbsp;</td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td colspan="3" align="center">
    <p>While you are in the "config.php" file, you should also set the local timezone of the server so that dates, timestamps, and the login session timer works correctly. If you experience immediate logout problems after logging in, this is what is likely causing the problem.</p>
   </td>
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

# ----------------------------------------------------------------- SQL ACTIONS

FUNCTION _check_schema() {
  GLOBAL $ACTIONS , $RESULTS;
  $dbname = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbname'] );
  $sql = <<<EOMYSQL
--
SELECT
  `schema_name`
FROM
  `information_schema`.`schemata`
WHERE
  `schema_name` = '{$dbname}';
--
EOMYSQL;
  $ACTIONS[] = 'Checking for Existing Database...';
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not access the database information schemas.';
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    $RESULTS[] = 'Error: A database with the name you specified already exists. Please choose a different name.';
    _output_error_bad_input_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _create_database() {
  GLOBAL $ACTIONS , $RESULTS;
  $dbname = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbname'] );
  $sql = <<<EOMYSQL
CREATE DATABASE {$dbname};
EOMYSQL;
  $ACTIONS[] = 'Creating Application Database...';
  IF ( FALSE === MYSQL_QUERY( $sql ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not create the application database.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _select_created_database() {
  GLOBAL $ACTIONS , $RESULTS;
  $dbname = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbname'] );
  $ACTIONS[] = 'Activating Database...';
  IF ( FALSE === MYSQL_SELECT_DB( $dbname ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not make the application database active.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _create_restricted_user() {
  GLOBAL $ACTIONS , $RESULTS;
  $dbnuser = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbnuser'] );
  $dbnpass = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbnpass'] );
  $sql = <<<EOMYSQL
CREATE USER '{$dbnuser}'@'%'
IDENTIFIED BY '{$dbnpass}';
EOMYSQL;
  $ACTIONS[] = 'Creating Restricted User Account...';
  IF ( FALSE === MYSQL_QUERY( $sql ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not create the restricted user account.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _grant_user_server_access() {
  GLOBAL $ACTIONS , $RESULTS;
  $dbnuser = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbnuser'] );
  $dbnpass = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbnpass'] );
  $sql = <<<EOMYSQL
GRANT USAGE
ON * . *
TO '{$dbnuser}'@'%'
IDENTIFIED BY '{$dbnpass}'
WITH
MAX_QUERIES_PER_HOUR 0
MAX_CONNECTIONS_PER_HOUR 0
MAX_UPDATES_PER_HOUR 0
MAX_USER_CONNECTIONS 0;
EOMYSQL;
  $ACTIONS[] = 'Granting Database Server Access to Restricted User...';
  IF ( FALSE === MYSQL_QUERY( $sql ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not grant server access to the restricted user.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _grant_user_database_privileges() {
  GLOBAL $ACTIONS , $RESULTS;
  $dbname = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbname'] );
  $dbnuser = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbnuser'] );
  $sql = <<<EOMYSQL
GRANT ALL PRIVILEGES
ON `{$dbname}` . *
TO '{$dbnuser}'@'%';
EOMYSQL;
  $ACTIONS[] = 'Granting Database Privileges to Restricted User...';
  IF ( FALSE === MYSQL_QUERY( $sql ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not grant database privileges to the restricted user.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _process_schemas() {
  GLOBAL $ACTIONS , $RESULTS;
  $ACTIONS[] = 'Reading Database Schemas Directory...';
  $lookup = DIRNAME( __FILE__ ) . '/schema';
  IF ( ! IS_READABLE( $lookup ) ) {
    $RESULTS[] = 'Error: Unable to read schema directory.';
    _output_error_default_html();
    EXIT;
  }
  $dir = OPENDIR( $lookup );
  $schemas = ARRAY();
  WHILE ( FALSE !== ( $file = READDIR( $dir ) ) ) {
    IF ( IS_DIR( $file ) ) {
      CONTINUE;
    }
    ELSEIF ( ! IN_ARRAY( $file , ARRAY( '.' , '..' ) ) ) {
      $schemas[] = $file;
    }
  }
  SORT( $schemas );
  $RESULTS[] = 'Done.';
  FOREACH ( $schemas as $schema ) {
    IF ( ! STRRPOS( $schema , '.sql' , -4 ) ) {
      CONTINUE;
    }
    ELSE {
      $sql = FILE_GET_CONTENTS( $lookup . '/' . $schema );
      IF ( 0 === STRPOS( $schema , 'trigger_' ) ) {
        $ACTIONS[] = 'Processing Trigger Definition File "' . $schema . '"...';
        IF ( 1 > STRLEN( TRIM( $sql ) ) ) {
          $RESULTS[] = 'File was empty.';
        }
        ELSE {
          IF ( FALSE === MYSQL_QUERY( $sql ) ) {
            $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: The schema contained an error.';
            _output_error_default_html();
            EXIT;
          }
          ELSE {
            $RESULTS[] = 'Done.';
          }
        }
      }
      ELSE {
        $ACTIONS[] = 'Processing Table Definition File "' . $schema . '"...';
        IF ( 1 > STRLEN( TRIM( $sql ) ) ) {
          $RESULTS[] = 'File was empty.';
        }
        ELSE {
          IF ( FALSE === MYSQL_QUERY( $sql ) ) {
            $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: The schema contained an error.';
            _output_error_default_html();
            EXIT;
          }
          ELSE {
            $RESULTS[] = 'Done.';
          }
        }
      }
    }
  }
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
<style>body{width:800px;margin:20px auto;}table{border:1px #000 outset;padding:30px;width:100%;}#messages{background:#f7f7f7;padding:30px;font-size:small;}</style>
</head>
<body>
<h3>Frontdesk Database Installer</h3>
<p id="messages">{$messages}</p>
<form action="2.php" method="post">
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
}

# ----------------------------------------------------------------- EOF
?>
