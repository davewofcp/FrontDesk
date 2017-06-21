<script type="text/javascript">
function purchase(id,price) {
	document.getElementById('inventory_id').value = id;
	document.getElementById('unit_price').value = price;
	document.getElementById('searchInvResult').innerHTML = '';
	document.getElementById('inventory_id_display').innerHTML = '<b>'+id+'</b>';
}
function toggleNewItemFields(obj) {
	if (obj.checked) {
		document.getElementById('newItemFields').style.display = '';
		document.getElementById('inventorySearch').style.display = 'none';
		document.getElementById('inventory_id').value = '0';
		document.getElementById('searchInvResult').innerHTML = '';
		document.getElementById('inventory_id_display').innerHTML = '<b>N/A</b>';
	} else {
		document.getElementById('newItemFields').style.display = 'none';
		document.getElementById('inventorySearch').style.display = '';
	}
}

var xmlINV;
var xmldesc;
var xmlsearchInv;
if (window.XMLHttpRequest) {
	xmlINV=new XMLHttpRequest();
	xmldesc=new XMLHttpRequest();
	xmlsearchInv=new XMLHttpRequest();
} else {
	xmlINV=new ActiveXObject("Microsoft.XMLHTTP");
	xmldesc=new ActiveXObject("Microsoft.XMLHTTP");
	xmlsearchInv=new ActiveXObject("Microsoft.XMLHTTP");
}

xmlsearchInv.onreadystatechange = function() {
	if (xmlsearchInv.readyState == 4 && xmlsearchInv.status == 200) {
	  var tt = JSON.parse(xmlsearchInv.responseText);
    document.getElementById("searchInvResult").style.opacity = 1;
    document.getElementById("searchInvResult").innerHTML = tt.content;
	}
}

function searchInv() {
	var sb = document.getElementById("search_term");
	xmlsearchInv.open("GET","inv/ajax.php?cmd=INV&str="+sb.value+"&invPurchase",true);
	xmlsearchInv.send();
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

var desiheight;
var desih;
function fadeInHeight(elem, chunk, speed, size){
  if(desiheight)clearInterval(desiheight);
      desiheight = setInterval(function(){
        if(!elem.style.height)elem.style.height="0px";
        desih = parseInt(elem.style.height);
        desih=desih+chunk;

        elem.style.height = ""+desih+"px";
        if(desih >= size)clearInterval(desiheight);
      }, speed);
}

var desoheight;
var desoh;
function fadeOutHeight(elem, chunk, speed){
  if(desoheight)clearInterval(desoheight);
      desoheight = setInterval(function(){
        if(!elem.style.height)elem.style.height="100px";
        desoh = parseInt(elem.style.height);
        desoh=desoh-chunk;
        elem.style.height = ""+desoh+"px";

        if(desoh <= 100)elem.style.display="none";
        if(desoh <= 0){
          clearInterval(desoheight);
          elem.style.height="0px";
        }
      }, speed);
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
        document.getElementById("name").innerHTML = jinv[0].productname;
        document.getElementById("purchase_price").value = jinv[0].price;
        document.getElementById("image").innerHTML = "<img src=\""+ jinv[0].imageurl +"\">";

        xmldesc.onreadystatechange = function() {
          if (xmldesc.readyState == 4 && xmldesc.status == 200) {
            document.getElementById("descr").innerHTML = xmldesc.responseText;
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
  tinv = document.getElementById("upc");

	xmlINV.open("GET","inv/ajax.php?cmd=search&str="+tinv.value+"&id=1",true);
	xmlINV.send();
}

function hideAll() {
	var classes = getElementsByClass("ihid");
	for(a in classes){
	    classes[a].style.display="none";
	}
}

</script>
<style type="text/css">
.catButton {
  position: relative;
  float: left;
  width: 150px;
  margin: 3px 1px 3px 1px;
  padding: 3px;
  border-radius: 3px;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
  background: #CCCCFF;
}
.catContain {
  position:relative;
  float:left;
}
.ihid {
  position: relative;
  width:140px;
  float: right;
  margin-top:-4px;
  border-radius: 5px;
    -moz-border-radius: 5px;
    -webkit-border-radius: 5px;
  padding-left:5px;
  border: 2px solid #003399;
  background: #CCCCFF;
  /*overflow: auto;*/
}

.background {
  background: #CCCCFF;
}
.border {
  border: 2px solid #003399;
}

</style>
<h3>Purchase Inventory</h3>
<form action="?module=cust&do=purchase&id=<?php echo $_GET["id"]; ?>" method="post">
New Item? <input type="checkbox" name="new_item" value="1" onChange="toggleNewItemFields(this);"><br>

<div id="newItemFields" style="display:none;">
<h3>Add New Item to Inventory</h3>
<table border="0">
 <tr>

  <td class="heading" align="right">UPC</td>
  <td><input type="edit" name="upc" id="upc" size="15"><a href="#" onClick="addInv()">Search</a></td>
  <td colspan="7"></td>
  <td class="heading" align="right">Item Number</td>
  <td><input type="edit" name="item_number" size="5"></td>

 </tr>
 <tr>

  <td></td>
  <td id="belowUPC"></td>
  <td colspan="8"><br /></td>

 </tr>
 <tr>

  <td rowspan="2" class="heading" align="right">Name</td>
  <td rowspan="2" colspan="8"><textarea name="name" id="name" rows="3" style="width:100%;"></textarea></td>
  <td class="heading" align="right">Purchase Price</td>
  <td><input type="edit" name="purchase_price" id="purchase_price" size="6"></td>

 </tr>
 <tr>

  <td class="heading" align="right">Sale Price</td>
  <td><input type="edit" name="cost" id="cost" size="6"></td>

 </tr>
 <tr>

  <td rowspan="2" class="heading" align="right">Description</td>
  <td rowspan="2" colspan="8"><textarea name="descr" id="descr" rows="5" style="width:100%;"></textarea></td>

 </tr>
 <tr>

  <td class="heading" align="right">Taxable</td>
  <td><input type="checkbox" name="taxable" id="taxable" value="1" CHECKED></td>

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
       <div id=\"ibut". $row["id"] ."\" onClick=\"$onClick\" class=\"ibut catButton\"><input class=\"floatL\" type=\"radio\" name=\"device_type\" value=\"". $row["id"] ."\">". $row["category_name"] . $subs ."</div>
       <div id=\"ihid". $row["id"] ."\" class=\"ihid clear\" style=\"display:none;\" align=\"left\">";
	if (isset($SUBS[$row["id"]])) {
    	foreach($SUBS[$row["id"]] as $row2) {
    		echo "<input type=\"radio\" name=\"device_type\" value=\"". $row2["id"] ."\">". $row2["category_name"] ."<br>\n";
    	}
	}
    echo "</div></div>\n";
    $x++;
  }
?>
  </td>
 </tr>
</table>
</div><br>

<h3>Purchase Details</h3>

<input type="hidden" name="inventory_id" id="inventory_id" value="0">
<table border="0">
 <tr>
  <td>Inventory ID</td>
  <td><div id="inventory_id_display"><b>N/A</b></div></td>
 </tr>
 <tr>
  <td>Quantity Purchased</td>
  <td><input type="text" name="qty" size="3"></td>
 </tr>
 <tr>
  <td>Unit Price</td>
  <td>$<input type="text" id="unit_price" name="unit_price" size="5"></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
  Serial Numbers (one per line):<br>
  <textarea name="serial_numbers" rows="2" cols="25"></textarea>
  </td>
 </tr>
</table><br>

<input type="submit" value="Purchase Inventory">
</form>

<div id="inventorySearch">
<h3>Inventory Item Being Purchased</h3>
<div width="100%" class="clear center">
  <div class="itemhead">Name/UPC/Description</div>
  <div class="itemcontent"><textarea name="search_term" onKeyUp="searchInv()" id="search_term" style="height:50px;width:350px;"></textarea></div>
  <div class="itemcontent bold" style="width:220px;font-size:16px;margin:10px 0px 0px 8px;">Start typing to select inventory item</div>
</div>
<div class="clear"><br></div>

<div id="searchInvResult"></div>
<div class="clear"><br><br></div>
</div>
