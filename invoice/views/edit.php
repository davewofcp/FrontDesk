<?php

display_header();

$data = mysql_fetch_assoc(mysql_query("SELECT IFNULL(customers__id,0) AS customers__id FROM invoices WHERE id = ". $IID ." LIMIT 1"));
if($data){
  $CUSTOMER_ID = intval($data["customers__id"]);
  if ($CUSTOMER_ID > 0) {
  	$CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ". $CUSTOMER_ID));
  }
}

$INVOICE = mysql_fetch_assoc(mysql_query("SELECT * FROM invoices WHERE id = ". $IID));
$INVOICE_ITEMS = mysql_query("SELECT * FROM invoice_items WHERE invoices__id = ". $IID);
$NOTES = mysql_query("SELECT * FROM user_notes LEFT JOIN users ON user_notes.users__id = users.id WHERE for_table = 'invoices' AND for_key = $IID ORDER BY note_ts DESC");

// get default org tax rate
$result = mysql_query("
SELECT
  oe.tax_rate
FROM
  org_entities oe,
  org_entity_types oet
WHERE
  oe.org_entity_types__id = oet.id
  AND oet.title = 'Organization'
  AND tax_rate IS NOT NULL
LIMIT 1
");
if (mysql_num_rows($result)) {
  $data = mysql_fetch_assoc($result);
  $tax_rate = floatval($data["tax_rate"]);
}
// try to get store-specific tax rate
$result = mysql_query("SELECT tax_rate FROM org_entities WHERE id={$USER['org_entities__id']} AND tax_rate IS NOT NULL LIMIT 1");
if (mysql_num_rows($result)) {
  $data = mysql_fetch_assoc($result);
  $tax_rate = floatval($data["tax_rate"]);
}
// hack fallback for now
if (!isset($tax_rate)) $tax_rate = floatval("0.08");

?>
<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<script type="text/javascript">
var invoice_id = '<?php echo $INVOICE["id"]; ?>';
function pay_partial() {
	var amt = parseFloat(document.getElementById('amt').value);
	if (amt <= 0) {
		alert('Amount must be a valid number greater than 0.');
		return;
	}
	window.location = '?module=invoice&do=checkout_partial&id='+invoice_id+'&amt='+encodeURIComponent(''+amt);
}
function apply_discount() {
	var amt = parseFloat(document.getElementById('disc').value);
	if (amt <= 0) {
		alert('Discount must be a valid number greater than 0.');
		return;
	}
	var reason = window.prompt("Reason for discount:");
	if (reason) {
		reason = '&rsn='+encodeURIComponent(reason);
	} else {
		reason = '';
	}
	window.location = '?module=invoice&do=discount&id='+invoice_id+'&amt='+encodeURIComponent(''+amt)+reason;
}
</script>
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

xmlinvoiceInv.onreadystatechange = function() {
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

function invoiceInv() {
  var sb = document.getElementById("search_term");
	xmlinvoiceInv.open("GET","inventory/ajax.php?cmd=INV&str="+sb.value+"&invoiceINV",true);
	xmlinvoiceInv.send();
}

var id,name,descr,purchase_price,cost,qty,taxable,device_type;
var invCount=0;
function invoiceInv_add(name,descr,purchase_price,cost,qty,taxable,inventory_id){
  document.getElementById("invoiceInvResult").innerHTML="";
  document.getElementById("search_term").value="";
  var invContent = document.getElementById("invContent");
  var str = "";
  invCount++;
  str += "<div id=\"itemid"+invCount+"\" class=\"relative clear center margin5 padding5\" style=\"border:5px ridge #000;border-top:1px solid #000;border-left:1px solid #000;border-radius:10px;\">";
    str += "<input type=\"hidden\" id=\"invCount\" name=\"invCount[]\" value=\""+invCount+"\">";
    str += "<input type=\"hidden\" id=\"invId\" name=\"invId[]\" value=\""+inventory_id+"\">";
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
<div class="floatL relative" style="font-size:46px;">
  <br>
<?php

  $result = mysql_query("SELECT * FROM invoices WHERE org_entities__id = {$USER['org_entities__id']} AND id < ".intval($_GET["id"])." ORDER BY id DESC LIMIT 1");
  if(mysql_num_rows($result)){
    $row = mysql_fetch_row($result);
    echo "
    <div class=\"floatL\"><a href=\"?module=invoice&do=view&id=". $row[1] ."\" class=\"arr\">&#9668;</a></div>";
  } else {
    echo "
    <div class=\"floatL relative\" style=\"width:100px;\"></div>";
  }

  $result = mysql_query("SELECT * FROM invoices WHERE org_entities__id = {$USER['org_entities__id']} AND id > ".intval($_GET["id"])." ORDER BY id ASC LIMIT 1");
  if(mysql_num_rows($result)){
    $row = mysql_fetch_row($result);
    echo "
    <a href=\"?module=invoice&do=view&id=". $row[1] ."\" class=\"arr\">&#9658;</a>";
  }
?>
  <br>
</div>


<div class="relative center" style="left:-40px;">
    <h2>Invoice # <?php echo $IID; ?>
    <br>
    <?php if (isset($RESPONSE)) { ?>
    <font size="+1"><?php echo $RESPONSE; ?></font><br>
    <?php } ?>
    <?php
      echo "<div class=\"inline\" style=\"padding:10px;\">". alink("Print","?module=invoice&do=print&id=".$IID) ."</div>";
      echo "&#149;";
      if (floatval($INVOICE["amt_paid"]) == 0) {
	      echo "<div class=\"inline\" style=\"padding:10px;\">". alink("Checkout","?module=invoice&do=checkout&id=".$IID) ."</div>";
	      echo "&#149;";
      }
      echo "<div class=\"inline\" style=\"padding:10px;\">". alink("View","?module=invoice&do=view&id=".$IID) ."</div>";
      echo "&#149;";
      echo "<div class=\"inline\" style=\"padding:10px;\">". alink_onclick("Delete","?module=invoice&do=delete&id=".$IID,"return confirm('Are you sure you wish to delete this Invoice ?');") ."</div>";
    ?></h2><?php

    if (isset($CUSTOMER)) {
    	$result = mysql_query("SELECT * FROM customer_accounts WHERE customers__id = ".$CUSTOMER_ID);
    	if (mysql_num_rows($result)) {
    		$account = mysql_fetch_assoc($result);
    		echo "<br>". alink("Go To Account","?module=acct&do=view&id=".$account["id"]) ."<br>";
    	}

    }

    if ($INVOICE["amt_paid"] < $INVOICE["amt"]) {
    ?>
$ <input type="edit" id="amt" size="5" value="<?php echo number_format($INVOICE["amt"] - $INVOICE["amt_paid"],2); ?>"> <input type="button" value="Make Partial Payment" onClick="pay_partial();"> &nbsp;
$ <input type="edit" id="disc" size="5" value="0.00"> <input type="button" value="Discount" onClick="apply_discount();"><br>
<?php } ?>
</div>


<div class="clear"></div>

<div class="invoicemain">
<?php if (isset($CUSTOMER)) { ?>
  <div class="invoicerow">
    <div class="invoicehead">Name:</div>
    <div class="invoicecontent"><?php echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; ?></div>
  </div>
  <div class="invoicerow clearL">
    <div class="invoicehead">Address</div>
    <div class="invoicecontent" style="min-width:120px;">
      <?php echo $CUSTOMER["address"] .($CUSTOMER["apt"] ? " #".$CUSTOMER["apt"] : ""); ?>
      <br>
      <?php echo $CUSTOMER["city"]; ?>, <?php echo $CUSTOMER["state"]; ?> <?php echo $CUSTOMER["postcode"]; ?>
    </div>
  </div>
<?php } ?>
  <div class="invoicerow">
    <div class="invoicecontent"><?php echo ($INVOICE["amt_paid"] >= $INVOICE["amt"] ? "<img src=\"images/paid-red-small.png\">" : "<img src=\"images/unpaid-black-small.png\">"); ?></div>
  </div>
  <div class="relative floatR">
    <div class="invoicerow">
      <div class="invoicehead">Invoice #:</div>
      <div class="invoicecontent"><?php echo $INVOICE["id"]; ?></div>
    </div>
    <div class="invoicerow clearL">
      <div class="invoicehead">Date:</div>
      <div class="invoicecontent">
        <?php
          $date = strtotime($INVOICE["toi"]);
          $hr = str_pad(date("H",$date),2,'0',STR_PAD_LEFT);
          $min = date("i",$date);
          $dt = date("Y-m-d",$date);
          ?>
          <form action="?module=invoice&do=edit_sub&id=<?php echo $IID; ?>" method="post">
          <input type="edit" name="invoice_date" id="inv_dt" size="10" value="<?php echo $dt; ?>"> @
          <select name="inv_hr">
          <?php

          for ($i = 0; $i < 24; $i++) {
          	$s = "";
          	if ($i == intval($hr)) $s = " SELECTED";
          	echo "<option value=\"$i\"$s>".str_pad("".$i,2,'0',STR_PAD_LEFT)."</option>\n";
          }

          ?>
          </select>
          <input type="edit" name="inv_min" size="2" value="<?php echo $min; ?>"><br>
          <input type="submit" value="Update Date/Time">
          </form>
      </div>
    </div>
  </div>
  <div class="clear"><br></div>
  <table class="invoiceTable">
    <tr class="invoiceTableHeader">
      <td style="width:120px">Item</td>
      <td style="width:30px">Qty</td>
      <td>Description</td>
      <td style="width:70px">Amount</td>
      <td style="width:35px">Tax</td>
      <td style="width:70px;">Delete</td>
    </tr>
<?php

$subtotal = 0;
$taxable = 0;
while ($item = mysql_fetch_assoc($INVOICE_ITEMS)) {

?>
    <tr class="invoiceTableContent">
<?php if ($item["is_heading"] && $item["qty"] > 0) { ?>
      <td class="center" colspan="3"><?php echo $item["name"]; ?></td>
      <td class="center">$<?php echo number_format($item["cost"],2); ?></td>
      <td class="center"><?php echo $item["is_taxable"] ? "Yes" : "No"; ?>
      <td class="center"><?php echo alink_onclick("Delete","?module=invoice&do=delete_item&id={$item["invoices__id"]}&iid={$item["id"]}","javascript:return confirm('Are you sure you want to delete this item?');"); ?></td>
<?php } else if ($item["is_heading"] && $item["qty"] == 0) { ?>
	  <td class="center" colspan="5"><?php echo $item["name"]; ?></td>
	  <td class="center"><?php echo alink_onclick("Delete","?module=invoice&do=delete_item&id={$item["invoices__id"]}&iid={$item["id"]}","javascript:return confirm('Are you sure you want to delete this item?');"); ?></td>
<?php } else { ?>
      <td class="center"><?php echo $item["name"]; ?></td>
      <td class="center"><?php echo $item["qty"]; ?></td>
      <td><?php echo $item["descr"]; ?><?php if ($item["name"] == "Discount") {
		echo " ". alink("Remove","?module=invoice&do=remove_discount&id={$item["invoices__id"]}&did={$item["id"]}");
	} ?></td>
      <td class="center">$<?php echo number_format($item["cost"],2); ?></td>
      <td class="center"><?php echo $item["is_taxable"] ? "Yes" : "No"; ?></td>
      <td class="center"><?php echo alink_onclick("Delete","?module=invoice&do=delete_item&id={$item["invoices__id"]}&iid={$item["id"]}","javascript:return confirm('Are you sure you want to delete this item?');"); ?></td>
    </tr>
<?php
	}

	if ($item["from_table"] == "issues") {
		//$result = mysql_query("SELECT o.value FROM optionvalues o JOIN devices d ON d.location = o.option_id JOIN issues i ON i.device_id = d.device_id WHERE i.issue_id = ".$item["from_key"]);
    $result = mysql_query("SELECT oe.title FROM org_entities oe JOIN inventory_type_devices d ON d.org_entities__id = oe.id JOIN issues i ON i.device_id = d.id WHERE i.id = ".$item["from_key"]);

		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			echo "<tr><td colspan=\"5\" align=\"center\">Location: <b>".$data["title"]."</b> ".alink("View Issue","?module=iss&do=view&id={$item["from_key"]}")."</td></tr>\n";
		}
	}

	$subtotal += $item["cost"] * $item["qty"];
	if ($item["is_taxable"]) $taxable += $item["cost"] * $item["qty"];
}

$total = round($subtotal + ($taxable * $tax_rate),2);

?>
  </table>
  <div class="clear"></div>
  <div class="invoicerow relative floatR">
    <div class="invoicerow floatR" style="margin-right:27px;margin-bottom:0px;">
      <div class="invoicehead absolute" style="left:-87;">Subtotal:</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format(floatval($subtotal),2); ?></div>
    </div>
    <div class="invoicerow floatR clearR" style="margin-right:27px;margin-top:0px;">
      <div class="invoicehead absolute" style="left:-52;">Tax:</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($taxable * $tax_rate,2); ?></div>
    </div>
    <div class="invoicerow floatR clearR" style="margin-right:27px;">
      <div class="invoicehead absolute" style="left:-62;">Total:</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($total,2); ?></div>
    </div>
    <div class="clear"><br></div>
    <div class="invoicerow floatR" style="margin-right:27px;">
      <div class="invoicehead absolute" style="left:-62;">Paid:</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($INVOICE["amt_paid"],2); ?></div>
    </div>
    <div class="invoicerow floatR clearR" style="margin-right:27px;">
      <div class="invoicehead absolute" style="left:-62;">Due:</div>
      <div class="invoicecontent relative bold floatR">$<?php echo number_format($total - $INVOICE["amt_paid"],2); ?></div>
    </div>
  </div>

</div>

<form action="?module=invoice&do=add_items&id=<?php echo $INVOICE["id"]; ?>" onSubmit="return invoiceCheck();" method="post" name="createInvoice" id="createInvoice">

<table border="0" id="invContent" width="100%">
</table><br>

<?php echo alink_onclick("Add Blank Item","#add","invoiceInv_add('','','','','','','','');"); ?>

<div width="100%" class="clear center">
  <div class="itemhead">Name/UPC/Description</div>
  <div class="itemcontent"><textarea name="search_term" onKeyUp="invoiceInv()" id="search_term" style="height:50px;width:350px;"></textarea></div>
  <div class="itemcontent bold" style="width:220px;font-size:16px;margin:10px 0px 0px 8px;">Start typing to add an item from inventory</div>
</div>
<div class="clear"><br></div>

<div id="invoiceInvResult"></div>

<input type="submit" value="Add Items to Invoice">
</form>

<h2>Notes</h2>
<?php

if (mysql_num_rows($NOTES)) {
	while ($note = mysql_fetch_assoc($NOTES)) {
		echo "Added by <b>{$note["firstname"]} {$note["lastname"]}</b> on <b>".date("D, j F Y </\\b>\\a\\t<\\b> h:iA",strtotime($note["note_ts"]))."</b><br>";
		echo $note["note"] ."\n<hr>\n";
	}
} else {
	echo "<i>No Notes</i><br><br>";
}

?>
<font size="+2">Add Note</font><br>
<form action="?module=invoice&do=add_note&id=<?php echo $IID; ?>" method="post">
<textarea name="note" rows="10" cols="50"></textarea><br>
<input type="submit" value="Add Note">
</form>

<?php echo alink("Back to Invoices","?module=invoice"); ?>
<div class="clear"><br></div>
<div class="clear"><br></div>
<div class="clear"><br></div>
<script type="text/javascript">
calendar.set("inv_dt");
</script>
<?php

display_footer();

?>
