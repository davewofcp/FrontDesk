<link rel="stylesheet" type="text/css" href="calendar.css">
<script src="js/calendar.js" type="text/javascript"></script>
<style type="text/css">
.itemcontent {
  padding: 5px 0px 5px;
  text-align: center;
}
.itemrow {
  clear: left;
  position: relative;
  float: none;
  display: block;
  text-align: center;
  margin-left: 250px;
}
</style>
<h2>New Account</h2>

<form action="?module=acct&do=new&customer=<?php echo $CUSTOMER["id"]; ?>" method="post">
<div class="relative center">

  <div class="itemrow">
    <div class="itemhead" align="right">Customer</div>
    <div class="itemcontent"><?php echo $CUSTOMER["firstname"] ." ". $CUSTOMER["lastname"]; ?></div>
  </div>
  <div class="itemrow">
    <div class="itemhead" align="right">Term Duration (days)</div>
    <div class="itemcontent"><input type="text" name="period" size="3" value="30"></div>
  </div>
  <div class="itemrow">
    <div class="itemhead" align="right">Term Payment</div>
    <div class="itemcontent">$<input type="text" name="amount" size="6" value="0.00"></div>
  </div>
  <div class="itemrow">
    <div class="itemcontent">Block Hours <b><u>AND</u></b> Rate will override Term Payment<br>Leave blank unless needed</div>
  </div>
  <div class="itemrow">
    <div class="itemhead" align="right">Block Hours</div>
    <div class="itemcontent"><input type="edit" name="block_hours" size="5"></div>
  </div>
  <div class="itemrow">
    <div class="itemhead" align="right">Block Rate</div>
    <div class="itemcontent bold">$<input type="edit" name="block_rate" size="7" value="0.00"></div>
  </div>
  <div class="itemrow">
    <div class="itemhead" align="right">Overage Rate</div>
    <div class="itemcontent bold">$<input type="edit" name="overage_rate" size="7" value="0.00"></div>
  </div>
  <!--div class="itemrow">
    <div class="itemhead" align="right">Starting Date</div>
    <div class="itemcontent bold"><input type="edit" id="start_date" name="start_date" size="10" value="<?php echo date("Y-m-d"); ?>"></div>
  </div-->
  <div class="itemrow">
    <div class="itemcontent" style="margin-left:37px;"><br><input type="submit" value="Create Account"></div>
  </div>

</div>
</form>

<script type="text/javascript">
calendar.set("start_date");
</script>
