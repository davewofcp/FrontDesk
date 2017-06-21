<?php display_header(); ?>
<script type="text/javascript"><!--
var aryClassElements = new Array();
function showPOS() {
  aryClassElements.length = 0;
  getElementsByClassName( "hiderow",document.body );
  for ( var i = 0; i < aryClassElements.length; i++ ) {
    aryClassElements[i].style.visibility = "visible";
  }
}
function hidePOS() {
  aryClassElements.length = 0;
  getElementsByClassName( "hiderow",document.body );
  for ( var i = 0; i < aryClassElements.length; i++ ) {
    aryClassElements[i].style.visibility = "hidden";
  }
}
function getElementsByClassName( strClassName, obj ) {
  if ( obj.className == strClassName ) {
    aryClassElements[aryClassElements.length] = obj;
  }
  for ( var i = 0; i < obj.childNodes.length; i++ )
    getElementsByClassName( strClassName, obj.childNodes[i] );
  }
//--></script>
<?php
  /*
  if(isset($_GET["do"]) && $_GET["do"]=="set_customer"){

  }else{
    echo '
    <a onClick="showPOS();" href="#">Choose a customer to show the POS or click here</a>';
  }
  */
?>
<script type="text/javascript">
var xmlhttp_cust;
var xmlhttp_cart;
var xmlhttp_item;
if (window.XMLHttpRequest) {
  // code for IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp_cust=new XMLHttpRequest();
	xmlhttp_cart=new XMLHttpRequest();
	xmlhttp_item=new XMLHttpRequest();
} else {
  // code for IE6, IE5
	xmlhttp_cust=new ActiveXObject("Microsoft.XMLHTTP");
	xmlhttp_cart=new ActiveXObject("Microsoft.XMLHTTP");
	xmlhttp_item=new ActiveXObject("Microsoft.XMLHTTP");
}

xmlhttp_cust.onreadystatechange = function() {
	if (xmlhttp_cust.readyState == 4 && xmlhttp_cust.status == 200) {
		document.getElementById("cust_resultbox").innerHTML = xmlhttp_cust.responseText;
		document.getElementById("cust_resultbox").style.border = "1px solid #120A8F";
		document.getElementById("cust_resultbox").style.visibility = "visible";
	}
}
xmlhttp_item.onreadystatechange = function() {
	if (xmlhttp_item.readyState == 4 && xmlhttp_item.status == 200) {
		document.getElementById("resultTwo").innerHTML = xmlhttp_item.responseText;
		document.getElementById("resultTwo").style.border = "1px solid #120A8F";
		document.getElementById("resultTwo").style.visibility = "visible";
	}
}
xmlhttp_cart.onreadystatechange = function() {
	if (xmlhttp_cart.readyState == 4 && xmlhttp_cart.status == 200) {
		var data = xmlhttp_cart.responseText.split(":");
		document.getElementById("subtotal").value = data[0];
		document.getElementById("tax").value = data[1];
		document.getElementById("total").value = data[2];
		recalculate();
	}
}
function cust_showResult() {
	var s = document.getElementById("search");
	document.getElementById("cust_resultbox").innerHTML = "";
	document.getElementById("cust_resultbox").style.border = "0px";
	document.getElementById("cust_resultbox").style.visibility = "hidden";
	var sb = document.getElementById("searchby");
	var sbOption = sb.options[sb.selectedIndex].value;
	if (s.value == '') return;
	xmlhttp_cust.open("GET","cust/ajax.php?cmd=psearch&str="+s.value+"&sb="+sbOption,true);
	xmlhttp_cust.send();
}
function showTwo() {
	var q = document.getElementById("descr");
	document.getElementById("resultTwo").innerHTML = "";
	document.getElementById("resultTwo").style.border = "0px";
	document.getElementById("resultTwo").style.visibility = "hidden";
	document.getElementById("resultTwo").style.display = "block";
	var sbOption = 5;
	if (q.value == '') return;
	xmlhttp_item.open("GET","cust/ajax.php?cmd=pbsearch&str="+q.value+"&sb="+sbOption,true);
	xmlhttp_item.send();
}
function addItem(descr,cost,qty,taxable){
  document.getElementById("descr").value = descr;
  document.getElementById("amt").value = cost;
  document.getElementById("qty").value = qty;
  if(taxable==1){
    document.getElementById("taxable").checked=true;
  } else {
    document.getElementById("taxable").checked=false;
  }
	document.getElementById("resultTwo").innerHTML = "";
	document.getElementById("resultTwo").style.border = "0px";
	document.getElementById("resultTwo").style.visibility = "hidden";
}
function cart_update(id,qty) {
	xmlhttp_cart.open("GET","<?php echo $ACTIVE_MODULE; ?>/ajax.php?cmd=cart_update&id="+id+"&qty="+qty,true);
	xmlhttp_cart.send();
}

function add_cash(amt) {
	var cash = parseFloat(document.getElementById("paid_cash").value);
	cash += amt;
	document.getElementById("paid_cash").value = cash.toFixed(2);
	recalculate();
}

function recalculate() {
	var cash = parseFloat(document.getElementById("paid_cash").value);
	var credit = parseFloat(document.getElementById("paid_credit").value);
	var check = parseFloat(document.getElementById("paid_check").value);
	var total = cash + credit + check;
	document.getElementById("total_paid").value = total.toFixed(2);
	var owed = parseFloat(document.getElementById("total").value);
	if (total >= owed) {
		var change = total - owed;
		document.getElementById("change").value = change.toFixed(2);
		document.getElementById("complete").disabled = false;
	} else {
		document.getElementById("change").value = "0.00";
		document.getElementById("complete").disabled = true;
	}
	if (check > 0) {
		document.getElementById("check_no_box").style.backgroundColor = "#FFFFAA";
		document.getElementById("check_no_box").style.color = "#000000";
		if (document.getElementById("check_drop_box")) {
			document.getElementById("check_drop_box").style.backgroundColor = "#FFFFAA";
			document.getElementById("check_drop_box").style.color = "#000000";
		}
	} else {
		document.getElementById("check_no_box").style.backgroundColor = "#003399";
		document.getElementById("check_no_box").style.color = "#FFFFFF";
		if (document.getElementById("check_drop_box")) {
			document.getElementById("check_drop_box").style.backgroundColor = "#003399";
			document.getElementById("check_drop_box").style.color = "#FFFFFF";
		}
	}
}

function clear_all() {
	document.getElementById("paid_cash").value = "0.00";
	document.getElementById("paid_credit").value = "0.00";
	document.getElementById("paid_check").value = "0.00";
	document.getElementById("check_no").value = "";
	recalculate();
}

window.onload = function() {
	document.getElementById('barcode').focus();
}

</script>

<?php if (isset($RESPONSE)) { ?><font color="red"><b><?php echo $RESPONSE; ?></b></font><br><?php } ?>

<form action="?module=pos&do=scan" method="post">
<b>Scan UPC:</b> <input type="edit" id="barcode" name="barcode" autocomplete="off">
</form>

<table border="1" width="780">
 <tr>
  <td colspan="2" align="center">
<?php

echo alink("Transaction History","?module=pos&do=thist")." &nbsp;&nbsp;&nbsp;&nbsp; ";

if ($SESSION["customer_ts"]) {
	$CUSTOMER = mysql_fetch_assoc(mysql_query("SELECT * FROM customers WHERE id = ". intval($SESSION["customers__id"])));
	if ($CUSTOMER) {
		echo "<b>Customer:</b> #". $CUSTOMER["id"] ." ". $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"] ." ";
		echo alink("Change","?module=pos&do=remove_customer")."<br>\n";
		$customer_id = $CUSTOMER["id"];
	} else {
		unset($CUSTOMER);
	}
}

if (!isset($CUSTOMER) || !$CUSTOMER) {
	$customer_id = 0;
?>
<b>Choose Customer:</b>
<select id="searchby" onChange="cust_showResult()">
  <option value="1">Customer ID</option>
  <option value="2">First Name</option>
  <option value="3">Last Name</option>
  <option value="4">Phone</option>
  <option value="6" SELECTED>Full Name</option>
</select>
<input id="search" size="30" onKeyUp="cust_showResult()">
<div id="cust_resultbox" style="background-color:#FFFFFF;position:absolute;left:50%;margin-left:0px;z-index:1;width:400px;"></div>
<?php
}
?>
  </td>
 </tr>
 <tr class="hiderow">
  <td width="75%" valign="top">
   <table border="1" width="100%">
    <tr class="major_heading" align="center">
     <td><font color="#FFFFFF"><b>Delete</b></font></td>
     <td><font color="#FFFFFF"><b>.</b></font></td>
     <td><font color="#FFFFFF"><b>Item Name</b></font></td>
     <td><font color="#FFFFFF"><b>ID #</b></font></td>
     <td><font color="#FFFFFF"><b>Price</b></font></td>
     <td><font color="#FFFFFF"><b>Units</b></font></td>
     <td><font color="#FFFFFF"><b>Taxable</b></font></td>
    </tr>
<?php

$result = mysql_query("SELECT * FROM pos_cart_items WHERE users__id__sale = '". $USER["id"] ."' ORDER BY id");
$i = 1;
$SUBTOTAL = 0;
$TAXABLE = 0;
while ($row = mysql_fetch_assoc($result)) {
	echo " <tr align=\"center\">\n";
	echo " <form action=\"?module=pos&do=delete&id=". $row["id"] ."\" method=\"post\">\n";
	echo "  <td><input type=\"submit\" value=\"X\"></td>\n";
	echo " </form>\n";
	echo "  <td>". $i++ ."</td>\n";
	echo "  <td>". $row["descr"] ."</td>\n";
	echo "  <td>". $row["from_key"] ."</td>\n";
	if ($row["is_heading"]) {
		echo "  <td>$". number_format(floatval($row["amt"]),2) ."</td>\n";
		echo "  <td colspan=\"2\"></td>\n";
	} else {
		echo "  <td>$". number_format(floatval($row["amt"]),2) ."</td>\n";
		echo "  <td><input type=\"edit\" value=\"". $row["qty"] ."\" onKeyUp=\"cart_update('". $row["id"] ."',parseInt(this.value))\" size=\"3\"></td>\n";
		echo "  <td>". ($row["is_taxable"] ? "Yes" : "No") ."</td>\n";
	}
	echo " </tr>\n";
	if ($row["qty"] == 0) {
		$total = $row["amt"];
	} else {
		$total = $row["amt"] * $row["qty"];
	}
	$SUBTOTAL += floatval($total);
	if ($row["is_taxable"]) $TAXABLE += floatval($total);
}

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

$SUBTOTAL = round($SUBTOTAL,2);
$TAX = round($TAXABLE * $tax_rate,2);
$TOTAL = $SUBTOTAL + $TAX;

?>
   </table>
  </td>
  <td width="25%" align="center" valign="top">
   <form action="?module=pos&do=pop" method="post">
   <input type="submit" style="width:210px;" value="Open Drawer">
   </form>

   <div style="text-align:left;margin-left:-10px;"><ul>
   <?php

    echo "<li style=\"margin:-10px;\">";
      echo alink("Drawer Adjustment","?module=pos&do=cash_adjust");
    echo "</li>";

    echo "<br>";

    echo "<li style=\"margin:-10px;\">";
      echo alink("Make Drop","?module=pos&do=drop");
    echo "</li>";

   ?></ul>
   </div>

   <table border="1" width="100%" class="hiderow">
    <tr>
     <td bgcolor="#120A8F" colspan="2" align="center">
     <font color="#FFFFFF"><b>Payment</b></font>
     </td>
    </tr>
    <tr>
     <td><input type="submit" style="width:100px;" onClick="add_cash(1)" value="$1.00"></td>
     <td><input type="submit" style="width:100px;" onClick="add_cash(5)" value="$5.00"></td>
    </tr>
    <tr>
     <td><input type="submit" style="width:100px;" onClick="add_cash(10)" value="$10.00"></td>
     <td><input type="submit" style="width:100px;" onClick="add_cash(20)" value="$20.00"></td>
    </tr>
    <tr>
     <td><input type="submit" style="width:100px;" onClick="add_cash(50)" value="$50.00"></td>
     <td><input type="submit" style="width:100px;" onClick="add_cash(100)" value="$100.00"></td>
    </tr>
    <form action="?module=pos&do=complete" method="post">
    <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
    <tr>
     <td align="right" class="heading" style="font-size:10pt;"><b>Subtotal</b></td>
     <td><input type="edit" id="subtotal" name="subtotal" style="width:100px;" value="<?php echo number_format($SUBTOTAL,2,'.',''); ?>" size="6"></td>
    </tr>
    <tr>
     <td align="right" class="heading" style="font-size:10pt;"><b>Tax</b></td>
     <td><input type="edit" id="tax" name="tax" style="width:100px;" value="<?php echo number_format($TAX,2,'.',''); ?>" size="6"></td>
    </tr>
    <tr>
     <td align="right" class="heading" style="font-size:10pt;"><b>Amount Owed</b></td>
     <td><input type="edit" id="total" name="total" style="width:100px;" value="<?php echo number_format($TOTAL,2,'.',''); ?>" size="6"></td>
    </tr>
    <tr bgcolor="#AAFFAA">
     <td align="right" style="font-size:10pt;"><b>Cash Amount</b></td>
     <td><input type="edit" id="paid_cash" name="paid_cash" onKeyUp="recalculate()" style="width:100px;" value="0.00" size="6"></td>
    </tr>
    <tr bgcolor="#AAFFAA">
     <td align="right" style="font-size:10pt;"><b>Credit Amount</b></td>
     <td><input type="edit" id="paid_credit" name="paid_credit" onKeyUp="recalculate()" style="width:100px;" value="0.00" size="6"></td>
    </tr>
    <tr bgcolor="#AAFFAA">
     <td align="right" style="font-size:10pt;"><b>Check Amount</b></td>
     <td><input type="edit" id="paid_check" name="paid_check" onKeyUp="recalculate()" style="width:100px;" value="0.00" size="6"></td>
    </tr>
    <tr>
     <td align="right" style="font-size:10pt;background-color:#003399;color:#FFFFFF;" id="check_no_box"><b>Check Number</b></td>
     <td><input type="edit" id="check_no" name="check_no" style="width:100px;" value="" size="6"></td>
    </tr>
<?php if (TFD_HAS_PERMS('admin','use')) { ?>
    <tr>
     <td align="right" style="font-size:10pt;background-color:#003399;color:#FFFFFF;" id="check_drop_box"><b>Auto-Drop Check?</b></td>
     <td><input type="checkbox" id="check_drop" name="check_drop" value="1"></td>
    </tr>
<?php } ?>
    <tr>
     <td align="right" class="heading" style="font-size:10pt;"><b>Total Paid</b></td>
     <td><input type="edit" id="total_paid" name="total_paid" style="width:100px;" value="0.00" size="6"></td>
    </tr>
    <tr>
     <td align="right" class="heading" style="font-size:10pt;"><b>Change Owed</b></td>
     <td><input type="edit" id="change" name="change" style="width:100px;" value="0.00" size="6"></td>
    </tr>
   </table>
   <input type="button" onClick="clear_all()" style="width:210px;" value="Clear">
   <input type="submit" id="complete" style="width:210px;" value="Complete Transaction" disabled="true">
  </td>
  </form>
 </tr>
 <tr>
  <td colspan="2">
   <form action="?module=pos&do=add_to_cart" method="post">
   <table width="100%" border="1">
    <tr class="heading" align="center">
     <td>Item Name</td>
     <td>Price</td>
     <td>Units</td>
     <td>Taxable</td>
     <td>Add To Cart</td>
    </tr>
    <tr align="center">
     <td><input type="edit" name="barcode" size="30"></td>
     <td>$<input type="edit" name="price" size="6" value="0.00"></td>
     <td><input type="edit" name="units" size="3" value="1"></td>
     <td><input type="checkbox" name="taxable" value="1" checked></td>
     <td><input type="submit" value="Add To Cart"></td>
    </tr>
   </table>
   </form>
  </td>
 </tr>
</table>
<br /><br />
<script type="text/javascript">
<?php
/*
  if($customer_id==0){
    echo "hidePOS();\n";
  } else {
    echo "showPOS();\n";
  }
*/
echo "showPOS();\n";
?>
</script>
<?php display_footer(); ?>
