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
  , 'tdbname' => 'tmp_fd_migrate_' . TIME()
  );

$_REQUEST = ARRAY_MERGE( $DEFAULTS , ARRAY_DIFF( $_REQUEST , ARRAY( '' ) ) );

$ACTIONS = ARRAY();
$RESULTS = ARRAY();

$upload_max_filesize = _convert_bytes( INI_GET( 'upload_max_filesize' ) );
$max_post_size = _convert_bytes( INI_GET( 'post_max_size' ) );

$MAX_FILE_SIZE = ( $upload_max_filesize > $max_post_size ) ? $upload_max_filesize : $max_post_size;
$MAX_FILE_MEGS = $MAX_FILE_SIZE / 1048576;

_check_required();

_check_file_uploaded();

$DB1 = _connect_to_database_server();

_import_sql_dump_file();

$DB2 = _connect_to_database_server();

_migrate_temporary_database();

_clean_up();

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
   <p>If you have another file you would like processed, you can select it for upload below.</p>
   <p>If you're done, you can choose "Exit Database Migration Tool" below.</p>
  </td>
 </tr>
 <tr><td colspan="3">&nbsp;</td></tr>
 <tr>
  <td class="heading" align="right">SQL Dump File:</td>
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
  } ELSE {
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

FUNCTION _check_file_uploaded() {
  GLOBAL $ACTIONS , $RESULTS;
  $ACTIONS[] = 'Verifying File Upload...';
  IF ( ! IS_UPLOADED_FILE( $_FILES['sqldump']['tmp_name'] ) ) {
    $RESULTS[] = 'Error: A securty exception was encountered. Import processing was refused.';
    _output_error_bad_input_html();
    EXIT;
  }
  IF ( UPLOAD_ERR_OK != $_FILES['sqldump']['error'] ) {
    SWITCH ( $_FILES['sqldump']['error'] ) {
      CASE UPLOAD_ERR_INI_SIZE:
      CASE UPLOAD_ERR_FORM_SIZE:
        $RESULTS[] = 'Error: The upload was aborted for exceeding the maximum file size limit.';
        BREAK;
      CASE UPLOAD_ERR_PARTIAL:
        $RESULTS[] = 'Error: The selected file was only partially uploaded.';
        BREAK;
      CASE UPLOAD_ERR_NO_FILE:
        $RESULTS[] = 'Error: No file was selected for upload.';
        BREAK;
      CASE UPLOAD_ERR_NO_TMP_DIR:
      CASE UPLOAD_ERR_CANT_WRITE:
      CASE UPLOAD_ERR_EXTENSION:
      DEFAULT:
        $RESULTS[] = 'Error: A server configuration error caused the upload to fail.';
        BREAK;
    }
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
  IF ( ! IS_RESOURCE( $DB = MYSQL_CONNECT( $dbhost , $dbpuser , $dbppass , TRUE ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not connect to the database server.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Connection established.';
  RETURN $DB;
}

# ----------------------------------------------------------------- SQL ACTIONS

FUNCTION _create_temporary_database( $DB ) {
  GLOBAL $ACTIONS , $RESULTS;
  $tdbname = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['tdbname'] );
  $sql = <<<EOMYSQL
CREATE DATABASE {$tdbname};
EOMYSQL;
  $ACTIONS[] = 'Creating Temporary Database...';
  IF ( FALSE === MYSQL_QUERY( $sql , $DB ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR( $DB ) . ' :: Could not create the temporary database.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _select_temporary_database( $DB ) {
  GLOBAL $ACTIONS , $RESULTS;
  $tdbname = $_REQUEST['tdbname'];
  $ACTIONS[] = 'Activating Temporary Database...';
  IF ( FALSE === MYSQL_SELECT_DB( $tdbname , $DB ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR( $DB ) . ' :: Could not make the temporary database active.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _import_sql_dump_file() {
  GLOBAL $ACTIONS , $RESULTS , $DB1;
  $filename = $_FILES['sqldump']['tmp_name'];
  _create_temporary_database( $DB1 );
  _select_temporary_database( $DB1 );
  $templine = '';
  $errors = ARRAY();
  $linenum = 0;
  $delimiter = ';';
  $dlen = 1;
  $lines = FILE( $filename );
  $ACTIONS[] = 'Importing SQL to Temporary Database...';
  FOREACH ( $lines AS $linenum => $line )
  {
    $line = TRIM( $line );
    IF ( 'DELIMITER' == SUBSTR( $line , 0 , 9 ) ) {
      $delimiter = STR_REPLACE( 'DELIMITER ' , '' , $line );
      $dlen = STRLEN( $delimiter );
      CONTINUE;
    }
    IF ( ( '--' != SUBSTR( $line , 0 , 2 ) ) AND ( '' != $line ) ) {
      $templine .= ' ' . $line;
      IF ( $delimiter == SUBSTR( TRIM( $line ) , -$dlen , $dlen ) )
      {
        IF ( FALSE === MYSQL_QUERY( RTRIM( $templine , $delimiter ) , $DB1 ) ) {
          $errors[] = 'Error: ' . MYSQL_ERROR( $DB1 ) . ' :: Unable to execute the SQL statement: ' . $templine . "<br>\n";
        }
        $templine = '';
        $delimiter = ';';
        $dlen = 1;
      }
    }
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < COUNT( $errors ) ) {
    $ACTIONS[] = 'The following import errors were encountered:<br>' . "\n";
    $RESULTS[] = IMPLODE( ( "<br>\n" ) , $errors );
  }
}

FUNCTION _select_created_database( $DB ) {
  GLOBAL $ACTIONS , $RESULTS;
  $dbname = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbname'] );
  $ACTIONS[] = 'Activating Database...';
  IF ( FALSE === MYSQL_SELECT_DB( $dbname , $DB ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR( $DB ) . ' :: Could not make the application database active.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _disable_foreign_key_checks( $DB ) {
  GLOBAL $ACTIONS , $RESULTS;
  $ACTIONS[] = 'Disabling Foreign Key Checks...';
  $error = '';
  $result = MYSQL_QUERY( 'SET FOREIGN_KEY_CHECKS = 0;' , $DB );
  IF ( FALSE === $result ) $error = MYSQL_ERROR();
  $result = MYSQL_QUERY( 'SELECT @@FOREIGN_KEY_CHECKS' , $DB );
  IF ( 0 < MYSQL_NUM_ROWS( $result ) ) $RESULTS[] = 'FOREIGN_KEY_CHECKS = ' . MYSQL_RESULT( $result , 0 );
  ELSE {
    $RESULTS[] = 'Error: ' . $error . ' :: Attempt to disable FOREIGN_KEY_CHECKS failed.';
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
}

FUNCTION _disable_trigger_checks( $DB ) {
  GLOBAL $ACTIONS , $RESULTS;
  $ACTIONS[] = 'Checking Install User Privileges...';
  $error = '';
  $result = MYSQL_QUERY( "SELECT * FROM `mysql`.`user` WHERE (`Super_priv` = 'Y') AND (`User` = LEFT(USER(),LOCATE('@',USER()) - 1 )) AND (`Host` = RIGHT(USER(),LENGTH(USER()) - LOCATE('@',USER())))" , $DB );
  IF ( 0 < MYSQL_NUM_ROWS( $result ) ) $RESULTS[] = "Install User has the necessary 'Super' privilege to disable triggers.";
  ELSE {
    $RESULTS[] = "Error: Install User does not have the necessary 'Super' privilege to disable triggers. Cannot proceed.";
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $ACTIONS[] = 'Disabling Triggers...';
  $error = '';
  $result = MYSQL_QUERY( 'SET @TRIGGER_CHECKS = 0;' , $DB );
  IF ( FALSE === $result ) $error = MYSQL_ERROR();
  $result = MYSQL_QUERY( 'SELECT @TRIGGER_CHECKS' , $DB );
  IF ( 0 < MYSQL_NUM_ROWS( $result ) ) $RESULTS[] = 'TRIGGER_CHECKS = ' . MYSQL_RESULT( $result , 0 );
  ELSE {
    $RESULTS[] = 'Error: ' . $error . ' :: Attempt to disable TRIGGER_CHECKS failed.';
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
}

FUNCTION _enable_foreign_key_checks( $DB ) {
  GLOBAL $ACTIONS , $RESULTS;
  $ACTIONS[] = 'Enabling Foreign Key Checks...';
  MYSQL_QUERY( 'SET FOREIGN_KEY_CHECKS = 1;' , $DB );
  $result = MYSQL_QUERY( 'SELECT @@FOREIGN_KEY_CHECKS' , $DB );
  IF ( 0 < MYSQL_NUM_ROWS( $result ) ) $RESULTS[] = 'FOREIGN_KEY_CHECKS = ' . MYSQL_RESULT( $result , 0 );
  ELSE $RESULTS[] = 'Warning: Attempt to enable FOREIGN_KEY_CHECKS failed.';
}

FUNCTION _enable_trigger_checks( $DB ) {
  GLOBAL $ACTIONS , $RESULTS;
  $ACTIONS[] = 'Enabling Triggers...';
  MYSQL_QUERY( 'SET @TRIGGER_CHECKS = 1;' , $DB );
  $result = MYSQL_QUERY( 'SELECT @TRIGGER_CHECKS' , $DB );
  IF ( 0 < MYSQL_NUM_ROWS( $result ) ) $RESULTS[] = 'TRIGGER_CHECKS = ' . MYSQL_RESULT( $result , 0 );
  ELSE $RESULTS[] = 'Warning: Attempt to enable TRIGGER_CHECKS failed.';
}

FUNCTION _callback_map_issues( $matches ) {
  GLOBAL $MAP;
  RETURN ISSET( $MAP['issues'][ $matches[0] ] ) ? $MAP['issues'][ $matches[0] ]['id'] : $matches[0];
}

FUNCTION _migrate_temporary_database() {
  GLOBAL $ACTIONS , $RESULTS , $DB1 , $DB2 , $MAP;
  _select_temporary_database( $DB1 );
  _select_created_database( $DB2 );
  _disable_foreign_key_checks( $DB2 );
  _disable_trigger_checks( $DB2 );
  $counters = ARRAY();
  $new = ARRAY();
  $ref = ARRAY();
  $MAP = ARRAY();
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $tdbname = "'" . MYSQL_REAL_ESCAPE_STRING( $_REQUEST['tdbname'] ) . "'";
  $ACTIONS[] = 'Analyzing Temporary Database...';
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `information_schema`.`tables`
WHERE
  `table_schema` = {$tdbname};
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not access the database information schemas.';
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 1 > MYSQL_RESULT( $query_result , 0 ) ) {
    $RESULTS[] = 'Error: The temporary database does not appear to contain any tables. Nothing to migrate.';
    RETURN;
  }
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $dbname = "'" . MYSQL_REAL_ESCAPE_STRING( $_REQUEST['dbname'] ) . "'";
  $ACTIONS[] = 'Setting Migration Counters...';
  $sql = <<<EOMYSQL
--
SELECT
  `table_name`,
  `auto_increment`
FROM
  `information_schema`.`tables`
WHERE
  `table_schema` = {$dbname};
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not access the database information schemas.';
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  WHILE ( FALSE !== ( $row = MYSQL_FETCH_ROW( $query_result ) ) ) {
    $counters[ $row[0] ] = $row[1];
  }
  $sql = <<<EOMYSQL
--
SELECT
  MAX( `id` )
FROM
  `pos_transactions`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not determine the next available \'id\' from the \'pos_transactions\' table.';
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $counters['pos_transactions'] = 1 + (int) MYSQL_RESULT( $query_result , 0 );
  ELSE $counters['pos_transactions'] = 1;
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Building Table Reference Map...';
  $MAP['tables'] = ARRAY
    ( 'bugs' => ARRAY
      ( 'name' => 'bugs'
      , 'key' => 'id'
      )
    , 'bugs_notes' => ARRAY
      ( 'name' => 'bugs_notes'
      , 'key' => 'id'
      )
    , 'calendar' => ARRAY
      ( 'name' => 'calendar'
      , 'key' => 'id'
      )
    , 'calendar_views' => ARRAY
      ( 'name' => 'calendar_views'
      , 'key' => 'id'
      )
    , 'categories' => ARRAY
      ( 'name' => 'categories'
      , 'key' => 'id'
      )
    , 'recent_changes' => ARRAY
      ( 'name' => 'changes'
      , 'key' => 'id'
      )
    , 'accounts' => ARRAY
      ( 'name' => 'customer_accounts'
      , 'key' => 'id'
      )
    , 'customer_dupes' => ARRAY
      ( 'name' => 'customer_dupes'
      , 'key' => 'id'
      )
    , 'customer_import' => ARRAY
      ( 'name' => 'customer_import'
      , 'key' => 'id'
      )
    , 'customer_sessions' => ARRAY
      ( 'name' => 'customer_sessions'
      , 'key' => 'id'
      )
    , 'customers' => ARRAY
      ( 'name' => 'customers'
      , 'key' => 'id'
      )
    , 'feedback' => ARRAY
      ( 'name' => 'feedback'
      , 'key' => 'id'
      )
    , 'feedback_questions' => ARRAY
      ( 'name' => 'feedback_questions'
      , 'key' => 'id'
      )
    , 'inventory' => ARRAY
      ( 'name' => 'inventory'
      , 'key' => 'id'
      )
    , 'inventory_changes' => ARRAY
      ( 'name' => 'inventory_changes'
      , 'key' => 'id'
      )
    , 'inventory_items' => ARRAY
      ( 'name' => 'inventory_items'
      , 'key' => 'id'
      )
    , 'customer_inv' => ARRAY
      ( 'name' => 'inventory_items_customer'
      , 'key' => 'id'
      )
    , 'inventory_requests' => ARRAY
      ( 'name' => 'inventory_requests'
      , 'key' => 'id'
      )
    , 'inventory_transfers' => ARRAY
      ( 'name' => 'inventory_transfers'
      , 'key' => 'id'
      )
    , 'devices' => ARRAY
      ( 'name' => 'inventory_type_devices'
      , 'key' => 'id'
      )
    , 'invoice_changes' => ARRAY
      ( 'name' => 'invoice_changes'
      , 'key' => 'change_id'
      )
    , 'invoice_items' => ARRAY
      ( 'name' => 'invoice_items'
      , 'key' => 'id'
      )
    , 'invoices' => ARRAY
      ( 'name' => 'invoices'
      , 'key' => 'id'
      )
    , 'issue_changes' => ARRAY
      ( 'name' => 'issue_changes'
      , 'key' => 'id'
      )
    , 'issue_inv' => ARRAY
      ( 'name' => 'issue_inv'
      , 'key' => 'id'
      )
    , 'issue_items' => ARRAY
      ( 'name' => 'issue_items'
      , 'key' => 'id'
      )
    , 'labor' => ARRAY
      ( 'name' => 'issue_labor'
      , 'key' => 'id'
      )
    , 'issues' => ARRAY
      ( 'name' => 'issues'
      , 'key' => 'id'
      )
    , 'messages' => ARRAY
      ( 'name' => 'messages'
      , 'key' => 'id'
      )
    , 'modules' => ARRAY
      ( 'name' => 'modules'
      , 'key' => 'id'
      )
    , 'newsletters' => ARRAY
      ( 'name' => 'newsletters'
      , 'key' => 'id'
      )
    , 'optionvalues' => ARRAY
      ( 'name' => 'option_values'
      , 'key' => 'id'
      )
    , 'order_items' => ARRAY
      ( 'name' => 'order_items'
      , 'key' => 'id'
      )
    , 'orders' => ARRAY
      ( 'name' => 'orders'
      , 'key' => 'id'
      )
    , 'locations' => ARRAY
      ( 'name' => 'org_entities'
      , 'key' => 'id'
      )
    , 'punchcards' => ARRAY
      ( 'name' => 'payroll_timecards'
      , 'key' => 'id'
      )
    , 'cart' => ARRAY
      ( 'name' => 'pos_cart_items'
      , 'key' => 'id'
      )
    , 'cash_log' => ARRAY
      ( 'name' => 'pos_cash_log'
      , 'key' => 'id'
      )
    , 'deposits' => ARRAY
      ( 'name' => 'pos_deposits'
      , 'key' => 'id'
      )
    , 'payments' => ARRAY
      ( 'name' => 'pos_payments'
      , 'key' => 'id'
      )
    , 'transactions' => ARRAY
      ( 'name' => 'pos_transactions'
      , 'key' => 'line_number'
      )
    , 'recurring_tasks' => ARRAY
      ( 'name' => 'recurring_tasks'
      , 'key' => 'task_id'
      )
    , 'reports_config' => ARRAY
      ( 'name' => 'reports_config'
      , 'key' => 'id'
      )
    , 'service_steps' => ARRAY
      ( 'name' => 'service_steps'
      , 'key' => 'id'
      )
    , 'services' => ARRAY
      ( 'name' => 'services'
      , 'key' => 'id'
      )
    , 'sessions' => ARRAY
      ( 'name' => 'sessions'
      , 'key' => 'id'
      )
    , 'tasks' => ARRAY
      ( 'name' => 'tasks'
      , 'key' => 'id'
      )
    , 'completed_tasks' => ARRAY
      ( 'name' => 'tasks_completed'
      , 'key' => 'id'
      )
    , 'timesheets' => ARRAY
      ( 'name' => 'timesheets'
      , 'key' => 'event_id'
      )
    , 'notes' => ARRAY
      ( 'name' => 'user_notes'
      , 'key' => 'id'
      )
    , 'user_rpt_submissions' => ARRAY
      ( 'name' => 'user_rpt_submissions'
      , 'key' => 'submission_id'
      )
    , 'user_rpt_templates' => ARRAY
      ( 'name' => 'user_rpt_templates'
      , 'key' => 'template_id'
      )
    , 'settings' => ARRAY
      ( 'name' => 'user_settings'
      , 'key' => 'id'
      )
    , 'users' => ARRAY
      ( 'name' => 'users'
      , 'key' => 'id'
      )
    );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Getting Current Store Configuration...';
  $ref['config'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT DISTINCT
  `setting`,
  `value`
FROM
  `config`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  WHILE ( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
    SWITCH ( $row['setting'] ) {
      CASE 'store_phone':
        $ref['config']['store_phone'] = $row['value'];
        BREAK;
      CASE 'tax_rate':
        $ref['config']['tax_rate'] = $row['value'];
        BREAK;
      CASE 'inv_pass':
        $ref['config']['inv_pass'] = $row['value'];
        BREAK;
      CASE 'magicno_cust_store':
        $ref['config']['magicno_cust_store'] = $row['value'];
        BREAK;
      CASE 'magicno_loc_cust':
        $ref['config']['magicno_loc_cust'] = $row['value'];
        BREAK;
    }
  }
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Building Entity Reference Map...';
  $MAP['org_entity_types'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  `id`
FROM
  `org_entity_types`
WHERE
  `title` = 'Store'
LIMIT 1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 1 > MYSQL_NUM_ROWS( $query_result ) ) {
    $RESULTS[] = 'Error: Could not determine the ID of the \'Store\' entity type: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $MAP['org_entity_types']['Store']['id'] = MYSQL_RESULT( $query_result , 0 );
  $ref['org_entities'] = ARRAY();
  $MAP['org_entities'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `org_entities`
WHERE
  `org_entity_types__id` = {$MAP['org_entity_types']['Store']['id']};
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      $ref['org_entities'][$row['location_code']]['id'] = $row['id'];
      $MAP['org_entities'][$row['id']]['id'] = $row['id'];
    }
  }
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Updating Store Information...';
  $ref['locations'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  `store_number`,
  `name`,
  `address`,
  `city`,
  `state`,
  `zip`,
  `is_here`
FROM
  `locations`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 1 > MYSQL_NUM_ROWS( $query_result ) ) {
    $RESULTS[] = 'Error: Could not retrieve a list of Store Location references:' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $current_index = 0;
  WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
    IF ( 0 < $row['is_here'] ) $ref['locations']['is_here'] = $row['store_number'];
    IF ( ISSET( $ref['org_entities'][$row['store_number']]) ) {
      $MAP['org_entities'][$row['store_number']]['id'] = $ref['org_entities'][$row['store_number']]['id'];
    }
    ELSE {
      $current_id = $counters['org_entities'] + $current_index;
      $new['org_entities'][$current_index] = ARRAY
        ( 'id' => $current_id
        , 'title' => $row['name']
        , 'location_code' => $row['store_number']
        , 'address' => $row['address']
        , 'city' => $row['city']
        , 'state' => $row['state']
        , 'postcode' => $row['zip']
        , 'phone' =>
          (
            ( ISSET( $ref['config']['store_phone'] ) AND ( 0 < $row['is_here'] ) )
            ? $ref['config']['store_phone']
            : NULL
          )
        , 'tax_rate' =>
          (
            ( ISSET( $ref['config']['tax_rate'] ) AND ( 0 < $row['is_here'] ) )
            ? $ref['config']['tax_rate']
            : NULL
          )
        , 'org_entity_types__id' =>
          (
            ( ISSET( $MAP['org_entity_types']['Store'] ) )
            ? $MAP['org_entity_types']['Store']['id']
            : NULL
          )
        , 'old_id' => $row['store_number']
        );
      $MAP['org_entities'][$row['store_number']]['id'] = $current_id;
      $current_index++;
    }
  }
  IF ( ISSET( $new['org_entities'] ) ) {
    $rows = ARRAY();
    FOREACH ( $new['org_entities'] AS $row ) {
      $values = ARRAY();
      FOREACH ( $row AS $key => $value ) {
        SWITCH ( GETTYPE( $value ) ) {
          CASE 'boolean':
            $values[$key] = (int) $value;
            BREAK;
          CASE 'integer':
          CASE 'double':
            $values[$key] = $value;
            BREAK;
          CASE 'string':
            $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
            BREAK;
          CASE 'NULL':
          DEFAULT:
            $values[$key] = 'NULL';
            BREAK;
        }
      }
      $rows[] = '(' . $values['id']
              . ',' . $values['title']
              . ',' . $values['location_code']
              . ',' . $values['address']
              . ',' . $values['city']
              . ',' . $values['state']
              . ',' . $values['postcode']
              . ',' . $values['phone']
              . ',' . $values['tax_rate']
              . ',' . $values['org_entity_types__id']
              . ')';
    }
    $rows = IMPLODE( ',' , $rows );
    $sql = <<<EOMYSQL
--
INSERT INTO `org_entities`
(`id`,`title`,`location_code`,`address`,`city`,`state`,`postcode`,`phone`,`tax_rate`,`org_entity_types__id`)
VALUES
{$rows};
--
EOMYSQL;
    MYSQL_QUERY( $sql , $DB2 );
    IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $counters['org_entities'] += $affected;
  }
  IF ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) ) {
    IF ( ( ! ISSET( $new['org_entities'] ) ) OR ( ! IN_ARRAY( $MAP['org_entities'][ $ref['locations']['is_here'] ]['id'] , $new['org_entities'] ) ) ) {
      $id = $MAP['org_entities'][$ref['locations']['is_here']]['id'];
      IF ( ISSET ( $ref['config']['store_phone'] ) ) {
        $phone = "'" . MYSQL_REAL_ESCAPE_STRING( $ref['config']['store_phone'] ) . "'";
        $sql = <<<EOMYSQL
--
UPDATE
  `org_entities`
SET
  `phone` = {$phone}
WHERE
  `id` = {$id}
--
EOMYSQL;
      }
      IF ( ISSET ( $ref['config']['tax_rate'] ) ) {
        $tax_rate = "'" . MYSQL_REAL_ESCAPE_STRING( $ref['config']['tax_rate'] ) . "'";
        $sql = <<<EOMYSQL
--
UPDATE
  `org_entities`
SET
  `tax_rate` = {$tax_rate}
WHERE
  `id` = {$id}
--
EOMYSQL;
      }
    }
  }
  UNSET( $new['org_entities'] );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Updating Organization Entity Cross-references...';
  $new['xref__org_entities__org_structs'][0] = ARRAY
    ( 'id' => $counters['xref__org_entities__org_structs']
    , 'org_entities__id' =>
      (
        ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
        ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
        : NULL
      )
    , 'org_structs__id' => NULL
    );
  $sql = <<<EOMYSQL
--
SELECT
  os.`id`
FROM
  `org_structs` os,
  `org_struct_groups` osg,
  `org_struct_types` ost
WHERE
  os.`title` = 'Organization'
  AND os.`org_struct_groups__id` = osg.`id`
  AND osg.`org_struct_types__id` = ost.`id`
  AND ost.`title` = 'Administrative'
LIMIT 1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 1 > MYSQL_NUM_ROWS( $query_result ) ) {
    $RESULTS[] = 'Error: Could not determine the ID of the \'Administrative\' organizational structure \'Organization\' : ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $new['xref__org_entities__org_structs'][0]['org_structs__id'] = MYSQL_RESULT( $query_result , 0 );
  $values = ARRAY();
  FOREACH ( $new['xref__org_entities__org_structs'][0] AS $key => $value ) {
    SWITCH ( GETTYPE( $value ) ) {
      CASE 'boolean':
        $values[$key] = (int) $value;
        BREAK;
      CASE 'integer':
      CASE 'double':
        $values[$key] = $value;
        BREAK;
      CASE 'string':
        $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
        BREAK;
      CASE 'NULL':
      DEFAULT:
        $values[$key] = 'NULL';
        BREAK;
    }
  }
  $sql = <<<EOMYSQL
--
INSERT INTO `xref__org_entities__org_structs`
(`org_entities__id`,`org_structs__id`)
VALUES
( {$values['org_entities__id']}
, {$values['org_structs__id']}
);
--
EOMYSQL;
  MYSQL_QUERY( $sql , $DB2 );
  IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $counters['xref__org_entities__org_structs'] += $affected;
  UNSET( $new['xref__org_entities__org_structs'] );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Merging Categories...';
  $ref['categories'] = ARRAY();
  $MAP['categories'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `categories`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      $ref['categories'][$row['category_set']][$row['category_name']] = ARRAY
        ( 'parent_id' => $row['parent_id']
        , 'id' => $row['id']
        );
      $MAP['categories'][$row['id']] = ARRAY
        ( 'id' => $row['id']
        , 'parent_id' => $row['parent_id']
        );
    }
  }
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `categories`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    $current_index = 0;
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      IF ( ISSET( $ref['categories'][$row['category_set']][$row['cat_name']] ) ) {
        IF ( 1 > $row['parent'] ) $row['parent'] = NULL;
        $MAP['categories'][$row['category_id']] = ARRAY
          ( 'id' => $ref['categories'][$row['category_set']][$row['cat_name']]['id']
          , 'parent_id' => $ref['categories'][$row['category_set']][$row['cat_name']]['parent_id']
          );
      }
      ELSE {
        $current_id = $counters['categories'] + $current_index;
        $new['categories'][$current_index] = ARRAY
          ( 'id' => $current_id
          , 'category_set' => $row['category_set']
          , 'category_name' => $row['cat_name']
          , 'parent_id' => NULL
          , 'old_id' => $row['category_id']
          , 'old_parent_id' => $row['parent']
          );
        $MAP['categories'][$row['category_id']] = ARRAY
          ( 'id' => $current_id
          , 'parent_id' => NULL
          );
        $current_index++;
      }
    }
    IF ( ISSET( $new['categories'] ) ) {
      FOREACH ( ARRAY_KEYS( $new['categories'] ) AS $id ) {
        IF ( NULL !== $new['categories'][$id]['parent_id'] ) CONTINUE;
        IF ( IN_ARRAY( $new['categories'][$id]['old_parent_id'] , $MAP['categories'] ) ) {
          $new['categories'][$id]['parent_id'] = $MAP['categories'][$new['categories'][$id]['old_parent_id']]['id'];
          $MAP['categories'][$new['categories'][$id]['old_id']]['parent_id'] = $new['categories'][$id]['parent_id'];
        }
      }
      $rows = ARRAY();
      FOREACH ( $new['categories'] AS $row ) {
        $values = ARRAY();
        FOREACH ( $row AS $key => $value ) {
          SWITCH ( GETTYPE( $value ) ) {
            CASE 'boolean':
              $values[$key] = (int) $value;
              BREAK;
            CASE 'integer':
            CASE 'double':
              $values[$key] = $value;
              BREAK;
            CASE 'string':
              $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
              BREAK;
            CASE 'NULL':
            DEFAULT:
              $values[$key] = 'NULL';
              BREAK;
          }
        }
        $rows[] = '(' . $values['id']
                . ',' . $values['category_set']
                . ',' . $values['category_name']
                . ')';
      }
      $rows = IMPLODE( ',' , $rows );
      $sql = <<<EOMYSQL
--
INSERT INTO `categories`
(`id`,`category_set`,`category_name`)
VALUES
{$rows};
--
EOMYSQL;
      MYSQL_QUERY( $sql , $DB2 );
      IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
        $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
        _clean_up();
        _output_error_default_html();
        EXIT;
      }
      $counters['categories'] += $affected;
      FOREACH ( $new['categories'] AS $row ) {
        $values = ARRAY();
        FOREACH ( $row AS $key => $value ) {
          SWITCH ( GETTYPE( $value ) ) {
            CASE 'boolean':
              $values[$key] = (int) $value;
              BREAK;
            CASE 'integer':
            CASE 'double':
              $values[$key] = $value;
              BREAK;
            CASE 'string':
              $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
              BREAK;
            CASE 'NULL':
            DEFAULT:
              $values[$key] = 'NULL';
              BREAK;
          }
        }
        $sql = <<<EOMYSQL
--
UPDATE
  `categories`
SET
  `parent_id` = {$values['parent_id']}
WHERE
  `id` = {$values['id']};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
      }
    }
  }
  UNSET( $new['categories'] );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Filtering and Merging Option Values...';
  $ref['option_values'] = ARRAY();
  $MAP['option_values'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `option_values`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      $ref['option_values'][$row['category']][$row['value']]['id'] = $row['id'];
      $MAP['option_values'][$row['id']]['id'] = $row['id'];
    }
  }
  $sql = <<<EOMYSQL
--
SELECT
  `option_id`,
  `category`,
  `value`
FROM
  `optionvalues`
WHERE
  `category` NOT IN ('user_type','locations');
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    $current_index = 0;
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      IF ( ISSET( $ref['option_values'][$row['category']][$row['value']] ) ) {
        $MAP['option_values'][$row['option_id']]['id'] = $ref['option_values'][$row['category']][$row['value']]['id'];
      }
      ELSE {
        $current_id = $counters['option_values'] + $current_index;
        $new['option_values'][$current_index] = ARRAY
          ( 'id' => $current_id
          , 'category' => $row['category']
          , 'value' => $row['value']
          , 'old_id' => $row['option_id']
          );
        $MAP['option_values'][$row['option_id']]['id'] = $current_id;
        $current_index++;
      }
    }
    IF ( ISSET( $new['option_values'] ) ) {
      $rows = ARRAY();
      FOREACH ( $new['option_values'] AS $row ) {
        $values = ARRAY();
        FOREACH ( $row AS $key => $value ) {
          SWITCH ( GETTYPE( $value ) ) {
            CASE 'boolean':
              $values[$key] = (int) $value;
              BREAK;
            CASE 'integer':
            CASE 'double':
              $values[$key] = $value;
              BREAK;
            CASE 'string':
              $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
              BREAK;
            CASE 'NULL':
            DEFAULT:
              $values[$key] = 'NULL';
              BREAK;
          }
        }
        $rows[] = '(' . $values['id']
                . ',' . $values['category']
                . ',' . $values['value']
                . ')';
      }
      $rows = IMPLODE( ',' , $rows );
      $sql = <<<EOMYSQL
--
INSERT INTO `option_values`
(`id`,`category`,`value`)
VALUES
{$rows};
--
EOMYSQL;
      MYSQL_QUERY( $sql , $DB2 );
      IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
        $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
        _clean_up();
        _output_error_default_html();
        EXIT;
      }
      $counters['option_values'] += $affected;
    }
  }
  UNSET( $new['option_values'] );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Extracting and Merging Inventory Locations...';
  $ref['inventory_locations'] = ARRAY();
  $MAP['inventory_locations'] = ARRAY();
  $new['xref__org_entities__inventory_locations'][0] = ARRAY
    ( 'id' => $counters['xref__org_entities__inventory_locations']
    , 'org_entities__id' =>
      (
        ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
        ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
        : NULL
      )
    , 'inventory_locations__id' => NULL
    , 'is_default' => 0
    );
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `inventory_locations`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      $ref['inventory_locations'][$row['title']]['id'] = $row['id'];
      $MAP['inventory_locations'][$row['id']]['id'] = $row['id'];
    }
  }
  $sql = <<<EOMYSQL
--
SELECT
  `option_id`,
  `value`
FROM
  `optionvalues`
WHERE
  `category` = 'locations';
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    $current_index = 0;
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      IF ( ISSET( $ref['inventory_locations'][$row['value']] ) ) {
        $MAP['inventory_locations'][$row['option_id']]['id'] = $ref['inventory_locations'][$row['value']]['id'];
      }
      ELSE {
        $current_id = $counters['inventory_locations'] + $current_index;
        $new['inventory_locations'][$current_index] = ARRAY
          ( 'id' => $current_id
          , 'title' => $row['value']
          );
        $MAP['inventory_locations'][$row['option_id']]['id'] = $current_id;
        $current_index++;
      }
    }
    IF ( ISSET( $new['inventory_locations'] ) ) {
      $values = ARRAY();
      FOREACH ( $new['inventory_locations'] AS $row ) {
        $values[] = '(' . $row['id']
                  . ',' . "'" . MYSQL_REAL_ESCAPE_STRING( $row['title'] ) . "'"
                  . ')';
      }
      $values = IMPLODE( ',' , $values );
      $sql = <<<EOMYSQL
--
INSERT INTO `inventory_locations`
(`id`,`title`)
VALUES
{$values};
--
EOMYSQL;
      MYSQL_QUERY( $sql , $DB2 );
      IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
        $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
        _clean_up();
        _output_error_default_html();
        EXIT;
      }
      $counters['inventory_locations'] += $affected;
    }
    IF ( ISSET( $ref['config']['magicno_loc_cust'] ) ) {
      IF ( ISSET( $MAP['inventory_locations'][$ref['config']['magicno_loc_cust']] ) ) {
        $new['xref__org_entities__inventory_locations'][0]['inventory_locations__id'] = $MAP['inventory_locations'][$ref['config']['magicno_loc_cust']]['id'];
        $new['xref__org_entities__inventory_locations'][0]['is_default'] = 1;
      }
    }
    $values = ARRAY();
    FOREACH ( $new['xref__org_entities__inventory_locations'][0] AS $key => $value ) {
      SWITCH ( GETTYPE( $value ) ) {
        CASE 'boolean':
          $values[$key] = (int) $value;
          BREAK;
        CASE 'integer':
        CASE 'double':
          $values[$key] = $value;
          BREAK;
        CASE 'string':
          $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
          BREAK;
        CASE 'NULL':
        DEFAULT:
          $values[$key] = 'NULL';
          BREAK;
      }
    }
    $sql = <<<EOMYSQL
--
INSERT INTO `xref__org_entities__inventory_locations`
(`org_entities__id`,`inventory_locations__id`,`is_default`)
VALUES
( {$values['org_entities__id']}
, {$values['inventory_locations__id']}
, {$values['is_default']}
);
--
EOMYSQL;
    MYSQL_QUERY( $sql , $DB2 );
    IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $counters['xref__org_entities__inventory_locations'] += $affected;
  }
  UNSET( $new['xref__org_entities__inventory_locations'] );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Extracting and Merging Inventory Passwords...';
  $new['inventory_passwords'][0] = ARRAY
    ( 'id' => $counters['inventory_passwords']
    , 'org_entities__id' =>
      (
        ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
        ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
        : NULL
      )
    , 'password' => NULL
    );
  IF ( ISSET( $ref['config']['inv_pass'] ) ) {
    $new['inventory_passwords'][0]['password'] = $ref['config']['inv_pass'];
  }
  $values = ARRAY();
  FOREACH ( $new['inventory_passwords'][0] AS $key => $value ) {
    SWITCH ( GETTYPE( $value ) ) {
      CASE 'boolean':
        $values[$key] = (int) $value;
        BREAK;
      CASE 'integer':
      CASE 'double':
        $values[$key] = $value;
        BREAK;
      CASE 'string':
        $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
        BREAK;
      CASE 'NULL':
      DEFAULT:
        $values[$key] = 'NULL';
        BREAK;
    }
  }
  $sql = <<<EOMYSQL
--
INSERT INTO `inventory_passwords`
(`org_entities__id`,`password`)
VALUES
( {$values['org_entities__id']}
, {$values['password']}
);
--
EOMYSQL;
  MYSQL_QUERY( $sql , $DB2 );
  IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $counters['inventory_passwords'] += $affected;
  UNSET( $new['inventory_passwords'] );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Users...';
  $ref['modules'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  `id`,
  `module`
FROM
  `modules`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 1 > MYSQL_NUM_ROWS( $query_result ) ) {
    $RESULTS[] = 'Error: Could not get a list of installed modules to assign user permissions: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  WHILE ( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
    $ref['modules'][$row['module']]['id'] = $row['id'];
  }
  $ref['perms'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  `bitmask`
FROM
  `perms`
WHERE
  `module` = 'admin'
  AND `action` = 'use'
LIMIT 1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 1 > MYSQL_NUM_ROWS( $query_result ) ) {
    $RESULTS[] = 'Error: Could not determine the bitmask associated with the permission to \'use\' the \'admin\' module: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $ref['perms']['admin']['use']['bitmask'] = MYSQL_RESULT( $query_result , 0 );
  $MAP['user_roles'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  `id`,
  `title`
FROM
  `user_roles`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 1 > MYSQL_NUM_ROWS( $query_result ) ) {
    $RESULTS[] = 'Error: Could not determine the IDs associated with User Roles: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  WHILE ( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
    SWITCH ( $row['title'] ) {
      CASE 'Regular Employee':
        $MAP['user_roles'][1]['id'] = $row['id'];
        BREAK;
      CASE 'Store Manager':
        $MAP['user_roles'][2]['id'] = $row['id'];
        BREAK;
      CASE 'Organization Administrator':
        $MAP['user_roles'][3]['id'] = $row['id'];
        BREAK;
      CASE 'Intern':
        $MAP['user_roles'][1224]['id'] = $row['id'];
        BREAK;
    }
  }
  $MAP['users'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `users`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `users`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['users'] + $current_index;
        $new['users'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'user_roles__id' =>
              (
                ( ISSET( $MAP['user_roles'][$row['user_type']] ) )
                ? $MAP['user_roles'][$row['user_type']]['id']
                : NULL
              )
            , 'username' => $row['username']
            , 'password' => $row['password']
            , 'salt' => $row['salt']
            , 'firstname' => $row['firstname']
            , 'lastname' => $row['lastname']
            , 'phone' => $row['phone']
            , 'email' => $row['email']
            , 'is_onsite' => $row['onsite']
            , 'hourlyrate' => $row['hourlyrate']
            , 'notepad' => $row['notepad']
            , 'timeout' => $row['timeout']
            , 'is_disabled' => $row['disabled']
            , 'rc_read' => $row['rc_read']
            , 'old_isadmin' => $row['isadmin']
            );
        $MAP['users'][$row['user_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['users'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['users'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['org_entities__id']
                  . ',' . $values['user_roles__id']
                  . ',' . $values['username']
                  . ',' . $values['password']
                  . ',' . $values['salt']
                  . ',' . $values['firstname']
                  . ',' . $values['lastname']
                  . ',' . $values['phone']
                  . ',' . $values['email']
                  . ',' . $values['is_onsite']
                  . ',' . $values['hourlyrate']
                  . ',' . $values['notepad']
                  . ',' . $values['timeout']
                  . ',' . $values['is_disabled']
                  . ',' . $values['rc_read']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `users`
(`id`,`org_entities__id`,`user_roles__id`,`username`,`password`,`salt`,`firstname`,`lastname`,`phone`,`email`,`is_onsite`,`hourlyrate`,`notepad`,`timeout`,`is_disabled`,`rc_read`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['users'] += $affected;
        $RESULTS[] = 'Done.';
        $ACTIONS[] = 'Assigning Permissions...';
        $rows = ARRAY();
        FOREACH ( $new['users'] AS $user ) {
          FOREACH ( ARRAY_KEYS( $ref['modules'] ) AS $module ) {
            $rows[] = '(' . $user['id']
                    . ',' . "'" . MYSQL_REAL_ESCAPE_STRING( $module ) . "'"
                    . ')';
          }
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `user_perms`
(`users__id`,`module`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['user_perms'] += $affected;
        FOREACH ( $new['users'] AS $user ) {
          IF ( 1 != $user['old_isadmin'] ) CONTINUE;
          $sql = <<<EOMYSQL
--
UPDATE
  `user_perms`
SET
  `bitfield_y` = `bitfield_y` | {$ref['perms']['admin']['use']['bitmask']}
WHERE
  `users__id` = {$user['id']}
  AND `module` = 'admin';
--
EOMYSQL;
          MYSQL_QUERY( $sql , $DB2 );
          IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
            $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
            _clean_up();
            _output_error_default_html();
            EXIT;
          }
        }
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['users'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Customers...';
  $MAP['customers'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `customers`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `customers`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        IF ( ISSET( $ref['config']['magicno_cust_store'] ) AND ( $row['customer_id'] === $ref['config']['magicno_cust_store'] ) ) {
          $MAP['customers'][$row['customer_id']]['id'] = 1;
        }
        ELSE {
          $current_id = $counters['customers'] + $current_index;
          $new['customers'][$current_index] = ARRAY
              ( 'id' => $current_id
              , 'firstname' => $row['firstname']
              , 'lastname' => $row['lastname']
              , 'is_male' => $row['is_male']
              , 'dob' => $row['dob']
              , 'company' => $row['company']
              , 'address' => $row['address']
              , 'apt' => $row['apt']
              , 'city' => $row['city']
              , 'state' => $row['state']
              , 'country' => NULL
              , 'postcode' => $row['zip']
              , 'email' => $row['email']
              , 'phone_home' => $row['phone_home']
              , 'phone_cell' => $row['phone_cell']
              , 'referral' => $row['referral']
              , 'is_subscribed' => $row['subscribed']
              , 'v_address' => $row['v_address']
              , 'user_pass' => $row['user_pass']
              , 'user_salt' => $row['user_salt']
              , 'email_add_date' => $row['email_add_date']
              , 'email_added_by' =>
                (
                  ( ISSET( $MAP['users'][$row['email_added_by']] ) )
                  ? $MAP['users'][$row['email_added_by']]['id']
                  : NULL
                )
              , 'old_id' => $row['customer_id']
              );
          $MAP['customers'][$row['customer_id']]['id'] = $current_id;
          $current_index++;
        }
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['customers'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['customers'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['firstname']
                  . ',' . $values['lastname']
                  . ',' . $values['is_male']
                  . ',' . $values['dob']
                  . ',' . $values['company']
                  . ',' . $values['address']
                  . ',' . $values['apt']
                  . ',' . $values['city']
                  . ',' . $values['state']
                  . ',' . $values['country']
                  . ',' . $values['postcode']
                  . ',' . $values['email']
                  . ',' . $values['phone_home']
                  . ',' . $values['phone_cell']
                  . ',' . $values['referral']
                  . ',' . $values['is_subscribed']
                  . ',' . $values['v_address']
                  . ',' . $values['user_pass']
                  . ',' . $values['user_salt']
                  . ',' . $values['email_add_date']
                  . ',' . $values['email_added_by']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `customers`
(`id`,`firstname`,`lastname`,`is_male`,`dob`,`company`,`address`,`apt`,`city`,`state`,`country`,`postcode`,`email`,`phone_home`,`phone_cell`,`referral`,`is_subscribed`,`v_address`,`user_pass`,`user_salt`,`email_add_date`,`email_added_by`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['customers'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['customers'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Imported Customer Table...';
  $MAP['customer_import'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `customer_import`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `customer_import`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE ( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['customer_import'] + $current_index;
        $new['customer_import'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'firstname' => $row['firstname']
            , 'lastname' => $row['lastname']
            , 'is_male' => $row['is_male']
            , 'dob' => $row['dob']
            , 'company' => $row['company']
            , 'address' => $row['address']
            , 'apt' => ''
            , 'city' => $row['city']
            , 'state' => $row['state']
            , 'country' => NULL
            , 'postcode' => $row['zip']
            , 'email' => $row['email']
            , 'phone_home' => $row['phone_home']
            , 'phone_cell' => $row['phone_cell']
            , 'referral' => $row['referral']
            , 'old_id' => $row['import_id']
            );
        $MAP['customer_import'][$row['import_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['customer_import'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['customer_import'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['firstname']
                  . ',' . $values['lastname']
                  . ',' . $values['is_male']
                  . ',' . $values['dob']
                  . ',' . $values['company']
                  . ',' . $values['address']
                  . ',' . $values['apt']
                  . ',' . $values['city']
                  . ',' . $values['state']
                  . ',' . $values['country']
                  . ',' . $values['postcode']
                  . ',' . $values['email']
                  . ',' . $values['phone_home']
                  . ',' . $values['phone_cell']
                  . ',' . $values['referral']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `customer_import`
(`id`,`firstname`,`lastname`,`is_male`,`dob`,`company`,`address`,`apt`,`city`,`state`,`country`,`postcode`,`email`,`phone_home`,`phone_cell`,`referral`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['customer_import'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['customer_import'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Customer Duplicates Table...';
  $MAP['customer_dupes'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `customer_dupes`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `customer_dupes`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['customer_dupes'] + $current_index;
        $new['customer_dupes'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'firstname' => $row['firstname']
            , 'lastname' => $row['lastname']
            , 'is_male' => $row['is_male']
            , 'dob' => $row['dob']
            , 'company' => $row['company']
            , 'address' => $row['address']
            , 'apt' => ''
            , 'city' => $row['city']
            , 'state' => $row['state']
            , 'country' => NULL
            , 'postcode' => $row['zip']
            , 'email' => $row['email']
            , 'phone_home' => $row['phone_home']
            , 'phone_cell' => $row['phone_cell']
            , 'referral' => $row['referral']
            , 'is_subscribed' => $row['subscribed']
            , 'v_address' => $row['v_address']
            , 'old_id' => $row['customer_id']
            );
        $MAP['customer_dupes'][$row['customer_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['customer_dupes'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['customer_dupes'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['firstname']
                  . ',' . $values['lastname']
                  . ',' . $values['is_male']
                  . ',' . $values['dob']
                  . ',' . $values['company']
                  . ',' . $values['address']
                  . ',' . $values['apt']
                  . ',' . $values['city']
                  . ',' . $values['state']
                  . ',' . $values['country']
                  . ',' . $values['postcode']
                  . ',' . $values['email']
                  . ',' . $values['phone_home']
                  . ',' . $values['phone_cell']
                  . ',' . $values['referral']
                  . ',' . $values['is_subscribed']
                  . ',' . $values['v_address']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `customer_dupes`
(`id`,`firstname`,`lastname`,`is_male`,`dob`,`company`,`address`,`apt`,`city`,`state`,`country`,`postcode`,`email`,`phone_home`,`phone_cell`,`referral`,`is_subscribed`,`v_address`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['customer_dupes'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['customer_dupes'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Customer Accounts...';
  $MAP['customer_accounts'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `accounts`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `accounts`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['customer_accounts'] + $current_index;
        $new['customer_accounts'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'customers__id' =>
              (
                ( ISSET( $ref['config']['magicno_cust_store'] ) AND ( $row['customer_id'] === $ref['config']['magicno_cust_store'] ) )
                ? 1
                : (
                    ( ISSET( $MAP['customers'][$row['customer_id']] ) )
                    ? $MAP['customers'][$row['customer_id']]['id']
                    : NULL
                  )
              )
            , 'created' => $row['created']
            , 'block_hours' => $row['block_hours']
            , 'block_rate' => $row['block_rate']
            , 'overage_rate' => $row['overage_rate']
            , 'period' => $row['period']
            , 'amount' => $row['amount']
            , 'is_disabled' => $row['is_disabled']
            , 'old_id' => $row['account_id']
            );
        $MAP['customer_accounts'][$row['account_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['customer_accounts'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['customer_accounts'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['customers__id']
                  . ',' . $values['created']
                  . ',' . $values['block_hours']
                  . ',' . $values['block_rate']
                  . ',' . $values['overage_rate']
                  . ',' . $values['period']
                  . ',' . $values['amount']
                  . ',' . $values['is_disabled']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `customer_accounts`
(`id`,`customers__id`,`created`,`block_hours`,`block_rate`,`overage_rate`,`period`,`amount`,`is_disabled`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['customer_accounts'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['customer_accounts'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Tasks...';
  $MAP['tasks'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `tasks`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `tasks`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['tasks'] + $current_index;
        $new['tasks'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id__assigned_to' =>
              (
                ( ISSET( $MAP['users'][$row['assigned_to']] ) )
                ? $MAP['users'][$row['assigned_to']]['id']
                : NULL
              )
            , 'users__id__assigned_by' =>
              (
                ( ISSET( $MAP['users'][$row['assigned_by']] ) )
                ? $MAP['users'][$row['assigned_by']]['id']
                : NULL
              )
            , 'task' => $row['task']
            , 'due' => $row['due']
            , 'is_completed' => $row['completed']
            , 'toc' => $row['toc']
            , 'points' => $row['points']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['task_id']
            );
        $MAP['tasks'][$row['task_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['tasks'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['tasks'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id__assigned_to']
                  . ',' . $values['users__id__assigned_by']
                  . ',' . $values['task']
                  . ',' . $values['due']
                  . ',' . $values['is_completed']
                  . ',' . $values['toc']
                  . ',' . $values['points']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `tasks`
(`id`,`users__id__assigned_to`,`users__id__assigned_by`,`task`,`due`,`is_completed`,`toc`,`points`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['tasks'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['tasks'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Completed Tasks...';
  $MAP['tasks_completed'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `completed_tasks`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `completed_tasks`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['tasks_completed'] + $current_index;
        $new['tasks_completed'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'date_done' => $row['date_done']
            , 'tasks__id' =>
              (
                ( ISSET( $MAP['tasks'][$row['task_id']] ) )
                ? $MAP['tasks'][$row['task_id']]['id']
                : NULL
              )
            , 'user_ids' =>
              (
                ( ISSET( $MAP['users'] ) )
                ? (
                    IMPLODE( ',' , ARRAY_MAP( 'CURRENT' , ARRAY_INTERSECT_KEY( $MAP['users'] , ARRAY_FLIP( EXPLODE( ',' , $row['user_ids'] ) ) ) ) )
                  )
                : ''
              )
            , 'points' => $row['points']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['completion_id']
            );
        $MAP['tasks_completed'][$row['completion_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['tasks_completed'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['tasks_completed'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['date_done']
                  . ',' . $values['tasks__id']
                  . ',' . $values['user_ids']
                  . ',' . $values['points']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `tasks_completed`
(`id`,`date_done`,`tasks__id`,`user_ids`,`points`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['tasks_completed'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['tasks_completed'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Recurring Tasks...';
  $MAP['recurring_tasks'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `recurring_tasks`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `recurring_tasks`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['recurring_tasks'] + $current_index;
        $new['recurring_tasks'][$current_index] = ARRAY
            ( 'task_id' => $current_id
            , 'reset_date' => $row['reset_date']
            , 'created_date' => $row['created_date']
            , 'descr' => $row['descr']
            , 'done_by' =>
              (
                ( ISSET( $MAP['users'][$row['done_by']] ) )
                ? $MAP['users'][$row['done_by']]['id']
                : NULL
              )
            , 'points' => $row['points']
            , 'report_id' => $row['report_id']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['task_id']
            );
        $MAP['recurring_tasks'][$row['task_id']]['id'] = $current_id;
          $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['recurring_tasks'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['recurring_tasks'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['task_id']
                  . ',' . $values['reset_date']
                  . ',' . $values['created_date']
                  . ',' . $values['descr']
                  . ',' . $values['done_by']
                  . ',' . $values['points']
                  . ',' . $values['report_id']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `recurring_tasks`
(`task_id`,`reset_date`,`created_date`,`descr`,`done_by`,`points`,`report_id`,`org_entities__id` )
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['recurring_tasks'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['recurring_tasks'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Merging Services...';
  $ref['services'] = ARRAY();
  $MAP['services'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `services`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      $ref['services'][$row['name']]['id'] = $row['id'];
      $MAP['services'][$row['id']]['id'] = $row['id'];
    }
  }
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `services`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    $current_index = 0;
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      IF ( ISSET( $ref['services'][$row['name']] ) ) {
        $MAP['services'][$row['service_id']]['id'] = $ref['services'][$row['name']]['id'];
      }
      ELSE {
        $current_id = $counters['services'] + $current_index;
        $new['services'][$current_index] = ARRAY
          ( 'id' => $current_id
          , 'name' => $row['name']
          , 'cost' => $row['cost']
          , 'old_id' => $row['service_id']
          );
        $MAP['services'][$row['service_id']]['id'] = $current_id;
        $current_index++;
      }
    }
    IF ( ISSET( $new['services'] ) ) {
      $rows = ARRAY();
      FOREACH ( $new['services'] AS $row ) {
        $values = ARRAY();
        FOREACH ( $row AS $key => $value ) {
          SWITCH ( GETTYPE( $value ) ) {
            CASE 'boolean':
              $values[$key] = (int) $value;
              BREAK;
            CASE 'integer':
            CASE 'double':
              $values[$key] = $value;
              BREAK;
            CASE 'string':
              $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
              BREAK;
            CASE 'NULL':
            DEFAULT:
              $values[$key] = 'NULL';
              BREAK;
          }
        }
        $rows[] = '(' . $values['id']
                . ',' . $values['name']
                . ',' . $values['cost']
                . ')';
      }
      $rows = IMPLODE( ',' , $rows );
      $sql = <<<EOMYSQL
--
INSERT INTO `services`
(`id`,`name`,`cost`)
VALUES
{$rows};
--
EOMYSQL;
      MYSQL_QUERY( $sql , $DB2 );
      IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
        $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
        _clean_up();
        _output_error_default_html();
        EXIT;
      }
      $counters['services'] += $affected;
    }
  }
  UNSET( $new['services'] );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Merging Service Steps...';
  $ref['service_steps'] = ARRAY();
  $MAP['service_steps'] = ARRAY();
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `service_steps`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB2 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      $ref['service_steps'][$row['services__id']][$row['order']]['id'] = $row['id'];
      $MAP['service_steps'][$row['id']]['id'] = $row['id'];
    }
  }
  $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `service_steps`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
    $current_index = 0;
    WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
      IF ( ! ISSET( $MAP['services'][$row['for_service']] ) ) CONTINUE;
      $row['for_service'] = $MAP['services'][$row['for_service']]['id'];
      IF ( ISSET( $ref['service_steps'][$row['for_service']][$row['ordr']] ) ) {
        $MAP['service_steps'][$row['step_id']]['id'] = $ref['service_steps'][$row['for_service']][$row['ordr']]['id'];
      }
      ELSE {
        $current_id = $counters['service_steps'] + $current_index;
        $new['service_steps'][$current_index] = ARRAY
          ( 'id' => $current_id
          , 'services__id' =>
              (
                ( ISSET( $MAP['services'][$row['for_service']] ) )
                ? $MAP['services'][$row['for_service']]['id']
                : NULL
              )
          , 'order' => $row['ordr']
          , 'step' => $row['step']
          , 'old_id' => $row['step_id']
          );
        $MAP['service_steps'][$row['step_id']]['id'] = $current_id;
        $current_index++;
      }
    }
    IF ( ISSET( $new['service_steps'] ) ) {
      $rows = ARRAY();
      FOREACH ( $new['service_steps'] AS $row ) {
        $values = ARRAY();
        FOREACH ( $row AS $key => $value ) {
          SWITCH ( GETTYPE( $value ) ) {
            CASE 'boolean':
              $values[$key] = (int) $value;
              BREAK;
            CASE 'integer':
            CASE 'double':
              $values[$key] = $value;
              BREAK;
            CASE 'string':
              $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
              BREAK;
            CASE 'NULL':
            DEFAULT:
              $values[$key] = 'NULL';
              BREAK;
          }
        }
        $rows[] = '(' . $values['id']
                . ',' . $values['services__id']
                . ',' . $values['order']
                . ',' . $values['step']
                . ')';
      }
      $rows = IMPLODE( ',' , $rows );
      $sql = <<<EOMYSQL
--
INSERT INTO `service_steps`
(`id`,`services__id`,`order`,`step`)
VALUES
{$rows};
--
EOMYSQL;
      MYSQL_QUERY( $sql , $DB2 );
      IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
        $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
        _clean_up();
        _output_error_default_html();
        EXIT;
      }
      $counters['service_steps'] += $affected;
    }
  }
  UNSET( $new['service_steps'] );
  $RESULTS[] = 'Done.';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Inventory...';
  $MAP['inventory'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `inventory`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `inventory`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['inventory'] + $current_index;
        $new['inventory'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'upc' => $row['upc']
            , 'descr' => $row['descr']
            , 'purchase_price' => $row['purchase_price']
            , 'cost' => $row['cost']
            , 'is_taxable' => $row['taxable']
            , 'item_type_table' => 'categories'
            , 'item_type_lookup' =>
              (
                ( ISSET( $MAP['categories'][$row['device_type']] ) )
                ? $MAP['categories'][$row['device_type']]['id']
                : NULL
              )
            , 'name' => $row['name']
            , 'qty' => $row['qty']
            , 'is_qty' => $row['is_qty']
            , 'do_notify_low_qty' => $row['flag_notify_low_qty']
            , 'low_qty' => $row['low_qty']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['inventory_id']
            );
        $MAP['inventory'][$row['inventory_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['inventory'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['inventory'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['upc']
                  . ',' . $values['descr']
                  . ',' . $values['purchase_price']
                  . ',' . $values['cost']
                  . ',' . $values['is_taxable']
                  . ',' . $values['item_type_table']
                  . ',' . $values['item_type_lookup']
                  . ',' . $values['name']
                  . ',' . $values['qty']
                  . ',' . $values['is_qty']
                  . ',' . $values['do_notify_low_qty']
                  . ',' . $values['low_qty']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `inventory`
(`id`,`upc`,`descr`,`purchase_price`,`cost`,`is_taxable`,`item_type_table`,`item_type_lookup`,`name`,`qty`,`is_qty`,`do_notify_low_qty`,`low_qty`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['inventory'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['inventory'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Invoices...';
  $MAP['invoices'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `invoices`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `invoices`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['invoices'] + $current_index;
        $new['invoices'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'customers__id' =>
              (
                ( ISSET( $MAP['customers'][$row['customer_id']] ) )
                ? $MAP['customers'][$row['customer_id']]['id']
                : NULL
              )
            , 'amt' => $row['amt']
            , 'toi' => $row['toi']
            , 'amt_paid' => $row['amt_paid']
            , 'emailed' => $row['emailed']
            , 'users__id__sale' =>
              (
                ( ISSET( $MAP['users'][$row['salesman']] ) )
                ? $MAP['users'][$row['salesman']]['id']
                : NULL
              )
            , 'ts_paid' => $row['ts_paid']
            , 'customer_accounts__id' =>
              (
                ( ISSET( $MAP['customer_accounts'][$row['account_id']] ) )
                ? $MAP['customer_accounts'][$row['account_id']]['id']
                : NULL
              )
            , 'tax' => $row['tax']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['invoice_id']
            );
        $MAP['invoices'][$row['invoice_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['invoices'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['invoices'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['customers__id']
                  . ',' . $values['amt']
                  . ',' . $values['toi']
                  . ',' . $values['amt_paid']
                  . ',' . $values['emailed']
                  . ',' . $values['users__id__sale']
                  . ',' . $values['ts_paid']
                  . ',' . $values['customer_accounts__id']
                  . ',' . $values['tax']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `invoices`
(`id`,`customers__id`,`amt`,`toi`,`amt_paid`,`emailed`,`users__id__sale`,`ts_paid`,`customer_accounts__id`,`tax`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['invoices'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['invoices'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Inventory Items...';
  $MAP['inventory_items'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `inventory_items`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `inventory_items`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['inventory_items'] + $current_index;
        $new['inventory_items'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'inventory__id' =>
              (
                ( ISSET( $MAP['inventory'][$row['inventory_id']] ) )
                ? $MAP['inventory'][$row['inventory_id']]['id']
                : NULL
              )
            , 'notes' => $row['notes']
            , 'sn' => $row['sn']
            , 'issues__id' => NULL
            , 'varref_status' => $row['status']
            , 'in_store_location' =>
              (
                ( ISSET( $MAP['inventory_locations'][$row['location']] ) )
                ? $MAP['inventory_locations'][$row['location']]['id']
                : NULL
              )
            , 'item_type_table' => 'categories'
            , 'item_table_lookup' =>
              (
                ( ISSET( $MAP['categories'][$row['device_id']] ) )
                ? $MAP['categories'][$row['device_id']]['id']
                : NULL
              )
            , 'is_in_transit' => $row['in_transit']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['inv_item_id']
            );
        $MAP['inventory_items'][$row['inv_item_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['inventory_items'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['inventory_items'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['inventory__id']
                  . ',' . $values['notes']
                  . ',' . $values['sn']
                  . ',' . $values['issues__id']
                  . ',' . $values['varref_status']
                  . ',' . $values['in_store_location']
                  . ',' . $values['item_type_table']
                  . ',' . $values['item_table_lookup']
                  . ',' . $values['is_in_transit']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `inventory_items`
(`id`,`inventory__id`,`notes`,`sn`,`issues__id`,`varref_status`,`in_store_location`,`item_type_table`,`item_table_lookup`,`is_in_transit`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['inventory_items'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['inventory_items'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Devices...';
  $MAP['inventory_type_devices'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `devices`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `devices`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['inventory_type_devices'] + $current_index;
        $new['inventory_type_devices'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'categories__id' =>
              (
                ( ISSET( $MAP['categories'][$row['device_type']] ) )
                ? $MAP['categories'][$row['device_type']]['id']
                : NULL
              )
            , 'manufacturer' => $row['device_mfc']
            , 'model' => $row['device_model']
            , 'serial_number' => $row['device_sn']
            , 'operating_system' => $row['device_os']
            , 'has_charger' => $row['device_charger']
            , 'username' => $row['device_user']
            , 'password' => $row['device_pass']
            , 'in_store_location' =>
              (
                ( ISSET( $MAP['inventory_locations'][$row['location']] ) )
                ? $MAP['inventory_locations'][$row['location']]['id']
                : NULL
              )
            , 'customers__id' =>
              (
                ( ISSET( $MAP['customers'][$row['customer_id']] ) )
                ? $MAP['customers'][$row['customer_id']]['id']
                : NULL
              )
            , 'inventory_item_number' =>
              (
                ( ISSET( $MAP['inventory_items'][$row['inv_item_id']] ) )
                ? $MAP['inventory_items'][$row['inv_item_id']]['id']
                : NULL
              )
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['device_id']
            );
        $MAP['inventory_type_devices'][$row['device_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['inventory_type_devices'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['inventory_type_devices'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['categories__id']
                  . ',' . $values['manufacturer']
                  . ',' . $values['model']
                  . ',' . $values['serial_number']
                  . ',' . $values['operating_system']
                  . ',' . $values['has_charger']
                  . ',' . $values['username']
                  . ',' . $values['password']
                  . ',' . $values['in_store_location']
                  . ',' . $values['customers__id']
                  . ',' . $values['inventory_item_number']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `inventory_type_devices`
(`id`,`categories__id`,`manufacturer`,`model`,`serial_number`,`operating_system`,`has_charger`,`username`,`password`,`in_store_location`,`customers__id`,`inventory_item_number`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['inventory_type_devices'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['inventory_type_devices'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Issues...';
  $MAP['issues'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `issues`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `issues`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['issues'] + $current_index;
        $new['issues'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'customers__id' =>
              (
                ( ISSET( $MAP['customers'][$row['customer_id']] ) )
                ? $MAP['customers'][$row['customer_id']]['id']
                : NULL
              )
            , 'varref_status' => $row['status']
            , 'device_id' =>
              (
                ( ISSET( $MAP['inventory_type_devices'][$row['device_id']] ) )
                ? $MAP['inventory_type_devices'][$row['device_id']]['id']
                : NULL
              )
            , 'services__id' => NULL
            , 'varref_issue_type' => $row['issue_type']
            , 'savedfiles' => $row['savedfiles']
            , 'troubledesc' => $row['troubledesc']
            , 'intake_ts' => $row['intake_ts']
            , 'users__id__intake' =>
              (
                ( ISSET( $MAP['users'][$row['intake_tech']] ) )
                ? $MAP['users'][$row['intake_tech']]['id']
                : NULL
              )
            , 'users__id__assigned' =>
              (
                ( ISSET( $MAP['users'][$row['assigned_to']] ) )
                ? $MAP['users'][$row['assigned_to']]['id']
                : NULL
              )
            , 'quote_price' => $row['quote_price']
            , 'do_price' => $row['do_price']
            , 'final_summary' => $row['final_summary']
            , 'invoices__id' =>
              (
                ( ISSET( $MAP['invoices'][$row['invoice_id']] ) )
                ? $MAP['invoices'][$row['invoice_id']]['id']
                : NULL
              )
            , 'is_resolved' => $row['resolved']
            , 'is_deleted' => $row['is_deleted']
            , 'last_modified' => $row['last_modified']
            , 'subtotal' => $row['subtotal']
            , 'issue_step' => $row['issue_step']
            , 'issue_step_done' => $row['issue_step_done']
            , 'customer_accounts__id' =>
              (
                ( ISSET( $MAP['customer_accounts'][$row['account_id']] ) )
                ? $MAP['customer_accounts'][$row['account_id']]['id']
                : NULL
              )
            , 'diagnosis' => $row['diagnosis']
            , 'last_status_chg' => $row['last_status_chg']
            , 'services' =>
              (
                ( ISSET( $row['services'] ) )
                ? (
                    SPRINTF( ':%s:' , IMPLODE( ':' , ARRAY_MAP( 'CURRENT' , ARRAY_INTERSECT_KEY( $MAP['services'] , ARRAY_FLIP( EXPLODE( ':' , $row['services'] ) ) ) ) ) )
                  )
                : NULL
              )
            , 'service_steps' => $row['service_steps']
            , 'last_step_ts' => $row['last_step_ts']
            , 'has_charger' => $row['with_charger']
            , 'check_notes' => $row['check_notes']
            , 'warranty_status' => $row['warranty_status']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['issue_id']
            );
        $MAP['issues'][$row['issue_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['issues'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['issues'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['customers__id']
                  . ',' . $values['varref_status']
                  . ',' . $values['device_id']
                  . ',' . $values['services__id']
                  . ',' . $values['varref_issue_type']
                  . ',' . $values['savedfiles']
                  . ',' . $values['troubledesc']
                  . ',' . $values['intake_ts']
                  . ',' . $values['users__id__intake']
                  . ',' . $values['users__id__assigned']
                  . ',' . $values['quote_price']
                  . ',' . $values['do_price']
                  . ',' . $values['final_summary']
                  . ',' . $values['invoices__id']
                  . ',' . $values['is_resolved']
                  . ',' . $values['is_deleted']
                  . ',' . $values['last_modified']
                  . ',' . $values['subtotal']
                  . ',' . $values['issue_step']
                  . ',' . $values['issue_step_done']
                  . ',' . $values['customer_accounts__id']
                  . ',' . $values['diagnosis']
                  . ',' . $values['last_status_chg']
                  . ',' . $values['services']
                  . ',' . $values['service_steps']
                  . ',' . $values['last_step_ts']
                  . ',' . $values['has_charger']
                  . ',' . $values['check_notes']
                  . ',' . $values['warranty_status']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `issues`
(`id`,`customers__id`,`varref_status`,`device_id`,`services__id`,`varref_issue_type`,`savedfiles`,`troubledesc`,`intake_ts`,`users__id__intake`,`users__id__assigned`,`quote_price`,`do_price`,`final_summary`,`invoices__id`,`is_resolved`,`is_deleted`,`last_modified`,`subtotal`,`issue_step`,`issue_step_done`,`customer_accounts__id`,`diagnosis`,`last_status_chg`,`services`,`service_steps`,`last_step_ts`,`has_charger`,`check_notes`,`warranty_status`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['issues'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['issues'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Customer Inventory Items...';
  $MAP['inventory_items_customer'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `customer_inv`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `customer_inv`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['inventory_items_customer'] + $current_index;
        $new['inventory_items_customer'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'customers__id' =>
              (
                ( ISSET( $MAP['customers'][$row['customer_id']] ) )
                ? $MAP['customers'][$row['customer_id']]['id']
                : NULL
              )
            , 'inventory__id' =>
              (
                ( ISSET( $MAP['inventory'][$row['inventory_id']] ) )
                ? $MAP['inventory'][$row['inventory_id']]['id']
                : NULL
              )
            , 'inventory_item_number' =>
              (
                ( ISSET( $MAP['inventory_items'][$row['inv_item_id']] ) )
                ? $MAP['inventory_items'][$row['inv_item_id']]['id']
                : NULL
              )
            , 'qty' => $row['qty']
            , 'ts' => $row['ts']
            , 'unit_cost' => $row['unit_cost']
            , 'total_cost' => $row['total_cost']
            , 'serial_numbers' => $row['serial_numbers']
            , 'old_id' => $row['customer_inv_id']
            );
        $MAP['inventory_items_customer'][$row['customer_inv_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['inventory_items_customer'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['inventory_items_customer'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['customers__id']
                  . ',' . $values['inventory__id']
                  . ',' . $values['inventory_item_number']
                  . ',' . $values['qty']
                  . ',' . $values['ts']
                  . ',' . $values['unit_cost']
                  . ',' . $values['total_cost']
                  . ',' . $values['serial_numbers']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `inventory_items_customer`
(`id`,`customers__id`,`inventory__id`,`inventory_item_number`,`qty`,`ts`,`unit_cost`,`total_cost`,`serial_numbers`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['inventory_items_customer'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['inventory_items_customer'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Inventory Changes...';
  $MAP['inventory_changes'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `inventory_changes`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `inventory_changes`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['inventory_changes'] + $current_index;
        $new['inventory_changes'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'inventory__id' =>
              (
                ( ISSET( $MAP['inventory'][$row['inv_id']] ) )
                ? $MAP['inventory'][$row['inv_id']]['id']
                : NULL
              )
            , 'inventory_item_number' =>
              (
                ( ISSET( $MAP['inventory_items'][$row['inv_item_id']] ) )
                ? $MAP['inventory_items'][$row['inv_item_id']]['id']
                : NULL
              )
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'varref_change_code' => $row['change_code']
            , 'qty' => $row['qty']
            , 'in_store_location' =>
              (
                ( ISSET( $MAP['inventory_locations'][$row['location']] ) )
                ? $MAP['inventory_locations'][$row['location']]['id']
                : NULL
              )
            , 'ts' => $row['ts']
            , 'descr' => $row['descr']
            , 'varref_status' => $row['status']
            , 'reason' => $row['reason']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['change_id']
            );
        $MAP['inventory_changes'][$row['change_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['inventory_changes'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['inventory_changes'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['inventory__id']
                  . ',' . $values['inventory_item_number']
                  . ',' . $values['users__id']
                  . ',' . $values['varref_change_code']
                  . ',' . $values['qty']
                  . ',' . $values['in_store_location']
                  . ',' . $values['ts']
                  . ',' . $values['descr']
                  . ',' . $values['varref_status']
                  . ',' . $values['reason']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `inventory_changes`
(`id`,`inventory__id`,`inventory_item_number`,`users__id`,`varref_change_code`,`qty`,`in_store_location`,`ts`,`descr`,`varref_status`,`reason`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['inventory_changes'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['inventory_changes'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Invoice Changes...';
  $MAP['invoice_changes'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `invoice_changes`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `invoice_changes`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['invoice_changes'] + $current_index;
        $new['invoice_changes'][$current_index] = ARRAY
            ( 'change_id' => $current_id
            , 'invoice_id' =>
              (
                ( ISSET( $MAP['invoices'][$row['invoice_id']] ) )
                ? $MAP['invoices'][$row['invoice_id']]['id']
                : NULL
              )
            , 'changed_by' =>
              (
                ( ISSET( $MAP['users'][$row['changed_by']] ) )
                ? $MAP['users'][$row['changed_by']]['id']
                : NULL
              )
            , 'change_summary' => $row['change_summary']
            , 'old_amt' => $row['old_amt']
            , 'new_amt' => $row['new_amt']
            , 'ts' => $row['ts']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['change_id']
            );
        $MAP['invoice_changes'][$row['change_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['invoice_changes'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['invoice_changes'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['change_id']
                  . ',' . $values['invoice_id']
                  . ',' . $values['changed_by']
                  . ',' . $values['change_summary']
                  . ',' . $values['old_amt']
                  . ',' . $values['new_amt']
                  . ',' . $values['ts']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `invoice_changes`
(`change_id`,`invoice_id`,`changed_by`,`change_summary`,`old_amt`,`new_amt`,`ts`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['invoice_changes'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['invoice_changes'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Issue Changes...';
  $MAP['issue_changes'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `issue_changes`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `issue_changes`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['issue_changes'] + $current_index;
        $new['issue_changes'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'issues__id' =>
              (
                ( ISSET( $MAP['issues'][$row['issue_id']] ) )
                ? $MAP['issues'][$row['issue_id']]['id']
                : NULL
              )
            , 'description' => $row['description']
            , 'varref_status' => $row['status']
            , 'tou' => $row['tou']
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['change_id']
            );
        $MAP['issue_changes'][$row['change_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['issue_changes'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['issue_changes'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['issues__id']
                  . ',' . $values['description']
                  . ',' . $values['varref_status']
                  . ',' . $values['tou']
                  . ',' . $values['users__id']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `issue_changes`
(`id`,`issues__id`,`description`,`varref_status`,`tou`,`users__id`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['issue_changes'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['issue_changes'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Issue Inventory References...';
  $MAP['issue_inv'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `issue_inv`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `issue_inv`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['issue_inv'] + $current_index;
        $new['issue_inv'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'issues__id' =>
              (
                ( ISSET( $MAP['issues'][$row['issue_id']] ) )
                ? $MAP['issues'][$row['issue_id']]['id']
                : NULL
              )
            , 'inventory__id' =>
              (
                ( ISSET( $MAP['inventory'][$row['inventory_id']] ) )
                ? $MAP['inventory'][$row['inventory_id']]['id']
                : NULL
              )
            , 'qty' => $row['qty']
            , 'do_add' => $row['add']
            , 'inventory_items__id' =>
              (
                ( ISSET( $MAP['inventory_items'][$row['inv_item_id']] ) )
                ? $MAP['inventory_items'][$row['inv_item_id']]['id']
                : NULL
              )
            , 'old_id' => $row['issue_inv_id']
            );
        $MAP['issue_inv'][$row['issue_inv_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['issue_inv'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['issue_inv'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['issues__id']
                  . ',' . $values['inventory__id']
                  . ',' . $values['qty']
                  . ',' . $values['do_add']
                  . ',' . $values['inventory_items__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `issue_inv`
(`id`,`issues__id`,`inventory__id`,`qty`,`do_add`,`inventory_items__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['issue_inv'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['issue_inv'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Issue Item References...';
  $MAP['issue_items'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `issue_items`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `issue_items`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['issue_items'] + $current_index;
        $new['issue_items'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'issues__id' =>
              (
                ( ISSET( $MAP['issues'][$row['issue_id']] ) )
                ? $MAP['issues'][$row['issue_id']]['id']
                : NULL
              )
            , 'descr' => $row['descr']
            , 'amt' => $row['amt']
            , 'qty`' => $row['qty`']
            , 'is_taxable' => $row['is_taxable']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['issue_item_id']
            );
        $MAP['issue_items'][$row['issue_item_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['issue_items'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['issue_items'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['issues__id']
                  . ',' . $values['descr']
                  . ',' . $values['amt']
                  . ',' . $values['qty`']
                  . ',' . $values['is_taxable']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `issue_items`
(`id`,`issues__id`,`descr`,`amt`,`qty`,`is_taxable`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['issue_items'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['issue_items'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Issue Labor References...';
  $MAP['issue_labor'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `labor`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `labor`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['issue_labor'] + $current_index;
        $new['issue_labor'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'customer_accounts__id' =>
              (
                ( ISSET( $MAP['customer_accounts'][$row['account_id']] ) )
                ? $MAP['customer_accounts'][$row['account_id']]['id']
                : NULL
              )
            , 'issues__id' =>
              (
                ( ISSET( $MAP['issues'][$row['issue_id']] ) )
                ? $MAP['issues'][$row['issue_id']]['id']
                : NULL
              )
            , 'amount' => $row['amount']
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['tech']] ) )
                ? $MAP['users'][$row['tech']]['id']
                : NULL
              )
            , 'ts' => $row['ts']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['labor_id']
            );
        $MAP['issue_labor'][$row['labor_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['issue_labor'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['issue_labor'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['customer_accounts__id']
                  . ',' . $values['issues__id']
                  . ',' . $values['amount']
                  . ',' . $values['users__id']
                  . ',' . $values['ts']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `issue_labor`
(`id`,`customer_accounts__id`,`issues__id`,`amount`,`users__id`,`ts`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['issue_labor'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['issue_labor'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Feedback Questions...';
  $MAP['feedback_questions'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `feedback_questions`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `feedback_questions`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['feedback_questions'] + $current_index;
        $new['feedback_questions'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'question' => $row['question']
            , 'is_active' => $row['is_active']
            , 'old_id' => $row['question_id']
            );
        $MAP['feedback_questions'][$row['question_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['feedback_questions'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['feedback_questions'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['question']
                  . ',' . $values['is_active']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `feedback_questions`
(`id`,`question`,`is_active`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['feedback_questions'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['feedback_questions'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Feedback...';
  $MAP['feedback'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `feedback`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `feedback`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['feedback'] + $current_index;
        $new['feedback'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'customers__id' =>
              (
                ( ISSET( $MAP['customers'][$row['customer_id']] ) )
                ? $MAP['customers'][$row['customer_id']]['id']
                : NULL
              )
            , 'score' => $row['score']
            , 'feedback' => $row['feedback']
            , 'ts' => $row['ts']
            , 'issues__id' =>
              (
                ( ISSET( $MAP['issues'][$row['issue_id']] ) )
                ? $MAP['issues'][$row['issue_id']]['id']
                : NULL
              )
            , 'questions' => $row['questions']
            , 'old_id' => $row['feedback_id']
            );
        $MAP['feedback'][$row['feedback_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['feedback'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['feedback'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['customers__id']
                  . ',' . $values['score']
                  . ',' . $values['feedback']
                  . ',' . $values['ts']
                  . ',' . $values['issues__id']
                  . ',' . $values['questions']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `feedback`
(`id`,`customers__id`,`score`,`feedback`,`ts`,`issues__id`,`questions`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['feedback'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['feedback'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Orders...';
  $MAP['orders'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `orders`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `orders`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['orders'] + $current_index;
        $new['orders'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'order_date' => $row['order_date']
            , 'purchased_from' => $row['purchased_from']
            , 'order_number' => $row['order_number']
            , 'shipping_type' => $row['shipping_type']
            , 'tracking_number' => $row['tracking_number']
            , 'receive_date' => $row['receive_date']
            , 'subtotal' => $row['subtotal']
            , 'tax' => $row['tax']
            , 'carrier' => $row['carrier']
            , 'varref_status' => $row['status']
            , 'desc' => $row['descr']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['order_id']
            );
        $MAP['orders'][$row['order_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['orders'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['orders'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['order_date']
                  . ',' . $values['purchased_from']
                  . ',' . $values['order_number']
                  . ',' . $values['shipping_type']
                  . ',' . $values['tracking_number']
                  . ',' . $values['receive_date']
                  . ',' . $values['subtotal']
                  . ',' . $values['tax']
                  . ',' . $values['carrier']
                  . ',' . $values['varref_status']
                  . ',' . $values['desc']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `orders`
(`id`,`order_date`,`purchased_from`,`order_number`,`shipping_type`,`tracking_number`,`receive_date`,`subtotal`,`tax`,`carrier`,`varref_status`,`desc`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['orders'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['orders'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Order Item References...';
  $MAP['order_items'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `order_items`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `order_items`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['order_items'] + $current_index;
        $new['order_items'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'orders__id' =>
              (
                ( ISSET( $MAP['orders'][$row['order_id']] ) )
                ? $MAP['orders'][$row['order_id']]['id']
                : NULL
              )
            , 'inventory__id' =>
              (
                ( ISSET( $MAP['inventory'][$row['inventory_id']] ) )
                ? $MAP['inventory'][$row['inventory_id']]['id']
                : NULL
              )
            , 'issues__id' =>
              (
                ( ISSET( $MAP['issues'][$row['issue_id']] ) )
                ? $MAP['issues'][$row['issue_id']]['id']
                : NULL
              )
            , 'cost' => $row['cost']
            , 'qty' => $row['qty']
            , 'varref_status' => $row['status']
            , 'rma_number' => $row['rma_number']
            , 'r_tracking_number' => $row['r_tracking_number']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['order_item_id']
            );
        $MAP['order_items'][$row['order_item_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['order_items'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['order_items'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['orders__id']
                  . ',' . $values['inventory__id']
                  . ',' . $values['issues__id']
                  . ',' . $values['cost']
                  . ',' . $values['qty']
                  . ',' . $values['varref_status']
                  . ',' . $values['rma_number']
                  . ',' . $values['r_tracking_number']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `order_items`
(`id`,`orders__id`,`inventory__id`,`issues__id`,`cost`,`qty`,`varref_status`,`rma_number`,`r_tracking_number`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['order_items'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['order_items'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Deposit Records...';
  $MAP['pos_deposits'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `deposits`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `deposits`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['pos_deposits'] + $current_index;
        $new['pos_deposits'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'amt' => $row['amt']
            , 'drops' => $row['drops']
            , 'tod' => $row['tod']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['deposit_id']
            );
        $MAP['pos_deposits'][$row['deposit_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['pos_deposits'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['pos_deposits'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id']
                  . ',' . $values['amt']
                  . ',' . $values['drops']
                  . ',' . $values['tod']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `pos_deposits`
(`id`,`users__id`,`amt`,`drops`,`tod`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['pos_deposits'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['pos_deposits'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Payment Records...';
  $MAP['pos_payments'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `payments`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `payments`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['pos_payments'] + $current_index;
        $new['pos_payments'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'customers__id' =>
              (
                ( ISSET( $MAP['customers'][$row['customer_id']] ) )
                ? $MAP['customers'][$row['customer_id']]['id']
                : NULL
              )
            , 'paid_cash' => $row['paid_cash']
            , 'paid_check' => $row['paid_check']
            , 'paid_credit' => $row['paid_credit']
            , 'top' => $row['top']
            , 'applied_to' => $row['applied_to']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['payment_id']
            );
        $MAP['pos_payments'][$row['payment_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['pos_payments'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['pos_payments'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['customers__id']
                  . ',' . $values['paid_cash']
                  . ',' . $values['paid_check']
                  . ',' . $values['paid_credit']
                  . ',' . $values['top']
                  . ',' . $values['applied_to']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `pos_payments`
(`id`,`customers__id`,`paid_cash`,`paid_check`,`paid_credit`,`top`,`applied_to`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['pos_payments'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['pos_payments'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Report Configurations...';
  $MAP['reports_config'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `reports_config`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `reports_config`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['reports_config'] + $current_index;
        $new['reports_config'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'reports' => $row['reports']
            , 'last_emailed' => $row['last_emailed']
            , 'email_every' => $row['email_every']
            , 'org_entities_list' =>
              (
                IMPLODE( ',' , ARRAY_MAP( 'CURRENT' , ARRAY_INTERSECT_KEY( $MAP['org_entities'] , ARRAY_FLIP( EXPLODE( ',' , $row['stores'] ) ) ) ) )
              )
            , 'hr' => $row['hr']
            , 'do_attach' => $row['attach']
            , 'users_list' =>
              (
                IMPLODE( ',' , ARRAY_MAP( 'CURRENT' , ARRAY_INTERSECT_KEY( $MAP['users'] , ARRAY_FLIP( EXPLODE( ',' , $row['users'] ) ) ) ) )
              )
            , 'old_id' => $row['report_config_id']
            );
        $MAP['reports_config'][$row['report_config_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['reports_config'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['reports_config'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id']
                  . ',' . $values['reports']
                  . ',' . $values['last_emailed']
                  . ',' . $values['email_every']
                  . ',' . $values['org_entities_list']
                  . ',' . $values['hr']
                  . ',' . $values['do_attach']
                  . ',' . $values['users_list']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `reports_config`
(`id`,`users__id`,`reports`,`last_emailed`,`email_every`,`org_entities_list`,`hr`,`do_attach`,`users_list`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['reports_config'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['reports_config'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Report Templates...';
  $MAP['user_rpt_templates'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `user_rpt_templates`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `user_rpt_templates`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['user_rpt_templates'] + $current_index;
        $new['user_rpt_templates'][$current_index] = ARRAY
            ( 'template_id' => $current_id
            , 'template_name' => $row['template_name']
            , 'created_by' =>
              (
                ( ISSET( $MAP['users'][$row['created_by']] ) )
                ? $MAP['users'][$row['created_by']]['id']
                : NULL
              )
            , 'created_ts' => $row['created_ts']
            , 'column_data' => $row['column_data']
            , 'point_value' => $row['point_value']
            , 'old_id' => $row['template_id']
            );
        $MAP['user_rpt_templates'][$row['template_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['user_rpt_templates'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['user_rpt_templates'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['template_id']
                  . ',' . $values['template_name']
                  . ',' . $values['created_by']
                  . ',' . $values['created_ts']
                  . ',' . $values['column_data']
                  . ',' . $values['point_value']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `user_rpt_templates`
(`template_id`,`template_name`,`created_by`,`created_ts`,`column_data`,`point_value`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['user_rpt_templates'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['user_rpt_templates'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Report Submissions...';
  $MAP['user_rpt_submissions'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `user_rpt_submissions`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `user_rpt_submissions`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['user_rpt_submissions'] + $current_index;
        $new['user_rpt_submissions'][$current_index] = ARRAY
            ( 'submission_id' => $current_id
            , 'template_id' =>
              (
                ( ISSET( $MAP['user_rpt_templates'][$row['template_id']] ) )
                ? $MAP['user_rpt_templates'][$row['template_id']]['id']
                : NULL
              )
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'submitted_ts' => $row['submitted_ts']
            , 'submitted_data' => $row['submitted_data']
            , 'was_viewed' => $row['was_viewed']
            , 'old_id' => $row['submission_id']
            );
        $MAP['user_rpt_submissions'][$row['submission_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['user_rpt_submissions'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['user_rpt_submissions'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['submission_id']
                  . ',' . $values['template_id']
                  . ',' . $values['users__id']
                  . ',' . $values['submitted_ts']
                  . ',' . $values['submitted_data']
                  . ',' . $values['was_viewed']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `user_rpt_submissions`
(`submission_id`,`template_id`,`user_id`,`submitted_ts`,`submitted_data`,`was_viewed`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['user_rpt_submissions'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['user_rpt_submissions'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Punchcards...';
  $MAP['payroll_timecards'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `punchcards`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `punchcards`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['payroll_timecards'] + $current_index;
        $new['payroll_timecards'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'punch_date' => $row['punch_date']
            , 'punch_in' => $row['punch_in']
            , 'punch_in_apt' => $row['punch_in_apt']
            , 'break_out' => $row['break_out']
            , 'break_out_apt' => $row['break_out_apt']
            , 'break_in' => $row['break_in']
            , 'break_in_apt' => $row['break_in_apt']
            , 'punch_out' => $row['punch_out']
            , 'punch_out_apt' => $row['punch_out_apt']
            , 'hours_worked' => $row['hours_worked']
            , 'is_m_in' => $row['m_out']
            , 'is_m_b_out' => $row['m_b_out']
            , 'is_m_b_in' => $row['m_b_in']
            , 'is_m_out' => $row['m_out']
            , 'addrs' => $row['addrs']
            , 'edits' => $row['edits']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['punch_id']
            );
        $MAP['payroll_timecards'][$row['punch_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['payroll_timecards'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['payroll_timecards'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id']
                  . ',' . $values['punch_date']
                  . ',' . $values['punch_in']
                  . ',' . $values['punch_in_apt']
                  . ',' . $values['break_out']
                  . ',' . $values['break_out_apt']
                  . ',' . $values['break_in']
                  . ',' . $values['break_in_apt']
                  . ',' . $values['punch_out']
                  . ',' . $values['punch_out_apt']
                  . ',' . $values['hours_worked']
                  . ',' . $values['is_m_in']
                  . ',' . $values['is_m_b_out']
                  . ',' . $values['is_m_b_in']
                  . ',' . $values['is_m_out']
                  . ',' . $values['addrs']
                  . ',' . $values['edits']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `payroll_timecards`
(`id`,`users__id`,`punch_date`,`punch_in`,`punch_in_apt`,`break_out`,`break_out_apt`,`break_in`,`break_in_apt`,`punch_out`,`punch_out_apt`,`hours_worked`,`is_m_in`,`is_m_b_out`,`is_m_b_in`,`is_m_out`,`addrs`,`edits`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['payroll_timecards'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['payroll_timecards'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Timesheets...';
  $MAP['timesheets'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `timesheets`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `timesheets`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['timesheets'] + $current_index;
        $new['timesheets'][$current_index] = ARRAY
            ( 'event_id' => $current_id
            , 'start' => $row['start']
            , 'start_time' => $row['start_time']
            , 'rec_end' => $row['rec_end']
            , 'end_time' => $row['end_time']
            , 'name' => $row['name']
            , 'descr' => $row['descr']
            , 'user_id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'created_by' =>
              (
                ( ISSET( $MAP['users'][$row['created_by']] ) )
                ? $MAP['users'][$row['created_by']]['id']
                : NULL
              )
            , 'recurring' => $row['recurring']
            , 'rec_type' => $row['rec_type']
            , 'parent' =>
              (
                ( ISSET( $MAP['timesheets'][$row['parent']] ) )
                ? $MAP['timesheets'][$row['parent']]['id']
                : NULL
              )
            , 'updated_by' =>
              (
                ( ISSET( $MAP['users'][$row['updated_by']] ) )
                ? $MAP['users'][$row['updated_by']]['id']
                : NULL
              )
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['event_id']
            );
        $MAP['timesheets'][$row['event_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['timesheets'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['timesheets'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['event_id']
                  . ',' . $values['start']
                  . ',' . $values['start_time']
                  . ',' . $values['rec_end']
                  . ',' . $values['end_time']
                  . ',' . $values['name']
                  . ',' . $values['descr']
                  . ',' . $values['user_id']
                  . ',' . $values['created_by']
                  . ',' . $values['recurring']
                  . ',' . $values['rec_type']
                  . ',' . $values['parent']
                  . ',' . $values['updated_by']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `timesheets`
(`event_id`,`start`,`start_time`,`rec_end`,`end_time`,`name`,`descr`,`user_id`,`created_by`,`recurring`,`rec_type`,`parent`,`updated_by`,`org_entities__id`)VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['timesheets'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['timesheets'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Calendar...';
  $MAP['calendar'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `calendar`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `calendar`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['calendar'] + $current_index;
        $new['calendar'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'start' => $row['start']
            , 'start_time' => $row['start_time']
            , 'rec_end' => $row['rec_end']
            , 'end_time' => $row['end_time']
            , 'name' => $row['name']
            , 'descr' => $row['descr']
            , 'users__id__target' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'users__id__created' =>
              (
                (
                  ISSET( $row['created_by'] )
                  AND ISSET( $MAP['users'][$row['created_by']] )
                )
                ? $MAP['users'][$row['created_by']]['id']
                : NULL
              )
            , 'is_recurring' => $row['recurring']
            , 'rec_type' => $row['rec_type']
            , 'event_type' => $row['event_type']
            , 'issues__id' =>
              (
                ( ISSET( $MAP['issues'][$row['issue_id']] ) )
                ? $MAP['issues'][$row['issue_id']]['id']
                : NULL
              )
            , 'parent' =>
              (
                ( ISSET( $MAP['timesheets'][$row['parent']] ) )
                ? $MAP['timesheets'][$row['parent']]['id']
                : NULL
              )
            , 'users__id__updated' =>
              (
                ( ISSET( $MAP['users'][$row['updated_by']] ) )
                ? $MAP['users'][$row['updated_by']]['id']
                : NULL
              )
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['event_id']
            );
        $MAP['calendar'][$row['event_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['calendar'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['calendar'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['start']
                  . ',' . $values['start_time']
                  . ',' . $values['rec_end']
                  . ',' . $values['end_time']
                  . ',' . $values['name']
                  . ',' . $values['descr']
                  . ',' . $values['users__id__target']
                  . ',' . $values['users__id__created']
                  . ',' . $values['is_recurring']
                  . ',' . $values['rec_type']
                  . ',' . $values['event_type']
                  . ',' . $values['issues__id']
                  . ',' . $values['parent']
                  . ',' . $values['users__id__updated']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `calendar`
(`id`,`start`,`start_time`,`rec_end`,`end_time`,`name`,`descr`,`users__id__target`,`users__id__created`,`is_recurring`,`rec_type`,`event_type`,`issues__id`,`parent`,`users__id__updated`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['calendar'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['calendar'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Calendar Views...';
  $MAP['calendar_views'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `calendar_views`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `calendar_views`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['calendar_views'] + $current_index;
        $new['calendar_views'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'name' => $row['name']
            , 'is_current' => $row['is_current']
            , 'event_types' => $row['event_types']
            , 'users' =>$row['users']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['view_id']
            );
        $MAP['calendar_views'][$row['view_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['calendar_views'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['calendar_views'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id']
                  . ',' . $values['name']
                  . ',' . $values['is_current']
                  . ',' . $values['event_types']
                  . ',' . $values['users']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `calendar_views`
(`id`,`users__id`,`name`,`is_current`,`event_types`,`users`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['calendar_views'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['calendar_views'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Newsletters...';
  $MAP['newsletters'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `newsletters`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `newsletters`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['newsletters'] + $current_index;
        $new['newsletters'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id__created_by' =>
              (
                ( ISSET( $MAP['users'][$row['created_by']] ) )
                ? $MAP['users'][$row['created_by']]['id']
                : NULL
              )
            , 'created' => $row['created']
            , 'last_emailed' => $row['last_emailed']
            , 'emailed_to' => $row['emailed_to']
            , 'subj' =>$row['subj']
            , 'msg' =>$row['msg']
            , 'html' =>$row['html']
            , 'is_attachment' =>$row['is_attachment']
            , 'old_id' => $row['newsletter_id']
            );
        $MAP['newsletters'][$row['newsletter_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['newsletters'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['newsletters'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id__created_by']
                  . ',' . $values['created']
                  . ',' . $values['last_emailed']
                  . ',' . $values['emailed_to']
                  . ',' . $values['subj']
                  . ',' . $values['msg']
                  . ',' . $values['html']
                  . ',' . $values['is_attachment']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `newsletters`
(`id`,`users__id__created_by`,`created`,`last_emailed`,`emailed_to`,`subj`,`msg`,`html`,`is_attachment`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['newsletters'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['newsletters'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Messages...';
  $MAP['messages'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `messages`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `messages`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['messages'] + $current_index;
        $new['messages'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id__1' =>
              (
                ( ISSET( $MAP['users'][$row['user1']] ) )
                ? $MAP['users'][$row['user1']]['id']
                : NULL
              )
            , 'users__id__2' =>
              (
                ( ISSET( $MAP['users'][$row['user2']] ) )
                ? $MAP['users'][$row['user2']]['id']
                : NULL
              )
            , 'box' => $row['box']
            , 'subject' => $row['subject']
            , 'message' => $row['message']
            , 'ts' =>$row['ts']
            , 'is_read' =>$row['is_read']
            , 'old_id' => $row['message_id']
            );
        $MAP['messages'][$row['message_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['messages'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['messages'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id__1']
                  . ',' . $values['users__id__2']
                  . ',' . $values['box']
                  . ',' . $values['subject']
                  . ',' . $values['message']
                  . ',' . $values['ts']
                  . ',' . $values['is_read']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `messages`
(`id`,`users__id__1`,`users__id__2`,`box`,`subject`,`message`,`ts`,`is_read`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['messages'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['messages'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Bug Reports (Hopefully Not The Bugs Themselves)...';
  $MAP['bugs'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `bugs`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `bugs`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['bugs'] + $current_index;
        $new['bugs'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'descr' => $row['descr']
            , 'created_ts' => $row['created_ts']
            , 'varref_status' => $row['status']
            , 'importance' =>$row['importance']
            , 'is_deleted' =>$row['is_deleted']
            , 'category' =>$row['category']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['bug_id']
            );
        $MAP['bugs'][$row['bug_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['bugs'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['bugs'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id']
                  . ',' . $values['descr']
                  . ',' . $values['created_ts']
                  . ',' . $values['varref_status']
                  . ',' . $values['importance']
                  . ',' . $values['is_deleted']
                  . ',' . $values['category']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `bugs`
(`id`,`users__id`,`descr`,`created_ts`,`varref_status`,`importance`,`is_deleted`,`category`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['bugs'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['bugs'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Bug Notes';
  $MAP['bugs_notes'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `bugs_notes`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `bugs_notes`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['bugs_notes'] + $current_index;
        $new['bugs_notes'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'note' => $row['note']
            , 'note_ts' => $row['note_ts']
            , 'bugs__id' =>
              (
                ( ISSET( $MAP['bugs'][$row['bug_id']] ) )
                ? $MAP['bugs'][$row['bug_id']]['id']
                : NULL
              )
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['note_id']
            );
        $MAP['bugs_notes'][$row['note_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['bugs_notes'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['bugs_notes'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id']
                  . ',' . $values['note']
                  . ',' . $values['note_ts']
                  . ',' . $values['bugs__id']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `bugs_notes`
(`id`,`users__id`,`note`,`note_ts`,`bugs__id`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['bugs_notes'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['bugs_notes'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Recent Change Notices';
  $MAP['changes'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `recent_changes`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `recent_changes`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['changes'] + $current_index;
        $new['changes'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'ts' => $row['ts']
            , 'subject' => $row['subject']
            , 'descr' => $row['descr']
            , 'old_id' => $row['change_id']
            );
        $MAP['changes'][$row['change_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['changes'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['changes'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['ts']
                  . ',' . $values['subject']
                  . ',' . $values['descr']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `changes`
(`id`,`ts`,`subject`,`descr`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['changes'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['changes'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Invoice Items...';
  $MAP['invoice_items'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `invoice_items`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `invoice_items`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['invoice_items'] + $current_index;
        $new['invoice_items'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'invoices__id' =>
              (
                ( ISSET( $MAP['invoices'][$row['invoice_id']] ) )
                ? $MAP['invoices'][$row['invoice_id']]['id']
                : NULL
              )
            , 'name' =>
              (
                PREG_REPLACE_CALLBACK( '/[0-9]+/' , '_callback_map_issues' , $row['name'] )
              )
            , 'descr' =>
              (
                PREG_REPLACE_CALLBACK( '/[0-9]+/' , '_callback_map_issues' , $row['descr'] )
              )
            , 'cost' => $row['cost']
            , 'qty' => $row['qty']
            , 'is_taxable' => $row['taxable']
            , 'from_table' =>
              (
                ( ISSET( $MAP['tables'][$row['from_table']] ) )
                ? $MAP['tables'][$row['from_table']]['name']
                : NULL
              )
            , 'from_key_name' =>
              (
                ( ISSET( $MAP['tables'][$row['from_table']] ) )
                ? $MAP['tables'][$row['from_table']]['key']
                : NULL
              )
            , 'from_key' =>
              (
                (
                  ISSET( $row['from_table'] )
                  AND ISSET( $row['from_key'] )
                  AND ISSET( $MAP[ $MAP['tables'][ $row['from_table'] ]['name'] ][ $row['from_key'] ] )
                )
                ? $MAP[ $MAP['tables'][ $row['from_table'] ]['name'] ][ $row['from_key'] ]['id']
                : NULL
              )
            , 'writeback' => $row['writeback']
            , 'is_heading' => $row['is_heading']
            , 'grp' => $row['grp']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['invoice_item_id']
            );
        $MAP['invoice_items'][$row['invoice_item_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['invoice_items'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['invoice_items'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['invoices__id']
                  . ',' . $values['name']
                  . ',' . $values['descr']
                  . ',' . $values['cost']
                  . ',' . $values['qty']
                  . ',' . $values['is_taxable']
                  . ',' . $values['from_table']
                  . ',' . $values['from_key_name']
                  . ',' . $values['from_key']
                  . ',' . $values['writeback']
                  . ',' . $values['is_heading']
                  . ',' . $values['grp']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `invoice_items`
(`id`,`invoices__id`,`name`,`descr`,`cost`,`qty`,`is_taxable`,`from_table`,`from_key_name`,`from_key`,`writeback`,`is_heading`,`grp`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['invoice_items'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['invoice_items'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge POS Cart References...';
  $MAP['pos_cart_items'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `cart`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `cart`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['pos_cart_items'] + $current_index;
        $new['pos_cart_items'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'from_table' =>
              (
                ( ISSET( $MAP['tables'][$row['from_table']] ) )
                ? $MAP['tables'][$row['from_table']]['name']
                : NULL
              )
            , 'from_key_name' =>
              (
                ( ISSET( $MAP['tables'][$row['from_table']] ) )
                ? $MAP['tables'][$row['from_table']]['key']
                : NULL
              )
            , 'from_key' =>
              (
                (
                  ISSET( $row['from_table'] )
                  AND ISSET( $row['from_key'] )
                  AND ISSET( $MAP[ $MAP['tables'][ $row['from_table'] ]['name'] ][ $row['from_key'] ] )
                )
                ? $MAP[$MAP['tables'][$row['from_table']]['name']][$row['from_key']]['id']
                : NULL
              )
            , 'writeback' => $row['writeback']
            , 'amt' => $row['amt']
            , 'qty' => $row['qty']
            , 'descr' => $row['descr']
            , 'users__id__sale' =>
              (
                ( ISSET( $MAP['users'][$row['salesman']] ) )
                ? $MAP['users'][$row['salesman']]['id']
                : NULL
              )
            , 'is_taxable' => $row['taxable']
            , 'grp' => $row['grp']
            , 'is_heading' => $row['is_heading']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['item_id']
            );
        $MAP['pos_cart_items'][$row['item_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['pos_cart_items'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['pos_cart_items'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['from_table']
                  . ',' . $values['from_key_name']
                  . ',' . $values['from_key']
                  . ',' . $values['writeback']
                  . ',' . $values['amt']
                  . ',' . $values['qty']
                  . ',' . $values['descr']
                  . ',' . $values['users__id__sale']
                  . ',' . $values['is_taxable']
                  . ',' . $values['grp']
                  . ',' . $values['is_heading']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `pos_cart_items`
(`id`,`from_table`,`from_key_name`,`from_key`,`writeback`,`amt`,`qty`,`descr`,`users__id__sale`,`is_taxable`,`grp`,`is_heading`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['pos_cart_items'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['pos_cart_items'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Transaction Records...';
  $MAP['pos_transactions'] = ARRAY();
  $minimum_id = $counters['pos_transactions'];
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `transactions`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `transactions`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        IF ( ISSET( $MAP['pos_transactions'][ $row['transaction_id'] ] ) AND ( $MAP['pos_transactions'][ $row['transaction_id'] ]['id'] >= $minimum_id ) ) {
          $current_id = $MAP['pos_transactions'][$row['transaction_id']]['id'];
        }
        ELSE {
          $current_id = $counters['pos_transactions'] + $current_index;
        }
        $new['pos_transactions'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'line_number' => $row['line_number']
            , 'from_table' =>
              (
                ( ISSET( $MAP['tables'][$row['from_table']] ) )
                ? $MAP['tables'][$row['from_table']]['name']
                : NULL
              )
            , 'from_key_name' =>
              (
                ( ISSET( $MAP['tables'][$row['from_table']] ) )
                ? $MAP['tables'][$row['from_table']]['key']
                : NULL
              )
            , 'from_key' =>
              (
                (
                  ISSET( $row['from_table'] )
                  AND ISSET( $row['from_key'] )
                  AND ( ISSET( $MAP['tables'][ $row['from_table'] ] ) )
                  AND ISSET( $MAP[ $MAP['tables'][ $row['from_table'] ]['name'] ][ $row['from_key'] ] )
                )
                ? $MAP[$MAP['tables'][$row['from_table']]['name']][$row['from_key']]['id']
                : NULL
              )
            , 'writeback' => $row['writeback']
            , 'amt' => $row['amt']
            , 'descr' => $row['descr']
            , 'qty' => $row['qty']
            , 'users__id__sale' =>
              (
                ( ISSET( $MAP['users'][$row['salesman']] ) )
                ? $MAP['users'][$row['salesman']]['id']
                : NULL
              )
            , 'customers__id' =>
              (
                ( ISSET( $MAP['customers'][$row['customer']] ) )
                ? $MAP['customers'][$row['customer']]['id']
                : NULL
              )
            , 'is_taxable' => $row['taxable']
            , 'is_refunded' => $row['refunded']
            , 'users__id__refund' =>
              (
                ( ISSET( $MAP['users'][$row['refunded_by']] ) )
                ? $MAP['users'][$row['refunded_by']]['id']
                : NULL
              )
            , 'tos' => $row['tos']
            , 'tor' => $row['tor']
            , 'paid_cash' => $row['paid_cash']
            , 'paid_credit' => $row['paid_credit']
            , 'paid_check' => $row['paid_check']
            , 'grp' => $row['grp']
            , 'is_heading' => $row['is_heading']
            , 'paid_tax' => $row['paid_tax']
            , 'check_no' => $row['check_no']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['transaction_id']
            );
        IF ( ! ISSET( $MAP['pos_transactions'][ $row['transaction_id'] ] ) ) {
          $MAP['pos_transactions'][ $row['transaction_id'] ]['id'] = $current_id;
          $current_index++;
        }
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['pos_transactions'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['pos_transactions'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['line_number']
                  . ',' . $values['from_table']
                  . ',' . $values['from_key_name']
                  . ',' . $values['from_key']
                  . ',' . $values['writeback']
                  . ',' . $values['amt']
                  . ',' . $values['descr']
                  . ',' . $values['qty']
                  . ',' . $values['users__id__sale']
                  . ',' . $values['customers__id']
                  . ',' . $values['is_taxable']
                  . ',' . $values['is_refunded']
                  . ',' . $values['users__id__refund']
                  . ',' . $values['tos']
                  . ',' . $values['tor']
                  . ',' . $values['paid_cash']
                  . ',' . $values['paid_credit']
                  . ',' . $values['paid_check']
                  . ',' . $values['grp']
                  . ',' . $values['is_heading']
                  . ',' . $values['paid_tax']
                  . ',' . $values['check_no']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `pos_transactions`
(`id`,`line_number`,`from_table`,`from_key_name`,`from_key`,`writeback`,`amt`,`descr`,`qty`,`users__id__sale`,`customers__id`,`is_taxable`,`is_refunded`,`users__id__refund`,`tos`,`tor`,`paid_cash`,`paid_credit`,`paid_check`,`grp`,`is_heading`,`paid_tax`,`check_no`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['pos_transactions'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['pos_transactions'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge Cash Log...';
  $MAP['pos_cash_log'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `cash_log`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start += 20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `cash_log`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['pos_cash_log'] + $current_index;
        $new['pos_cash_log'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'amt' => $row['amt']
            , 'reason' => $row['reason']
            , 'ts' => $row['ts']
            , 'is_reset' => $row['is_reset']
            , 'pos_transactions__id' =>
              (
                ( ISSET( $MAP['pos_transactions'][$row['tid']] ) )
                ? $MAP['pos_transactions'][$row['tid']]['id']
                : NULL
              )
            , 'is_checks' => $row['is_checks']
            , 'is_drop' => $row['is_drop']
            , 'is_deposited' => $row['deposited']
            , 'org_entities__id' =>
              (
                ( ISSET( $MAP['org_entities'][$ref['locations']['is_here']] ) )
                ? $MAP['org_entities'][$ref['locations']['is_here']]['id']
                : NULL
              )
            , 'old_id' => $row['log_id']
            );
        $MAP['pos_cash_log'][$row['log_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['pos_cash_log'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['pos_cash_log'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['users__id']
                  . ',' . $values['amt']
                  . ',' . $values['reason']
                  . ',' . $values['ts']
                  . ',' . $values['is_reset']
                  . ',' . $values['pos_transactions__id']
                  . ',' . $values['is_checks']
                  . ',' . $values['is_drop']
                  . ',' . $values['is_deposited']
                  . ',' . $values['org_entities__id']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `pos_cash_log`
(`id`,`users__id`,`amt`,`reason`,`ts`,`is_reset`,`pos_transactions__id`,`is_checks`,`is_drop`,`is_deposited`,`org_entities__id`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['pos_cash_log'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['pos_cash_log'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Preparing to Merge User Notes...';
  $MAP['user_notes'] = ARRAY();
  $row_count = 0;
  $sql = <<<EOMYSQL
--
SELECT
  COUNT(*)
FROM
  `notes`
WHERE
  1;
--
EOMYSQL;
  IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
    _clean_up();
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
  IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) $row_count = MYSQL_RESULT( $query_result , 0 );
  FOR ( $limit_start = 0 ; 0 < $row_count ; $row_count -= 20 , $limit_start +=20 ) {
    $ACTIONS[] = 'Getting a Batch...';
    $sql = <<<EOMYSQL
--
SELECT
  *
FROM
  `notes`
WHERE
  1
LIMIT {$limit_start} , 20;
--
EOMYSQL;
    IF ( FALSE === ( $query_result = MYSQL_QUERY( $sql , $DB1 ) ) ) {
      $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
      _clean_up();
      _output_error_default_html();
      EXIT;
    }
    $RESULTS[] = 'Done.';
    IF ( 0 < MYSQL_NUM_ROWS( $query_result ) ) {
      $ACTIONS[] = 'Processing Batch...';
      $current_index = 0;
      WHILE( FALSE !== ( $row = MYSQL_FETCH_ASSOC( $query_result ) ) ) {
        $current_id = $counters['user_notes'] + $current_index;
        $new['user_notes'][$current_index] = ARRAY
            ( 'id' => $current_id
            , 'for_table' =>
              (
                ( ISSET( $MAP['tables'][$row['for_table']] ) )
                ? $MAP['tables'][$row['for_table']]['name']
                : NULL
              )
            , 'for_key' =>
              (
                (
                  ISSET( $row['for_table'] )
                  AND ISSET( $row['for_key'] )
                  AND ISSET( $MAP[ $MAP['tables'][ $row['for_table'] ]['name'] ][ $row['for_key'] ] )
                )
                ? $MAP[$MAP['tables'][$row['for_table']]['name']][$row['for_key']]['id']
                : NULL
              )
            , 'note' => $row['note']
            , 'users__id' =>
              (
                ( ISSET( $MAP['users'][$row['user_id']] ) )
                ? $MAP['users'][$row['user_id']]['id']
                : NULL
              )
            , 'note_ts' => $row['note_ts']
            , 'old_id' => $row['note_id']
            );
        $MAP['user_notes'][$row['note_id']]['id'] = $current_id;
        $current_index++;
      }
      MYSQL_FREE_RESULT( $query_result );
      IF ( ISSET( $new['user_notes'] ) ) {
        $rows = ARRAY();
        FOREACH ( $new['user_notes'] AS $row ) {
          $values = ARRAY();
          FOREACH ( $row AS $key => $value ) {
            SWITCH ( GETTYPE( $value ) ) {
              CASE 'boolean':
                $values[$key] = (int) $value;
                BREAK;
              CASE 'integer':
              CASE 'double':
                $values[$key] = $value;
                BREAK;
              CASE 'string':
                $values[$key] = "'" . MYSQL_REAL_ESCAPE_STRING( $value ) . "'";
                BREAK;
              CASE 'NULL':
              DEFAULT:
                $values[$key] = 'NULL';
                BREAK;
            }
          }
          $rows[] = '(' . $values['id']
                  . ',' . $values['for_table']
                  . ',' . $values['for_key']
                  . ',' . $values['note']
                  . ',' . $values['users__id']
                  . ',' . $values['note_ts']
                  . ')';
        }
        $rows = IMPLODE( ',' , $rows );
        $sql = <<<EOMYSQL
--
INSERT INTO `user_notes`
(`id`,`for_table`,`for_key`,`note`,`users__id`,`note_ts`)
VALUES
{$rows};
--
EOMYSQL;
        MYSQL_QUERY( $sql , $DB2 );
        IF ( -1 == ( $affected = MYSQL_AFFECTED_ROWS( $DB2 ) ) ) {
          $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not execute the query: ' . $sql;
          _clean_up();
          _output_error_default_html();
          EXIT;
        }
        $counters['user_notes'] += $affected;
        $RESULTS[] = 'Done.';
      }
    }
    UNSET( $new['user_notes'] );
  }
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  _enable_foreign_key_checks( $DB2 );
  _enable_trigger_checks( $DB2 );
}

FUNCTION _clean_up() {
  GLOBAL $ACTIONS , $RESULTS , $DB1 , $DB2;
  $tdbname = "`" . MYSQL_REAL_ESCAPE_STRING( $_REQUEST['tdbname'] ) . "`";
  $sql = <<<EOMYSQL
--
DROP DATABASE {$tdbname};
--
EOMYSQL;
  MYSQL_QUERY( $sql , $DB1 );
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
  GLOBAL $ACTIONS , $RESULTS , $MAX_FILE_SIZE, $MAX_FILE_MEGS;
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
  <td class="heading" align="right">SQL Dump File:</td>
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
}

# ----------------------------------------------------------------- EOF
?>
