<?php
display_header();
  //$data = mysql_fetch_assoc(mysql_query("SELECT IFNULL(customer_id,0) AS customer_id FROM invoices WHERE invoice_id = ". $IID ." LIMIT 1"));
  //$CUSTOMER_ID = $data["customer_id"];
  if(isset($_GET["customer_id"])){
    $CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ". $_GET["customer_id"]));
  }

?>
<script type="text/javascript">
var xmlinvoiceInv;
var xmlhttp;
if (window.XMLHttpRequest) {
  // code for IE7+, Firefox, Chrome, Opera, Safari
	xmlinvoiceInv=new XMLHttpRequest();
	xmlhttp=new XMLHttpRequest();
} else {
  // code for IE6, IE5
	xmlinvoiceInv=new ActiveXObject("Microsoft.XMLHTTP");
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
}

xmlinvoiceInv.onreadystatechange = xmlinvoiceResult;

function xmlinvoiceResult() {
	if (xmlinvoiceInv.readyState == 4 && xmlinvoiceInv.status == 200) {
	  var tt = JSON.parse(xmlinvoiceInv.responseText);
    document.getElementById("invoiceInvResult").style.opacity = 1;
    document.getElementById("invoiceInvResult").innerHTML = tt.content;
	}
}

xmlhttp.onreadystatechange = function() {
	if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
		document.getElementById("resultbox").innerHTML = xmlhttp.responseText;
		document.getElementById("resultbox").style.border = "1px solid #120A8F";
	}
}

function invoiceResult() {
	var s = document.getElementById("search");
	document.getElementById("resultbox").innerHTML = "";
	document.getElementById("resultbox").style.border = "0px";
	var sb = document.getElementById("searchby");
	var sbOption = sb.options[sb.selectedIndex].value;
	if (s.value == '') return;
	xmlhttp.open("GET","cust/ajax.php?cmd=search&str="+s.value+"&sb="+sbOption+"&page=invoice",true);
	xmlhttp.send();
}

function invoiceInv(all) {
  var sb = document.getElementById("search_term");
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
	xmlinvoiceInv.abort();
	xmlinvoiceInv.onreadystatechange = xmlinvoiceResult;
	xmlinvoiceInv.open("GET","inventory/ajax.php?cmd=INV&cat="+cat+allOpt+soOpt+"&str="+sb.value+"&invoiceINV",true);
	xmlinvoiceInv.send();
}

function invoiceListItems(id) {
	document.getElementById("invoiceInvResult").innerHTML = "<b>Getting Item List...</b>";
	document.getElementById("invoiceInvResult").style.border = "0px";
	xmlinvoiceInv.open("GET","inventory/ajax.php?cmd=list_items_invc&id="+id,true);
	xmlinvoiceInv.send();
}

var id,name,descr,purchase_price,cost,qty,taxable,device_type;
var invCount=0;
function invoiceInv_add(name,descr,purchase_price,cost,qty,taxable,inventory_id,inv_item_id){
  document.getElementById("invoiceInvResult").innerHTML="";
  document.getElementById("search_term").value="";
  var invContent = document.getElementById("invContent");
  var str = "";
  invCount++;
  str += "<div id=\"itemid"+invCount+"\" class=\"relative clear center margin5 padding5\" style=\"border:5px ridge #000;border-top:1px solid #000;border-left:1px solid #000;border-radius:10px;\">";
    str += "<input type=\"hidden\" id=\"invCount\" name=\"invCount[]\" value=\""+invCount+"\">";
    str += "<input type=\"hidden\" id=\"invId\" name=\"invId[]\" value=\""+inventory_id+"\">";
    str += "<input type=\"hidden\" id=\"invItemId\" name=\"invItemId[]\" value=\""+inv_item_id+"\">";
    str += "<div class=\"inline padding5\"><b>Item #"+invCount+"</b> -</div>";
    str += "<div class=\"inline padding3\"><a href=\"#delete_inv\" class=\"link\" onClick=\"invoiceInv_delete("+invCount+");\" onMouseOver=\"this.setAttribute('class','link_hover');\" onMouseOut=\"this.setAttribute('class','link');\">Delete</a></div><br>";

    str += "<div class=\"floatL relative left\">";
      str += "<div class=\"itemcontent relative\" style=\"position:relative;\"><b>Name:</b><br><textarea id=\"invName\" name=\"invName[]\" style=\"position:relative;height:60px;width:250px;z-index:99;\">"+name+"</textarea>";
        str += "<br><b>Cost:</b> $<input type=\"edit\" id=\"invCost\" name=\"invCost[]\" size=\"3\" value=\""+cost+"\"> ";
        if(purchase_price)str += " <b>Our Cost:</b> $"+purchase_price+"";
      str += "</div>";
      str += "<div class=\"itemcontent relative bolder\">Description:<br><textarea id=\"invDescr\" name=\"invDescr[]\" style=\"position:relative;height:90px;width:300px;z-index:99;\">"+descr+"</textarea></div>";
    str += "</div>";

    str += "<div class=\"floatL relative padding5 left\">";
      str += "<div class=\"relative bolder\"><br>Qty:<input id=\"invQty\" name=\"invQty[]\" type=\"edit\" size=\"1\" value=\"1\"></div>";
      if(qty)str += "<div class=\"relative\"><b>Qty Remaining:</b> "+qty+"</div>";
      str += "<div class=\"relative bolder\">Taxable:<select id=\"invTax\" name=\"invTax[]\"><option value=\"1\">Yes</option><option value=\"0\">No</option></select></div>";
    str += "</div>";
    str += "<div class=\"clear\"></div>";
  str += "</div>";
  var row = invContent.insertRow(-1);
  var cell = row.insertCell(0);
  cell.innerHTML = str;
}

var del_id;
function invoiceInv_delete(del_id){
  document.getElementById("itemid"+del_id).innerHTML="";
  document.getElementById("itemid"+del_id).style.border="";
  document.getElementById("itemid"+del_id).style.display="none";
}

function invoiceCheck(){

var errorStr;
var elemName;
var iOld;
var bordVal = "4px ridge red";
var nameVal,descrVal,qtyVal,costVal,taxVal;
var nameValOld,descrValOld,qtyValOld,costValOld,taxValOld;

  errorStr="";
  iOld=0;
  nameVal=0,descrVal=0,qtyVal=0,costVal=0,taxVal=0;
  if(createInvoice.elements.length<6){
    alert("You must add an item from inventory or add a new blank item");
    return false;
  }
  for (i=0; i<createInvoice.elements.length; i++){
    var invCountObj = createInvoice.elements["invCount"];
    var formElement = createInvoice.elements[i];
    var countVal=0;
    elemName="";
    formElement.style.border="";

    nameValOld=nameVal,descrValOld=descrVal,qtyValOld=qtyVal,costValOld=costVal,taxValOld=taxVal;

    elemName="invName";
    if (formElement.id==elemName && formElement.value==""){
      if( Object.prototype.toString.call(invCountObj) === "[object NodeList]"){countVal = createInvoice.elements["invCount"][nameVal].value;
      } else if( Object.prototype.toString.call(invCountObj) === "[object HTMLInputElement]"){countVal = createInvoice.elements["invCount"].value;
      } else {
        alert("Error::"+elemName+"::Closing Loop");
        countVal=0;
        return false;
      }
      errorStr += "\nEnter a Name for Item #"+countVal;
      formElement.style.border=bordVal;
      nameVal++;
    }

    elemName="invDescr";
    if (formElement.id==elemName && formElement.value==""){
      if( Object.prototype.toString.call(invCountObj) === "[object NodeList]"){countVal = createInvoice.elements["invCount"][descrVal].value;
      } else if( Object.prototype.toString.call(invCountObj) === "[object HTMLInputElement]"){countVal = createInvoice.elements["invCount"].value;
      } else {
        alert("Error::"+elemName+"::Closing Loop");
        countVal=0;
        return false;
      }
      errorStr += "\nEnter a Description for Item #"+countVal;
      formElement.style.border=bordVal;
      descrVal++;
    }

    elemName="invCost";
    if (formElement.id==elemName && (formElement.value=="" || isNaN(formElement.value))){
      if( Object.prototype.toString.call(invCountObj) === "[object NodeList]"){countVal = createInvoice.elements["invCount"][costVal].value;
      } else if( Object.prototype.toString.call(invCountObj) === "[object HTMLInputElement]"){countVal = createInvoice.elements["invCount"].value;
      } else {
        alert("Error::"+elemName+"::Closing Loop");
        countVal=0;
        return false;
      }
      if(formElement.value==""){
        errorStr += "\nEnter a Cost for Item #"+countVal;
      } else if(isNaN(formElement.value)){
        errorStr += "\nInvalid Cost for Item #"+countVal;
      } else {}
      formElement.style.border=bordVal;
      costVal++;
    }

    elemName="invQty";
    if (formElement.id==elemName && (formElement.value=="" || isNaN(formElement.value))){
      if( Object.prototype.toString.call(invCountObj) === "[object NodeList]"){countVal = createInvoice.elements["invCount"][qtyVal].value;
      } else if( Object.prototype.toString.call(invCountObj) === "[object HTMLInputElement]"){countVal = createInvoice.elements["invCount"].value;
      } else {
        alert("Error::"+elemName+"::Closing Loop");
        countVal=0;
        return false;
      }
      if(formElement.value==""){
        errorStr += "\nEnter Qty for Item #"+countVal;
      } else if(isNaN(formElement.value)){
        errorStr += "\nInvalid Qty for Item #"+countVal;
      } else {}
      formElement.style.border=bordVal;
      qtyVal++;
    }

    if(iOld+5<i){
      iOld=i;
      //errorStr += "\n";
    }

  }

  //errorStr = errorStr.replace("\n","");
  //errorStr = errorStr.replace(" ","");
  //errorStr = errorStr.replace(/(\r\n|\n|\r)/gm,"");

  if(errorStr!=""){
    alert(errorStr);
    return false;
  }

}

</script>
<style type="text/css">
  .inv_description{
    height: 90px;
    width: 300px;
  }
  .inv_address{
    height: 60px;
    width: 200px;
  }
  .vertical{
    vertical-align: top;
    text-align: center;
  }
</style>
<?php
 if(isset($CUSTOMER)){
    if(!isset($_GET["customer_id"])){
?>

<h2>Create Invoice</h2>
<div class="relative inline padding10"><?php echo alink("Create New Invoice","?module=invoice&do=create"); ?></div>
<div class="relative inline padding10"><?php echo alink("View Invoices","?module=invoice"); ?></div>

<?php
    } else {
      echo "
        <h2>Create Invoice</h2>
        <div class=\"relative inline padding10\">". alink("Back to Invoices","?module=invoice") ."</div>
        <div class=\"clear\"><br></div>";
    }
  } else {
    echo alink("+ Add New Customer","?module=cust&do=new");
?>

<div class="clear"><br></div>
<h3>Select Customer for New Invoice</h3>

Search By
<select id="searchby" onChange="invoiceResult()">
<option value="1">Customer ID</option>
<option value="2">First Name</option>
<option value="3">Last Name</option>
<option value="4">Phone</option>
<option value="6" SELECTED>Full Name</option>
<option value="8">Company</option>
</select>
<input id="search" size="30" onKeyUp="invoiceResult()">
<b>OR</b>
<?php echo alink("No Customer","?module=invoice&do=create&customer_id=0"); ?>
<div id="resultbox" style="background-color:#fff;position:absolute;width:400px;left:50%;margin-left:-200px;"></div>
<div class="clear"><br></div>
<br>

<?php
  //echo alink("View All Customers","?module=cust&do=list");
}
?>

<?php
if(isset($CUSTOMER)){
?>
<form action="?module=invoice&do=create" onSubmit="return invoiceCheck();" method="post" name="createInvoice" id="createInvoice">
<table border="0" width="780">
 <tr>
  <td width="75%" valign="top">
   <table border="0" width="100%">
   <?php
    if(intval($_GET["customer_id"])!=0){
   ?>
   <input type="hidden" name="customer_id" value="<?php echo $CUSTOMER["id"]; ?>">
    <tr>
      <td>
        <div width="100%" class="clear center" style="margin-left:35px;">
          <div class="itemhead">Customer Name</div>
          <div class="itemcontent" style="padding:7px 0px 5px 7px;">
            <?php echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; ?>
            <?php echo alink_pop("#".$CUSTOMER["id"],"?module=cust&do=view&id=".$CUSTOMER["id"]); ?>
          </div>

          <div class="itemhead" style="margin-left:27px;">Home Phone</div>
          <div class="itemcontent" style="padding:7px 0px 5px 5px;"><?php echo display_phone($CUSTOMER["phone_home"]); ?></div>

          <div class="itemhead" style="margin-left:17px;">Cell Phone</div>
          <div class="itemcontent" style="padding:7px 0px 5px 5px;"><?php echo display_phone($CUSTOMER["phone_cell"]); ?></div>

          <div class="itemhead clearL">Address</div>
          <div class="itemcontent left" style="padding:7px 0px 5px 5px;"><?php echo $CUSTOMER["address"]."<br>".$CUSTOMER["city"].", ".$CUSTOMER["state"]." ".$CUSTOMER["postcode"]; ?></div>

          <div class="itemhead" style="margin-left:27px;">Company</div>
          <div class="itemcontent" style="padding:7px 0px 5px 5px;"><?php echo ($CUSTOMER["company"] ? $CUSTOMER["company"] : "N/a"); ?></div>

          <div class="itemhead" style="margin-left:27px;">E-mail</div>
          <div class="itemcontent" style="padding:7px 0px 5px 5px;"><?php echo ($CUSTOMER["email"] ? $CUSTOMER["email"] : "N/a"); ?></div>
        </div>
      </td>
    </tr>
    <?php
      }
    ?>
    <tr>
      <td colspan="6" align="center">
      <table border="0" id="invContent" width="100%">
      </table>
      </td>
    </tr>
    <tr>
      <td colspan="6" align="center"><br><br><?php echo alink_onclick("Add Blank Item","#add","invoiceInv_add('','','','','','','','','');"); ?></td>
    </tr>
    <tr>
      <td colspan="6" align="center"><br><input type="submit" value="Create Invoice"></td>
    </tr>
   </table>

  </td>
 </tr>
</table>
</form>

<table border="0">
 <tr>
  <td colspan="2" align="center"><b>Search Inventory</b></td>
 </tr>
 <tr>
  <td align="right" class="heading">Category</td>
  <td><select id="inv_search_cat">
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
  <td><input type="edit" id="search_term" onKeyUp="invoiceInv(0)" size="20" autocomplete="off"></td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <font size="-1"><i>Type at least 3 characters to begin search, or</i></font><br>
   <?php echo alink_onclick("Show All In Category","#","invoiceInv('all');"); ?>
  </td>
 </tr>
</table><br>
<!--
  <div class="clearL"></div>
  <div class="itemhead">Name/UPC/Description</div>
  <div class="itemcontent"><textarea name="search_term" onKeyUp="invoiceInv()" id="search_term" style="height:50px;width:350px;"></textarea></div>
  <div class="itemcontent bold" style="width:220px;font-size:16px;margin:10px 0px 0px 8px;">Start typing to add an item from inventory</div>
</div>
<div class="clear"><br></div> -->

<div id="invoiceInvResult"></div>
<div class="clear"><br><br></div>
<?php

}

?>

<script type="text/javascript">
<?php
  if(isset($CUSTOMER)){
    //echo "  document.getElementById(\"customer_id\").style.width=\"35px\";\n";
  }
?>
</script>

<?php

display_footer();

?>
