<?php display_header(); ?>

<script type="text/javascript">

  var str="",x;
  function dropChange(str,x){
    if(str=="is_checks"){
      document.getElementById("amtDrop"+x).style.visibility = "visible";
      document.getElementById("checkDrop"+x).style.visibility = "visible";
    } else if(str=="is_cash"){
      document.getElementById("amtDrop"+x).style.visibility = "visible";
      document.getElementById("checkDrop"+x).style.visibility = "hidden";
    } else{
      document.getElementById("amtDrop"+x).style.visibility = "hidden";
      document.getElementById("checkDrop"+x).style.visibility = "hidden";
    }
  }

  var i;
  function dropCheck(){
    str="";
    var bordVal = "4px ridge red";
    var cnt=0;
    for (i=0; i<dropForm.elements.length; i++){

      var formElement = dropForm.elements[i];
      var dropCountObj = dropForm.elements["dropCount"][i];

      if(dropCountObj){

        var dropCurrency_type = dropForm.elements["currency_type"][dropCountObj.value];
        var dropCheck_Num = dropForm.elements["check_num"][dropCountObj.value];
        var dropAmt = dropForm.elements["amt"][dropCountObj.value];

        if(dropCurrency_type.value || dropCheck_Num.value || dropAmt.value)cnt++;

        dropCheck_Num.style.border="";
        dropAmt.style.border="";

        if(dropCurrency_type.value=="is_checks"){
          if(dropCheck_Num.value==""){
            str += "\nAdd a check # on row #"+dropCountObj.value;
            dropCheck_Num.style.border=bordVal;
          }
          if(isNaN(dropCheck_Num.value)){
            str += "\nEnter a valid check # on row #"+dropCountObj.value;
            dropCheck_Num.style.border=bordVal;
          }

          if(dropAmt.value==""){
            str += "\nNo amount on row #"+dropCountObj.value;
            dropAmt.style.border=bordVal;
          }
          if(isNaN(dropAmt.value)){
            str += "\nEnter a valid amount on row #"+dropCountObj.value;
            dropAmt.style.border=bordVal;
          }
        }
        if(dropCurrency_type.value=="is_cash"){
          if(dropAmt.value==""){
            str += "\nNo amount on row #"+dropCountObj.value;
            dropAmt.style.border=bordVal;
          }
          if(isNaN(dropAmt.value)){
            str += "\nEnter a valid amount on row #"+dropCountObj.value;
            dropAmt.style.border=bordVal;
          }
        }
      }

    }

    if(cnt<1){
      alert("Fully fill out atleast one field to submit the form");
      return false;
    }
    if(str!=""){
      alert(str);
      return false;
    }

  }
</script>
<style type="text/css">
  .drop {visibility: hidden;}
</style>
<h3>Make Drop</h3>
<?php

if(isset($_GET["make_drop"])){
  echo "
  <div class=\"bold underline red\">DROP SUCCESSFUL - Click link(s) below for receipts</div>";
  $drop_ids = explode(",",$_GET["make_drop"]);
  foreach($drop_ids as $drop_id) {
  	if (!$drop_id) continue;
	echo alink_pop("Drop Receipt ".$drop_id,"?module=pos&do=drop_receipt&id=$drop_id")."<br>\n";
  }
  echo "<br>\n";
  echo alink("Drawer Adjustment","?module=pos&do=cash_adjust") ."<br><br>\n";
}

$TOTAL = 0;
while ($entry = mysql_fetch_assoc($LOG)) {

	if ($entry["is_reset"]) {
		$TOTAL = $entry["amt"];
	} elseif($entry["is_drop"]) {
      $TOTAL -= $entry["amt"];
  } else {
      $TOTAL += $entry["amt"];
  }

}

?>
<div style="clear"></div>
Make cash/check drops into the safe to be deposited later<br>
Current Drawer Total: <b>$<?php echo number_format($TOTAL,2); ?></b>

<div style="clear"><br><br></div>

<form action="?module=pos&do=make_drop" method="post" id="dropForm" name="dropForm" onSubmit="return dropCheck();">
<table border="0">
 <tr align="center">
  <td class="itemhead" style="float:none;width:82px;">Type</td>
  <td class="itemhead" style="float:none;width:82px;">Amount</td>
  <td class="itemhead" style="float:none;width:82px;">Check #</td>
 </tr>

<?php
$x=0;
$max=10;
while($x<$max){
echo "
 <tr>
  <td style=\"width:85px;\">
    <input type=\"hidden\" id=\"dropCount\" name=\"dropCount[]\" value=\"".$x."\">
    <b>".$x.".</b>
    <select id=\"currency_type\" name=\"currency_type[]\" onChange=\"dropChange(this.value,".$x.");\">
      <option value=\"\"></option>
      <option id=\"is_cash\" value=\"is_cash\">Cash</option>
      <option id=\"is_checks\" value=\"is_checks\">Check</option>
    </select>
  </td>
  <td class=\"drop bold\" style=\"width:85px;\" id=\"amtDrop".$x."\">$<input type=\"edit\" id=\"amt\" name=\"amt[]\" size=\"8\"></td>
  <td class=\"drop\" style=\"width:85px;\" id=\"checkDrop".$x."\"><input type=\"edit\" id=\"check_num\" name=\"check_num[]\" value=\"\" style=\"width:80px;\"></td>
 </tr>
 ";
 $x++;
}
?>
 <tr align="center">
  <td colspan="3"><br><input type="submit" value="Make Drop"></td>
 </tr>

</table>
</form>
<?php display_footer(); ?>
