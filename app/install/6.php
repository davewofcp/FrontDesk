<?php

$REQUIRED = ARRAY
  ( 'dbhost'
  , 'dbpuser'
  , 'dbppass'
  , 'dbname'
  , 'adminuser'
  , 'adminpass'
  , 'deftax'
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

_connect_to_database_server();

_select_created_database();

_process_skeleton_inserts();

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
<form action="7.php" method="post">
<p id="messages">{$messages}</p>
<input type="hidden" name="dbhost" value="{$_REQUEST['dbhost']}">
<input type="hidden" name="dbpuser" value="{$_REQUEST['dbpuser']}">
<input type="hidden" name="dbppass" value="{$_REQUEST['dbppass']}">
<input type="hidden" name="dbname" value="{$_REQUEST['dbname']}">
<table border="0">
 <tr>
  <td colspan="3" align="center">
    <p>Basic installation is complete.</p>
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

FUNCTION _select_created_database() {
  GLOBAL $ACTIONS , $RESULTS;
  $dbname = $_REQUEST['dbname'];
  $ACTIONS[] = 'Activating Database...';
  IF ( FALSE === MYSQL_SELECT_DB( $dbname ) ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not make the application database active.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done.';
}

FUNCTION _process_skeleton_inserts() {
  GLOBAL $ACTIONS , $RESULTS;
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Application Levels...';
  $sql = <<<EOMYSQL
--
INSERT INTO app_levels
(id,title)
VALUES
(1,'Application'),
(2,'Organization'),
(3,'Entity'),
(4,'Client');
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Organization Structure Hierarchy Types...';
  $sql = <<<EOMYSQL
--
INSERT INTO org_struct_types
(id,title)
VALUES
(1,'Administrative');
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Organization Structure Groups...';
  $sql = <<<EOMYSQL
--
INSERT INTO org_struct_groups
(id,title,org_struct_types__id,parent_id)
VALUES
(1,'Organization',1,NULL);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Organization Structures...';
  $sql = <<<EOMYSQL
--
INSERT INTO org_structs
(id,title,org_struct_groups__id,parent_id)
VALUES
(1,'Organization',1,NULL);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Organization Entity Types...';
  $sql = <<<EOMYSQL
--
INSERT INTO org_entity_types
(id,title)
VALUES
(1,'Organization'),
(2,'Store');
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Organization Entities...';
  $orgtitle = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgtitle'] );
  $orgloc = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgloc'] );
  $orgaddr = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgaddr'] );
  $orgcity = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgcity'] );
  $orgstate = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgstate'] );
  $orgcntry = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgcntry'] );
  $orgpost = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgpost'] );
  $orgphone = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgphone'] );
  $orgfax = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['orgfax'] );
  $tax_rate = MYSQL_REAL_ESCAPE_STRING( FLOATVAL( $_REQUEST["deftax"] ) / 100 );
  $sql = <<<EOMYSQL
--
INSERT INTO org_entities
(id,title,location_code,address,city,state,country,postcode,phone,fax,tax_rate,org_entity_types__id)
VALUES
(1,'{$orgtitle}','{$orgloc}','{$orgaddr}','{$orgcity}','{$orgstate}','{$orgcntry}','{$orgpost}','{$orgphone}','{$orgfax}','{$tax_rate}',1);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Creating Organization Cross-references...';
  $sql = <<<EOMYSQL
--
INSERT INTO xref__org_entities__org_structs
(id,org_entities__id,org_structs__id)
VALUES
(1,1,1);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Categories...';
  $sql_last_insert_id = <<<EOMYSQL
--
SELECT MAX(id)
FROM categories
WHERE 1
--
EOMYSQL;
  $count = 0;
  $sql = <<<EOMYSQL
--
INSERT INTO categories
(category_set,category_name,parent_id)
VALUES
('inventory','Battery',NULL),
('inventory','CPU/Processor',NULL);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $count += MYSQL_AFFECTED_ROWS();
  $res = MYSQL_QUERY( $sql_last_insert_id );
  $cpu = MYSQL_RESULT( $res , 0 );
  $sql = <<<EOMYSQL
--
INSERT INTO categories
(category_set,category_name,parent_id)
VALUES
('inventory','AMD',{$cpu}),
('inventory','Intel',{$cpu}),
('inventory','Desktop',NULL),
('inventory','External Hard Drive',NULL),
('inventory','GPS',NULL),
('inventory','Hard Drive',NULL),
('inventory','Heatsink',NULL),
('inventory','Keyboard/Mouse',NULL),
('inventory','Laptop',NULL),
('inventory','RAM',NULL);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $count += MYSQL_AFFECTED_ROWS();
  $res = MYSQL_QUERY( $sql_last_insert_id );
  $ram = MYSQL_RESULT( $res , 0 );
  $sql = <<<EOMYSQL
--
INSERT INTO categories
(category_set,category_name,parent_id)
VALUES
('inventory','1GB',{$ram}),
('inventory','2GB',{$ram}),
('inventory','Monitor',NULL),
('inventory','Motherboard',NULL),
('inventory','Other',NULL),
('inventory','Power Supply',NULL),
('inventory','Printer',NULL),
('inventory','Projector/Screen',NULL),
('inventory','Scanner',NULL),
('inventory','Software',NULL),
('inventory','Sound Card',NULL),
('inventory','Tablet',NULL),
('inventory','USB Flash Drive',NULL),
('inventory','Video/Graphics Card',NULL),
('inventory','Webcam',NULL),
('inventory','Wireless/Networking',NULL);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $count += MYSQL_AFFECTED_ROWS();
  $RESULTS[] = 'Done. (' . $count . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Option Values...';
  $sql = <<<EOMYSQL
--
INSERT INTO option_values
(category,value)
VALUES
('referall_location','Better Business Bureau'),
('referall_location','Consumer Pack'),
('referall_location','Craigslist'),
('referall_location','Customer Recommendation'),
('referall_location','Driving By'),
('referall_location','Google Local'),
('referall_location','Google Review'),
('referall_location','Google.com'),
('referall_location','Internet Search'),
('referall_location','Live Chat'),
('referall_location','Local.com'),
('referall_location','Localedge Yellow Pages'),
('referall_location','Magicyellow.com'),
('referall_location','Mail Flyer'),
('referall_location','Newspaper Ad'),
('referall_location','Other'),
('referall_location','Return Customer'),
('referall_location','St. Rose'),
('referall_location','superpages.com'),
('referall_location','Times Union'),
('referall_location','UAlbany Couponbook'),
('referall_location','Verizon Yellow Pages'),
('referall_location','Walk By'),
('referall_location','Yahoo Local'),
('referall_location','None'),
('referall_location','Our Sign'),
('referall_location','Mildred Elley'),
('referall_location','Business Card handout'),
('manufacturer','Unknown'),
('os','Unknown');
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Modules...';
  $sql = <<<EOMYSQL
--
INSERT INTO modules
(module,title,`version`,is_default,in_nav)
VALUES
('org','Organization','1.0',0,0),
('admin','Administration','1.0',0,1),
('core','System','1.0',0,1),
('cust','Customers','1.0',0,1),
('inventory','Inventory','1.0',0,1),
('iss','Issues','1.0',1,1),
('orders','Orders','1.0',0,1),
('pos','Point-of-Sale','1.0',0,1),
('msg','Messaging','1.0',0,1),
('invoice','Invoices','1.0',0,1),
('acct','Accounts','1.0',0,1),
('cal','Calendar','1.0',0,1),
('bugs','Bugs','1.0',0,1),
('time','Timesheets','1.0',0,1);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Module Permissions Definitions...';
  $sql = <<<EOMYSQL
--
INSERT INTO perms
(title,module,action,bitmask)
VALUES
('Use Organization Module','org','use',1),
('Use Administration Module','admin','use',1),
('Use System Module','core','use',1),
('Use Customers Module','cust','use',1),
('Use Inventory Module','inventory','use',1),
('Use Issues Module','iss','use',1),
('Use Orders Module','orders','use',1),
('Use Point-of-Sale Module','pos','use',1),
('Use Messaging Module','msg','use',1),
('Use Invoices Module','invoice','use',1),
('Use Accounts Module','acct','use',1),
('Use Calendar Module','cal','use',1),
('Use Bugs Module','bugs','use',1),
('Use Timesheets Module','time','use',1);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Creating Organization Self-Reference Customer...';
  $sql = <<<EOMYSQL
--
INSERT INTO customers
(id,firstname,lastname,is_subscribed)
VALUES
(1,'Organization','Customer',0);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding User Roles...';
  $sql = <<<EOMYSQL
--
INSERT INTO user_roles
(id,title,app_levels__id)
VALUES
(1,'Application Administrator',1),
(2,'Organization Administrator',2),
(3,'Store Manager',3),
(4,'Regular Employee',3),
(5,'Intern',3);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Setting User Role Permissions...';
  $sql = <<<EOMYSQL
--
INSERT INTO user_roles_perms
(user_roles__id,module,bitfield)
VALUES
(1,'org',1),
(1,'admin',1),
(1,'core',1),
(1,'cust',1),
(1,'inventory',1),
(1,'iss',1),
(1,'orders',1),
(1,'pos',1),
(1,'msg',1),
(1,'invoice',1),
(1,'acct',1),
(1,'cal',1),
(1,'bugs',1),
(1,'time',1),
(2,'org',1),
(2,'admin',1),
(2,'core',1),
(2,'cust',1),
(2,'inventory',1),
(2,'iss',1),
(2,'orders',1),
(2,'pos',1),
(2,'msg',1),
(2,'invoice',1),
(2,'acct',1),
(2,'cal',1),
(2,'bugs',1),
(2,'time',1),
(3,'org',0),
(3,'admin',1),
(3,'core',1),
(3,'cust',1),
(3,'inventory',1),
(3,'iss',1),
(3,'orders',1),
(3,'pos',1),
(3,'msg',1),
(3,'invoice',1),
(3,'acct',1),
(3,'cal',1),
(3,'bugs',1),
(3,'time',1),
(4,'org',0),
(4,'admin',0),
(4,'core',1),
(4,'cust',1),
(4,'inventory',1),
(4,'iss',1),
(4,'orders',1),
(4,'pos',1),
(4,'msg',1),
(4,'invoice',1),
(4,'acct',1),
(4,'cal',1),
(4,'bugs',1),
(4,'time',1),
(5,'org',0),
(5,'admin',0),
(5,'core',1),
(5,'cust',1),
(5,'inventory',1),
(5,'iss',1),
(5,'orders',1),
(5,'pos',1),
(5,'msg',1),
(5,'invoice',1),
(5,'acct',1),
(5,'cal',1),
(5,'bugs',1),
(5,'time',1);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Creating Organization Administrator...';
  $length = 10;
  $characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $string = "";
  FOR ( $p = 0 ; $p < $length ; $p++ ) {
    $string .= $characters[ MT_RAND( 0 , STRLEN( $characters ) - 1 ) ];
  }
  $salt = $string;
  $username = MYSQL_REAL_ESCAPE_STRING( $_REQUEST['adminuser'] );
  $password = MYSQL_REAL_ESCAPE_STRING( MD5( $_REQUEST["adminpass"] . $salt ) );
  $sql = <<<EOMYSQL
--
INSERT INTO users
(id,org_entities__id,user_roles__id,username,`password`,salt,firstname,lastname,timeout)
VALUES
(2,1,2,'{$username}','{$password}','{$salt}','Organization','Admin',1800);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Setting Default User Permissions...';
  $sql = <<<EOMYSQL
--
INSERT INTO user_perms
(users__id,module)
VALUES
(2,'org'),
(2,'admin'),
(2,'core'),
(2,'cust'),
(2,'inventory'),
(2,'iss'),
(2,'orders'),
(2,'pos'),
(2,'msg'),
(2,'invoice'),
(2,'acct'),
(2,'cal'),
(2,'bugs'),
(2,'time');
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Services...';
  $sql = <<<EOMYSQL
--
INSERT INTO services
(name,cost)
VALUES
('Light Work/Tune-Up',95),
('Keyboard Replacement',95),
('Virus Removal',135),
('Full Reinstall',135),
('Full Reinstall & Win 7',150),
('Full Reinstall w/ Backup',150),
('Full Reinstall & Win 7 w/ Backup',225),
('Replace Hard Drive',225),
('Power Supply',95),
('RAM Upgrade',90),
('Replace CD/DVD',95),
('Replace Power Jack',150),
('Replace Motherboard (Laptop)',300),
('Replace Motherboard (Desktop)',200),
('Replace Motherboard (Netbook)',150),
('Board Level Repair',200),
('Reball',275),
('Bezzle Work',85),
('Screen Replacement (Laptop)',180),
('Screen Replacement (Netbook)',145),
('Data Backup',95),
('Phone Screen and Digitizer Repair',95),
('Data Recovery (Good Drive)',185),
('Data Recovery (Damaged)',250),
('Warranty',0);
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
  # - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  $ACTIONS[] = 'Adding Inventory Locations...';
  $sql = <<<EOMYSQL
--
INSERT INTO inventory_locations
(title)
VALUES
('Nowhere');
--
EOMYSQL;
  MYSQL_QUERY( $sql );
  IF ( -1 == MYSQL_AFFECTED_ROWS() ) {
    $RESULTS[] = 'Error: ' . MYSQL_ERROR() . ' :: Could not complete insert operation.';
    _output_error_default_html();
    EXIT;
  }
  $RESULTS[] = 'Done. (' . MYSQL_AFFECTED_ROWS() . ' rows affected)';
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
  <td><input type="edit" name="deftax" size="3" value="{$_REQUEST['deftax']}">% *</td>
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
