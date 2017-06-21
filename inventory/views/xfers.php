<script type="text/javascript" src="js/md5.js"></script>
<h2>Inventory Transfer Manager</h2>

<div id="unlock">

<b>Inventory Password:</b> <input type="password" id="inv_pass" size="20" onKeyPress="unlockEnterCheck(event);"> <input type="button" value="Unlock" onClick="unlock();"><br><br>

<div id="unlock_response"></div>

</div>
<div id="manager" style="display:none;">
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$ENTITIES = array();
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

//$CONS = array(); DEFUNCT

$REQUESTS = array(); // Where the index is the entity id requested from
$APV_REQUESTS = array(); // Same as above; for approved requests
$Q_PRODUCT_IDS = array(); // Where index is the entity id requested from, and is an array of inventory IDs.
						  //   Represents QUANTITY requests.
$I_PRODUCT_IDS = array(); // Where index is the entity id requested from, and is an array of inventory IDs.
						  //   Represents ITEM requests.
$ITEM_IDS = array(); // Where index is the entity id requested from, and the value is an array.
					 //   Second dimension index is inventory ID, and the value is an array of item IDs.
$ITEMS = array(); // Where index is the entity id requested from, and the value is an array.
				  //   Second dimension index is inventory ID, and the value is an array.
				  //   Third dimension index is item ID, and the value is item information.
$INFO = array(); // Where index is the entity id requested from, and is an array of inventory information.

$XFERS = array(); // Where index is the entity id requested from, and is an array of inventory transfers

function get_requests($req_by) {
	global $Q_PRODUCT_IDS,$I_PRODUCT_IDS,$ITEM_IDS,$REQUESTS,$APV_REQUESTS;//,$CONS;
	$result = mysql_query("SELECT * FROM inventory_requests ir LEFT JOIN users u ON ir.users__id = u.id WHERE ir.org_entities__id__dest = {$req_by} AND ir.varref_status IN (1,2)");//,$CONS[$req_by]);
	while ($row = mysql_fetch_assoc($result)) {
		$row["req_by"] = $req_by;

		// Quantity requests
		if (!$row["inventory_item_number_dest"]) {
			// Record inventory # to get info from inventory table
			if (!isset($Q_PRODUCT_IDS[$row[$req_by]])) $Q_PRODUCT_IDS[$row[$req_by]] = array();
			$Q_PRODUCT_IDS[$row[$req_by]][] = $row["inventory__id__dest"];
		}

		// Item requests
		if ($row["inventory_item_number_dest"]) {
			// Record inventory/item # to get info from inventory_items table
			if (!isset($ITEM_IDS[$row[$req_by]][$row["inventory__id__dest"]])) $ITEM_IDS[$row[$req_by]] = array();
			if (!isset($ITEM_IDS[$row[$req_by]][$row["inventory__id__dest"]])) $ITEM_IDS[$row[$req_by]][$row["inventory__id__dest"]] = array();
			$ITEM_IDS[$row[$req_by]][$row["inventory__id__dest"]][] = $row["inventory_item_number_dest"];

			// Record inventory # to get info from inventory table
			if (!isset($I_PRODUCT_IDS[$row[$req_by]])) $I_PRODUCT_IDS[$row[$req_by]] = array();
			$I_PRODUCT_IDS[$row[$req_by]][] = $row["inventory__id__dest"];
		}

		if ($row["varref_status"] == '1') {
			if (!isset($REQUESTS[$row[$req_by]])) $REQUESTS[$row[$req_by]] = array();
			$REQUESTS[$row[$req_by]][] = $row;
		}
		if ($row["varref_status"] == '2') {
			if (!isset($APV_REQUESTS[$row[$req_by]])) $APV_REQUESTS[$row[$req_by]] = array();
			$APV_REQUESTS[$row[$req_by]][] = $row;
		}
	}
}

function get_transfers($req_from) {
	global $CONS,$XFERS,$Q_PRODUCT_IDS;
	$result = mysql_query("SELECT * FROM inventory_transfers WHERE ir.org_entities__id__orig = {$req_from} AND is_incoming = 0 AND varref_status != 5");//,$CONS[$req_from]);
	while ($row = mysql_fetch_assoc($result)) {
    $row["req_from"] = $req_from;
		if (!isset($XFERS[$req_from])) $XFERS[$req_from] = array();
		$XFERS[$req_from][] = $row;
		$Q_PRODUCT_IDS[$req_from][] = $row["inventory__id__orig"];
	}
}

// Get header info from inventory table
function get_inventory_info($eid) {
	//global $CONS,$Q_PRODUCT_IDS,$I_PRODUCT_IDS,$INFO;
  global $Q_PRODUCT_IDS,$I_PRODUCT_IDS,$INFO;

	if (!isset($Q_PRODUCT_IDS[$eid]) && !isset($I_PRODUCT_IDS[$eid])) return;
	if (count($Q_PRODUCT_IDS[$eid]) < 1 && count($I_PRODUCT_IDS[$eid]) < 1) return;

	$rids = array();
	if (isset($Q_PRODUCT_IDS[$eid])) $rids = array_merge($rids,$Q_PRODUCT_IDS[$eid]);
	if (isset($I_PRODUCT_IDS[$eid])) $rids = array_merge($rids,$I_PRODUCT_IDS[$eid]);

	if (!isset($INFO[$eid])) $INFO[$eid] = array();
	$result = mysql_query("SELECT * FROM inventory i LEFT JOIN categories c ON i.item_type_lookup = c.id WHERE i.id IN (".join(",",$rids).")");//,$CONS[$eid]);
	while ($row = mysql_fetch_assoc($result)) {
		$INFO[$eid][$row["id"]] = $row;
	}
}

// Get information for each requested inventory item
function get_inv_item_info($eid) {
	//global $CONS,$I_PRODUCT_IDS,$ITEM_IDS,$ITEMS;
  global $I_PRODUCT_IDS,$ITEM_IDS,$ITEMS;
	if (!isset($ITEM_IDS[$eid])) return;
	if (count($ITEM_IDS[$eid]) < 1) return;

	if (!isset($ITEMS[$eid])) $ITEMS[$eid] = array();

	foreach ($ITEM_IDS[$eid] as $inv_id => $itms) {
		if (!isset($ITEMS[$eid][$inv_id])) $ITEMS[$eid][$inv_id] = array();

		$result = mysql_query("SELECT * FROM inventory_items ii WHERE ii.inventory__id = ".$inv_id." AND ii.id IN (".join(",",$itms).")");//,$CONS[$eid]);
		while ($row = mysql_fetch_assoc($result)) {
			$ITEMS[$eid][$inv_id][$row["id"]] = $row;
		}
	}
}

// Compile list of requested items and open database connections
foreach($ENTITIES as $eid => $entity) {
// 	$CONS[$eid] = @mysql_connect($entity["db_host"],$entity["db_user"],$entity["db_pass"],true);
// 	if (!$CONS[$eid]) {
// 		echo "<b>ERROR: Unable to connect to store # {$entity["store_number"]} ({$entity["name"]}).</b><br><br>\n\n";
// 		unset($CONS[$eid]);
// 		continue;
// 	}
// 	$dbok = @mysql_select_db($entity["db_db"],$CONS[$eid]);
// 	if (!$dbok) {
// 		echo "<b>ERROR: Unable to select database for store # {$entity["store_number"]} ({$entity["name"]}).</b><br><br>\n\n";
// 		unset($CONS[$eid]);
// 		continue;
// 	}

	get_requests($eid);
	get_transfers($eid);
  get_inventory_info($eid);
  get_inv_item_info($eid);
}

//foreach ($CONS as $eid => $dbc) {
//	get_inventory_info($eid);
//	get_inv_item_info($eid);
//}

?>
<hr>
<h3>Pending Transfer Requests</h3>
<?php

foreach ($ENTITIES as $eid => $entity) {
	$rfrom = $eid;
	if (isset($REQUESTS[$eid])) $reqs = $REQUESTS[$eid];
	if (!isset($reqs)) continue;
	echo "<div id=\"rpend_".str_replace('"','',$rfrom)."\">\n";
	echo "<font size=\"+1\"><b>Requested From # ".$rfrom." - ".$entity["title"]."</b></font><br>\n";
	echo "<input type=\"hidden\" id=\"rct_".str_replace('"','',$rfrom)."\" value=\"".count($reqs)."\">\n";
	echo "<table border=\"0\">\n <tr align=\"center\" class=\"heading\" style=\"font-size:12px;\">\n";
	echo "  <td>By Store</td><td>By User</td><td>Product ID</td><td>Item ID</td><td>Name</td><td>Requested</td><td>Change QTY</td><td>Deny</td><td>Approve</td>\n </tr>";
	foreach ($reqs as $request) {
		$san_sn = str_replace('"','',$request["req_by"]);
		echo " <tr align=\"center\" id=\"req_{$san_sn}_{$request["id"]}\" style=\"font-size:12px;\">\n";
		echo "  <td id=\"r_{$san_sn}_{$request["id"]}_id\"># {$request["req_by"]} - ".$ENTITIES[$request["req_by"]]["title"]."</td>\n";
		echo "  <td id=\"r_{$san_sn}_{$request["id"]}_user\">{$request["firstname"]} {$request["lastname"]}</td>\n";
		echo "  <td id=\"r_{$san_sn}_{$request["id"]}_iid\">{$request["inventory__id__dest"]}</td>\n";
		echo "  <td id=\"r_{$san_sn}_{$request["id"]}_iiid\">".($request["inventory_item_number_dest"] ? $request["inventory_item_number_dest"] : "<i>n/a</i>")."</td>\n";
		echo "  <td id=\"r_{$san_sn}_{$request["id"]}_name\">".$INFO[$request["req_by"]][$request["inventory__id__dest"]]["name"]."</td>\n";
		echo "  <td id=\"r_{$san_sn}_{$request["id"]}_qty\">".($request["qty"] ? $request["qty"] : "1")."</td>\n";
		echo "  <td id=\"rc_{$san_sn}_{$request["id"]}\">".($request["inventory_item_number_dest"] ? "<strike>Change</strike>" : alink_onclick("Change","#","changeQty('$rfrom','{$request["id"]}');"))."</td>\n";
		echo "  <td id=\"rd_{$san_sn}_{$request["id"]}\">".alink_onclick("Deny","#","reqDeny('$rfrom','{$request["id"]}');")."</td>\n";
		echo "  <td id=\"ra_{$san_sn}_{$request["id"]}\">".alink_onclick("Approve","#","reqApprove('$rfrom','{$request["id"]}');")."</td>\n";
		echo " </tr>\n";
	}
	echo "</table><br></div>\n";
}

?>

<hr>
<h3>Approved Transfer Requests</h3>
<?php

foreach ($ENTITIES as $eid => $entity) {
	$rfrom = $eid;
	if (isset($APV_REQUESTS[$eid])) $reqs = $APV_REQUESTS[$eid];
	else $reqs = array();
	//if (!isset($reqs)) continue;
	echo "<font size=\"+1\"><b>Requested From # ".$rfrom." - ".$entity["title"]."</b></font><br>\n";
	echo "<table border=\"0\" id=\"apvr_".str_replace('"','',$rfrom)."\">\n <tr align=\"center\" class=\"heading\" style=\"font-size:12px;\">\n";
	echo "  <td>By Store</td><td>By User</td><td>Product ID</td><td>Item ID</td><td>Name</td><td>Requested</td><td>Transfer</td>\n </tr>";
	foreach ($reqs as $request) {
		$san_sn = str_replace('"','',$request["req_by"]);
		echo " <tr align=\"center\" id=\"areq_{$san_sn}_{$request["id"]}\" style=\"font-size:12px;\">\n";
		echo "  <td># {$request["req_by"]} - ".$ENTITIES[$request["req_by"]]["name"]."</td>\n";
		echo "  <td>{$request["firstname"]} {$request["lastname"]}</td>\n";
		echo "  <td>{$request["inventory__id__dest"]}</td>\n";
		echo "  <td>".($request["inventory_item_number_dest"] ? $request["inventory_item_number_dest"] : "<i>n/a</i>")."</td>\n";
		echo "  <td>".$INFO[$request["req_by"]][$request["inventory__id__dest"]]["name"]."</td>\n";
		echo "  <td>".($request["qty"] ? $request["qty"] : "1")."</td>\n";
		echo "  <td id=\"tr_{$san_sn}_{$request["id"]}\">".alink_onclick("Transfer","#","do_transfer('".$san_sn."','{$request["id"]}');")."</td>\n";
		echo " </tr>\n";
	}
	echo "</table><br>\n";
}

?>

<hr>
<h3>Transfers In Progress</h3>
<table>
 <tr align="center" class="heading">
  <td colspan="4">Sender</td>
  <td colspan="3">Receiver</td>
 </tr>
 <tr align="center" class="heading" style="font-size:12px;">
  <td>Store</td>
  <td>Transfer ID</td>
  <td>UPC</td>
  <td>Name</td>
  <td>Store</td>
  <td>Transfer ID</td>
  <td>UPC</td>
  <td>QTY</td>
  <td>Status</td>
  <td>Update</td>
  <td>Last Update</td>
 </tr>
<?php

foreach ($XFERS as $rfrom => $xfers) {
	foreach ($xfers as $xfer) {
		$san_sn = str_replace('"','',$rfrom);
		echo " <tr align=\"center\" style=\"font-size:12px;\">\n";
		echo "  <td># {$rfrom} - ".$ENTITIES[$rfrom]["title"]."</td>\n";
		echo "  <td>".$xfer["id"]."</td>\n";
		if ($xfer["inventory_item_number_orig"]) {
			echo "  <td>".encodeUpc("inventory_item",$xfer["inventory_item_number_orig"])."</td>\n";
		} else {
			echo "  <td>".encodeUpc("inventory",$xfer["inventory__id__orig"])."</td>\n";
		}
		echo "  <td>".$INFO[$rfrom][$xfer["inventory__id__orig"]]["name"]."</td>\n";
		echo "  <td># {$xfer["req_from"]} - ".$ENTITIES[$xfer["req_from"]]["name"]."</td>\n";
		echo "  <td>".$xfer["inventory_transfers__id__dest"]."</td>\n";
		if ($xfer["inventory_item_number_orig"]) {
			echo "  <td><i>Not In DB Yet</i></td>\n";
		} else {
			echo "  <td>".encodeUpc("inventory",$xfer["inventory__id__dest"])."</td>\n";
		}
		echo "  <td>".$xfer["qty"]."</td>\n";
		echo "  <td><select id=\"status_{$san_sn}_{$xfer["id"]}\">\n";
		foreach ($TRANSFER_STATUS as $id => $tst) {
			$sel = "";
			if ($id == $xfer["varref_status"]) $sel = " SELECTED";
			echo "<option value=\"$id\"$sel>$tst</option>\n";
		}
		echo "</select></td>\n";
		echo "  <td id=\"subc_{$san_sn}_{$xfer["id"]}\"><input id=\"sbutton_{$san_sn}_{$xfer["id"]}\" type=\"button\" value=\"Update\" onClick=\"updateStatus('$san_sn','{$xfer["id"]}');\"></td>\n";
		echo "  <td id=\"supdated_{$san_sn}_{$xfer["id"]}\">".date("Y-m-d H:i:s",strtotime($xfer["ts_updated"]))."</td>\n";
		echo " </tr>\n";
	}
}

?>
</table>

</div>
<script type="text/javascript">
var xf_hash = '';
var xf_from_store = null;
var xf_from_inv_id = 0;
var xf_from_item_id = 0;
var xf_to_store = null;
var xf_to_inv_id = 0;
var xf_to_item_id = 0;
var xf_qty = 0;
var xf_code = ''; // transfer_id at receiving store

var xf_active_fstore = '';

function html(id) {
	return document.getElementById(id);
}

function san_sn(sn) {
	return sn.replace('"','').replace("\n",'').replace("\r",'');
}

var xferAjax;
if (window.XMLHttpRequest) {
	xferAjax = new XMLHttpRequest();
} else {
	xferAjax = new ActiveXObject("Microsoft.XMLHTTP");
}

function xferAjaxResponseHandler() {
	if (xferAjax.readyState == 4 && xferAjax.status == 200) {
		//alert(xferAjax.responseText);
		var data = JSON.parse(xferAjax.responseText);
		switch (data.action) {
			case "alert":
				alert(data.alrt);
				break;
			case "pass_wrong":
				html('unlock_response').innerHTML = '<b>Password incorrect.</b>';
				break;
			case "pass_ok":
				html('unlock').style.display = 'none';
				html('manager').style.display = '';
				break;
			case "denied":
				html('req_'+data.sn+'_'+data.id).style.display = 'none';
				var rct = parseInt(html('rct_'+data.sn).value,10);
				rct = rct - 1;
				html('rct_'+data.sn).value = rct;
				if (rct == 0) html('rpend_'+data.sn).style.display = 'none';
				alert('Request '+data.id+' from store #'+data.sn+' denied.');
				break;
			case "approved":
				var prow = html('req_'+data.sn+'_'+data.id);
				var apvt = html('apvr_'+data.sn);
				var newRow = apvt.insertRow(-1);
				newRow.setAttribute('id','areq_'+data.sn+'_'+data.id);
				newRow.style.cssText = "font-size:12px;";
				newRow.align = 'center';
				var c = newRow.insertCell(0);
				c.innerHTML = prow.cells[0].innerHTML;
				c = newRow.insertCell(1);
				c.innerHTML = prow.cells[1].innerHTML;
				c = newRow.insertCell(2);
				c.innerHTML = prow.cells[2].innerHTML;
				c = newRow.insertCell(3);
				c.innerHTML = prow.cells[3].innerHTML;
				c = newRow.insertCell(4);
				c.innerHTML = prow.cells[4].innerHTML;
				c = newRow.insertCell(5);
				c.innerHTML = prow.cells[5].innerHTML;
				c = newRow.insertCell(6);
				c.innerHTML = data.link;
				prow.style.display = 'none';
				var rct = parseInt(html('rct_'+data.sn).value,10);
				rct = rct - 1;
				html('rct_'+data.sn).value = rct;
				if (rct == 0) html('rpend_'+data.sn).style.display = 'none';
				break;
			case "changed":
				html('r_'+san_sn(data.sn)+'_'+data.id+'_qty').innerHTML = data.qty;
				break;
			case "status":
				html('supdated_'+data.sn+'_'+data.id).innerHTML = data.updated;
				break;
			case "transfer":
				html('supdated_'+data.sn+'_'+data.id).innerHTML = data.updated;
				html('subc_'+data.sn+'_'+data.id).innerHTML = '<b>Received</b>';
				break;
		}
	}
}

function ajax(op,str) {
	xferAjax.abort();
	xferAjax.onreadystatechange = xferAjaxResponseHandler;
	xferAjax.open("POST","inventory/x_ajax.php",true);
	xferAjax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	switch (op) {
		case "unlock":
			var cols = ['cmd','p'];
			var vals = ['unlock',xf_hash];
			html('unlock_response').innerHTML = '<b>Please Wait...</b>';
			break;
		case "deny":
			var cols = ['cmd','p','store','id'];
			var vals = ['deny',xf_hash,xf_active_fstore,str];
			html('rd_'+san_sn(xf_active_fstore)+'_'+str).innerHTML = '...';
			break;
		case "approve":
			var cols = ['cmd','p','store','id'];
			var vals = ['approve',xf_hash,xf_active_fstore,str];
			html('ra_'+san_sn(xf_active_fstore)+'_'+str).innerHTML = '...';
			break;
		case "change":
			var cols = ['cmd','p','store','id','qty'];
			var vals = ['change',xf_hash,xf_active_fstore,str[0],str[1]];
			html('r_'+san_sn(xf_active_fstore)+'_'+str[0]+'_qty').innerHTML = '...';
			break;
		case "status":
			var cols = ['cmd','p','store','id','status'];
			var vals = ['status',xf_hash,str[0],str[1],str[2]];
			html('supdated_'+san_sn(str[0])+'_'+str[1]).innerHTML = '......';
			break;
		case "transfer":
			var cols = ['cmd','p','store','id'];
			var vals = ['transfer',xf_hash,xf_active_fstore,str];
			html('tr_'+san_sn(xf_active_fstore)+'_'+str).innerHTML = '...';
			break;
		default:
			xferAjax.abort();
			return;
	}
	var params = buildParams(cols,vals);
	xferAjax.send(params);
}

function buildParams(cols,vals) {
	var retArr = [];
	for (var i = 0; i < cols.length; i++) {
		var thisVal = vals[i] || '';
		retArr.push(cols[i]+'='+encodeURIComponent(''+thisVal));
	}
	return retArr.join('&');
}

function updateStatus(rfrom,id) {
	var sbox = html('status_'+rfrom+'_'+id);
	var sv = sbox.options[sbox.selectedIndex].value;
	ajax('status',[rfrom,id,sv]);
}

function changeQty(rfrom,id) {
	xf_active_fstore = rfrom;
	var qty = prompt("Change request to what quantity?");
	ajax('change',[id,qty]);
}

function reqDeny(rfrom,id) {
	xf_active_fstore = rfrom;
	ajax('deny',id);
}

function reqApprove(rfrom,id) {
	xf_active_fstore = rfrom;
	ajax('approve',id);
}

function do_transfer(rfrom,id) {
	xf_active_fstore = rfrom;
	ajax('transfer',id);
}

function unlock() {
	var p = html('inv_pass').value;
	xf_hash = hex_md5(p+'butter');
	ajax('unlock');
}

function unlockEnterCheck(e) {
	if (e.keyCode == 13) {
		unlock();
	}
}

function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;

	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";

	if(typeof(arr) == 'object') { //Array/Hashes/Objects
		for(var item in arr) {
			var value = arr[item];

			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}
</script>
