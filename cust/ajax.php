<?php

if (!isset($_GET["cmd"])) exit;

require_once("../mysql_connect.php");
require_once("../core/sessions.php");
require_once("../core/common.php");

if (!isset($USER)) { exit; }

if (isset($_GET["sb"])) {
	$sb = intval($_GET["sb"]);
	switch ($sb) {
		case 1: $searchby = "id";  	break;
		case 2: $searchby = "firstname";    	break;
		case 3: $searchby = "lastname";	    	break;
		case 4: $searchby = "phone";	       	break;
		case 5: $searchby = "descr";	       	break;
		case 6: $searchby = "fullname";	    	break;
		case 7: $searchby = "issue_id";	    	break;
		case 8: $searchby = "company";			break;
	}
}

function escape($str) {
	return str_replace("'","\\'",$str);
}

switch ($_GET["cmd"]) {
	case "validate":
		if (!isset($_GET["address"]) || !isset($_GET["zip"])) {
			echo "{action:'error',error:'Address and Zipcode are required for address validation.'}";
			exit;
		}
		include "usps.php";
		$x = address_validate($_GET["address"],$_GET["address2"],$_GET["city"],$_GET["state"],$_GET["zip"]);
		//print_r($x);
		//exit;
		if (!$x) {
			echo "{action:'notfound'}";
			exit;
		}
		if ($x == "ERROR-CONNECT") {
			echo "{action:'error',error:'Unable to connect to third-party validator. Please notify the developer.'}";
			exit;
		}
		if ($x == "ERROR-NODATA") {
			echo "{action:'error',error:'Third-party validator neither validated nor rejected the address. Please notify the developer, this is an error condition.'}";
			exit;
		}
		if (!isset($x["address"])) $x["address"] = "";
		if (!isset($x["address2"])) $x["address2"] = "";
		if (!isset($x["city"])) $x["city"] = "";
		if (!isset($x["state"])) $x["state"] = "";
		if (!isset($x["zip"])) $x["zip"] = "";
		if (!isset($x["zip4"])) $x["zip4"] = "";
		echo "{action:'validated',address:'".escape($x["address"])."',address2:'".escape($x["address2"])."',city:'".escape($x["city"])."',state:'".escape($x["state"])."',zip:'".escape($x["zip"])."',zip4:'".escape($x["zip4"])."'}";
		exit;
		break;
	case "cookie_refresh":
		mysql_query("UPDATE sessions SET last = NOW() WHERE id = '{$SESSION["id"]}'");
  case "cookie":
    $result = mysql_query("SELECT last FROM sessions WHERE id= '".$SESSION["id"]."'");
    if (!mysql_num_rows($result)) die("{\"remaining\":0}");
    $var = mysql_fetch_assoc($result);
    $date = strtotime($var["last"]);
    $diff = time() - $date;
    $var = ($USER["timeout"] - $diff);
    echo '{';
    echo '"diff":"'.$diff.'"';echo ',';
    echo '"remaining":"'.$var.'"';
    echo '}';
  break;
	case "search": // Regular search
		if ($searchby == "phone") {
			$search_string = strtoupper($_GET["str"]);
			$search_string = str_replace("-","",$search_string);
			$search_string = str_replace(" ","",$search_string);
			$result = mysql_query("SELECT * FROM customers WHERE phone_home LIKE '%". mysql_real_escape_string($search_string) ."%' OR phone_cell LIKE '%". mysql_real_escape_string($search_string) ."%' LIMIT 10") or die("Error: ". mysql_error());
		} elseif($searchby == "fullname") {
			$search_string = strtoupper($_GET["str"]);
      		$search_string = explode(" ", $search_string);
      		if (count($search_string) == 2) {
				$data = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM customers WHERE (UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%' AND UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[1])."%') OR (UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[0])."%' OR UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%')"));
				$count = $data["count"];
				$result = mysql_query("SELECT * FROM customers WHERE (UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%' AND UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[1])."%') OR (UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[0])."%' OR UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%') LIMIT 10") or die("Error: ". mysql_error());
      		} else {
      			$data = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM customers WHERE (UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%' OR UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[0])."%')"));
      			$count = $data["count"];
      			$result = mysql_query("SELECT * FROM customers WHERE (UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%' OR UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[0])."%') LIMIT 10");
      		}
		} elseif($searchby == "company") {
			$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
      		$data = mysql_fetch_assoc(mysql_query("SELECT COUNT(*) AS count FROM customers WHERE UPPER(company) LIKE '%".$search_string."%'"));
      		$count = $data["count"];
      		$result = mysql_query("SELECT * FROM customers WHERE UPPER(company) LIKE '%".$search_string."%' LIMIT 10");
		} elseif($searchby == "issue_id") {
			$search_string = intval($_GET["str"]);
			$result = mysql_query("SELECT * FROM issues WHERE org_entities__id = {$USER['org_entities__id']} AND id LIKE '".$search_string."%' LIMIT 10") or die("Error: ". mysql_error());
			if (!mysql_num_rows($result)) {
				header($_SERVER['SERVER_PROTOCOL'] . ' 500 No Matches Found', true, 500);
				exit;
			}
			echo "<div align=\"center\">\n";
			while ($row = mysql_fetch_assoc($result)) {
				echo alink($row["issue_id"],"?module=iss&do=view&id=".$row["issue_id"]) ."<br>\n";
			}
			echo "</div>\n";
      		exit;
		} else {
			$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
			$result = mysql_query("SELECT * FROM customers WHERE UPPER(".$searchby.") LIKE '%". $search_string ."%' LIMIT 10") or die("Error: ". mysql_error());
		}
		if (mysql_num_rows($result) < 1) {
			echo "No matches.";
			exit;
		}
		echo "<table border=\"0\" width=\"100%\">";
		while ($row = mysql_fetch_assoc($result)) {
      echo "<tr><td>";
	     if(isset($_GET["inv"]) && $_GET["inv"]==1){
          echo "<a href=\"?module=invoice&do=create&customer_id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
        } elseif(isset($_GET["acci"]) && $_GET["acci"]==1){
          echo "<a href=\"?module=acct&do=new_account&customer=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
          if ($searchby == "company") {
				echo "</td><td>{$row["company"]}";
          }
        } elseif(isset($_GET["page"]) && $_GET["page"]=="invoice"){
          echo "<a href=\"?module=invoice&do=create&customer_id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
          if ($searchby == "company") {
          	echo "</td><td>{$row["company"]}";
          }
        } elseif(isset($_GET["page"]) && $_GET["page"] == "areplace") {
        	echo alink_onclick($row["firstname"] ." ". $row["lastname"],"?module=acct&do=replace_cust&account_id=".$_GET["aid"]."&customer_id=".$row["id"],"javascript:return confirm('Are you sure you want to do that?');");
        	echo "</td><td>{$row["company"]}";
        } elseif($searchby == "company") {
        	echo "<a href=\"?module=cust&do=view&id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a></td><td>{$row["company"]}";
        } else {
          echo "<a href=\"?module=cust&do=view&id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
        }
			echo "</td><td>". display_phone($row['phone_home']) ."</td>";
			echo "<td>". display_phone($row['phone_cell']) ."</td></tr>\n";
		}
		echo "</table>";
		if (isset($count) && $count > 10) {
			echo "<div align=\"center\"><a href=\"?module=cust&do=search&sb=$sb&str={$_GET["str"]}\">Show All $count Results</a></div>";
		}
		break;
	case "asearch": // Account-creation search
			if ($searchby == "phone") {
	  		$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
  			$search_string = str_replace("-","",$search_string);
	 		  $search_string = str_replace(" ","",$search_string);
	 	   	$result = mysql_query("SELECT * FROM customers WHERE UPPER(phone_home) LIKE '%". $search_string ."%' OR UPPER(phone_cell) LIKE '%". $search_string ."%' LIMIT 10") or die("Error: ". mysql_error());
		  } else {
				$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
				$result = mysql_query("SELECT * FROM customers WHERE UPPER(".$searchby.") LIKE '%". $search_string ."%' LIMIT 10") or die("Error: ". mysql_error());
			}
			if (mysql_num_rows($result) < 1) {
				echo "No matches.";
				exit;
			}
			echo "<table border=\"0\" width=\"100%\">";
			while ($row = mysql_fetch_assoc($result)) {
				echo "<tr><td>";
          echo "<a href=\"?module=acct&do=new_account&customer=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
				echo "</td><td>". display_phone($row['phone_home']) ."</td>";
				echo "<td>". display_phone($row['phone_cell']) ."</td></tr>\n";
			}
			echo "</table>";
			break;
	case "psearch": // Point-of-Sale
		if ($searchby == "phone") {
			$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
			$search_string = str_replace("-","",$search_string);
			$search_string = str_replace(" ","",$search_string);
			$result = mysql_query("SELECT * FROM customers WHERE UPPER(phone_home) LIKE '%". $search_string ."%' OR UPPER(phone_cell) LIKE '%". $search_string ."%' LIMIT 10") or die("Error: ". mysql_error());
		} elseif($searchby == "fullname") {
	 		  $search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
        $search_string = explode(" ", $search_string);
		  	$result = mysql_query("SELECT * FROM customers WHERE
        (UPPER(firstname) LIKE '%".$search_string[0]."%' AND UPPER(lastname) LIKE '%".$search_string[1]."%')
          OR
        (UPPER(lastname) LIKE '%".$search_string[0]."%' OR UPPER(firstname) LIKE '%".$search_string[0]."%')
         LIMIT 10") or die("Error: ". mysql_error());
		} else {
			$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
			$result = mysql_query("SELECT * FROM customers WHERE UPPER(".$searchby.") LIKE '%". $search_string ."%' LIMIT 10") or die("Error: ". mysql_error());
		}
		if (mysql_num_rows($result) < 1) {
			echo "No matches.";
			exit;
		}
		echo "<table border=\"0\" width=\"100%\">";
		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr><td>";
			echo "<a href=\"?module=pos&do=set_customer&id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
			echo "</td><td>". display_phone($row['phone_home']) ."</td>";
			echo "<td>". display_phone($row['phone_cell']) ."</td></tr>\n";
		}
		echo "</table>";
		break;

	case "pbsearch": // Point-of-Sale
		if ($searchby == "descr") {
			$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
			$result = mysql_query("SELECT * FROM inventory WHERE org_entities__id = {$USER['org_entities__id']} AND UPPER(".$searchby.") LIKE '%". $search_string ."%' LIMIT 8") or die("Error: ". mysql_error());
		}
		if (mysql_num_rows($result) < 1) {
			echo "No matches.";
			exit;
		}
		echo "<table border=\"0\" width=\"100%\" style=\"text-align: center\">";
			echo "<tr>";
      echo "<th>ID</th>";
      echo "<th>UPC</th>";
      echo "<th>Descr.</th>";
      echo "<th>Our Cost</th>";
      echo "<th>Price</th>";
      echo "<th>Quantity</th>";
      echo "<th>Tax</th>";
      echo "</tr>\n";
		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr>";
      echo "<td><a href=\"#search\" onClick=\"addItem('".$row['descr']."','".$row['cost']."','".$row['qty']."','".$row['is_taxable']."')\">". $row['id'] ."</a></td>";
      echo "<td><a href=\"#search\" onClick=\"addItem('".$row['descr']."','".$row['cost']."','".$row['qty']."','".$row['is_taxable']."')\">". $row['upc'] ."</a></td>";
      echo "<td><a href=\"#search\" onClick=\"addItem('".$row['descr']."','".$row['cost']."','".$row['qty']."','".$row['is_taxable']."')\">". $row['descr'] ."</a></td>";
      echo "<td><a href=\"#search\" onClick=\"addItem('".$row['descr']."','".$row['cost']."','".$row['qty']."','".$row['is_taxable']."')\">". $row['purchase_price'] ."</a></td>";
      echo "<td><a href=\"#search\" onClick=\"addItem('".$row['descr']."','".$row['cost']."','".$row['qty']."','".$row['is_taxable']."')\">". $row['cost'] ."</a></td>";
      echo "<td><a href=\"#search\" onClick=\"addItem('".$row['descr']."','".$row['cost']."','".$row['qty']."','".$row['is_taxable']."')\">". $row['qty'] ."</a></td>";
      echo "<td><a href=\"#search\" onClick=\"addItem('".$row['descr']."','".$row['cost']."','".$row['qty']."','".$row['is_taxable']."')\">". $row['is_taxable'] ."</a></td>";
      echo "</tr>\n";
		}
		echo "</table>";
		break;
	case "isearch": // From an issue screen
		$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
		$search_string = str_replace("-","",$search_string);
		$search_string = str_replace(" ","",$search_string);
		$result = mysql_query("SELECT * FROM customers WHERE UPPER(phone_home) LIKE '%". $search_string ."%' OR UPPER(phone_cell) LIKE '%". $search_string ."%' LIMIT 10") or die("Error: ". mysql_error());
		if (mysql_num_rows($result) < 1) {
			echo "No matches.";
			exit;
		}
		echo "<table border=\"0\" width=\"100%\">";
		while ($row = mysql_fetch_assoc($result)) {
			echo "<tr><td>";
			echo "<a href=\"#\" onClick=\"set_customer('". $row['id'] ."','". $row['firstname'] ." ". $row['lastname'] ."')\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
			echo "</td><td>". display_phone($row['phone_home']) ."</td>";
			echo "<td>". display_phone($row['phone_cell']) ."</td></tr>\n";
		}
		echo "</table>";
		break;
	case "zipsearch": // Zip code lookup when adding customers
    echo file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?address=". intval($_GET["str"]) ."&sensor=false");
	break;
	case "notepad":
    echo "


    <form action=\"#notepad_submitted\" class=\"fixed\" method=\"post\">
    <div class=\"relative center\">
      <div class=\"relative floatL clear center\">
        <textarea name=\"notepad_textarea\" id=\"notepad_textarea\">". ($USER["notepad"] ? $USER["notepad"] : "No notes") ."</textarea>
      </div>
      <div class=\"relative clear center\">
        <button>Update/Save Notepad</button>
      </div>
    </div>
    </form>
    <div class=\"absolute\" style=\"margin-left:15px;bottom:5px;\">
      <a href=\"#close\" onClick=\"notepad()\"><img src=\"images/black-x-small.png\"></a>
    </div>";
	break;
	case "notepad_update":
	  mysql_query("UPDATE users SET notepad='". mysql_real_escape_string($_GET["str"]) ."' WHERE org_entities__id = {$USER['org_entities__id']} AND id=".$USER["id"]);
    echo $_GET["str"];
	break;
	default:
		exit;
		break;
}

?>