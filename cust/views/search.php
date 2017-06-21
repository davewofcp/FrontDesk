<h2>Searching for "<?php echo $_GET["str"]; ?>"</h2>
<?php

$sb = intval($_GET["sb"]);

$searchby = "lastname";
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

if ($searchby == "phone") {
	$search_string = strtoupper($_GET["str"]);
	$search_string = str_replace("-","",$search_string);
	$search_string = str_replace(" ","",$search_string);
	$result = mysql_query("SELECT * FROM customers WHERE phone_home LIKE '%". mysql_real_escape_string($search_string) ."%' OR phone_cell LIKE '%". mysql_real_escape_string($search_string) ."%'") or die("Error: ". mysql_error());
} elseif($searchby == "fullname") {
	$search_string = strtoupper($_GET["str"]);
	$search_string = explode(" ", $search_string);
	if (count($search_string) == 2)
		$result = mysql_query("SELECT * FROM customers WHERE (UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%' AND UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[1])."%') OR (UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[0])."%' OR UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%')") or die("Error: ". mysql_error());
	else
		$result = mysql_query("SELECT * FROM customers WHERE (UPPER(firstname) LIKE '%".mysql_real_escape_string($search_string[0])."%' OR UPPER(lastname) LIKE '%".mysql_real_escape_string($search_string[0])."%')");
} elseif($searchby == "issue_id") {
	$search_string = intval($_GET["str"]);
	$result = mysql_query("SELECT * FROM issues WHERE org_entities__id = {$USER['org_entities__id']} AND id LIKE '".$search_string."%'") or die("Error: ". mysql_error());
	if (!mysql_num_rows($result)) {
		echo "No matches.";
		display_footer();
		exit;
	}
	echo "<div align=\"center\">\n";
	while ($row = mysql_fetch_assoc($result)) {
		echo alink($row["id"],"?module=iss&do=view&id=".$row["id"]) ."<br>\n";
	}
	echo "</div>\n";
	exit;
} else {
	$search_string = mysql_real_escape_string(strtoupper($_GET["str"]));
	$result = mysql_query("SELECT * FROM customers WHERE UPPER(".$searchby.") LIKE '%". $search_string ."%'") or die("Error: ". mysql_error());
}
if (mysql_num_rows($result) < 1) {
	echo "No matches.";
	exit;
}

echo "<table border=\"0\">";
while ($row = mysql_fetch_assoc($result)) {
	echo "<tr><td>";
	if(isset($_GET["inv"]) && $_GET["inv"]==1){
		echo "<a href=\"?module=invoice&do=create&customer_id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
	} elseif(isset($_GET["acci"]) && $_GET["acci"]==1){
		echo "<a href=\"?module=acct&do=new_account&customer=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
	} elseif(isset($_GET["page"]) && $_GET["page"]=="invoice"){
		echo "<a href=\"?module=invoice&do=create&customer_id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
	} elseif(isset($_GET["page"]) && $_GET["page"] == "areplace") {
		echo alink_onclick($row["firstname"] ." ". $row["lastname"],"?module=acct&do=replace_cust&account_id=".$_GET["aid"]."&customer_id=".$row["id"],"javascript:return confirm('Are you sure you want to do that?');");
	} elseif($searchby == "company") {
		echo "<a href=\"?module=cust&do=view&id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a></td><td>{$row["company"]}";
	} else {
		echo "<a href=\"?module=cust&do=view&id=". $row['id'] ."\">". $row['firstname'] ." ". $row['lastname'] ."</a>";
	}
	echo "</td><td>". display_phone($row['phone_home']) ."</td>";
	echo "<td>". display_phone($row['phone_cell']) ."</td></tr>\n";
}
echo "</table>";

?>
