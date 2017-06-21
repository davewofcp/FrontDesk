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

$SERVICES = array();
$result = mysql_query("SELECT * FROM services");
while ($row = mysql_fetch_assoc($result)) {
	$SERVICES[$row["id"]] = $row["name"];
}

?>
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
<div class="floatL relative" style="font-size:46px;">
  <br>
<?php

  $result = mysql_query("SELECT * FROM invoices WHERE id < ".$IID." ORDER BY id DESC LIMIT 1");
  if(mysql_num_rows($result)){
    $row = mysql_fetch_row($result);
    echo "
    <div class=\"floatL\"><a href=\"?module=invoice&do=view&id=". $row[1] ."\" class=\"arr\">&#9668;</a></div>";
  } else {
    echo "
    <div class=\"floatL relative\" style=\"width:100px;\"></div>";
  }

  $result = mysql_query("SELECT * FROM invoices WHERE id > ".$IID." ORDER BY id ASC LIMIT 1");
  if(mysql_num_rows($result)){
    $row = mysql_fetch_row($result);
    echo "
    <a href=\"?module=invoice&do=view&id=". $row[1] ."\" class=\"arr\">&#9658;</a>";
  }
?>
  <br>
</div>


<div class="relative center">
    <h2>Invoice # <?php echo $IID; ?>
    <br>
    <?php
      echo "<div class=\"inline\" style=\"padding:10px;\">". alink("Print","?module=invoice&do=print&id=".$IID) ."</div>";
      echo "&#149;";

	  echo "<div class=\"inline\" style=\"padding:10px;\">". alink("Checkout","?module=invoice&do=checkout&id=".$IID) ."</div>";
	  echo "&#149;";

      if (TFD_HAS_PERMS('admin','use')) {
      	echo "<div class=\"inline\" style=\"padding:10px;\">". alink("Edit","?module=invoice&do=edit&id=".$IID) ."</div>";
      	echo "&#149;";
      }
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

<form action="?module=invoice&do=merge&id=<?php echo $INVOICE["id"]; ?>" method="post">
Merge with Invoice #<input type="edit" name="id2" size="6">
<input type="submit" value="Merge Invoices" onClick="javascript:return confirm('Make sure the invoice number is correct, this action cannot be undone.');">
<br><font size="-1">The customer information and invoice number on this page will be used.</font>
</form>

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
          echo date('n/d/Y',$date);
          echo '
                <br>';
          echo "(".date('l g:i A',$date).")";
        ?>
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
<?php } else if ($item["is_heading"] && $item["qty"] == 0) { ?>
	  <td class="center" colspan="5"><?php echo $item["name"]; ?></td>
<?php } else { ?>
      <td class="center"><?php echo $item["name"]; ?></td>
      <td class="center"><?php echo $item["qty"]; ?></td>
      <td><?php echo $item["descr"]; ?><?php if ($item["name"] == "Discount") {
		echo " ". alink("Remove","?module=invoice&do=remove_discount&id={$item["invoices__id"]}&did={$item["invoices__item_id"]}");
	} ?></td>
      <td class="center">$<?php echo number_format($item["cost"],2); ?></td>
      <td class="center"><?php echo $item["is_taxable"] ? "Yes" : "No"; ?>
    </tr>
<?php
	}

	if ($item["from_table"] == "issues") {
		$result = mysql_query("SELECT i.services__id,oe.title FROM org_entities oe JOIN inventory_type_devices d ON d.org_entities__id = oe.id JOIN issues i ON i.device_id = d.id WHERE i.id = ".$item["from_key"]);
		if (mysql_num_rows($result)) {
			$data = mysql_fetch_assoc($result);
			if ($data["services__id"] && $data["services__id"] != "") {
				$s = explode(":",$data["services__id"]);
				$sn = array();
				foreach ($s as $svc) {
					if ($svc == "" || !isset($SERVICES[$svc])) continue;
					$sn[] = $SERVICES[$svc];
				}
				if (count($sn) > 0) {
					echo "<tr><td colspan=\"5\" align=\"center\">- ". join("<br>- ",$sn) ."</td></tr>\n";
				}
			}
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
<?php

display_footer();

?>
