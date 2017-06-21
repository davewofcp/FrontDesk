<script type="text/javascript">
var issueId = '<?php if (isset($_GET["issue_id"])) echo $_GET["issue_id"]; ?>';
var xmlorderInv;
var xmlINV;
var xmldesc;
if (window.XMLHttpRequest) {
  // code for IE7+, Firefox, Chrome, Opera, Safari
	xmlorderInv=new XMLHttpRequest();
	xmlINV=new XMLHttpRequest();
	xmldesc=new XMLHttpRequest();
} else {
  // code for IE6, IE5
	xmlorderInv=new ActiveXObject("Microsoft.XMLHTTP");
	xmlINV=new ActiveXObject("Microsoft.XMLHTTP");
	xmldesc=new ActiveXObject("Microsoft.XMLHTTP");
}
xmlorderInv.onreadystatechange = function() {
	if (xmlorderInv.readyState == 4 && xmlorderInv.status == 200) {
		var tt = JSON.parse(xmlorderInv.responseText);
		document.getElementById("orderInvResult").style.opacity = 1;
		document.getElementById("orderInvResult").innerHTML = tt.content;
	}
}
var desvanecer;
function fadeOut(elem, speed){
  if(desvanecer)clearInterval(desvanecer);
    elem.style.opacity = 1;

    desvanecer = setInterval(function(){
    elem.style.opacity -= .02;
    if(elem.style.opacity <= 0){
        clearInterval(desvanecer);
    }
  }, speed / 50);
}
function clearFade(elem){
  if(elem){
    clearInterval(elem);
  } else {
    clearInterval(desvanecer);
  }
}
function addInv(){
	  document.getElementById("belowUPC").innerHTML = "<b>Searching UPC</b>";
	  document.getElementById("belowUPC").style.opacity = 1;

	  var tinv;
	  var jinv;
	  xmlINV.onreadystatechange = function() {
	    if (xmlINV.readyState == 4 && xmlINV.status == 200) {
	        if(xmlINV.responseText=="False"){
	          document.getElementById("belowUPC").innerHTML = "<b>Product Not Found</b>";
	          fadeOut(document.getElementById("belowUPC"),4000);
	        } else {
	          document.getElementById("belowUPC").innerHTML = "<b>Product Found!</b>";
	          fadeOut(document.getElementById("belowUPC"),2000);
	        }
	        jinv = JSON.parse(xmlINV.responseText);
	        document.getElementById("newName").innerHTML = jinv[0].productname;
	        document.getElementById("newPurchase_price").value = jinv[0].price;
	        document.getElementById("image").innerHTML = "<img src=\""+ jinv[0].imageurl +"\">";

	        xmldesc.onreadystatechange = function() {
	          if (xmldesc.readyState == 4 && xmldesc.status == 200) {
	            document.getElementById("newDescr").innerHTML = xmldesc.responseText;
	            document.getElementById("belowUPC").innerHTML = "<b>Found Description</b>";
	            fadeOut(document.getElementById("belowUPC"),4000);
	          }
	        }

	        clearFade(desvanecer);
	        document.getElementById("belowUPC").innerHTML = "<b>Searching for description<br />Loading Image</b>";
	        document.getElementById("belowUPC").style.opacity = 1;
	      	xmldesc.open("GET","inv/ajax.php?cmd=descr&str="+ encodeURIComponent(jinv[0].producturl) +"",true);
	      	xmldesc.send();
	    }
	  }
	  tinv = document.getElementById("newUpc");

		xmlINV.open("GET","inv/ajax.php?cmd=search&str="+tinv.value+"&id=1",true);
		xmlINV.send();
	}
function hideAll() {
	var classes = getElementsByClass("ihid");
	for(a in classes){
	    classes[a].style.display="none";
	}
}

function icatclick(x,option_id) {
  var catdoc = document.getElementById("ihid"+ option_id +"");
  var catbut = document.getElementById("ibut"+ option_id +"");
  var classes = getElementsByClass("ihid");
  var bclasses = getElementsByClass("ibut");

  for(a in classes){
    if(classes[a].style.display!="none"){
      bclasses[a].style.border="";
      //fadeOutHeight(classes[a],15,100);
    } else {
      classes[a].style.display="none";
    }
  }
  for(b in bclasses){
    if(bclasses[b].style.border!="")bclasses[b].style.border="";
  }

  //document.inv_addForm.device_type[x].checked=true;
  catdoc.style.display = "block";
  //catdoc.style.height = "0px";
  //catbut.style.border = "2px solid #003399";
  //fadeInHeight(catdoc,10,50,80);
}
var orderCount = 0;
function orderInv_add(upc,name,descr,purchase_price,cost,qty,taxable,device_type_name,device_type,inventory_id){
	document.getElementById("orderInvResult").innerHTML="";
	document.getElementById("search_term").value="";
	var orderContent = document.getElementById("orderContent");
	var str = ""+orderContent.innerHTML+"";
	orderCount++;
	str += "<div id=\"itemid"+orderCount+"\" class=\"relative clear center margin5 padding5\" style=\"border:5px ridge #000;border-top:1px solid #000;border-left:1px solid #000;border-radius:10px;\">";
	str += "<input type=\"hidden\" id=\"orderCount\" name=\"orderCount[]\" value=\""+orderCount+"\">";
	str += "<input type=\"hidden\" id=\"invId\" name=\"invId[]\" value=\""+inventory_id+"\">";
	if (inventory_id != '') {
		str += "<div class=\"inline padding3\">Inventory Item</div>";
	} else {
		str += "<div class=\"inline padding3\">New Item</div>";
	}
	str += "<div class=\"inline padding3\"><a href=\"#items\" class=\"link\" onClick=\"orderInv_delete("+orderCount+");\" onMouseOver=\"this.setAttribute('class','link_hover');\" onMouseOut=\"this.setAttribute('class','link');\">Delete</a></div>";
	str += "<div class=\"inline padding3\"><b>Attach to Issue ID:</b> <input type=\"edit\" id=\"orderIssue\" name=\"orderIssue[]\" size=\"6\" value=\""+issueId+"\"></div>";

	    str += "<div class=\"floatL relative left\">";
	      str += "<div class=\"itemcontent relative\" style=\"position:relative;\"><b>Name:</b><br><textarea id=\"orderName\" name=\"orderName[]\" style=\"position:relative;height:60px;width:250px;z-index:99;\">"+name+"</textarea>";
	        str += "<br><b>Sale Price:</b> $<input type=\"edit\" id=\"orderCost\" name=\"orderCost[]\" size=\"3\" value=\""+cost+"\"> ";
	        str += "<br><b>Purchase Price:</b> $<input type=\"edit\" id=\"orderPPrice\" name=\"orderPPrice[]\" size=\"3\" value=\""+purchase_price+"\"> ";
	      str += "</div>";
	      str += "<div class=\"itemcontent relative bolder\">Description:<br><textarea id=\"orderDescr\" name=\"orderDescr[]\" style=\"position:relative;height:90px;width:300px;z-index:99;\">"+descr+"</textarea>";
	      str += "<br><b>Type:</b> "+device_type_name+"<input type=\"hidden\" id=\"orderDevType\" name=\"orderDevType[]\" value=\""+device_type+"\"></div>";
	    str += "</div>";

	    str += "<div class=\"floatL relative padding5 left\">";
	      str += "Qty:<input id=\"orderQty\" name=\"orderQty[]\" type=\"edit\" size=\"1\" value=\"1\">";
	      if(qty)str += " <b>Qty In Stock:</b> "+qty;
	      str += " <b>Taxable:</b> <select id=\"orderTax\" name=\"orderTax[]\"><option value=\"1\">Yes</option><option value=\"0\">No</option></select>";
	      str += " <b>UPC:</b> <input id=\"orderUpc\" name=\"orderUpc[]\" type=\"edit\" size=\"15\" value=\""+upc+"\">";
	      str += "</div>";
	    str += "<div class=\"clear\"></div>";
	  str += "</div>";
	  orderContent.innerHTML = str;
}
function orderInv_delete(del_id){
	  document.getElementById("itemid"+del_id).innerHTML="";
	  document.getElementById("itemid"+del_id).style.border="";
	  document.getElementById("itemid"+del_id).style.display="none";
}
function orderInv() {
	var sb = document.getElementById("search_term");
	xmlorderInv.open("GET","inv/ajax.php?cmd=INV&str="+sb.value+"&orderINV",true);
	xmlorderInv.send();
}
function addToOrder() {
	var upc = document.getElementById('newUpc');
	var name = document.getElementById('newName');
	var descr = document.getElementById('newDescr');
	var purchase_price = document.getElementById('newPurchase_price');
	var cost = document.getElementById('newCost');
	var qty = document.getElementById('newQty');
	var taxable = document.getElementById('newTaxable');
	var radios = document.getElementsByName('device_type');
	var device_type = '';
	for (var i = 0, length = radios.length; i < length; i++) {
		if (radios[i].checked) {
			device_type = radios[i].value;
		}
	}
	var device_type_name = document.getElementById('catname'+device_type);
	var inventory_id = '';
	orderInv_add(upc.value,name.value,descr.value,purchase_price.value,cost.value,qty.value,taxable.value,device_type_name.value,device_type,inventory_id);
	upc.value = '';
	name.value = '';
	descr.value = '';
	purchase_price.value = '';
	cost.value = '';
	qty.value = '';
	document.getElementById("image").innerHTML = '';
}
function checkItems() {
	if (orderCount == 0) {
		return confirm('Are you sure you want to enter an order with no items on it?');
	}
	for (var i = 1; i <= orderCount; i++) {
		if (!document.getElementById("itemid"+i)) continue;
		if (document.getElementById("itemid"+i).style.display == "none") continue;
		return true;
	}
	return confirm('Are you sure you want to enter an order with no items on it?');
}
</script>
<h3>Add New Order</h3>
<form action="?module=orders&do=new" method="post">
<table border="0" cellspacing="3">
 <tr>
  <td class="heading" align="right">Purchased From</td>
  <td><input type="edit" name="purchased_from" size="25"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Order Number</td>
  <td><input type="edit" name="order_number" size="5"> (optional)</td>
 </tr>
 <tr>
  <td class="heading" align="right">Shipping Carrier</td>
  <td>
    <select name="carrier">
      <option value="1">USPS</option>
      <option value="2">UPS</option>
      <option value="3">FedEx</option>
      <option value="0" SELECTED>Other</option>
    </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Shipping Type</td>
  <td>
    <select name="shipping_type">
      <option value="1">Overnight (1-2 business days)</option>
      <option value="2">2nd Day Air</option>
      <option value="3">Priority (2-3 business days)</option>
      <option value="4" SELECTED>Ground (5-7+ business days)</option>
    </select>
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Tracking Number</td>
  <td><input type="edit" name="tracking_number" size="30"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Subtotal</td>
  <td>$<input type="edit" name="subtotal" size="5"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Tax</td>
  <td>$<input type="edit" name="tax" size="5"></td>
 </tr>
</table>

<a name="items"></a>
<h3>Items Being Ordered</h3>

<div id="orderContent"></div><br>

<input type="submit" value="Order Complete" onClick="return checkItems();">
</form>

<hr>

<div width="100%" class="clear center">
  <div class="itemhead">Name/UPC/Description</div>
  <div class="itemcontent"><textarea name="search_term" onKeyUp="orderInv()" id="search_term" style="height:50px;width:350px;"></textarea></div>
  <div class="itemcontent bold" style="width:220px;font-size:16px;margin:10px 0px 0px 8px;">Start typing to add an item from inventory</div>
</div>
<div class="clear"><br></div>

<div id="orderInvResult"></div>

<hr>

<a name="addNewItem"></a>
<h4>Add New Item to Order</h4>
<table border="0">
 <tr>

  <td class="heading" align="right">UPC</td>
  <td><input type="edit" name="upc" id="newUpc" size="15"><a href="#addNewItem" onClick="addInv()">Search</a></td>
  <td colspan="7"></td>

 </tr>
 <tr>

  <td></td>
  <td id="belowUPC"></td>
  <td colspan="8"><br /></td>

 </tr>
 <tr>

  <td rowspan="2" class="heading" align="right">Name</td>
  <td rowspan="2" colspan="8"><textarea name="name" id="newName" rows="3" style="width:100%;"></textarea></td>
  <td class="heading" align="right">Purchase Price</td>
  <td><input type="edit" name="purchase_price" id="newPurchase_price" size="6"></td>

 </tr>
 <tr>

  <td class="heading" align="right">Sale Price</td>
  <td><input type="edit" name="cost" id="newCost" size="6"></td>

 </tr>
 <tr>

  <td rowspan="2" class="heading" align="right">Description</td>
  <td rowspan="2" colspan="8"><textarea name="descr" id="newDescr" rows="5" style="width:100%;"></textarea></td>
  <td class="heading" align="right">Quantity</td>
  <td><input type="edit" name="qty" id="newQty" size="1" value="1"></td>

 </tr>
 <tr>

  <td class="heading" align="right">Taxable</td>
  <td><input type="checkbox" name="taxable" id="newTaxable" value="1" CHECKED></td>

 </tr>
 <tr align="center">
  <td colspan="10" id="image"></td>
 </tr>
 <tr>
  <td style="font-weight: bold; text-align:right;">Category</td>
  <td colspan="9">
<?php

$result = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NULL ORDER BY category_name");
$result2 = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent_id IS NOT NULL ORDER BY category_name");
$SUBS = array();
while ($row2 = mysql_fetch_assoc($result2)) {
	if (!isset($SUBS[$row2["parent_id"]])) $SUBS[$row2["parent_id"]] = array();
	$SUBS[$row2["parent_id"]][] = $row2;
}
$x = 0;
while($row = mysql_fetch_assoc($result)){
	$var1="catContain";

	if(is_int($x/3)){
		$var1="catContain clear";
	}

	//$result2 = mysql_query("SELECT * FROM categories WHERE category_set = 'inventory' AND parent = ".$row["category_id"]);
	if (isset($SUBS[$row["id"]])) {
		$onClick = "icatclick($x,{$row["id"]})";
		$subs = " (".count($SUBS[$row["id"]]).")";
	} else {
		$onClick = "hideAll()";
		$subs = "";
	}

	echo "<div class=\"". $var1 ."\">
       <div id=\"ibut". $row["id"] ."\" onClick=\"$onClick\" class=\"ibut catButton\"><input class=\"floatL\" type=\"radio\" name=\"device_type\" id=\"newDeviceType\" value=\"". $row["id"] ."\">". $row["category_name"] . $subs ."</div>
	   <input type=\"hidden\" id=\"catname". $row["id"] ."\" value=\"". $row["category_name"] ."\">
	   <div id=\"ihid". $row["id"] ."\" class=\"ihid clear\" style=\"display:none;\" align=\"left\">";
	if (isset($SUBS[$row["id"]])) {
    	foreach($SUBS[$row["id"]] as $row2) {
    		echo "<input type=\"radio\" name=\"device_type\" id=\"newDeviceType\" value=\"". $row2["id"] ."\">". $row2["category_name"] ."<br>\n";
    	}
	}
    echo "</div></div>\n";
    $x++;
  }
?>
  </td>
 </tr>
 <tr align="center">
  <td colspan="10"><input type="button" value="Add Item to Order" onClick="addToOrder();"></td>
 </tr>
 <tr align="center">
  <td colspan="10"><br /></td>
 </tr>
</table>

</form>
