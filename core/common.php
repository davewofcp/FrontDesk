<?php

# PERMISSIONS CONSTANTS

DEFINE('TFD_PERM_ORG_USE',1);
DEFINE('TFD_PERM_ADMIN_USE',1);
DEFINE('TFD_PERM_CORE_USE',1);
DEFINE('TFD_PERM_CUST_USE',1);
DEFINE('TFD_PERM_INVENTORY_USE',1);
DEFINE('TFD_PERM_ISS_USE',1);
DEFINE('TFD_PERM_ORDERS_USE',1);
DEFINE('TFD_PERM_POS_USE',1);
DEFINE('TFD_PERM_MSG_USE',1);
DEFINE('TFD_PERM_INVOICE_USE',1);
DEFINE('TFD_PERM_ACCT_USE',1);
DEFINE('TFD_PERM_CAL_USE',1);

# ARRAYS

$STATUS_CHG = array(
	array(0),               //0 Not Used
	array(1,3,9),           //1 Warranty
	array(2,5,7,8,9,10),    //2 Urgent
	array(3,5,8,10),        //3 Diagnosed
	array(4,5,7,8,9),		//4 Parts Received
	array(5,2,7,8,9,10),    //5 Do It
	array(1,3,6),           //6 New
	array(4,5,7,8,9,10),      //7 Waiting (Parts)
	array(5,7,8,9,10),      //8 Waiting (Customer)
	array(1,9),             //9 Finished
	array(5,10),            //10 No Go
);

$ONSITE_STATUS_OPTIONS = array(1,4,5,6,9);

$ONSITE_STATUS_CHG = array(
	array(0),				//0 Not Used			[not used]
	array(1,5,9),			//1 Warranty
	array(0),				//2 Urgent				[not used]
	array(0),				//3 Diagnosed			[not used]
	array(0),				//4 Parts Received
	array(1,5,9),			//5 Do It
	array(1,5,6),			//6 New
	array(0),				//7 Waiting (Parts)		[not used]
	array(0),				//8 Waiting (Customer)	[not used]
	array(1,5,9),			//9 Finished
	array(0),				//10 No Go				[not used]
);

$STATUS = array(
	"None",					    // 0
	"Warranty",				    // 1
	"Urgent",				    // 2
	"Diagnosed",			    // 3
	"Parts Received",			// 4
	"Do It",				    // 5
	"New",					    // 6
	"Waiting (Parts)",		    // 7
	"Waiting (Customer)",	    // 8
	"Finished",				    // 9
	"No Go",				    // 10
);

$ST_COLORS = array(
	"#FFFFFF",           // none
	"#999999",           // warranty
	"#F78181",           // urgent
	"#FFCCFF",           // diagnosed
	"#99FF33",			 // incomplete
	"#81F781",           // do it
	"#81F7F3",           // new
	"#5FB404",           // waiting (parts)
	"#F5A9F2",           // waiting (customer)
	"#F3F781",           // finished
	"#CC9933",           // no go

);

$ISSUE_TYPE = array (
	"None",
	"In-Store",
	"On-Site",
	"Remote Support",
	"Internal",
);

$ORDER_STATUS = array (
	"Unknown",
	"In Transit",
	"Arrived",
	"In Transit (Return)",
	"Returned",
);

$ORDER_SHIPPING_TYPES = array (
	"Other",
	"Overnight",
	"2nd Day Air",
	"Priority",
	"Ground",
);

$ORDER_CARRIERS = array (
	"Other",
	"USPS",
	"UPS",
	"FedEx",
);

$INVENTORY_STATUS = array (
	"New",					// 0
	"Just Arrived",			// 1
	"Broken",				// 2
	"Being Repaired",		// 3
	"Ready for Sale",		// 4
	"On Display",			// 5
	"Junk",					// 6
	"Sold",					// 7
	"On an Invoice"			// 8
);

$INVENTORY_CHANGE_CODE = array (
	"Unknown",				// 0
	"Product Added",		// 1
	"Item Added",			// 2
	"Edited",				// 3
	"Transferred",			// 4
	"Arrived at Store",		// 5
	"Status Changed",		// 6
	"Deleted",				// 7
	"Added Device Info",	// 8
	"Item Deleted",			// 9
	"Log Cleared",			// 10
	"QTY Transferred",		// 11
	"QTY Arrived",			// 12
	"Sold",					// 13
);

$TRANSFER_STATUS = array(
	"Unknown",				// 0
	"Preparing",			// 1
	"Ready to Go",			// 2
	"In Transit",			// 3
	"Dropped Off",			// 4
	"Received",				// 5
);

$INV_REQUEST_STATUS = array(
	"Unknown",				// 0
	"New",					// 1
	"Approved",				// 2
	"Denied",				// 3
);

# CLASSES

class Product {
  public $type;
  public $id;
}

# FUNCTIONS

function alink($text,$href) {
	$link = "<a href=\"". $href ."\" class=\"link\" onMouseOver=\"this.setAttribute('class','link_hover');\" onMouseOut=\"this.setAttribute('class','link');\">".$text."</a>";
	return $link;
}

function alink_onclick($text,$href,$onclick,$red = false) {
	$link = "<a href=\"". $href ."\" class=\"".($red ? "r":"")."link\" onClick=\"". $onclick ."\" onMouseOver=\"this.setAttribute('class','".($red ? "r":"")."link_hover');\" onMouseOut=\"this.setAttribute('class','".($red ? "r":"")."link');\">".$text."</a>";
	return $link;
}

function alink_normal($text,$href) {
	$link = "<a href=\"". $href ."\">".$text."</a>";
	return $link;
}

function alink_plain($text,$href) {
	$link = "<a href=\"". $href ."\" class=\"blueshadow nostyle\">".$text."</a>";
	return $link;
}

function alink_pop($text,$href) {
	$link = "<a href=\"". $href ."\" class=\"link\" onMouseOver=\"this.setAttribute('class','link_hover');\" onMouseOut=\"this.setAttribute('class','link');\" target=\"_blank\">".$text."</a>";
	return $link;
}

function display_phone($phone) {
	switch (strlen($phone)) {
		case 7:
			return substr($phone,0,3) ."-". substr($phone,3,4);
			break;
		case 10:
			return substr($phone,0,3) ."-". substr($phone,3,3) ."-". substr($phone,6,4);
			break;
		default:
			return $phone;
			break;
	}
}

function age($birthday) {
  if ($birthday==NULL) return 'n/a';
	list($year,$month,$day) = explode("-",$birthday);
	$year_diff  = date("Y") - $year;
	$month_diff = date("m") - $month;
	$day_diff   = date("d") - $day;
	if ($day_diff < 0 || $month_diff < 0)
		$year_diff--;
	return $year_diff;
}

function new_salt(/*int*/ $length) {
	$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$string = "";
	for ($p = 0; $p < $length; $p++) {
		$string .= $characters[mt_rand(0, strlen($characters) - 1)];
	}
	return $string;
}

function check_email_address($email) {
	// First, we check that there's one @ symbol,
	// and that the lengths are right.
	if (!preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) {
		// Email invalid because wrong number of characters
		// in one section or wrong number of @ symbols.
		return false;
	}
	// Split it into sections to make life easier
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	for ($i = 0; $i < sizeof($local_array); $i++) {
		if
		(!preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&
				↪'*+\/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/",
				$local_array[$i])) {
			return false;
		}
	}
	// Check if domain is IP. If not,
	// it should be valid domain name
	if (!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1])) {
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2) {
			return false; // Not enough parts to domain
		}
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if
			(!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|
					↪([A-Za-z0-9]+))$/",
					$domain_array[$i])) {
				return false;
			}
		}
	}
	return true;
}

function encodeUpc($type,$id) {
	$upc = "4";
	switch ($type) {
		case "inventory":
			$upc .= "1";
			break;
		case "inventory_item":
			$upc .= "2";
			break;
		case "coupon":
			$upc .= "3";
			break;
		case "price":
			$upc .= "4";
			break;
		case "issue":
			$upc .= "5";
			break;
		case "invoice":
			$upc .= "6";
			break;
		case "device":
			$upc .= "7";
			break;
		case "customer":
			$upc .= "8";
			break;
		case "transfer":
			$upc .= "9";
			break;
		default:
			return false;
	}
	$upc .= str_pad($id,9,"0",STR_PAD_LEFT);
	$upc .= calcUPCCheckDigit($upc);
	return $upc;
}

function calcUPCCheckDigit($upc_code) {
	$odd_total  = 0;
	$even_total = 0;

	for($i=0; $i<11; $i++)
	{
		if((($i+1)%2) == 0) {
			/* Sum even digits */
			$even_total += $upc_code[$i];
		} else {
			/* Sum odd digits */
			$odd_total += $upc_code[$i];
		}
	}

	$sum = (3 * $odd_total) + $even_total;

	/* Get the remainder MOD 10*/
	$check_digit = $sum % 10;

	/* If the result is not zero, subtract the result from ten. */
	return ($check_digit > 0) ? 10 - $check_digit : $check_digit;
}

// UPC or 4XAAAAAAAAAC
function decodeUpc($upc) {
	if (strlen($upc) < 12) return false;
	$product = new Product;
	if (substr($upc,0,1) != "4") {
		$product->type = "upc";
		$product->id = $upc;
		return $product;
	}
	$mod_code = substr($upc,1,1); // X
	switch ($mod_code) {
		case "1":
			$product->type = "inventory";
			break;
		case "2":
			$product->type = "inventory_item";
			break;
		case "3":
			$product->type = "coupon";
			break;
		case "4":
			$product->type = "price";
			break;
		case "5":
			$product->type = "issue";
			break;
		case "6":
			$product->type = "invoice";
			break;
		case "7":
			$product->type = "device";
			break;
		case "8":
			$product->type = "customer";
			break;
		case "9":
			$product->type = "transfer";
			break;
		default:
			return false;
	}
	$product->id = intval(substr($upc,2,9),10);
	return $product;
}

FUNCTION TFD_POPULATE_PERMS() {
  GLOBAL $USER;
  GLOBAL $PERMS;
  # user role

  $user_role = mysql_fetch_assoc(mysql_query("SELECT * FROM user_roles WHERE id = ". $USER["user_roles__id"]));

  # user groups

  $r = mysql_query("SELECT * FROM xref__users__user_groups WHERE users__id = ". $USER["id"]);
  $user_groups = ARRAY();
  WHILE ( FALSE !== ($row = MYSQL_FETCH_ASSOC($r))) $user_groups[] = $row['user_groups__id'];

  # modules

  $r = mysql_query("SELECT * FROM modules WHERE 1");
  $modules = ARRAY();
  WHILE ( FALSE !== ($row = MYSQL_FETCH_ASSOC($r))) $modules[] = $row;

  # perms

  $PERMS = ARRAY();
  FOREACH(ARRAY_KEYS($modules) AS $idx){
    $module = $modules[$idx]['module'];
    $PERMS[$module] = 0;
    $r = mysql_query("SELECT bitfield FROM user_roles_perms WHERE user_roles__id=".$USER["user_roles__id"]." AND module='".$module."'");
    if(0<mysql_num_rows($r)){
      $bitfield = (int) mysql_result($r,0);
      $PERMS[$module] = $bitfield;
    }
    FOREACH($user_groups AS $g) {
      $r = mysql_query("SELECT bitfield FROM user_groups_perms WHERE user_groups__id=".$g." AND module='".$module."'");
      if(0<mysql_num_rows($r)){
        $bitfield = (int) mysql_result($r,0);
        $PERMS[$module] |= $bitfield;
      }
    }
    $row = mysql_fetch_assoc(mysql_query("SELECT bitfield_n,bitfield_y FROM user_perms WHERE users__id=".$USER["id"]." AND module='".$module."'"));
    $bitfield_n = (int) $row['bitfield_n'];
    $bitfield_y = (int) $row['bitfield_y'];
    $PERMS[$module] &= $bitfield_n;
    $PERMS[$module] |= $bitfield_y;
  } //loop to get perms for next module
}

FUNCTION TFD_HAS_PERMS() {
  GLOBAL $PERMS;
  $result = FALSE;
  $args = FUNC_GET_ARGS();
  $module = STRTOLOWER( ARRAY_SHIFT($args) );
  FOREACH ( $args AS $arg ) {
    $perm = 'TFD_PERM_' . STRTOUPPER( $module ) . '_' . STRTOUPPER( $arg );
    $bitmask = constant( $perm );
    $result = ( $PERMS[$module] & $bitmask ) == $bitmask;
    IF (!$result) BREAK;
  }
  RETURN $result;
}

?>