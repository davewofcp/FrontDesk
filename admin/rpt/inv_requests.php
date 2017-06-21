<?php

// run only once, in case being included from a per-store loop

if (isset($run_once)) return true;
else $run_once = 1;

if (!isset($REPORT)) $REPORT = "";

$REPORT .= "<h3>Inventory Requests</h3>";

$REQUESTS = array(); // Where the index is the entity id requested from
$ITEMS = array(); // Where index is the entity id requested from, and is an array of inventory item numbers.
$INFO = array(); // Where index is the entity id requested from, and is an array of inventory information.

function get_requests($req_by=NULL) {
	global $ITEMS,$REQUESTS;//,$CONS;
	$result = mysql_query("
SELECT ir.*,u.*
FROM inventory_requests ir
LEFT JOIN users u
ON ir.users__id = u.id
LEFT JOIN org_entities oe
ON ir.org_entities__id__dest = oe.id
WHERE ir.org_entities__id__dest = {$req_by}
");//,$CONS[$req_by]);
	while (false !==( $row = mysql_fetch_assoc($result))) {
		$row["req_by"] = $req_by;

		if (!isset($ITEMS[$row[$req_by]])) $ITEMS[$row[$req_by]] = array();
		$ITEMS[$row[$req_by]][] = $row["inventory__id__dest"];

		if (!isset($REQUESTS[$row[$req_by]])) $REQUESTS[$row[$req_by]] = array();
		$REQUESTS[$row[$req_by]][] = $row;
	}
}

function get_inventory_info($eid) {
	//global $CONS,$ITEMS,$INFO;
  global $ITEMS,$INFO;
	if (!isset($ITEMS[$eid])) return;
	if (count($ITEMS[$eid]) < 1) return;
	if (!isset($INFO[$eid])) $INFO[$eid] = array();
	$result = mysql_query("SELECT * FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.id IN (".join(",",$ITEMS[$eid]).")");//,$CONS[$eid]);
	while ($row = mysql_fetch_assoc($result)) {
		$INFO[$eid][$row["id"]] = $row;
	}
}

$ENTITIES = array();
//$result = mysql_query("SELECT * FROM locations");
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
while ($row = mysql_fetch_assoc($result)) {
	$ENTITIES[$row["id"]] = $row;
}

//mysql_close();
//$CONS = array();

// Compile list of requested items and open database connections
foreach($ENTITIES as $eid => $entity) {
// 	$CONS[$eid] = mysql_connect($entity["db_host"],$entity["db_user"],$entity["db_pass"]);
// 	if (!$CONS[$eid]) {
// 		$REPORT .= "<b>ERROR: Unable to connect to store # {$entity["store_number"]} ({$entity["name"]}).</b><br><br>\n\n";
// 		unset($CONS[$eid]);
// 		continue;
// 	}
// 	$dbok = mysql_select_db($entity["db_db"],$CONS[$eid]);
// 	if (!$dbok) {
// 		$REPORT .= "<b>ERROR: Unable to select database for store # {$entity["store_number"]} ({$entity["name"]}).</b><br><br>\n\n";
// 		unset($CONS[$eid]);
// 		continue;
// 	}

	get_requests($eid);
  get_inventory_info($eid);
}

foreach ($REQUESTS as $rfrom => $reqs) {
	if (count($reqs) < 1) {
		$REPORT .= "<h3>Nothing requested from # ".$rfrom." - ".$ENTITIES[$rfrom]["title"]."</h3>\n";
		continue;
	}
	$REPORT .= "<h3>Requested From # ".$rfrom." - ".$ENTITIES[$rfrom]["title"]."</h3>\n";
	$REPORT .= "<table border=\"0\">\n <tr align=\"center\" class=\"heading\">\n";
	$REPORT .= "  <td>By Store</td><td>By User</td><td>Product ID</td><td>Item ID</td><td>Name</td><td>Requested</td><td>Status</td>\n </tr>";
	foreach ($reqs as $request) {
		$REPORT .= " <tr align=\"center\">\n";
		$REPORT .= "  <td># {$request["req_by"]} - ".$ENTITIES[$request["req_by"]]["title"]."</td>\n";
		$REPORT .= "  <td>{$request["firstname"]} {$request["lastname"]}</td>\n";
		$REPORT .= "  <td>{$request["inventory__id__dest"]}</td>\n";
		$REPORT .= "  <td>".($request["inventory_item_number_dest"] ? $request["inventory_item_number_dest"] : "<i>n/a</i>")."</td>\n";
		$REPORT .= "  <td>".$INFO[$request["req_by"]][$request["inventory__id__dest"]]["name"]."</td>\n";
		$REPORT .= "  <td>".($request["qty"] ? $request["qty"] : "1")."</td>\n";
		$REPORT .= "  <td>".$INV_REQUEST_STATUS[$request["varref_status"]]."</td>\n";
		$REPORT .= " </tr>\n";
	}
	$REPORT .= "</table><br>\n";
}

// foreach ($CONS as $dbc) {
// 	mysql_close($dbc);
// }

if (!isset($EMAILING)) {
	echo $REPORT;
	echo "\n\n<hr>\n\n";
	echo alink("Back to Administration","?module=admin");
}
// else {
// 	// Reset the database connection
// 	mysql_close($DB);
// 	$DB = mysql_connect($db_host, $db_user, $db_pass) or die("Couldn't connect to database.");
// 	mysql_select_db($db_database) or die("Couldn't select database.");
// }
?>
