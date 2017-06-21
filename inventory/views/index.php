<h3>Inventory</h3>

<?php if (isset($RESPONSE)) { ?><font color="red" size="+1"><b><?php echo $RESPONSE; ?></b></font><?php } ?>

<table border="0">
 <tr>
  <td width="50%" align="center">

<table border="0">
 <tr>
  <td colspan="2" align="center"><b>Search Inventory</b></td>
 </tr>
 <tr>
  <td align="right" class="heading">Category</td>
  <td>
   <select id="inv_search_cat">
   <option value="0">Any Category</option>
<?php

$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NULL ORDER BY category_name");
$result2 = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NOT NULL ORDER BY category_name");
$SUBS = array();
while ($row2 = mysql_fetch_assoc($result2)) {
	if (!isset($SUBS[$row2["parent_id"]])) $SUBS[$row2["parent_id"]] = array();
	$SUBS[$row2["parent_id"]][] = $row2;
}
while ($row = mysql_fetch_assoc($result)) {
	echo "   <option value=\"{$row["id"]}\">{$row["category_name"]}</option>\n";
	if (isset($SUBS[$row["id"]])) {
		foreach($SUBS[$row["id"]] as $row2) {
			echo "   <option value=\"{$row2["id"]}\">- {$row2["category_name"]}</option>\n";
		}
	}
}

?>
   </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Hide Sold-Out?</td>
  <td><input type="checkbox" id="inv_sold_out" value="1" checked></td>
 </tr>
 <tr>
  <td class="heading" align="right">Search Term</td>
  <td><input type="edit" id="inv_search_term" onKeyUp="searchInv(0)" size="20" autocomplete="off"></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <font size="-1"><i>Type at least 3 characters to begin search, or</i></font><br>
   <?php echo alink_onclick("Show All In Category","#","searchInv('all');"); ?>
  </td>
 </tr>
</table><br>

  </td>
  <td width="50%" align="center">

<form action="?module=inventory&do=scan" method="post">
<b>Go To Item:</b> <input type="edit" id="barcode" name="barcode" autocomplete="off">
</form>
<?php
echo alink("Add New Product","?module=inventory&do=add") . "<br><br>";
echo alink("Request Inventory","?module=inventory&do=request") ."<br><br>";
if (TFD_HAS_PERMS('admin','use')) echo alink("Transfer Manager","?module=inventory&do=xfers") ."<br><br>";
?>

  </td>
 </tr>
</table>

<div id="inv_results"></div>

<script type="text/javascript">
window.onload = function() {
	document.getElementById('barcode').focus();
}

var xmlInv;
if (window.XMLHttpRequest) {
	xmlInv = new XMLHttpRequest();
} else {
	xmlInv = new ActiveXObject("Microsoft.XMLHTTP");
}

function xmlInvResponseHandler() {
	if (xmlInv.readyState == 4 && xmlInv.status == 200) {
		var tt = JSON.parse(xmlInv.responseText);
		document.getElementById("inv_results").innerHTML = tt.content;
	}
}

function searchInv(all) {
	if (all == 0 && document.getElementById("inv_search_term").value.length < 3) return;
	document.getElementById("inv_results").innerHTML = "<b>Searching Inventory...</b>";
	document.getElementById("inv_results").style.display = '';
	var st = document.getElementById("inv_search_term").value;
	var catbox = document.getElementById("inv_search_cat");
	var cat = catbox.options[catbox.selectedIndex].value;
	var hideso = document.getElementById("inv_sold_out").checked;
	var allOpt = '';
	var soOpt = '';
	if (all == 'all') {
		allOpt = '&all=1';
		st = '';
	}
	if (hideso) {
		soOpt = '&hso=1';
	}
	xmlInv.abort();
	xmlInv.onreadystatechange = xmlInvResponseHandler;
	xmlInv.open("GET","inventory/ajax.php?cmd=search&cat="+cat+allOpt+soOpt+"&str="+st+"<?php echo (isset($_GET["issue_id"]) ? "&issue_id=".intval($_GET["issue_id"]) : "") ?>",true);
	xmlInv.send();
}
</script>
