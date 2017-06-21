<script type="text/javascript">
var xmlvalidate;
if (window.XMLHttpRequest) {
  // IE7+, Firefox, Chrome, Opera, Safari
	xmlvalidate=new XMLHttpRequest();
} else {
  // IE6, IE5
	xmlvalidate=new ActiveXObject("Microsoft.XMLHTTP");
}
xmlvalidate.onreadystatechange = validateHandler;

function validateHandler() {
	if (xmlvalidate.readyState == 4 && xmlvalidate.status == 200) {
		var data = eval("("+xmlvalidate.responseText+")");
		if (!data || !data.action) return;
		switch (data.action) {
			case "error":
				document.getElementById('v_address_display').value = 'Not Validated';
				alert(data.error);
				break;
			case "notfound":
				alert("That address was not found.");
				document.getElementById('v_address_display').value = 'INVALID';
				document.getElementById('v_address').value = 'INVALID';
				break;
			case "validated":
				var apt = '';
				var zip4 = '', no_service = '';
				if (data.address2 != '') apt = ' #'+data.address2;
				if (data.zip4 != '') zip4 = '-'+data.zip4;
				else no_service = 'NO SERVICE TO: ';
				document.getElementById('v_address_display').value = data.address + apt +"\n"+ data.city +", "+ data.state +"\n"+ data.zip + zip4;
				document.getElementById('v_address').value = no_service + data.address +", "+ data.address2 +", "+ data.city +", "+ data.state +", "+ data.zip + zip4;
				break;
		}
	}
}

function invalidate() {
	document.getElementById('v_address').value = '';
	document.getElementById('v_address_display').value = 'Not Validated';
}

function validate() {
	if (document.getElementById('address').value == '' || document.getElementById('zip').value == '') {
		alert('Address and zip code are required for address validation.');
		return;
	}

	var params = [];
	params.push("address="+encodeURIComponent(document.getElementById('address').value));
	params.push("address2="+encodeURIComponent(document.getElementById('apt').value));
	params.push("city="+encodeURIComponent(document.getElementById('city').value));
	params.push("state="+encodeURIComponent(document.getElementById('state').value));
	params.push("zip="+encodeURIComponent(document.getElementById('zip').value));

	document.getElementById('v_address_display').value = 'Validating...';
	xmlvalidate.abort();
	xmlvalidate.onreadystatechange = validateHandler;
	xmlvalidate.open("GET","cust/ajax.php?cmd=validate&"+params.join('&'),true);
	xmlvalidate.send();
}
</script>

<h3>Edit Customer # <?php echo $CUSTOMER["id"]; ?></h3>
<form action="?module=cust&do=edit_sub&id=<?php echo $CUSTOMER["id"]; ?>" method="post">
<table border="0" cellspacing="3">
 <tr>
  <td class="heading" align="right">First Name</td>
  <td><input type="edit" name="firstname" size="30" value="<?php echo $CUSTOMER["firstname"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Last Name</td>
  <td><input type="edit" name="lastname" size="30" value="<?php echo $CUSTOMER["lastname"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Sex</td>
  <td>
   <input type="radio" name="is_male" value="1"<?php if ($CUSTOMER["is_male"]) echo " CHECKED"; ?>> Male
   <input type="radio" name="is_male" value="0"<?php if (!$CUSTOMER["is_male"]) echo " CHECKED"; ?>> Female
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">DOB (yyyy-mm-dd)</td>
  <td><input type="edit" name="dob" size="10" value="<?php echo $CUSTOMER["dob"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Company</td>
  <td><input type="edit" name="company" size="30" value="<?php echo $CUSTOMER["company"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Address</td>
  <td><input type="edit" name="address" id="address" size="40" value="<?php echo $CUSTOMER["address"]; ?>" onKeyUp="invalidate();"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Apt/Suite #</td>
  <td><input type="edit" name="apt" id="apt" size="4" value="<?php echo $CUSTOMER["apt"]; ?>" onKeyUp="invalidate();"></td>
 </tr>
 <tr>
  <td class="heading" align="right">City</td>
  <td><input type="edit" name="city" id="city" size="30" value="<?php echo $CUSTOMER["city"]; ?>" onKeyUp="invalidate();"></td>
 </tr>
 <tr>
  <td class="heading" align="right">State</td>
  <td><input type="edit" name="state" id="state" size="30" value="<?php echo $CUSTOMER["state"]; ?>" onKeyUp="invalidate();"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Zipcode</td>
  <td><input type="edit" name="zip" id="zip" size="30" value="<?php echo $CUSTOMER["postcode"]; ?>" onKeyUp="invalidate();"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Validated Address</td>
  <td><input type="hidden" name="v_address" id="v_address" value="<?php echo ($CUSTOMER["v_address"] ? $CUSTOMER["v_address"] : ""); ?>"><textarea id="v_address_display" rows="2" cols="25" disabled="disabled" style="background-color:#CCCCCC;"><?php echo ($CUSTOMER["v_address"] ? $CUSTOMER["v_address"] : "Not Validated"); ?></textarea><br>
  <input type="button" value="Validate Address" onClick="validate();">
  </td>
 </tr>
 <tr>
  <td class="heading" align="right">Email</td>
  <td><input type="edit" name="email" size="40" value="<?php echo $CUSTOMER["email"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Home Phone</td>
  <td><input type="edit" name="phone_home" size="30" value="<?php echo $CUSTOMER["phone_home"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Cell Phone</td>
  <td><input type="edit" name="phone_cell" size="30" value="<?php echo $CUSTOMER["phone_cell"]; ?>"></td>
 </tr>
 <tr>
  <td class="heading" align="right">Referral</td>
  <td><input type="edit" name="referral" size="40" value="<?php echo $CUSTOMER["referral"]; ?>"></td>
 </tr>
 <tr>
  <td colspan="2" align="center"><input type="submit" value="Save Changes"></td>
 </tr>
</table>
</form>
