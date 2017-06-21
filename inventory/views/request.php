<?php

$ENTITIES = array();
$result = mysql_query("
SELECT
  oe.*
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.id != {$USER['org_entities__id']}
  AND oe.org_entity_types__id = oet.id
  AND oet.title = 'Store'
");
while ($row = mysql_fetch_assoc($result)) {
	$ENTITIES[] = $row;
}

?>
<h3>Inter-Store Inventory Request</h3>

<select id="store" onChange="storeChange();">
<option value="0">Select a Store</option>
<?php

foreach ($ENTITIES as $entity) {
	echo "<option value=\"{$entity["location_code"]}\">{$entity["title"]}</option>\n";
}

?>
</select> <select id="category" onChange="catChange();">
<option value="0">&lt;-----------</option>
</select><br>
<?php echo alink_onclick("Show All","#","stPress('all');"); ?> &nbsp; <b>-or-</b> &nbsp;
<b>Enter Search Term:</b> <input type="edit" id="sstr" size="20">
<?php echo alink_onclick("Search","#","stPress('manual');"); ?><br><br>

<div id="results"></div>

<script type="text/javascript">
var store = '0';
var sName = '';
var cg = '0';
var lastProductList = null;

var rqtd_prod_id = '0';
var rqtd_item_id = '0';
var rqtd_qty = '0';
var rqtd_name = '';

var storeCats = []; // array of storeCat

function storeCat() {
	this.id = '';
	this.cats = []; // array of category
}

function category() {
	this.id = 0;
	this.name = '';
}

function getCats(id) {
	for (var i = 0; i < storeCats.length; i++) {
		if (storeCats[i].id == id) return storeCats[i];
	}
	return null;
}
function removeCats(id) {
	for (var i = 0; i < storeCats.length; i++) {
		if (storeCats[i].id == id) {
			storeCats.splice(i+1,1);
			return;
		}
	}
}
function emptyCatBox(w) {
	html('category').options.length = 0;
	if (w == 'wait') {
		html('category').options[0] = new Option('Loading.....','0');
	} else {
		html('category').options[0] = new Option('<-----------','0');
	}
}
function setCats(theseCats) {
	html('category').options.length = 0;
	html('category').options[0] = new Option('Any Category','0');
	for (var i = 0; i < theseCats.cats.length; i++) {
		html('category').options[i+1] = new Option(theseCats.cats[i].name,theseCats.cats[i].id);
	}
}

function html(id) {
	return document.getElementById(id);
}

var reqAjax;
if (window.XMLHttpRequest) {
	reqAjax = new XMLHttpRequest();
} else {
	reqAjax = new ActiveXObject("Microsoft.XMLHTTP");
}

function reqAjaxResponseHandler() {
	if (reqAjax.readyState == 4 && reqAjax.status == 200) {
		//alert(reqAjax.responseText);
		var data = JSON.parse(reqAjax.responseText);
		switch (data.action) {
			case "alert":
				alert(data.alrt);
				break;
			case "cats":
				sName = data.name;
				removeCats(data.id);
				sc = new storeCat();
				sc.id = data.id;
				for (var i = 0; i < data.cats.length; i++) {
					c = new category();
					c.id = data.cats[i].id;
					c.name = data.cats[i].name;
					sc.cats.push(c);
				}
				storeCats.push(sc);
				setCats(sc);
				break;
			case "results":
				lastProductList = data.results;
				html('results').innerHTML = data.results;
				break;
			case "list":
				var ls = "<tr><td class=\"heading\" align=\"right\">";
				var m = "</td><td>";
				var rs = "</td></tr>\n";
				var content = data.back+"<br><br>\n\n";
				content += "<table border=\"0\">\n";
				content += ls+"Product ID"+m+data.id+rs;
				content += ls+"UPC"+m+data.upc+rs;
				content += ls+"Name"+m+data.name+rs;
				content += ls+"Description"+m+data.descr+rs;
				content += ls+"Type"+m+data.category+rs;
				content += ls+"QTY In Stock"+m+data.qty+rs;
				content += ls+"Cost"+m+data.cost+rs;
				content += ls+"Retail"+m+data.retail+rs;
				content += ls+"Taxable"+m+data.taxable+rs;
				content += "</table><h3>Available Items</h3>\n";
				content += "<table border=\"0\">\n";
				content += "<tr align=\"center\" class=\"heading\" style=\"font-size:12px;\">\n";
				content += "<td>Item ID</td><td>Serial No.</td><td width=\"300\">Notes</td><td>Status</td><td>Location</td><td>Request</td></tr>\n";
				for (var i = 0; i < data.items.length; i++) {
					content += "<tr align=\"center\" style=\"font-size:12px;\"><td>"+ data.items[i].id +"</td>";
					content += "<td>"+ data.items[i].sn +"</td>";
					content += "<td align=\"left\">"+ data.items[i].notes +"</td>";
					content += "<td>"+ data.items[i].status +"</td>";
					content += "<td>"+ data.items[i].location +"</td>";
					content += "<td>"+ data.items[i].link +"</td></tr>\n";
				}
				content += "</table>";
				html('results').innerHTML = content;
				break;
			case "list_matches":
				var ls = "<tr><td class=\"heading\" align=\"right\">";
				var m = "</td><td>";
				var rs = "</td></tr>\n";
				var content = "<h3>Remote Product Requested (x "+data.rqty+") From "+data.name+"</h3>\n";
				content += "<table border=\"0\">\n";
				content += ls+"Product ID"+m+data.frn_item.id+rs;
				content += ls+"UPC"+m+data.frn_item.upc+rs;
				content += ls+"Name"+m+data.frn_item.name+rs;
				content += ls+"Description"+m+data.frn_item.descr+rs;
				content += ls+"Type"+m+data.frn_item.cat+rs;
				content += ls+"Cost"+m+data.frn_item.cost+rs;
				content += ls+"Retail"+m+data.frn_item.retail+rs;
				content += ls+"Taxable"+m+data.frn_item.taxable+rs;
				content += "</table><h3>Select Product In Local Inventory</h3>\n";
				content += "<table border=\"0\">\n";
				content += "<tr align=\"center\" class=\"heading\" style=\"font-size:12px;\">\n";
				content += "<td>ID</td><td>UPC</td><td>Name (hover for Description)</td><td>Type</td><td>QTY</td><td>Cost</td><td>Retail</td><td>Taxable</td><td>Select</td></tr>\n";
				content += "<tr style=\"font-size:12px;\"><td colspan=\"8\" align=\"center\">Not In Local Inventory - Add It</td><td>"+data.nil_button+"</td></tr>\n";
				content += data.content;
				content += "</table>\n";
				html('results').innerHTML = content;
				break;
			case "request_done":
				html('results').innerHTML = '<b>'+data.message+'</b>';
				break;
		}
	}
}

function ajax(op,str) {
	reqAjax.abort();
	reqAjax.onreadystatechange = reqAjaxResponseHandler;
	reqAjax.open("POST","inventory/r_ajax.php",true);
	reqAjax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	switch (op) {
		case "cats":
			var cols = ['cmd','store'];
			var vals = ['cats',store];
			break;
		case "search":
			var cols = ['cmd','store','cat','str'];
			var vals = ['search',store,cg,html('sstr').value];
			break;
		case "list":
			var cols = ['cmd','store','id'];
			var vals = ['list',store,str];
			break;
		case "request_prod":
			var cols = ['cmd','store','id','qty'];
			var vals = ['request_prod',store,str[0],str[1]];
			break;
		case "request_item":
			var cols = ['cmd','store','id','iid'];
			var vals = ['request_item',store,str[0],str[1]];
			break;
		case "list_matches":
			var cols = ['cmd','store','id','iid','qty','name'];
			var vals = ['list_matches',store,str[0],str[1],str[2],str[3]];
			break;
		case "do_request":
			var cols = ['cmd','store','id','iid','qty','name','lid'];
			var vals = ['do_request',store,rqtd_prod_id,rqtd_item_id,rqtd_qty,rqtd_name,str];
			break;
		default:
			reqAjax.abort();
			return;
	}
	var params = buildParams(cols,vals);
	reqAjax.send(params);
}

function buildParams(cols,vals) {
	var retArr = [];
	for (var i = 0; i < cols.length; i++) {
		var thisVal = vals[i] || '';
		retArr.push(cols[i]+'='+encodeURIComponent(''+thisVal));
	}
	return retArr.join('&');
}

function stPress(m) {
	var str = html('sstr').value;
	if (store == '0') {
		if (m == 'manual' || m == 'all') alert('Please select a store.');
		return;
	}
	if (str.length < 3) {
		if (m == 'manual') alert('Please enter at least 3 characters.');
		if (m != 'all') return;
	}
	lastSearchParam = m;
	html('results').innerHTML = '<b>Searching Database, Please Wait...</b>';

	rqtd_prod_id = '0';
	rqtd_item_id = '0';
	rqtd_qty = '0';
	rqtd_name = '';

	ajax('search');
}

function goBack() {
	if (!lastProductList) return;
	html('results').innerHTML = lastProductList;
}

function storeChange() {
	var s = html('store');
	var sv = s.options[s.selectedIndex].value;
	if (sv != store && sv != '0') {
		store = sv;
		html('results').innerHTML = '';
		var cats = getCats(store);
		if (cats == null) {
			emptyCatBox('wait');
			ajax('cats');
		} else {
			setCats(cats);
		}
	} else {
		return;
	}
}

function catChange() {
	cg = html('category').options[html('category').selectedIndex].value;
}

function reqInv(id,qm,name) {
	qm = parseInt(qm);
	if (qm == 0) return;
	var qty = qm + 1;
	while (qty > qm || qty < 1) {
		var qty = prompt("From Store: "+sName+"\nProduct ID: "+id+"\nProduct: "+name+"\n\nRequest how many? ("+qm+" maximum)");
		if (qty == null) return;
		qty = parseInt(qty);
		if (qty > qm) alert("That quantity exceeds this store's stock.");
		if (qty < 1) alert("You cannot request zero.");
	}
	rqtd_prod_id = id;
	rqtd_item_id = '0';
	rqtd_qty = qty;
	rqtd_name = name;
	//ajax('request_prod',[id,qty]);
	//window.location = '?module=inventory&do=request_sub&store='+store+'&id='+id+'&qty='+qty;
	html('results').innerHTML = '<b>Getting list of local inventory...</b>';
	listMatches(id,'0',qty,name);
}

function reqItem(id,iid,name) {
	var c = confirm("Are you sure you want to request Item ID "+iid+" ?");
	if (!c) return;
	//ajax('request_item',[id,iid]);
	//window.location = '?module=inventory&do=request_sub&store='+store+'&id='+id+'&iid='+iid;
	rqtd_prod_id = id;
	rqtd_item_id = iid;
	rqtd_qty = '1';
	rqtd_name = name;
	html('results').innerHTML = '<b>Getting list of local inventory...</b>';
	listMatches(id,iid,1,false);
}

function matchSelect(local_id) {
	html('results').innerHTML = '<b>Submitting inventory request...</b>';
	ajax("do_request",local_id);
}

function listMatches(id,iid,qty,name) {
	ajax("list_matches",[id,iid,qty,name]);
}

function listItems(id) {
	html('results').innerHTML = '<b>Listing Items, Please Wait...</b>';
	ajax('list',id);
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
